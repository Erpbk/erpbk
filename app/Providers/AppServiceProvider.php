<?php

namespace App\Providers;

use App\Helpers\Currency;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use App\Models\AgreementCategory;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Settings;
use App\Support\CompanyQuery;
use App\Support\CompanyRouteContext;
use Illuminate\Support\Facades\DB;
use App\Support\ErpModuleRegistry;
use App\Support\ModuleRouteResolver;
use App\Support\PublicStorageLink;
use App\Services\Email\CompanyEmailBrandingService;
use App\Services\Module\TopBarListingService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

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
    PublicStorageLink::ensure();

    DB::macro('companyTable', function (string $table, ?string $connection = null) {
      return CompanyQuery::table($table, $connection);
    });

    // Company-side administrators should have full software access.
    Gate::before(function ($user, string $ability) {
      if ($user instanceof \App\Models\User && $user->hasAnyRole(['Administrator', 'Super Admin'])) {
        return true;
      }

      return null;
    });

    // Force HTTPS in production
    if (config('app.env') === 'production') {
      URL::forceScheme('https');
    }

    View::composer('layouts.settingsPanelLayout', function ($view) {
      $view->with('settingsPanelLabels', Settings::getMenuLabels());
    });

    View::composer(['emails.template', 'emails.general'], function ($view) {
      if (!$view->offsetExists('emailBranding')) {
        $view->with('emailBranding', app(CompanyEmailBrandingService::class)->resolveForEmail());
      }
    });

    View::composer('*', function ($view) {
      static $topBarShared = false;
      if ($topBarShared) {
        return;
      }

      $moduleKey = ModuleRouteResolver::fromRequest();
      if ($moduleKey === null || !ErpModuleRegistry::hasTopBar($moduleKey)) {
        return;
      }

      $listingData = app(TopBarListingService::class)->listingViewData($moduleKey, request());
      View::share($listingData);
      $topBarShared = true;
    });

    // Dynamic ERP menu labels (Settings + optional per-company admin overrides)
    View::composer('layouts.menu', function ($view) {
      $labels = Settings::getMenuLabels();
      $shared = app('view')->getShared();
      $company = $view->getData()['currentCompany'] ?? ($shared['currentCompany'] ?? null);
      if ($company instanceof Company && is_array($company->modules_settings)) {
        $overrides = $company->modules_settings['label_overrides'] ?? [];
        if ($overrides !== []) {
          $labels = array_merge($labels, array_filter($overrides, fn($v) => $v !== null && $v !== ''));
        }
      }
      $fallbackSlug = request()->route('company_slug') ?? session('company_slug');
      $menuCompanySlug = CompanyRouteContext::slug() ?? $fallbackSlug;
      $agreementMenuModules = [];
      if (auth()->check() && ! request()->routeIs('admin.*')) {
        try {
          AgreementCategory::ensureDefaultsForCompany();
          $configured = array_keys(config('agreement_modules.modules', []));
          $active = AgreementCategory::activeModuleKeysWithAgreements();
          $agreementMenuModules = array_values(array_intersect($configured, $active));
        } catch (\Throwable) {
          $agreementMenuModules = [];
        }
      }

      $view->with([
        'menuLabels' => $labels,
        'menuCompanySlug' => $menuCompanySlug,
        'agreementMenuModules' => $agreementMenuModules,
      ]);
    });

    // Make company branding available across all Blade views.
    View::composer('*', function ($view) {
      $branding = app(\App\Services\Email\CompanyEmailBrandingService::class)->resolve();
      $companyName = $branding['name'] ?? config('app.name');

      if (!empty($branding['company_id'])) {
        config([
          'variables.templateName' => $companyName,
        ]);
      }

      $view->with('companyLogoUrl', $branding['logo_url'] ?? null);
      $view->with('companyDisplayName', $companyName);
      $view->with('appCurrencyCode', Currency::code());
      $view->with('appCurrencySymbol', Currency::symbol());
    });

    Relation::morphMap([
      'employee' => \App\Models\Employee::class,
      'rider' => \App\Models\Riders::class,
    ]);

    app()->singleton('user_branches', function () {
      /** @var \App\Models\User|null $user */
      $user = Auth::user();

      if (!$user) {
        return [];
      }

      // Check if user is admin
      if ($user->hasAnyRole('Administrator', 'Super Admin')) {
        // All branches for this company (BelongsToCompany global scope applies).
        return Branch::query()->pluck('id')->toArray();
      }

      // Non-admin: return only assigned branches.
      // branch_ids is cast to array on User model; keep backward compatibility
      // for legacy rows where it may still be stored as raw JSON string.
      $branchIds = $user->branch_ids;
      if (is_array($branchIds)) {
        return $branchIds;
      }

      return json_decode((string) ($branchIds ?? '[]'), true) ?? [];
    });
  }
}
