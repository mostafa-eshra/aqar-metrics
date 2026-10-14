<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin\Project;

use App\Enums\ProjectStatus;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Project */
class ProjectResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $project = $this->resource;
        $location = $project->location !== null && $project->location !== ''
            ? '<small class="d-block text-secondary mt-1">'.e($project->location).'</small>'
            : '';

        return [
            'name' => '<strong>'.e($project->name).'</strong>'.$location,
            'owner_name' => e($project->owner->name),
            'status' => $this->statusBadge($project->status),
            'currency_code' => e($project->currency_code ?? trans('app.projects.not_available')),
            'project_duration_years' => trans_choice(
                'app.projects.duration_years',
                $project->project_duration_years,
                ['count' => $project->project_duration_years],
            ),
            'updated_at' => $project->updated_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
            'actions' => $this->actions($request, $project),
            'DT_RowId' => "project-{$project->getKey()}",
        ];
    }

    private function statusBadge(ProjectStatus $status): string
    {
        $class = match ($status) {
            ProjectStatus::DRAFT => 'bg-yellow-lt text-yellow',
            ProjectStatus::COMPLETED => 'bg-success-lt text-success',
            ProjectStatus::ARCHIVED => 'bg-secondary-lt text-secondary',
        };

        return '<span class="badge '.$class.'"><span class="status-dot"></span>'.e($status->label()).'</span>';
    }

    private function actions(Request $request, Project $project): string
    {
        $user = $request->user();
        $primaryAction = '';
        $menuActions = [];

        if ($user?->can('view', $project)) {
            $primaryAction = sprintf(
                '<a class="btn btn-sm btn-aqar" href="%s">%s</a>',
                e(route('admin.projects.show', $project)),
                e(trans('app.projects.view')),
            );
        }

        if ($user?->can('update', $project)) {
            $menuActions[] = sprintf(
                '<li><a class="dropdown-item" href="%s">%s</a></li>',
                e(route('admin.projects.edit', $project)),
                e(trans('app.edit')),
            );
        }

        if ($user?->can('duplicate', $project)) {
            $menuActions[] = sprintf(
                '<li><form method="POST" action="%s">%s<button class="dropdown-item" type="submit">%s</button></form></li>',
                e(route('admin.projects.duplicate', $project)),
                csrf_field(),
                e(trans('app.projects.duplicate')),
            );
        }

        if ($user?->can('archive', $project)) {
            $menuActions[] = sprintf(
                '<li><hr class="dropdown-divider"></li><li><form class="js-confirm-archive" method="POST" action="%s" data-confirm="%s">%s<button class="dropdown-item text-danger" type="submit">%s</button></form></li>',
                e(route('admin.projects.archive', $project)),
                e(trans('app.projects.archive_confirmation')),
                csrf_field(),
                e(trans('app.projects.archive')),
            );
        }

        $menu = $menuActions === [] ? '' : sprintf(
            '<div class="dropdown"><button class="btn btn-sm btn-icon btn-outline-secondary" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="%s">•••</button><ul class="dropdown-menu dropdown-menu-end">%s</ul></div>',
            e(trans('app.actions')),
            implode('', $menuActions),
        );

        return '<div class="d-flex justify-content-end gap-1">'.$primaryAction.$menu.'</div>';
    }
}
