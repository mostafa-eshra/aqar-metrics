<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Contracts\View\View;

final class DashboardController extends BaseController
{
    public function __invoke(): View
    {
        return view('admin.dashboard');
    }
}
