<?php

declare(strict_types=1);

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectCollectionScheduleService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

it('completes the first-version project workflow without losing inputs or changing calculation results', function () {
    $user = User::factory()->create([
        'email' => 'phase-eight@example.com',
        'password' => Hash::make('phase-eight-password'),
    ]);
    $sourceData = phaseEightProjectData();

    $this->post(route('login.store'), [
        'email' => 'phase-eight@example.com',
        'password' => 'phase-eight-password',
    ])->assertRedirect(route('admin.dashboard'));
    $this->assertAuthenticatedAs($user);

    $this->get(route('admin.projects.create'))
        ->assertOk()
        ->assertSee('إنشاء مشروع')
        ->assertSee('name="project_duration_years"', false);

    $this->postJson(route('admin.projects.preview'), $sourceData)
        ->assertOk()
        ->assertJsonPath('saved', false)
        ->assertJsonPath('metrics.revenues.total_with_hotel', '2442600000.00')
        ->assertJsonPath('metrics.totals.estimated_profit', '821997000.00')
        ->assertJsonPath('metrics.annual_collection_total', '2442600000.00');
    $this->assertDatabaseCount('projects', 0);

    $createResponse = $this->post(route('admin.projects.create'), $sourceData);
    $project = Project::query()->where('name', 'مشروع اختبار المسار الكامل')->firstOrFail();

    $createResponse->assertRedirect(route('admin.projects.show', $project));
    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'user_id' => $user->id,
        'project_duration_years' => 8,
        'status' => ProjectStatus::COMPLETED->value,
        'land_area' => 69000,
        'hotel_revenue' => 0,
    ]);

    $this->get(route('admin.projects.show', $project))
        ->assertOk()
        ->assertSee('2,442,600,000.00')
        ->assertSee('821,997,000.00');

    $this->get(route('admin.projects.edit', $project))
        ->assertOk()
        ->assertSee('value="69000.0000"', false)
        ->assertSee('name="residential_collection_schedule[residential_phase_1][8]"', false);

    $updatedData = [
        ...$sourceData,
        'name' => 'مشروع اختبار المسار الكامل بعد التعديل',
        'project_duration_years' => 10,
        'residential_phase_1_price_per_sqm' => '90000',
        ...ProjectCollectionScheduleService::defaults(10),
    ];

    $this->post(route('admin.projects.edit', $project), $updatedData)
        ->assertRedirect(route('admin.projects.show', $project));

    $project->refresh();

    expect($project->name)->toBe('مشروع اختبار المسار الكامل بعد التعديل')
        ->and($project->project_duration_years)->toBe(10)
        ->and($project->residential_phase_1_price_per_sqm)->toBe('90000.00')
        ->and($project->land_area)->toBe('69000.0000')
        ->and($project->hotel_revenue)->toBe('0.00')
        ->and($project->residential_collection_schedule['residential_phase_1'][9])->toBe('0')
        ->and($project->commercial_collection_schedule['commercial_group_2'][10])->toBe('0');

    $this->get(route('admin.projects.show', $project))
        ->assertOk()
        ->assertSee('2,525,400,000.00')
        ->assertSee('891,963,000.00');

    $this->post(route('logout'))->assertRedirect(route('login'));
    $this->assertGuest();
});

/** @return array<string, mixed> */
function phaseEightProjectData(): array
{
    return [
        'name' => 'مشروع اختبار المسار الكامل',
        'description' => 'تغطية مسار النسخة الأولى من تسجيل الدخول حتى تعديل النتائج.',
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
