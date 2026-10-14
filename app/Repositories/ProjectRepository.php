<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Project;
use App\Models\User;
use App\Repositories\DTO\ProjectFilterDTO;
use Illuminate\Database\Eloquent\Builder;

final class ProjectRepository
{
    /** @var array<string, string> */
    private const SORTABLE_COLUMNS = [
        'name' => 'projects.name',
        'status' => 'projects.status',
        'currency_code' => 'projects.currency_code',
        'project_duration_years' => 'projects.project_duration_years',
        'updated_at' => 'projects.updated_at',
    ];

    /** @return Builder<Project> */
    public static function queryVisibleTo(User $user): Builder
    {
        $query = Project::query();

        if (! $user->is_active) {
            return $query->whereKey([]);
        }

        if (! $user->isAdministrator()) {
            $query->where(function (Builder $query) use ($user): void {
                $query->whereBelongsTo($user, 'owner')
                    ->orWhereHas('viewers', static function (Builder $query) use ($user): void {
                        $query->whereKey($user->getKey());
                    });
            });
        }

        return $query;
    }

    /** @return Builder<Project> */
    public static function filter(User $user, ProjectFilterDTO $filter): Builder
    {
        $query = self::queryVisibleTo($user)
            ->with(['owner', 'viewers:id'])
            ->select('projects.*');

        if ($filter->text !== null) {
            $text = $filter->text;

            $query->where(function (Builder $query) use ($text): void {
                $query->where('projects.name', 'like', "%{$text}%")
                    ->orWhere('projects.location', 'like', "%{$text}%")
                    ->orWhere('projects.currency_code', 'like', "%{$text}%")
                    ->orWhereHas('owner', function (Builder $query) use ($text): void {
                        $query->where('users.name', 'like', "%{$text}%");
                    });
            });
        }

        if ($filter->status !== null) {
            $query->where('projects.status', $filter->status->value);
        }

        $direction = strtolower($filter->orderByColumnDirection ?? 'desc');
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : 'desc';

        if ($filter->orderByColumnName === 'owner_name') {
            $query->orderBy(
                User::query()
                    ->select('name')
                    ->whereColumn('users.id', 'projects.user_id'),
                $direction,
            );
        } else {
            $column = self::SORTABLE_COLUMNS[$filter->orderByColumnName ?? 'updated_at']
                ?? self::SORTABLE_COLUMNS['updated_at'];
            $query->orderBy($column, $direction);
        }

        return $query->orderByDesc('projects.id');
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
