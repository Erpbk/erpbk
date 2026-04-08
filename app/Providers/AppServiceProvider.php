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
      $shared = app('view')->getShared();
      $company = $view->getData()['currentCompany'] ?? ($shared['currentCompany'] ?? null);
      if ($company instanceof Company && is_array($company->modules_settings)) {
        $overrides = $company->modules_settings['label_overrides'] ?? [];
        if ($overrides !== []) {
          $labels = array_merge($labels, array_filter($overrides, fn ($v) => $v !== null && $v !== ''));
        }
      }
      $view->with('menuLabels', $labels);
    });

    // Make company branding available across all Blade views.
    View::composer('*', function ($view) {
      $shared = app('view')->getShared();
      $company = $view->getData()['currentCompany'] ?? ($shared['currentCompany'] ?? null);
      $logoUrl = asset('assets/img/logo-full.png');
      $companyName = config('app.name');

      if ($company instanceof Company) {
        if (!empty($company->logo)) {
          $logoUrl = asset('storage/' . $company->logo);
        }
        if (!empty($company->name)) {
          $companyName = $company->name;
        }
      }

      $view->with('companyLogoUrl', $logoUrl);
      $view->with('companyDisplayName', $companyName);
    });

    Relation::morphMap([
        'employee' => \App\Models\Employee::class,
        'rider' => \App\Models\Riders::class,
    ]);

    app()->singleton('user_branches', function () {
        $user = Auth::user();
        
        if (!$user) {
            return [];
        }
        
        // Check if user is admin
        if ($user->hasAnyRole('Administrator', 'Super Admin')) {
            // Return ALL branch IDs
            return \App\Models\Branch::pluck('id')->toArray() ?? [];
        }
        
        // Non-admin: return only assigned branches
        return json_decode($user->branch_ids, true) ?? [];
    });
  }
}
