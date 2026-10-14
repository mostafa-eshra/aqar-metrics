<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Http\Requests\Admin\ProjectSensitivityRequest;
use App\Models\Project;
use App\Services\ProjectCalculationService;
use App\Services\ProjectCashFlowService;
use App\Services\ProjectSensitivityService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Gate;

final class ProjectAnalysisController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            self::setPermissionToController(PermissionEnum::PROJECT_ANALYZE, ['show', 'sensitivity']),
        ];
    }

    public function show(Request $request, Project $project): View
    {
        Gate::authorize('analyze', $project);

        return $this->render($request, $project);
    }

    public function sensitivity(ProjectSensitivityRequest $request, Project $project): View
    {
        $validated = $request->validated();

        return $this->render(
            request: $request,
            project: $project,
            sensitivityRows: ProjectSensitivityService::analyze(
                project: $project,
                field: $validated['field'],
                changes: array_map('intval', $validated['changes']),
            ),
            sensitivityField: $validated['field'],
        );
    }

    /** @param list<array<string, string|int|null>> $sensitivityRows */
    private function render(
        Request $request,
        Project $project,
        array $sensitivityRows = [],
        ?string $sensitivityField = null,
    ): View {
        $project->loadMissing(['owner', 'viewers', 'scenarios']);
        $metrics = ProjectCalculationService::calculate($project);
        $cashFlow = ProjectCashFlowService::calculate($project, $metrics);

        return view('admin.projects.analysis', [
            'project' => $project,
            'metrics' => $metrics,
            'cashFlow' => $cashFlow,
            'sensitivityRows' => $sensitivityRows,
            'sensitivityField' => $sensitivityField,
            'canManageProject' => $request->user()?->can('share', $project) ?? false,
        ]);
    }
}
