<?php

use App\Enums\ProjectStatus;
use App\Enums\UserRole;
use App\Models\Project;
use App\Models\User;
use App\Policies\ProjectPolicy;

it('allows active owners and administrators to manage a project', function () {
    $owner = policyUser(id: 10);
    $administrator = policyUser(id: 20, role: UserRole::ADMINISTRATOR);
    $project = (new Project)->forceFill([
        'user_id' => $owner->id,
        'status' => ProjectStatus::COMPLETED,
    ]);
    $policy = new ProjectPolicy;

    expect($policy->viewAny($owner))->toBeTrue()
        ->and($policy->create($owner))->toBeTrue()
        ->and($policy->view($owner, $project))->toBeTrue()
        ->and($policy->update($owner, $project))->toBeTrue()
        ->and($policy->duplicate($owner, $project))->toBeTrue()
        ->and($policy->archive($owner, $project))->toBeTrue()
        ->and($policy->view($administrator, $project))->toBeTrue()
        ->and($policy->update($administrator, $project))->toBeTrue();
});

it('denies inactive users and users who do not own the project', function () {
    $owner = policyUser(id: 10);
    $otherUser = policyUser(id: 20);
    $inactiveAdministrator = policyUser(id: 30, role: UserRole::ADMINISTRATOR, isActive: false);
    $project = (new Project)->forceFill([
        'user_id' => $owner->id,
        'status' => ProjectStatus::COMPLETED,
    ]);
    $policy = new ProjectPolicy;

    expect($policy->view($otherUser, $project))->toBeFalse()
        ->and($policy->update($otherUser, $project))->toBeFalse()
        ->and($policy->duplicate($otherUser, $project))->toBeFalse()
        ->and($policy->archive($otherUser, $project))->toBeFalse()
        ->and($policy->viewAny($inactiveAdministrator))->toBeFalse()
        ->and($policy->create($inactiveAdministrator))->toBeFalse()
        ->and($policy->view($inactiveAdministrator, $project))->toBeFalse();
});

it('does not offer archiving for an already archived project', function () {
    $owner = policyUser(id: 10);
    $project = (new Project)->forceFill([
        'user_id' => $owner->id,
        'status' => ProjectStatus::ARCHIVED,
    ]);

    expect((new ProjectPolicy)->archive($owner, $project))->toBeFalse();
});

function policyUser(int $id, UserRole $role = UserRole::USER, bool $isActive = true): User
{
    return (new User)->forceFill([
        'id' => $id,
        'name' => "User {$id}",
        'email' => "user{$id}@example.com",
        'role' => $role,
        'is_active' => $isActive,
    ]);
}
