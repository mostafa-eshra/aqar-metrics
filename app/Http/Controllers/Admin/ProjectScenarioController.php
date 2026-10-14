<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Http\Requests\Admin\ProjectScenarioRequest;
use App\Models\Project;
use App\Services\ProjectService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;

final class ProjectScenarioController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [self::setPermissionToController(PermissionEnum::PROJECT_SCENARIO, ['store'])];
    }

    public function store(ProjectScenarioRequest $request, Project $project): RedirectResponse
    {
        $scenario = ProjectService::createScenario($project, (string) $request->validated('scenario_name'));

        return redirect()
            ->route('admin.projects.edit', $scenario)
            ->with('status', trans('app.phase_nine.scenarios.created'));
    }
}
