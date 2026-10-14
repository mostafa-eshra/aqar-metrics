<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ProjectController;
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
            Route::match(['get', 'post'], '/create', [ProjectController::class, 'create'])->name('create');
            Route::post('/preview', [ProjectController::class, 'preview'])->name('preview');
            Route::match(['get', 'post'], '/{project}/edit', [ProjectController::class, 'edit'])->name('edit');
            Route::post('/{project}/duplicate', [ProjectController::class, 'duplicate'])->name('duplicate');
            Route::post('/{project}/archive', [ProjectController::class, 'archive'])->name('archive');
        });

        Route::prefix('users')->name('users.')->group(function (): void {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::match(['get', 'post'], '/create', [UserController::class, 'create'])->name('create');
            Route::match(['get', 'post'], '/{user}/edit', [UserController::class, 'edit'])->name('edit');
        });
    });
