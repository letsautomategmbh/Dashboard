<?php

namespace Modules\Dashboard\Providers;

use Illuminate\Support\ServiceProvider;

class DashboardServiceProvider extends ServiceProvider
{
    const MODULE_NAME = 'Dashboard';
    const MODULE_ALIAS = 'dashboard';

    public function boot()
    {
        $this->loadViews();
        $this->loadRoutes();
        $this->loadMigrations();
        $this->loadTranslations();
        $this->registerHooks();
    }

    public function register()
    {
        //
    }

    protected function loadViews()
    {
        $this->loadViewsFrom(__DIR__.'/../Resources/views', self::MODULE_ALIAS);
    }

    protected function loadRoutes()
    {
        // Deliberately a single require with no wrapping Route::group() here —
        // a namespace-only Route::group() wrapper breaks
        // RouteGroup::formatPrefix() on PHP 8.1+ (same fix already applied
        // across every recent module, e.g. Notes' own provider).
        require __DIR__.'/../Http/routes.php';
    }

    protected function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../Database/Migrations');
    }

    protected function loadTranslations()
    {
        $this->app['translator']->addJsonPath(__DIR__.'/../Resources/lang');
    }

    protected function registerHooks()
    {
        \Eventy::addAction('menu.append', function () {
            $currentRoute = \Request::route() ? \Request::route()->getName() : '';
            $active = \Illuminate\Support\Str::startsWith($currentRoute, 'dashboard.') ? 'active' : '';
            echo '<li class="'.$active.'" data-menu-key="dashboard"><a href="'.route('dashboard.index').'"><i class="glyphicon glyphicon-th-large"></i> '.__('Dashboard').'</a></li>';
        }, 20);
    }
}
