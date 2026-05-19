@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
$settingsPanelLabels = \App\Models\Settings::getMenuLabels();
$settingsCompanySlug = request()->route('company_slug') ?? session('company_slug');
$settingsIsCompanyAdmin = auth()->check() && auth()->user()->isAdmin();
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
        <a href="{{ $settingsIsCompanyAdmin ? route('settings-panel.company', ['company_slug' => $settingsCompanySlug]) : route('settings-panel.profile', ['company_slug' => $settingsCompanySlug]) }}" class="app-brand-link">
          <span class="app-brand-logo">
            <i class="ti ti-settings ti-lg text-primary"></i>
          </span>
          <span class="app-brand-text demo menu-text fw-bold fs-6 ms-2">Settings</span>
        </a>
      </div>

      <div class="menu-inner-shadow"></div>

      <ul class="menu-inner py-3">
        @if($settingsIsCompanyAdmin)
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

        {{-- ERP Module Settings — mirrors main app sidebar (layouts/menu.blade.php) --}}
        <li class="menu-header small text-uppercase mt-3">
          <span class="menu-header-text">ERP Module Settings</span>
        </li>
        @include('settings.partials.erp_module_settings_nav')
        @else
        <li class="menu-header small text-uppercase">
          <span class="menu-header-text">{{ __('My account') }}</span>
        </li>
        <li class="menu-item {{ Request::routeIs('settings-panel.profile') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.profile', ['company_slug' => $settingsCompanySlug]) }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-user"></i>
            <div>{{ __('Profile') }}</div>
          </a>
        </li>
        @endif
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
          <div class="navbar-nav-right d-flex align-items-center ms-auto gap-2">
            <a href="{{ route('home', ['company_slug' => $settingsCompanySlug]) }}" target="_blank" class="btn btn-sm btn-outline-primary">
              <i class="ti ti-external-link me-1 ti-sm"></i>
              Open main app
            </a>
            @if($settingsCompanySlug)
            <form method="POST" action="{{ route('company.logout', ['company_slug' => $settingsCompanySlug]) }}" class="d-inline">
              @csrf
              <button type="submit" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-logout me-1 ti-sm"></i>
                Logout
              </button>
            </form>
            @endif
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