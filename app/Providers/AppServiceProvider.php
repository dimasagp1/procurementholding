<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Support\Facades\Gate;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\Setting;

class AppServiceProvider extends ServiceProvider
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
        Gate::before(function ($user, $ability) {
            return $user->hasRole('superadmin') ? true : null;
        });

        // Share settings with all views
        if (!app()->runningInConsole() && Schema::hasTable('settings')) {
            View::share('appName', Setting::get('app_name', config('app.name')));
            View::share('appLogo', Setting::get('app_logo'));
            View::share('appFavicon', Setting::get('app_favicon'));
        }
    }
}
