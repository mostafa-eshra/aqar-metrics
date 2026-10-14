<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectCollectionScheduleService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('redirects guests to login from the project list', function () {
    $response = $this->get(route('admin.projects.index'));

    $response->assertRedirect(route('login'));
});

it('renders the project list and create form for an active user', function () {
    $user = User::factory()->create();

    $indexResponse = $this->actingAs($user)->get(route('admin.projects.index'));
    $createResponse = $this->actingAs($user)->get(route('admin.projects.create'));

    $indexResponse
        ->assertOk()
        ->assertSee('المشروعات')
        ->assertSee(route('admin.projects.create'));
    $createResponse
        ->assertOk()
        ->assertSee('إنشاء مشروع')
        ->assertSee('name="land_area"', false)
        ->assertSee('name="hotel_revenue"', false)
        ->assertSee('عمر المشروع وجدول التحصيل')
        ->assertSee('name="residential_collection_schedule[residential_phase_1][1]"', false)
        ->assertSee('data-schedule-input', false)
        ->assertDontSee('type="file"', false);
});

it('returns scoped filtered and escaped DataTables rows', function () {
    $owner = User::factory()->create(['name' => 'مالك المشروع']);
    $otherUser = User::factory()->create();
    $listedProject = Project::factory()->for($owner, 'owner')->create([
        'name' => 'A <script>alert("unsafe")</script> مشروع النيل',
        'status' => ProjectStatus::COMPLETED,
        'updated_at' => '2026-10-14 10:00:00',
    ]);
    Project::factory()->for($owner, 'owner')->create([
        'name' => 'B مشروع النيل',
        'status' => ProjectStatus::COMPLETED,
        'updated_at' => '2026-10-12 10:00:00',
    ]);
    Project::factory()->draft()->for($owner, 'owner')->create([
        'name' => 'مشروع آخر',
        'updated_at' => '2026-10-13 10:00:00',
    ]);
    Project::factory()->for($otherUser, 'owner')->create(['name' => 'مشروع مخفي']);

    $response = $this->actingAs($owner)->getJson(route('admin.projects.index', [
        'draw' => 4,
        'start' => 0,
        'length' => 1,
        'filter_search' => 'النيل',
        'filter_status' => ProjectStatus::COMPLETED->value,
        'columns' => dataTableColumns(),
        'order' => [['column' => 0, 'dir' => 'asc']],
    ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $response
        ->assertOk()
        ->assertJsonPath('draw', 4)
        ->assertJsonPath('recordsTotal', 3)
        ->assertJsonPath('recordsFiltered', 2)
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.owner_name', 'مالك المشروع')
        ->assertJsonMissing(['مشروع مخفي']);

    expect($response->json('data.0.name'))
        ->toContain('&lt;script&gt;')
        ->not->toContain('<script>alert("unsafe")</script>');
    expect($response->json('data.0.actions'))
        ->toContain(route('admin.projects.edit', $listedProject))
        ->toContain(route('admin.projects.duplicate', $listedProject))
        ->toContain(route('admin.projects.archive', $listedProject));
});

it('ignores untrusted DataTables sort names and directions', function () {
    $owner = User::factory()->create();
    Project::factory()->count(2)->for($owner, 'owner')->create();
    $columns = dataTableColumns();
    $columns[0]['name'] = 'projects.name desc; drop table projects';

    $response = $this->actingAs($owner)->getJson(route('admin.projects.index', [
        'draw' => 1,
        'start' => 0,
        'length' => 1,
        'columns' => $columns,
        'order' => [['column' => 0, 'dir' => 'delete']],
    ]), ['X-Requested-With' => 'XMLHttpRequest']);

    $response
        ->assertOk()
        ->assertJsonPath('recordsTotal', 2)
        ->assertJsonCount(1, 'data');
    $this->assertDatabaseCount('projects', 2);
});

it('creates and reopens a draft with server assigned ownership', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();

    $response = $this->actingAs($owner)->post(route('admin.projects.create'), [
        'name' => 'مشروع الواجهة',
        'project_duration_years' => '٨',
        'status' => ProjectStatus::DRAFT->value,
        'has_hotel' => '0',
        'land_cost' => '١٢٬٣٤٥٫٦٧',
        'user_id' => $otherUser->id,
    ]);

    $response
        ->assertRedirect(route('admin.projects.index'))
        ->assertSessionHas('status', 'تم إنشاء المشروع بنجاح.');

    $project = Project::query()->where('name', 'مشروع الواجهة')->firstOrFail();

    expect($project->user_id)->toBe($owner->id)
        ->and($project->project_duration_years)->toBe(8)
        ->and($project->land_cost)->toBe('12345.67')
        ->and($project->residential_collection_schedule)->toBe(
            ProjectCollectionScheduleService::defaults(8)['residential_collection_schedule'],
        );

    $this->actingAs($owner)
        ->get(route('admin.projects.edit', $project))
        ->assertOk()
        ->assertSee('مشروع الواجهة');
});

it('updates an owned project without changing its owner', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->draft()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post(route('admin.projects.edit', $project), [
        'name' => 'اسم المشروع بعد التعديل',
        'project_duration_years' => 8,
        'status' => ProjectStatus::DRAFT->value,
        'has_hotel' => '0',
        'user_id' => $otherUser->id,
        'residential_collection_schedule' => $project->residential_collection_schedule,
        'commercial_collection_schedule' => $project->commercial_collection_schedule,
    ]);

    $response
        ->assertRedirect(route('admin.projects.edit', $project))
        ->assertSessionHas('status', 'تم تحديث المشروع بنجاح.');

    expect($project->refresh()->name)->toBe('اسم المشروع بعد التعديل')
        ->and($project->user_id)->toBe($owner->id);
});

it('creates a completed project when every required input and schedule is valid', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->post(
        route('admin.projects.create'),
        completeProjectFormData(),
    );

    $response
        ->assertRedirect(route('admin.projects.index'))
        ->assertSessionHas('status', 'تم إنشاء المشروع بنجاح.');

    $project = Project::query()->where('name', 'مشروع المعاينة')->firstOrFail();

    expect($project->status)->toBe(ProjectStatus::COMPLETED)
        ->and($project->user_id)->toBe($owner->id)
        ->and($project->commercial_collection_schedule)->toBe(
            ProjectCollectionScheduleService::defaults(8)['commercial_collection_schedule'],
        );
});

