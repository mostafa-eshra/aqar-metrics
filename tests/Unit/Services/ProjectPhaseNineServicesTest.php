<?php

declare(strict_types=1);

use App\Enums\CalculationVersion;
use App\Models\Project;
use App\Services\ProjectCalculationService;
use App\Services\ProjectCashFlowService;
use App\Services\ProjectCollectionScheduleService;
use App\Services\ProjectExcelExportService;
use App\Services\ProjectSensitivityService;
use Illuminate\Database\Eloquent\Collection;
use Tests\TestCase;

uses(TestCase::class);

it('allocates residential area using a validated custom phase distribution', function () {
    $project = makePhaseNineProject([
        'residential_phase_distribution' => [
            'phase_1' => '20',
            'phase_2' => '30',
            'phase_3' => '50',
        ],
    ]);

    $metrics = ProjectCalculationService::calculate($project);

    expect($metrics->areas)
        ->toMatchArray([
            'residential_phase_1_area' => '4968.0000',
            'residential_phase_2_area' => '7452.0000',
            'residential_phase_3_area' => '12420.0000',
        ])
        ->and($metrics->revenues['residential_total'])->toBe('2595780000.00');
});

it('calculates NPV IRR and fractional payback from annual project cash flows', function () {
    $project = makePhaseNineProject([
        'project_duration_years' => 2,
        'discount_rate_percent' => '10',
        'annual_cash_flow_schedule' => [
            0 => cashFlowYear(projectCost: '1000'),
            1 => cashFlowYear(projectCost: '2442599400'),
            2 => cashFlowYear(otherRevenue: '600'),
        ],
        ...phaseNineOneYearCollectionSchedule(2),
    ]);

    $cashFlow = ProjectCashFlowService::calculate($project);

    expect(array_column($cashFlow->annualRows, 'net_cash_flow'))->toBe([
        '-1000.00',
        '600.00',
        '600.00',
    ])
        ->and($cashFlow->netPresentValue)->toBe('41.32')
        ->and($cashFlow->internalRateOfReturnPercent)->toBe('13.07')
        ->and($cashFlow->paybackPeriodYears)->toBe('1.67')
        ->and($cashFlow->unavailableReasons)->toBe([]);
});

it('runs sensitivity cases on temporary copies without changing the saved input object', function () {
    $project = makePhaseNineProject();

    $rows = ProjectSensitivityService::analyze(
        project: $project,
        field: 'residential_phase_1_price_per_sqm',
        changes: [-10, 0, 10],
    );

    expect($project->residential_phase_1_price_per_sqm)->toBe('80000.00')
        ->and($rows)->toHaveCount(3)
        ->and($rows[0]['input_value'])->toBe('72000.00')
        ->and($rows[1]['estimated_profit'])->toBe('852012000.00')
        ->and($rows[2]['input_value'])->toBe('88000.00');
});

it('exports a valid Excel workbook with inputs results and cash flow sheets', function () {
    $project = makePhaseNineProject([
        'discount_rate_percent' => '10',
        'annual_cash_flow_schedule' => ProjectCashFlowService::defaults(8),
    ]);
    $project->setRelation('viewers', new Collection);
    $metrics = ProjectCalculationService::calculate($project);
    $cashFlow = ProjectCashFlowService::calculate($project, $metrics);

    $path = ProjectExcelExportService::create($project, $metrics, $cashFlow);
    $archive = new ZipArchive;
    $opened = $archive->open($path);
    $workbook = $archive->getFromName('xl/workbook.xml');
    $results = $archive->getFromName('xl/worksheets/sheet2.xml');
    $archive->close();
    unlink($path);

    expect($opened)->toBeTrue()
        ->and($workbook)->toContain('المدخلات', 'النتائج', 'التدفقات النقدية')
        ->and($results)->toContain('852012000.00', 'flexible-phases-v2');
});

/** @param array<string, mixed> $overrides */
function makePhaseNineProject(array $overrides = []): Project
{
    return (new Project)->forceFill([
        'name' => 'مشروع المرحلة التاسعة',
        'currency_code' => 'EGP',
        'project_duration_years' => 8,
        'status' => 'completed',
        'has_hotel' => false,
        'building_coverage_percent' => '60',
        'calculation_version' => CalculationVersion::FLEXIBLE_PHASES_V2,
        'developer_share_percent' => '60',
        'residential_phase_distribution' => config('aqar_metrics.project.residential_phase_distribution'),
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
        ...$overrides,
    ]);
}

/** @return array<string, string> */
function cashFlowYear(
    string $otherRevenue = '0',
    string $projectCost = '0',
    string $financingInflow = '0',
    string $financingCost = '0',
    string $taxes = '0',
): array {
    return [
        'other_revenue' => $otherRevenue,
        'project_cost' => $projectCost,
        'financing_inflow' => $financingInflow,
        'financing_cost' => $financingCost,
        'taxes' => $taxes,
    ];
}

/** @return array<string, array<string, array<int, string>>> */
function phaseNineOneYearCollectionSchedule(int $durationYears): array
{
    $schedule = [];

    for ($year = 1; $year <= $durationYears; $year++) {
        $schedule[$year] = $year === 1 ? '100' : '0';
    }

    return [
        'residential_collection_schedule' => [
            'residential_phase_1' => $schedule,
            'residential_phase_2' => $schedule,
            'residential_phase_3' => $schedule,
        ],
        'commercial_collection_schedule' => [
            'commercial_group_1' => $schedule,
            'commercial_group_2' => $schedule,
        ],
    ];
}
