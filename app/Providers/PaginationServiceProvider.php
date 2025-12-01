<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class PaginationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // Set the default pagination view to our global pagination component
        Paginator::defaultView('components.global-pagination');

        // Set the default simple pagination view
        Paginator::defaultSimpleView('components.global-pagination');

        // Register the global pagination component
        $this->app->make('view')->addNamespace('components', resource_path('views/components'));
    }
}
