@isset($pageConfigs)
{!! Helper::updatePageConfig($pageConfigs) !!}
@endisset
@php
$configData = Helper::appClasses();
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
        <a href="{{ route('settings-panel.company') }}" class="app-brand-link">
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

        @canany(['gn_settings','department_view','dropdown_view','visaexpense_view'])
        <li class="menu-item {{ Request::is('settings-panel/company') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.company') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-building-community"></i>
            <div>Company Details</div>
          </a>
        </li>
        @can('gn_settings')
        <li class="menu-item {{ Request::is('settings-panel/erp') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.erp') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-adjustments"></i>
            <div>Settings</div>
          </a>
        </li>
        @endcan
        @can('department_view')
        <li class="menu-item {{ Request::is('settings-panel/departments*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.departments.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-sitemap"></i>
            <div>Departments</div>
          </a>
        </li>
        @endcan
        @can('dropdown_view')
        <li class="menu-item {{ Request::is('settings-panel/dropdowns*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.dropdowns.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-list"></i>
            <div>Dropdown Management</div>
          </a>
        </li>
        @endcan
        @can('visaexpense_view')
        <li class="menu-item {{ Request::is('settings-panel/visa-statuses*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.visa-statuses.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-list-check"></i>
            <div>Visa Status Types</div>
          </a>
        </li>
        @endcan
        @can('gn_settings')
        <li class="menu-item {{ Request::is('settings-panel/account-fields*') ? 'active' : '' }}">
          <a href="{{ route('settings-panel.account-fields.index') }}" class="menu-link">
            <i class="menu-icon tf-icons ti ti-wallet"></i>
            <div>Accounts</div>
          </a>
        </li>
        @endcan
        @endcanany

        {{-- ERP Modules list (names only; functionality to be added later) --}}
        <li class="menu-header small text-uppercase mt-3">
          <span class="menu-header-text">ERP Modules</span>
        </li>
        @foreach(config('erp_modules.modules', []) as $moduleName)
        <li class="menu-item">
          <span class="menu-link text-muted" style="cursor: default; pointer-events: none;">
            <span class="menu-icon tf-icons ti ti-circle-dotted ti-sm opacity-50"></span>
            <div>{{ $moduleName }}</div>
          </span>
        </li>
        @endforeach
      </ul>

      <div class="mt-auto border-top pt-3">
        <a href="{{ route('home') }}" target="_blank" class="menu-link d-flex align-items-center px-3 py-2 text-muted">
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
            <a href="{{ route('home') }}" target="_blank" class="btn btn-sm btn-outline-primary">
              <i class="ti ti-external-link me-1 ti-sm"></i>
              Open main app
            </a>
          </div>
        </div>
      </nav>

      <div class="content-wrapper">
        <div class="container-fluid flex-grow-1 container-p-y">
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
    border-right-color: rgba(255,255,255,0.08);
  }
</style>
@endsection
