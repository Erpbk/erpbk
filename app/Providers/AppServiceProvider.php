<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Models\Company;
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
    Fortify::ignoreRoutes();
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

    // Dynamic ERP menu labels (Settings + optional per-company admin overrides)
    View::composer('layouts.menu', function ($view) {
      $labels = Settings::getMenuLabels();
      $company = $view->getData()['currentCompany'] ?? view()->shared('currentCompany');
      if ($company instanceof Company && is_array($company->modules_settings)) {
        $overrides = $company->modules_settings['label_overrides'] ?? [];
        if ($overrides !== []) {
          $labels = array_merge($labels, array_filter($overrides, fn ($v) => $v !== null && $v !== ''));
        }
      }
      $view->with('menuLabels', $labels);
    });

    Relation::morphMap([
        'employee' => \App\Models\Employee::class,
        'rider' => \App\Models\Riders::class,
    ]);

    app()->singleton('user_branches', function () {
        $ids = Auth::user()?->branch_ids;
        if ($ids === null) {
            return [];
        }
        if (is_array($ids)) {
            return $ids;
        }

        return json_decode((string) $ids, true) ?: [];
    });
  }
}
