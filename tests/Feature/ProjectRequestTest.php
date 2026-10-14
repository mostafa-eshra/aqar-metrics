<?php

use App\Enums\ProjectStatus;
use App\Http\Requests\Admin\ProjectRequest;
use App\Models\User;
use App\Services\ProjectCashFlowService;
use App\Services\ProjectCollectionScheduleService;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::post('/_testing/projects', static function (ProjectRequest $request) {
        return response()->json($request->validated());
    })->middleware('auth');
});

it('allows incomplete drafts and normalizes Arabic numbers without accepting protected fields', function () {
    $user = User::factory()->make(['id' => 1]);

    $response = $this->actingAs($user)->postJson('/_testing/projects', [
        'name' => 'مسودة',
        'project_duration_years' => '٨',
        'status' => ProjectStatus::DRAFT->value,
        'has_hotel' => '1',
        'land_cost' => '١٢٬٣٤٥٫٦٧',
        'user_id' => 999,
        'calculation_version' => 'excel-source-v1',
        'total_profit' => '100',
    ]);

    $response
        ->assertOk()
        ->assertJsonPath('project_duration_years', '8')
        ->assertJsonPath('land_cost', '12345.67')
        ->assertJsonMissingPath('user_id')
        ->assertJsonMissingPath('calculation_version')
        ->assertJsonMissingPath('total_profit');
});

it('accepts a complete project with zero-valued source inputs', function () {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['currency_code'] = ' egp ';

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertOk()
        ->assertJsonPath('currency_code', 'EGP')
        ->assertJsonPath('land_cost', '0')
        ->assertJsonPath('commercial_sales_1_area', '0')
        ->assertJsonPath('hotel_revenue', '0');
});

it('rejects archived status in project form payloads', function () {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['status'] = ProjectStatus::ARCHIVED->value;

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

it('rejects non-numeric collection year keys even for drafts', function () {
    $user = User::factory()->make(['id' => 1]);

    $this->actingAs($user)
        ->postJson('/_testing/projects', [
            'name' => 'مسودة',
            'project_duration_years' => 8,
            'status' => ProjectStatus::DRAFT->value,
            'has_hotel' => false,
            'residential_collection_schedule' => [
                'residential_phase_1' => ['first' => '100'],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['residential_collection_schedule.residential_phase_1']);
});

it('rejects completed data that violates project business rules', function () {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['land_area'] = '0';
    $data['commercial_sales_1_area'] = '100';
    $data['commercial_sales_1_price_per_sqm'] = '0';
    $data['residential_collection_schedule']['residential_phase_1'][8] = '1';

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'land_area',
            'commercial_sales_1_price_per_sqm',
            'residential_collection_schedule.residential_phase_1',
        ]);
});

it('requires hotel revenue when the project includes a hotel', function () {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['has_hotel'] = true;
    unset($data['hotel_revenue']);

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['hotel_revenue']);
});

it('requires every completed collection schedule to match the saved duration', function () {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['project_duration_years'] = 7;

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'residential_collection_schedule.residential_phase_1',
            'commercial_collection_schedule.commercial_group_1',
        ]);
});

it('accepts completed collection schedules for supported dynamic durations', function (int $durationYears) {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['project_duration_years'] = $durationYears;
    $data = [...$data, ...collectionScheduleForDuration($durationYears)];

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertOk()
        ->assertJsonPath('project_duration_years', $durationYears);
})->with([
    'one year' => 1,
    'five years' => 5,
    'source eight years' => 8,
    'ten years' => 10,
]);

it('allows excluded positive collection years in a draft but rejects them for a completed project', function () {
    $user = User::factory()->make(['id' => 1]);
    $draftData = completeProjectRequestData();
    $draftData['project_duration_years'] = 5;
    $draftData['status'] = ProjectStatus::DRAFT->value;

    $this->actingAs($user)
        ->postJson('/_testing/projects', $draftData)
        ->assertOk()
        ->assertJsonPath('residential_collection_schedule.residential_phase_1.5', '10')
        ->assertJsonPath('residential_collection_schedule.residential_phase_1.6', '10');

    $draftData['status'] = ProjectStatus::COMPLETED->value;

    $this->actingAs($user)
        ->postJson('/_testing/projects', $draftData)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'residential_collection_schedule.residential_phase_1',
            'commercial_collection_schedule.commercial_group_1',
        ]);
});

