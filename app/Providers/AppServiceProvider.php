<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Models\Settings;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;

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
    // Force HTTPS in production
    if (config('app.env') === 'production') {
      URL::forceScheme('https');
    }

    // Dynamic ERP menu labels (editable from Settings)
    View::composer('layouts.menu', function ($view) {
      $view->with('menuLabels', Settings::getMenuLabels());
    });

    Relation::morphMap([
        'employee' => \App\Models\Employee::class,
        'rider' => \App\Models\Riders::class,
    ]);

    app()->singleton('user_branches', function () {
        return json_decode(Auth::user()?->branch_ids) ?? [];
    });
  }
}
