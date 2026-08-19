<?php

namespace App\Providers;

use App\Helpers\Currency;
use Illuminate\Support\ServiceProvider;
use Laravel\Fortify\Fortify;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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
use App\Services\GlobalAccountResolver;
use App\Services\Module\TopBarListingService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    Fortify::ignoreRoutes();

    $this->app->singleton(GlobalAccountResolver::class);
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    PublicStorageLink::ensure();

    // Register model observers
    \App\Models\Files::observe(\App\Observers\FileObserver::class);

    DB::macro('companyTable', function (string $table, ?string $connection = null) {
      return CompanyQuery::table($table, $connection);
    });

    // Company-side administrators should have full software access.
    Gate::before(function ($user, string $ability) {
      if ($user instanceof \App\Models\User && $user->hasAnyRole(['Administrator', 'Super Admin'])) {
        return true;
      }

      // Bridge legacy/flat ability names (e.g. "voucher_view", "salik_view", "gn_ledger")
      // still used by @can directives and the sidebar to the new hierarchical permission
      // scheme. Only ever grants (true) or defers (null) — never denies, never over-grants.
      return \App\Support\RoleFieldAccess::gateFallback(
        $user instanceof \App\Models\User ? $user : null,
        $ability
      );
    });

    // ---- Field-level permission Blade directives ----
    // These delegate to the global field_* helpers (app/functions.php), which are the
    // single source of truth and in turn delegate to App\Support\RoleFieldAccess.
    //
    // Wrap a field so it only renders when the current user may VIEW it:
    //   @fieldVisible('rider', 'phone') ... @endfieldVisible
    Blade::if('fieldVisible', function ($entity, $field) {
      return field_visible((string) $entity, (string) $field);
    });
    // Render a block only when the field is EDITABLE for the current user:
    //   @fieldEditable('rider', 'phone') ... @else ... @endfieldEditable
    Blade::if('fieldEditable', function ($entity, $field) {
      return field_editable((string) $entity, (string) $field);
    });
    // Inline attribute helpers for hardcoded inputs:
    //   <input ... @fieldReadonly('rider', 'phone') @fieldRequired('rider', 'phone')>
    Blade::directive('fieldReadonly', function ($expression) {
      return "<?php echo field_editable($expression) ? '' : 'readonly disabled data-rfp-locked=\"1\"'; ?>";
    });
    Blade::directive('fieldRequired', function ($expression) {
      return "<?php echo field_required($expression) ? 'required' : ''; ?>";
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
      static $rfpLocksShared = false;

      if (!$rfpLocksShared && Auth::check()) {
        $rfpLocksShared = true;
        $moduleKey = ModuleRouteResolver::fromRequest();
        $defaultEntity = \App\Support\RoleFieldAccess::entityKeyFromModuleKey($moduleKey);
        $locks = [];
        if ($defaultEntity) {
          $map = \App\Support\RoleFieldAccess::nonEditableFieldMap($defaultEntity);
          if ($map !== []) {
            $locks[$defaultEntity] = $map;
          }
        }
        // Always expose common entities that appear in modals across pages.
        foreach (['rider', 'employees', 'bike', 'account', 'customer', 'vendor', 'sim', 'cheques', 'expenses', 'voucher', 'bank'] as $entity) {
          if (isset($locks[$entity])) {
            continue;
          }
          $map = \App\Support\RoleFieldAccess::nonEditableFieldMap($entity);
          if ($map !== []) {
            $locks[$entity] = $map;
          }
        }
        View::share('rfpDefaultEntity', $defaultEntity);
        View::share('rfpFieldLocks', $locks);
      }

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

      $view->with([
        'menuLabels' => $labels,
        'menuCompanySlug' => $menuCompanySlug,
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

    // Centralized delete-approval: must use wildcard events — Model::deleting()
    // only listens on Illuminate\Database\Eloquent\Model, not subclasses.
    // Wildcard listeners receive ($eventName, $payload).
    Event::listen('eloquent.deleting: *', function ($event, $payload) {
      $model = is_array($payload) ? ($payload[0] ?? null) : $payload;
      if (! $model instanceof \Illuminate\Database\Eloquent\Model) {
        return null;
      }

      return \App\Services\DeleteRequestService::handleDeleting($model);
    });
    Event::listen('eloquent.updating: *', function ($event, $payload) {
      $model = is_array($payload) ? ($payload[0] ?? null) : $payload;
      if (! $model instanceof \Illuminate\Database\Eloquent\Model) {
        return null;
      }

      if (! \App\Services\DeleteRequestService::handleUpdating($model)) {
        return false;
      }
    });

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
