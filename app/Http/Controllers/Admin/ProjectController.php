<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Enums\ProjectStatus;
use App\Http\Requests\Admin\ProjectRequest;
use App\Http\Resources\Admin\Project\ProjectResource;
use App\Models\Project;
use App\Models\User;
use App\Repositories\DTO\ProjectFilterDTO;
use App\Repositories\ProjectRepository;
use App\Services\ProjectCollectionScheduleService;
use App\Services\ProjectService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Gate;
use Yajra\DataTables\Facades\DataTables;

final class ProjectController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            self::setPermissionToController(PermissionEnum::PROJECT_LIST, ['index']),
            self::setPermissionToController(PermissionEnum::PROJECT_CREATE, ['create']),
            self::setPermissionToController(PermissionEnum::PROJECT_EDIT, ['edit']),
            self::setPermissionToController(PermissionEnum::PROJECT_DUPLICATE, ['duplicate']),
            self::setPermissionToController(PermissionEnum::PROJECT_ARCHIVE, ['archive']),
            self::setPermissionToController(PermissionEnum::PROJECT_PREVIEW, ['preview']),
        ];
    }

    public function index(Request $request): JsonResponse|View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($request->ajax()) {
            $this->normalizeDataTableColumns($request);
            $filter = $this->collectFilterParams($request);
            $query = ProjectRepository::filter(user: $user, filter: $filter);
            $totalRecords = ProjectRepository::queryVisibleTo($user)->count();
            $filteredRecords = (clone $query)->reorder()->count();

            return DataTables::eloquent($query)
                ->setTotalRecords($totalRecords)
                ->setFilteredRecords($filteredRecords)
                ->filter(static function (Builder $query): void {})
                ->order(static function (Builder $query): void {})
                ->setTransformer(
                    fn (Project $project): array => ProjectResource::make($project)->resolve($request),
                )
                ->toJson();
        }

        return view('admin.projects.index');
    }

    public function create(ProjectRequest $request): RedirectResponse|View
    {
        Gate::authorize('create', Project::class);

        if ($request->isMethod('post')) {
            $user = $request->user();
            abort_unless($user instanceof User, 403);

            ProjectService::create(owner: $user, data: $request->validated());

            return redirect()
                ->route('admin.projects.index')
                ->with('status', trans('app.projects.created'));
        }

        $project = new Project;
        $durationYears = (int) config('aqar_metrics.project.default_duration_years');
        $schedules = ProjectCollectionScheduleService::defaults($durationYears);
        $project->fill([
            'currency_code' => array_key_first(config('aqar_metrics.project.currencies')),
            'project_duration_years' => $durationYears,
            'status' => ProjectStatus::DRAFT,
            'has_hotel' => false,
            'building_coverage_percent' => config('aqar_metrics.project.default_building_coverage_percent'),
            ...$schedules,
        ]);

        return view('admin.projects.create', ['project' => $project]);
    }

    public function edit(ProjectRequest $request, Project $project): RedirectResponse|View
    {
        Gate::authorize('update', $project);

        if ($request->isMethod('post')) {
            ProjectService::update(project: $project, data: $request->validated());

            return redirect()
                ->route('admin.projects.edit', $project)
                ->with('status', trans('app.projects.updated'));
        }

        return view('admin.projects.edit', ['project' => $project]);
    }

    public function preview(ProjectRequest $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $metrics = ProjectService::preview(owner: $user, data: $request->validated());

        return response()->json([
            'saved' => false,
            'currency_code' => $request->validated('currency_code'),
            'metrics' => $metrics->toArray(),
        ]);
    }

    public function duplicate(Project $project): RedirectResponse
    {
        Gate::authorize('duplicate', $project);

        $duplicate = ProjectService::duplicate(
            project: $project,
            name: trans('app.projects.copy_name', ['name' => $project->name]),
        );

        return redirect()
            ->route('admin.projects.edit', $duplicate)
            ->with('status', trans('app.projects.duplicated'));
    }

    public function archive(Project $project): RedirectResponse
    {
        Gate::authorize('archive', $project);
        ProjectService::archive($project);

        return redirect()
            ->route('admin.projects.index')
            ->with('status', trans('app.projects.archived'));
    }

    private function collectFilterParams(Request $request): ProjectFilterDTO
    {
        $text = trim((string) $request->input('filter_search', ''));
        $status = ProjectStatus::tryFrom((string) $request->input('filter_status', ''));
        $orderIndex = max(0, (int) $request->input('order.0.column', 5));
        $orderColumnName = $request->input("columns.{$orderIndex}.name");

        return ProjectFilterDTO::create(
            text: $text !== '' ? $text : null,
            status: $status,
            orderByColumnName: is_string($orderColumnName) ? $orderColumnName : null,
            orderByColumnDirection: (string) $request->input('order.0.dir', 'desc'),
        );
    }

    private function normalizeDataTableColumns(Request $request): void
    {
        $columns = [
            ['data' => 'name', 'name' => 'name', 'searchable' => false, 'orderable' => true],
            ['data' => 'owner_name', 'name' => 'owner_name', 'searchable' => false, 'orderable' => true],
            ['data' => 'status', 'name' => 'status', 'searchable' => false, 'orderable' => true],
            ['data' => 'currency_code', 'name' => 'currency_code', 'searchable' => false, 'orderable' => true],
            ['data' => 'project_duration_years', 'name' => 'project_duration_years', 'searchable' => false, 'orderable' => true],
            ['data' => 'updated_at', 'name' => 'updated_at', 'searchable' => false, 'orderable' => true],
            ['data' => 'actions', 'name' => 'actions', 'searchable' => false, 'orderable' => false],
        ];

        $request->merge(['columns' => $columns]);
    }
}