it('previews calculations without saving a project', function () {
    $owner = User::factory()->create();

    $response = $this->actingAs($owner)->postJson(
        route('admin.projects.preview'),
        completeProjectFormData(),
    );

    $response
        ->assertOk()
        ->assertJsonPath('saved', false)
        ->assertJsonPath('currency_code', 'EGP')
        ->assertJsonPath('metrics.revenues.total_with_hotel', '2442600000.00')
        ->assertJsonPath('metrics.totals.estimated_profit', '852012000.00')
        ->assertJsonCount(8, 'metrics.annual_collections')
        ->assertJsonPath('metrics.annual_collection_total', '2442600000.00');
    $this->assertDatabaseCount('projects', 0);
});

it('adds zero collection years when a draft duration increases from eight to ten years', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->draft()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post(route('admin.projects.edit', $project), [
        'name' => $project->name,
        'project_duration_years' => 10,
        'status' => ProjectStatus::DRAFT->value,
        'has_hotel' => '0',
    ]);

    $response->assertRedirect(route('admin.projects.edit', $project));

    $project->refresh();

    expect($project->project_duration_years)->toBe(10)
        ->and($project->residential_collection_schedule['residential_phase_1'][9])->toBe('0')
        ->and($project->residential_collection_schedule['residential_phase_1'][10])->toBe('0')
        ->and($project->commercial_collection_schedule['commercial_group_2'][9])->toBe('0')
        ->and($project->commercial_collection_schedule['commercial_group_2'][10])->toBe('0');
});

