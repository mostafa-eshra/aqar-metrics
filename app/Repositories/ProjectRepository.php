<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class ProjectRepository
{
    /** @return Builder<Project> */
    public static function queryVisibleTo(User $user): Builder
    {
        $query = Project::query();

        if (! $user->is_active) {
            return $query->whereKey([]);
        }

        if (! $user->isAdministrator()) {
            $query->whereBelongsTo($user, 'owner');
        }

        return $query;
    }

    /** @param array<string, mixed> $attributes */
    public static function createForOwner(User $owner, array $attributes, string $calculationVersion): Project
    {
        $project = new Project;
        $project->fill($attributes);
        $project->owner()->associate($owner);
        $project->setAttribute('calculation_version', $calculationVersion);
        $project->save();

        return $project->refresh();
    }

    /** @param array<string, mixed> $attributes */
    public static function update(Project $project, array $attributes): Project
    {
        $project->fill($attributes);
        $project->save();

        return $project->refresh();
    }
}
