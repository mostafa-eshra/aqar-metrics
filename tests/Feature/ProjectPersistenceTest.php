<?php

use App\Enums\CalculationVersion;
use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Services\ProjectCollectionScheduleService;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('persists every source input with exact casts and server assigned ownership', function () {
    $owner = User::factory()->create();
    $injectedOwner = User::factory()->create();

    $project = ProjectService::create($owner, [
        ...projectPersistenceData(),
        'user_id' => $injectedOwner->id,
        'calculation_version' => CalculationVersion::EXCEL_SOURCE_V1->value,
        'total_profit' => '999999999.99',
    ]);

    expect($project->user_id)->toBe($owner->id)
        ->and($project->status)->toBe(ProjectStatus::COMPLETED)
        ->and($project->calculation_version)->toBe(CalculationVersion::CORRECTED_V1)
        ->and($project->project_duration_years)->toBe(8)
        ->and($project->has_hotel)->toBeFalse()
        ->and($project->developer_share_percent)->toBe('60.00')
        ->and($project->land_area)->toBe('69000.1234')
        ->and($project->land_cost)->toBe('0.00')
        ->and($project->design_supervision_percent)->toBe('2.50')
        ->and($project->hotel_revenue)->toBe('0.00')
        ->and($project->residential_collection_schedule)->toBe(
            ProjectCollectionScheduleService::defaults(8)['residential_collection_schedule'],
        )
        ->and($project->commercial_collection_schedule)->toBe(
            ProjectCollectionScheduleService::defaults(8)['commercial_collection_schedule'],
        )
        ->and($owner->projects()->whereKey($project)->exists())->toBeTrue()
        ->and($injectedOwner->projects()->whereKey($project)->exists())->toBeFalse()
        ->and(array_key_exists('total_profit', $project->getAttributes()))->toBeFalse();
});

it('preserves null separately from zero in a draft', function () {
    $owner = User::factory()->create();

    $project = ProjectService::create($owner, [
        'name' => 'مسودة مشروع',
        'project_duration_years' => 8,
        'status' => ProjectStatus::DRAFT->value,
        'has_hotel' => true,
        'land_cost' => '0',
        'developer_share_percent' => null,
        'hotel_revenue' => null,
    ]);

    expect($project->land_cost)->toBe('0.00')
        ->and($project->developer_share_percent)->toBeNull()
        ->and($project->hotel_revenue)->toBeNull();
});

it('keeps ownership and calculation version unchanged during updates', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $updatedProject = ProjectService::update($project, [
        'name' => 'الاسم المعدل',
        'user_id' => $otherUser->id,
        'calculation_version' => CalculationVersion::EXCEL_SOURCE_V1->value,
    ]);

    expect($updatedProject->name)->toBe('الاسم المعدل')
        ->and($updatedProject->user_id)->toBe($owner->id)
        ->and($updatedProject->calculation_version)->toBe(CalculationVersion::CORRECTED_V1);
});

it('scopes repository queries to owners while administrators can see all projects', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $administrator = User::factory()->administrator()->create();
    $inactiveAdministrator = User::factory()->administrator()->inactive()->create();
    Project::factory()->count(2)->for($owner, 'owner')->create();
    Project::factory()->for($otherUser, 'owner')->create();

    expect(ProjectRepository::queryVisibleTo($owner)->count())->toBe(2)
        ->and(ProjectRepository::queryVisibleTo($administrator)->count())->toBe(3)
        ->and(ProjectRepository::queryVisibleTo($inactiveAdministrator)->count())->toBe(0);
});

/** @return array<string, mixed> */
function projectPersistenceData(): array
{
    return [
        'name' => 'مشروع مرجعي',
        'description' => 'بيانات اختبار المرحلة الثالثة',
        'location' => 'القاهرة',
        'currency_code' => 'EGP',
        'project_duration_years' => 8,
        'status' => ProjectStatus::COMPLETED->value,
        'has_hotel' => false,
        'building_coverage_percent' => '60',
        'developer_share_percent' => '60',
        'land_area' => '69000.1234',
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
        'hotel_revenue' => '5000000',
        ...ProjectCollectionScheduleService::defaults(8),
    ];
}
