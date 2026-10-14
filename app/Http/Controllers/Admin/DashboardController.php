<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Repositories\ProjectRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

final class DashboardController extends BaseController
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return view('admin.dashboard', [
            'dashboard' => ProjectRepository::dashboardFor($user),
        ]);
    }
}
