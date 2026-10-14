<?php

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('shows permission scoped project metrics and recent work on the dashboard', function () {
    $owner = User::factory()->create(['name' => 'مالك لوحة التحكم']);
    $otherUser = User::factory()->create();
    $sharedProject = Project::factory()->for($otherUser, 'owner')->create([
        'name' => 'مشروع مشترك',
        'status' => ProjectStatus::COMPLETED,
        'updated_at' => now()->subDay(),
    ]);
    $sharedProject->viewers()->attach($owner);
    Project::factory()->for($owner, 'owner')->create([
        'name' => 'مسودة المالك',
        'status' => ProjectStatus::DRAFT,
        'updated_at' => now()->subDays(2),
    ]);
    Project::factory()->for($otherUser, 'owner')->create([
        'name' => 'مشروع غير متاح',
        'status' => ProjectStatus::COMPLETED,
    ]);

    $response = $this->actingAs($owner)->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertViewIs('admin.dashboard')
        ->assertViewHas('dashboard', static fn (array $dashboard): bool => $dashboard['total'] === 2
            && $dashboard['drafts'] === 1
            && $dashboard['completed'] === 1
            && $dashboard['recently_updated'] === 2
            && $dashboard['recent_projects']->count() === 2)
        ->assertSee('مشروع مشترك')
        ->assertSee('مسودة المالك')
        ->assertDontSee('مشروع غير متاح')
        ->assertDontSee(route('admin.users.index'), false);
});

it('shows user administration navigation only to administrators', function () {
    $administrator = User::factory()->administrator()->create();

    $response = $this->actingAs($administrator)->get(route('admin.dashboard'));

    $response
        ->assertOk()
        ->assertSee(route('admin.users.index'), false)
        ->assertSee('إدارة المستخدمين');
});
