<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use App\Repositories\DTO\ProjectFilterDTO;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

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

    /**
     * @return array{
     *     total: int,
     *     drafts: int,
     *     completed: int,
     *     recently_updated: int,
     *     recent_projects: Collection<int, Project>
     * }
     */
    public static function dashboardFor(User $user): array
    {
        $totals = self::queryVisibleTo($user)
            ->selectRaw(
                'COUNT(*) as total, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as drafts, '
                .'SUM(CASE WHEN status = ? THEN 1 ELSE 0 END) as completed, '
                .'SUM(CASE WHEN updated_at >= ? THEN 1 ELSE 0 END) as recently_updated',
                [ProjectStatus::DRAFT->value, ProjectStatus::COMPLETED->value, now()->subDays(7)],
            )
            ->first();

        $recentProjects = self::queryVisibleTo($user)
            ->with('owner:id,name')
            ->select(['id', 'user_id', 'name', 'location', 'status', 'currency_code', 'updated_at'])
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return [
            'total' => (int) ($totals?->getAttribute('total') ?? 0),
            'drafts' => (int) ($totals?->getAttribute('drafts') ?? 0),
            'completed' => (int) ($totals?->getAttribute('completed') ?? 0),
            'recently_updated' => (int) ($totals?->getAttribute('recently_updated') ?? 0),
            'recent_projects' => $recentProjects,
        ];
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
