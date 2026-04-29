@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
$settingsPanelLabels = \App\Models\Settings::getMenuLabels();
$settingsPanelRidersLabel = \App\Models\Settings::getMenuLabel('rider_settings');
$settingsCompanySlug = request()->route('company_slug') ?? session('company_slug');
$moduleIcons = [
'dashboard' => 'ti-layout-dashboard',
'recycle_bin' => 'ti-trash',
'cash_banks' => 'ti-building-bank',
'employees' => 'ti-user',
'attendance' => 'ti-calendar-check',
'items' => 'ti-notes',
'leads' => 'ti-user-plus',
'customers' => 'ti-user-star',
'vendors' => 'ti-user-star',
'recruiters' => 'ti-user-star',
'bikes' => 'ti-motorbike',
'sims' => 'ti-device-sim',
'fuel_cards' => 'ti-gas-station',
'rta_fines' => 'ti-file-alert',
'rta_saliks' => 'ti-cash',
'inventory' => 'ti-package',
'visa_expense' => 'ti-credit-card',
'expenses' => 'ti-receipt',
'leasing_companies' => 'ti-building',
'garages' => 'ti-parking',
'supplier' => 'ti-truck',
'assets' => 'ti-box',
'documents' => 'ti-upload',
'cheques' => 'ti-file',
'items_list' => 'ti-list-details',
'garage_items' => 'ti-tool',
'attendance_records' => 'ti-calendar-check',
'attendance_summary' => 'ti-calendar-stats',
'riders' => 'ti-user-pin',
'riders_list' => 'ti-users',
'invoices' => 'ti-file-invoice',
'activities' => 'ti-bike',
'live_activities' => 'ti-activity',
'rider_report' => 'ti-chart-bar',
'bike_list' => 'ti-motorbike',
'maintenance_overview' => 'ti-tool',
'vat' => 'ti-receipt-tax',
'vat_ledger' => 'ti-receipt-tax',
'vat_return_file' => 'ti-file-export',
'leasing_companies_list' => 'ti-building',
'leasing_invoices' => 'ti-file-invoice',
'suppliers' => 'ti-truck',
'supplier_invoices' => 'ti-file-invoice',
'vouchers' => 'ti-ticket',
'accounts' => 'ti-graph',
'chart_of_accounts' => 'ti-list-tree',
'ledger' => 'ti-book',
];
$erpModuleMenu = [
['key' => 'dashboard'],
['key' => 'cash_banks', 'children' => ['cash_banks', 'cheques']],
['key' => 'employees'],
['key' => 'attendance', 'children' => ['attendance', 'attendance_records', 'attendance_summary']],
['key' => 'items', 'children' => ['items', 'items_list', 'garage_items']],
['key' => 'leads'],
['key' => 'customers'],
['key' => 'vendors'],
['key' => 'recruiters'],
['key' => 'riders', 'children' => ['rider-settings', 'invoices', 'activities', 'live_activities', 'rider_report']],
['key' => 'bikes', 'children' => ['bike_list']],
['key' => 'sims'],
['key' => 'fuel_cards'],
['key' => 'rta_fines'],
['key' => 'rta_saliks'],
['key' => 'inventory'],
['key' => 'visa_expense'],
['key' => 'expenses'],
['key' => 'vat', 'children' => ['vat', 'vat_ledger', 'vat_return_file']],
['key' => 'leasing_companies', 'children' => ['leasing_companies', 'leasing_companies_list', 'leasing_invoices']],
['key' => 'garages'],
['key' => 'supplier', 'children' => ['supplier', 'suppliers', 'supplier_invoices']],
['key' => 'assets'],
['key' => 'documents'],
['key' => 'vouchers'],
['key' => 'accounts', 'children' => ['accounts', 'chart_of_accounts', 'ledger']],
];
@endphp
@extends('layouts/commonMaster')

@php
$containerNav = 'container-fluid';
@endphp

