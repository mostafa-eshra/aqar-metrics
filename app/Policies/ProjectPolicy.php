<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;

final class ProjectPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_active;
    }

    public function view(User $user, Project $project): bool
    {
        if ($user->canAccessProjectOwnedBy((int) $project->user_id)) {
            return true;
        }

        if (! $user->is_active || ! $project->exists) {
            return false;
        }

        if ($project->relationLoaded('viewers')) {
            return $project->viewers->contains($user);
        }

        return $project->viewers()->whereKey($user->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $user->is_active;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->canAccessProjectOwnedBy((int) $project->user_id)
            && $project->status !== ProjectStatus::ARCHIVED;
    }

    public function duplicate(User $user, Project $project): bool
    {
        return $user->canAccessProjectOwnedBy((int) $project->user_id);
    }

    public function archive(User $user, Project $project): bool
    {
        return $user->canAccessProjectOwnedBy((int) $project->user_id)
            && $project->status !== ProjectStatus::ARCHIVED;
    }

    public function analyze(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function export(User $user, Project $project): bool
    {
        return $this->view($user, $project);
    }

    public function share(User $user, Project $project): bool
    {
        return $user->canAccessProjectOwnedBy((int) $project->user_id);
    }

    public function createScenario(User $user, Project $project): bool
    {
        return $user->canAccessProjectOwnedBy((int) $project->user_id);
    }
}
