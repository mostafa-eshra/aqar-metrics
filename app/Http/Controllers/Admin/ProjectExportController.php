<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\PermissionEnum;
use App\Models\Project;
use App\Services\ProjectCalculationService;
use App\Services\ProjectCashFlowService;
use App\Services\ProjectExcelExportService;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProjectExportController extends BaseController implements HasMiddleware
{
    public static function middleware(): array
    {
        return [self::setPermissionToController(PermissionEnum::PROJECT_EXPORT, ['excel'])];
    }

    public function excel(Project $project): BinaryFileResponse
    {
        Gate::authorize('export', $project);
        $project->loadMissing(['owner', 'viewers']);
        $metrics = ProjectCalculationService::calculate($project);
        $cashFlow = ProjectCashFlowService::calculate($project, $metrics);
        $path = ProjectExcelExportService::create($project, $metrics, $cashFlow);

        return response()->download(
            $path,
            "project-{$project->getKey()}.xlsx",
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        )->deleteFileAfterSend(true);
    }
}