@section('layoutContent')
<div class="layout-wrapper layout-content-navbar">
  <div class="layout-container">

    {{-- Settings panel sidebar: Zoho-style clean admin --}}
    <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme settings-panel-sidebar">
      <div class="app-brand demo border-bottom">
        <a href="{{ route('settings-panel.company', ['company_slug' => $settingsCompanySlug]) }}" class="app-brand-link">
          <span class="app-brand-logo">
            <i class="ti ti-settings ti-lg text-primary"></i>
          </span>
          <span class="app-brand-text demo menu-text fw-bold fs-6 ms-2">Settings</span>
        </a>
      </div>

      <div class="menu-inner-shadow"></div>

      <ul class="menu-inner py-3">
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">Administration</span>
        </li>

        @canany(['gn_settings','department_view','dropdown_view','visaexpense_view','branches_view'])
        <li class="menu-item {{ Request::is('settings-panel/company') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.company', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-building-community"></i>
            <div>Company Details</div>
          </a>
        </li>
        @can('gn_settings')
        <li class="menu-item {{ Request::is('settings-panel/erp') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.erp', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-adjustments"></i>
            <div>Settings</div>
          </a>
        </li>
        @endcan
        @can('department_view')
        <li class="menu-item {{ Request::is('settings-panel/departments*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.departments.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-sitemap"></i>
            <div>Departments</div>
          </a>
        </li>
        @endcan
        @can('branches_view')
        <li class="menu-item {{ Request::is('settings-panel/branches*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.branches.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-building"></i>
            <div>Branches</div>
          </a>
        </li>
        @endcan

        @endcanany

        {{-- User Management, Activity Logs, Recycle Bin --}}
        <li class="menu-header small text-uppercase mt-3">
          <span class="menu-header-text">User & System</span>
        </li>
        <li class="menu-item {{ Request::is('settings-panel/users*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.users.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-users-group"></i>
            <div>Users</div>
          </a>
        </li>

        <li class="menu-item {{ Request::is('settings-panel/profile*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.profile', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-user"></i>
            <div>Profile</div>
          </a>
        </li>

        <li class="menu-item {{ Request::is('settings-panel/email-settings*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.email-settings.edit', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-mail"></i>
            <div>Email Settings</div>
          </a>
        </li>
        @can('role_view')
        <li class="menu-item {{ Request::is('settings-panel/roles*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.roles.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-user-check"></i>
            <div>Roles</div>
          </a>
        </li>
        @endcan
        @can('permissions_view')
        <li class="menu-item {{ Request::is('settings-panel/permissions*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.permissions.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-lock"></i>
            <div>Permissions</div>
          </a>
        </li>
        @endcan
        @can('activity_logs_view')
        <li class="menu-item {{ Request::is('settings-panel/activity-logs*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.activity-logs.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-history"></i>
            <div>Activity Logs</div>
          </a>
        </li>
        @endcan
        @can('trash_view')
        <li class="menu-item {{ Request::is('settings-panel/trash*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.trash.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-trash text-warning"></i>
            <div>Recycle Bin</div>
          </a>
        </li>
        @endcan

        {{-- ERP Module Settings (General tab: change module name in menu) --}}
        <li class="menu-header small text-uppercase mt-3">
          <span class="menu-header-text">ERP Module Settings</span>
        </li>
        {{-- Specific module settings (Account Fields, Voucher, Rider, Visa Status Types) --}}
        @can('gn_settings')
        <li class="menu-item {{ Request::is('settings-panel/account-fields*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.account-fields.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-wallet"></i>
            <div>{{ $settingsPanelLabels['accounts'] ?? 'Accounts' }}</div>
          </a>
        </li>
        <li class="menu-item {{ Request::is('settings-panel/voucher-settings*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.voucher-settings.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-file-invoice"></i>
            <div>{{ $settingsPanelLabels['vouchers'] ?? 'Vouchers' }}</div>
          </a>
        </li>
        @endcan
        @can('vat_view')
        <li class="menu-item {{ Request::is('settings-panel/vat-settings*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.vat-settings.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-receipt-tax"></i>
            <div>{{ $settingsPanelLabels['vat_settings'] ?? 'VAT Settings' }}</div>
          </a>
        </li>
        @endcan
        @can('visaexpense_view')
        <li class="menu-item {{ Request::is('settings-panel/visa-statuses*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.visa-statuses.index', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-list-check"></i>
            <div>{{ $settingsPanelLabels['visa_status_types'] ?? 'Visa Status Types' }}</div>
          </a>
        </li>
        @endcan
        @foreach($erpModuleMenu as $menuItem)
        @php
        $parentKey = $menuItem['key'];
        $children = $menuItem['children'] ?? [];
        $parentRoutePattern = 'settings-panel/module-settings/' . $parentKey;
        $anyChildActive = false;
        foreach ($children as $childKey) {
        if (Request::is('settings-panel/module-settings/' . $childKey) || ($childKey === 'rider-settings' && Request::is('settings-panel/rider-settings*'))) {
        $anyChildActive = true;
        break;
        }
        }
        $isOpen = Request::is($parentRoutePattern) || $anyChildActive;
        $isVisible = \App\Support\CompanyModuleVisibility::enabled($parentKey);
        @endphp
        @if($isVisible)
        @if(!empty($children))
        <li class="menu-item {{ $isOpen ? 'open' : '' }}">
          <a href="javascript:void(0);" class="menu-link menu-toggle">
            <i class="menu-icon tf-icons ti {{ $moduleIcons[$parentKey] ?? 'ti-adjustments-alt' }}"></i>
            <div>{{ $parentKey === 'riders' ? $settingsPanelRidersLabel : ($settingsPanelLabels[$parentKey] ?? config('menu_labels.defaults.' . $parentKey, ucwords(str_replace('_', ' ', $parentKey)))) }}</div>
          </a>
          <ul class="menu-sub">
            @foreach($children as $childKey)
            <li class="menu-item {{ Request::is('settings-panel/module-settings/' . $childKey) || ($childKey === 'rider-settings' && Request::is('settings-panel/rider-settings*')) ? 'active' : '' }}">
              <a href="{{ $childKey === 'rider-settings' ? route('settings-panel.rider-settings.index', ['company_slug' => $settingsCompanySlug]) : route('settings-panel.module-settings.index', ['company_slug' => $settingsCompanySlug, 'module' => $childKey]) }}" class="menu-link">
                <i class="menu-icon tf-icons ti {{ $moduleIcons[$childKey] ?? 'ti-adjustments-alt' }}"></i>
                <div>{{ $childKey === 'rider-settings' ? $settingsPanelRidersLabel : ($settingsPanelLabels[$childKey] ?? config('menu_labels.defaults.' . $childKey, ucwords(str_replace('_', ' ', $childKey)))) }}</div>
              </a>
            </li>
            @endforeach
          </ul>
        </li>
        @else
        <li class="menu-item {{ Request::is($parentRoutePattern) ? 'active' : '' }}">
          <a href="{{ route('settings-panel.module-settings.index', ['company_slug' => $settingsCompanySlug, 'module' => $parentKey]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti {{ $moduleIcons[$parentKey] ?? 'ti-adjustments-alt' }}"></i>
            <div>{{ $settingsPanelLabels[$parentKey] ?? config('menu_labels.defaults.' . $parentKey, ucwords(str_replace('_', ' ', $parentKey))) }}</div>
          </a>
        </li>
        @endif
        @endif
        @endforeach
      </ul>

      <div class="mt-auto border-top pt-3">
        <a href="{{ route('home', ['company_slug' => $settingsCompanySlug]) }}" target="_blank" class="menu-link d-flex align-items-center px-3 py-2 text-muted">
          <i class="ti ti-arrow-left me-2 ti-sm"></i>
          <span>Back to main app</span>
        </a>
      </div>
    </aside>

    <div class="layout-page">
      {{-- Minimal navbar for settings panel --}}
      <nav class="layout-navbar navbar navbar-expand-xl align-items-center bg-navbar-theme">
        <div class="container-fluid">
          <div class="navbar-brand app-brand demo d-flex align-items-center">
            <span class="app-brand-logo">
              <i class="ti ti-settings ti-lg text-primary"></i>
            </span>
            <span class="app-brand-text demo menu-text fw-semibold ms-2">Settings Panel</span>
          </div>
          <div class="navbar-nav-right d-flex align-items-center ms-auto">
            <a href="{{ route('home', ['company_slug' => $settingsCompanySlug]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
              <i class="ti ti-external-link me-1 ti-sm"></i>
              Open main app
            </a>
          </div>
        </div>
      </nav>

      <div class="content-wrapper">
        <div class="container-fluid flex-grow-1 container-p-y">
          @php
          $settingsPanelAlerts = [];
          if (session('success')) {
          $settingsPanelAlerts[] = ['icon' => 'success', 'title' => 'Success', 'text' => session('success')];
          }
          if (session('error')) {
          $settingsPanelAlerts[] = ['icon' => 'error', 'title' => 'Error', 'text' => session('error')];
          }
          if (session('warning')) {
          $settingsPanelAlerts[] = ['icon' => 'warning', 'title' => 'Warning', 'text' => session('warning')];
          }
          if (session('info')) {
          $settingsPanelAlerts[] = ['icon' => 'info', 'title' => 'Info', 'text' => session('info')];
          }
          if ($errors->any()) {
          $settingsPanelAlerts[] = ['icon' => 'error', 'title' => 'Validation Error', 'text' => $errors->first()];
          }
          @endphp
          @if(!empty($settingsPanelAlerts))
          <div id="settings-panel-alert-data" data-alerts='@json($settingsPanelAlerts)' hidden></div>
          <script>
            document.addEventListener('DOMContentLoaded', function() {
              var alerts = [];
              var alertDataEl = document.getElementById('settings-panel-alert-data');
              if (alertDataEl) {
                try {
                  alerts = JSON.parse(alertDataEl.getAttribute('data-alerts') || '[]');
                } catch (e) {
                  alerts = [];
                }
              }

              alerts.forEach(function(item) {
                if (typeof Swal !== 'undefined') {
                  Swal.fire({
                    icon: item.icon,
                    title: item.title,
                    text: item.text
                  });
                } else if (typeof toastr !== 'undefined') {
                  var fn = toastr[item.icon] || toastr.info;
                  fn(item.text);
                } else {
                  alert(item.text);
                }
              });
            });
          </script>
          @endif
          @yield('content')
        </div>
        <div class="content-backdrop fade"></div>
      </div>
    </div>
  </div>
</div>

<style>
  .settings-panel-sidebar {
    width: 260px;
    background: var(--bs-body-bg);
    border-right: 1px solid var(--bs-border-color);
  }

  [data-theme="dark"] .settings-panel-sidebar {
    border-right-color: rgba(255, 255, 255, 0.08);
  }
</style>
@endsection