it('accepts and normalizes the phase distribution and complete cash flow years', function () {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['residential_phase_distribution'] = [
        'phase_1' => '٢٠',
        'phase_2' => '٣٠',
        'phase_3' => '٥٠',
    ];
    $data['discount_rate_percent'] = '١٠٫٥';
    $data['annual_cash_flow_schedule'] = ProjectCashFlowService::defaults(8);
    $data['annual_cash_flow_schedule'][0]['project_cost'] = '١٬٠٠٠';

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertOk()
        ->assertJsonPath('residential_phase_distribution.phase_1', '20')
        ->assertJsonPath('discount_rate_percent', '10.5')
        ->assertJsonPath('annual_cash_flow_schedule.0.project_cost', '1000');
});

it('rejects an unbalanced phase distribution and incomplete cash flow years', function () {
    $user = User::factory()->make(['id' => 1]);
    $data = completeProjectRequestData();
    $data['residential_phase_distribution'] = [
        'phase_1' => '20',
        'phase_2' => '30',
        'phase_3' => '40',
    ];
    $data['annual_cash_flow_schedule'] = [0 => cashFlowRequestYear()];

    $this->actingAs($user)
        ->postJson('/_testing/projects', $data)
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'residential_phase_distribution',
            'annual_cash_flow_schedule',
        ]);
});

/** @return array<string, mixed> */
function completeProjectRequestData(): array
{
    return [
        'name' => 'مشروع مكتمل',
        'description' => null,
        'location' => 'القاهرة',
        'currency_code' => 'EGP',
        'project_duration_years' => 8,
        'status' => ProjectStatus::COMPLETED->value,
        'has_hotel' => false,
        'building_coverage_percent' => '60',
        'developer_share_percent' => '60',
        'land_area' => '69000',
        'land_cost' => '0',
        'residential_phase_1_price_per_sqm' => '80000',
        'residential_phase_2_price_per_sqm' => '95000',
        'residential_phase_3_price_per_sqm' => '120000',
        'commercial_sales_1_area' => '0',
        'commercial_sales_1_price_per_sqm' => '150000',
        'commercial_sales_2_area' => '0',
        'commercial_sales_2_price_per_sqm' => '200000',
        'hotel_construction_area' => '0',
        'hotel_construction_cost_per_sqm' => '120000',
        'residential_construction_cost_per_sqm' => '25000',
        'commercial_construction_area' => '0',
        'commercial_construction_cost_per_sqm' => '30000',
        'garage_construction_area' => '0',
        'garage_construction_cost_per_sqm' => '15000',
        'infrastructure_landscape_area' => '41400',
        'infrastructure_landscape_cost_per_sqm' => '5000',
        'marketing_percent' => '4',
        'sales_percent' => '5',
        'administration_percent' => '4',
        'design_supervision_percent' => '2.5',
        'hotel_revenue' => '0',
        ...ProjectCollectionScheduleService::defaults(8),
    ];
}

/** @return array<string, array<string, array<int, string>>> */
function collectionScheduleForDuration(int $durationYears): array
{
    $percentages = [];

    for ($year = 1; $year <= $durationYears; $year++) {
        $percentages[$year] = $year === $durationYears ? '100' : '0';
    }

    return [
        'residential_collection_schedule' => [
            'residential_phase_1' => $percentages,
            'residential_phase_2' => $percentages,
            'residential_phase_3' => $percentages,
        ],
        'commercial_collection_schedule' => [
            'commercial_group_1' => $percentages,
            'commercial_group_2' => $percentages,
        ],
    ];
}

/** @return array<string, string> */
function cashFlowRequestYear(): array
{
    return [
        'other_revenue' => '0',
        'project_cost' => '0',
        'financing_inflow' => '0',
        'financing_cost' => '0',
        'taxes' => '0',
    ];
}
