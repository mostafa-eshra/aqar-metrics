<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Http\Requests\Admin\ProjectComparisonRequest;
use App\Models\User;
use App\Repositories\ProjectRepository;
use App\Services\ProjectCalculationService;
use App\Services\ProjectCashFlowService;
use Illuminate\Contracts\View\View;
use Illuminate\Routing\Controllers\HasMiddleware;

final class ProjectComparisonController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [self::setPermissionToController(PermissionEnum::PROJECT_COMPARE, ['index'])];
    }

    public function index(ProjectComparisonRequest $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $availableProjects = ProjectRepository::queryVisibleTo($user)
            ->with('owner')
            ->orderBy('name')
            ->get();
        $selectedIds = array_map('intval', $request->validated('projects', []));
        $selectedProjects = $availableProjects->whereIn('id', $selectedIds)->values();

        abort_if(count($selectedIds) !== $selectedProjects->count(), 404);

        $comparison = $selectedProjects->map(function ($project): array {
            $metrics = ProjectCalculationService::calculate($project);
            $cashFlow = ProjectCashFlowService::calculate($project, $metrics);

            return [
                'project' => $project,
                'metrics' => $metrics,
                'cash_flow' => $cashFlow,
            ];
        });

        return view('admin.projects.compare', [
            'availableProjects' => $availableProjects,
            'selectedIds' => $selectedIds,
            'comparison' => $comparison,
            'hasDifferentCurrencies' => $selectedProjects->pluck('currency_code')->filter()->unique()->count() > 1,
        ]);
    }
}
