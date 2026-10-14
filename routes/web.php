<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProjectAnalysisController;
use App\Http\Controllers\Admin\ProjectComparisonController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ProjectExportController;
use App\Http\Controllers\Admin\ProjectScenarioController;
use App\Http\Controllers\Admin\ProjectShareController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Route;

Route::get('/', function (): View {
    return view('welcome');
})->name('home');

Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

Route::middleware(['auth', EnsureUserIsActive::class])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');

        Route::prefix('projects')->name('projects.')->group(function (): void {
            Route::get('/', [ProjectController::class, 'index'])->name('index');
            Route::get('/compare', [ProjectComparisonController::class, 'index'])->name('compare');
            Route::match(['get', 'post'], '/create', [ProjectController::class, 'create'])->name('create');
            Route::post('/preview', [ProjectController::class, 'preview'])->name('preview');
            Route::match(['get', 'post'], '/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::post('/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('duplicate');
            Route::post('/{project}/archive', [ProjectController::class, 'archive'])->name('archive');
            Route::get('/{project}/analysis', [ProjectAnalysisController::class, 'show'])->name('analysis');
            Route::post('/{project}/sensitivity', [ProjectAnalysisController::class, 'sensitivity'])
                ->middleware('throttle:30,1')
                ->name('sensitivity');
            Route::post('/{project}/scenarios', [ProjectScenarioController::class, 'store'])->name('scenarios.store');
            Route::post('/{project}/viewers', [ProjectShareController::class, 'store'])->name('viewers.store');
            Route::delete('/{project}/viewers/{viewer}', [ProjectShareController::class, 'destroy'])
                ->whereNumber('viewer')
                ->name('viewers.destroy');
            Route::get('/{project}/exports/excel', [ProjectExportController::class, 'excel'])
                ->middleware('throttle:10,1')
                ->name('exports.excel');
            Route::get('/{project}', [ProjectController::class, 'show'])->name('show');
        });

        Route::prefix('users')->name('users.')->group(function (): void {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::match(['get', 'post'], '/create', [UserController::class, 'create'])->name('create');
            Route::match(['get', 'post'], '/{user}/edit', [UserController::class, 'edit'])->name('edit');
        });
    });
