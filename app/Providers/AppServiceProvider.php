<?php

namespace App\Providers;

use App\Enums\PermissionEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (str_contains(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Model::automaticallyEagerLoadRelationships();
        Model::preventLazyLoading(! $this->app->isProduction());
        Paginator::useBootstrapFive();

        foreach (PermissionEnum::cases() as $permission) {
            Gate::define(
                $permission->value,
                static fn (User $user): bool => $user->is_active && $user->isAdministrator(),
            );
        }
    }
}