it('keeps positive excluded years visible after reducing a draft from eight to five years', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->draft()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post(route('admin.projects.edit', $project), [
        'name' => $project->name,
        'project_duration_years' => 5,
        'status' => ProjectStatus::DRAFT->value,
        'has_hotel' => '0',
    ]);

    $response->assertRedirect(route('admin.projects.edit', $project));

    $project->refresh();

    expect($project->project_duration_years)->toBe(5)
        ->and($project->residential_collection_schedule['residential_phase_1'])->toBe([
            1 => '25',
            2 => '15',
            3 => '15',
            4 => '25',
            5 => '10',
            6 => '10',
        ])
        ->and($project->commercial_collection_schedule['commercial_group_2'][8])->toBe('15');

    $this->actingAs($owner)
        ->get(route('admin.projects.edit', $project))
        ->assertOk()
        ->assertSee('توجد نسب تحصيل خارج مدة المشروع الجديدة.')
        ->assertSee('name="residential_collection_schedule[residential_phase_1][6]"', false);
});

it('previews annual collection as unavailable while a reduced draft still has excluded percentages', function () {
    $owner = User::factory()->create();
    $data = completeProjectFormData();
    $data['project_duration_years'] = 5;
    $data['status'] = ProjectStatus::DRAFT->value;

    $response = $this->actingAs($owner)->postJson(route('admin.projects.preview'), $data);

    $response
        ->assertOk()
        ->assertJsonPath('metrics.revenues.total_with_hotel', '2442600000.00')
        ->assertJsonPath('metrics.annual_collection_total', null)
        ->assertJsonPath(
            'metrics.unavailable_reasons.annual_collections.1.residential_phase_1.0',
            'schedule_year_outside_duration.residential_phase_1.6',
        );
    $this->assertDatabaseCount('projects', 0);
});

it('duplicates a project as a draft while preserving ownership duration and schedules', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post(route('admin.projects.duplicate', $project));

    $duplicate = Project::query()->whereKeyNot($project->id)->firstOrFail();
    $response
        ->assertRedirect(route('admin.projects.edit', $duplicate))
        ->assertSessionHas('status', 'تم إنشاء نسخة جديدة كمسودة.');
    expect($duplicate->user_id)->toBe($owner->id)
        ->and($duplicate->status)->toBe(ProjectStatus::DRAFT)
        ->and($duplicate->project_duration_years)->toBe($project->project_duration_years)
        ->and($duplicate->residential_collection_schedule)->toBe($project->residential_collection_schedule)
        ->and($duplicate->commercial_collection_schedule)->toBe($project->commercial_collection_schedule);
});

it('archives an owned project', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $this->actingAs($owner)->post(route('admin.projects.archive', $project));

    $response
        ->assertRedirect(route('admin.projects.index'))
        ->assertSessionHas('status', 'تمت أرشفة المشروع.');
    expect($project->refresh()->status)->toBe(ProjectStatus::ARCHIVED);
});

it('forbids access to another users project', function (string $routeName) {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $response = $routeName === 'admin.projects.edit'
        ? $this->actingAs($otherUser)->get(route($routeName, $project))
        : $this->actingAs($otherUser)->post(route($routeName, $project));

    $response->assertForbidden();
    expect($project->refresh()->status)->toBe(ProjectStatus::COMPLETED);
    $this->assertDatabaseCount('projects', 1);
})->with([
    'edit' => ['admin.projects.edit'],
    'duplicate' => ['admin.projects.duplicate'],
    'archive' => ['admin.projects.archive'],
]);

/** @return list<array<string, mixed>> */
function dataTableColumns(): array
{
    return [
        ['data' => 'name', 'name' => 'name', 'searchable' => false, 'orderable' => true],
        ['data' => 'owner_name', 'name' => 'owner_name', 'searchable' => false, 'orderable' => true],
        ['data' => 'status', 'name' => 'status', 'searchable' => false, 'orderable' => true],
        ['data' => 'currency_code', 'name' => 'currency_code', 'searchable' => false, 'orderable' => true],
        ['data' => 'project_duration_years', 'name' => 'project_duration_years', 'searchable' => false, 'orderable' => true],
        ['data' => 'updated_at', 'name' => 'updated_at', 'searchable' => false, 'orderable' => true],
        ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
    ];
}

/** @return array<string, mixed> */
function completeProjectFormData(): array
{
    return [
        'name' => 'مشروع المعاينة',
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
