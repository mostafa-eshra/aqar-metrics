<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Http\Requests\Admin\ProjectShareRequest;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectSharingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Gate;

final class ProjectShareController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [self::setPermissionToController(PermissionEnum::PROJECT_SHARE, ['store', 'destroy'])];
    }

    public function store(ProjectShareRequest $request, Project $project): RedirectResponse
    {
        $viewer = User::query()->where('email', $request->validated('email'))->firstOrFail();
        ProjectSharingService::share($project, $viewer);

        return back()->with('status', trans('app.phase_nine.sharing.shared'));
    }

    public function destroy(Project $project, int $viewer): RedirectResponse
    {
        Gate::authorize('share', $project);
        $sharedViewer = $project->viewers()->whereKey($viewer)->firstOrFail();
        ProjectSharingService::stopSharing($project, $sharedViewer);

        return back()->with('status', trans('app.phase_nine.sharing.removed'));
    }
}
