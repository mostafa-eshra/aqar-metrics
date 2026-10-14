<?php

declare(strict_types=1);

use App\Models\Project;
use App\Models\User;
use App\Services\ProjectCashFlowService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('lets an owner create a linked scenario and share the project for read-only access', function () {
    $owner = User::factory()->create();
    $viewer = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($owner)
        ->post(route('admin.projects.scenarios.store', $project), ['scenario_name' => 'متفائل'])
        ->assertRedirect();

    $scenario = Project::query()->where('parent_project_id', $project->id)->firstOrFail();
    expect($scenario->scenario_name)->toBe('متفائل');

    $this->post(route('admin.projects.viewers.store', $project), ['email' => $viewer->email])
        ->assertRedirect();

    $this->actingAs($viewer)
        ->get(route('admin.projects.show', $project))
        ->assertOk();
    $this->get(route('admin.projects.edit', $project))->assertForbidden();
});

it('exports the project as a real Excel document', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create([
        'annual_cash_flow_schedule' => ProjectCashFlowService::defaults(8),
    ]);

    $this->actingAs($owner)
        ->get(route('admin.projects.exports.excel', $project))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('forbids exporting a project that the user cannot view', function () {
    $owner = User::factory()->create();
    $otherUser = User::factory()->create();
    $project = Project::factory()->for($owner, 'owner')->create();

    $this->actingAs($otherUser)
        ->get(route('admin.projects.exports.excel', $project))
        ->assertForbidden();
});

it('compares visible projects without converting or aggregating different currencies', function () {
    $user = User::factory()->create();
    $egyptProject = Project::factory()->for($user, 'owner')->create(['currency_code' => 'EGP']);
    $saudiProject = Project::factory()->for($user, 'owner')->create(['currency_code' => 'SAR']);

    $this->actingAs($user)
        ->get(route('admin.projects.compare', ['projects' => [$egyptProject->id, $saudiProject->id]]))
        ->assertOk()
        ->assertSee('القيم معروضة منفصلة دون تحويل أو جمع')
        ->assertSee('EGP')
        ->assertSee('SAR');
});
