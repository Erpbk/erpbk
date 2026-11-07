@extends($layout ?? 'layouts.app')
@section('title', 'Company Details')

@push('page-styles')
<link rel="stylesheet" href="{{ asset('assets/css/company-details-settings.css') }}?v={{ @filemtime(public_path('assets/css/company-details-settings.css')) }}">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Public+Sans:wght@400;500;600;700&family=IBM+Plex+Mono:wght@400;500&display=swap">
<style>
  .company-details-root .cd-wrap { max-width: 100%; margin: 0; padding: 0 0 28px; }
  .company-details-root .proto,
  .company-details-root details.dev { display: none !important; }
</style>
@endpush

@section('content')
@include('flash::message')

@php
  $slug = request()->route('company_slug') ?? session('company_slug');
  $isPanel = (bool) (View::shared('settings_panel') ?? false);
  $routePrefix = $isPanel ? 'settings-panel.company' : 'settings.company';
  $config = [
    'company' => $bootstrap['company'] ?? null,
    'entities' => $bootstrap['entities'] ?? [],
    'field_orders' => $bootstrap['field_orders'] ?? [],
    'is_admin' => (bool) ($bootstrap['is_admin'] ?? false),
    'can_edit' => (bool) ($bootstrap['can_edit'] ?? false),
    'admin_sections' => $bootstrap['admin_sections'] ?? [],
    'warn_sections' => $bootstrap['warn_sections'] ?? [],
    'theme' => $bootstrap['theme'] ?? [],
    'users' => $bootstrap['users'] ?? [],
    'user_stamps' => $bootstrap['user_stamps'] ?? new \stdClass(),
    'current_user_id' => $bootstrap['current_user_id'] ?? null,
    'departments' => $bootstrap['departments'] ?? [],
    'csrf' => csrf_token(),
    'storage_base' => rtrim(str_replace('\\', '/', request()->getBasePath()), '/') . '/storage/',
    'routes' => [
      'storeEntity' => route($routePrefix . '.entities.store', ['company_slug' => $slug]),
      'saveEntity' => route($routePrefix . '.entities.save', ['company_slug' => $slug, 'profile' => '__ID__']),
      'destroyEntity' => route($routePrefix . '.entities.destroy', ['company_slug' => $slug, 'profile' => '__ID__']),
      'uploadAsset' => route($routePrefix . '.entities.asset', ['company_slug' => $slug, 'profile' => '__ID__']),
      'uploadDocument' => route($routePrefix . '.entities.document', ['company_slug' => $slug, 'profile' => '__ID__']),
      'fieldOrders' => route($routePrefix . '.field-orders', ['company_slug' => $slug]),
      'saveUserStamp' => route($routePrefix . '.user-stamps.save', ['company_slug' => $slug, 'user' => '__UID__']),
      'uploadUserStamp' => route($routePrefix . '.user-stamps.asset', ['company_slug' => $slug, 'user' => '__UID__']),
      'storeDepartment' => route($routePrefix . '.departments.store', ['company_slug' => $slug]),
      'updateDepartment' => route($routePrefix . '.departments.update', ['company_slug' => $slug, 'department' => '__ID__']),
      'destroyDepartment' => route($routePrefix . '.departments.destroy', ['company_slug' => $slug, 'department' => '__ID__']),
    ],
  ];
@endphp

<div class="company-details-root" id="companyDetailsRoot">
  <div class="cd-wrap">
    <header class="cd-top" id="top">
      <div class="logo" id="hLogo"><div class="wordmark">ERP<small>COMPANY</small></div></div>
      <div class="meta">
        <div class="eyebrow" id="hEyebrow">Company Profile</div>
        <button class="sw" id="sw" type="button" aria-haspopup="true" aria-expanded="false">
          <h1 id="hName"></h1>
          <span class="tag ho" id="hTag">Head Office</span>
          <span class="caret">▾</span>
        </button>
        <div class="chips" id="hChips"></div>
      </div>
      <div class="comp">
        <div class="h"><span>Profile complete</span><b id="cPct">0%</b></div>
        <div class="bar"><i id="cBar"></i></div>
        <small id="cTxt"></small>
      </div>
      <div class="cd-switcher-menu" id="menu" hidden></div>
    </header>
    <div id="banner"></div>

    <div class="cd-body">
      <nav class="cd-sec-nav" id="nav" aria-label="Settings sections"></nav>
      <div class="cd-main" style="min-width:0">
        <section class="panel" id="panel"></section>
        <div class="savebar">
          <small id="saveNote">One button saves every section.</small>
          <button class="cd-btn" id="save" type="button">Save Settings</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-scripts')
<script>
  document.body.classList.add('settings-panel-company-details');
  window.CompanyDetailsConfig = @json($config);
</script>
<script src="{{ asset('assets/js/company-details-theme.js') }}?v={{ @filemtime(public_path('assets/js/company-details-theme.js')) }}"></script>
<script src="{{ asset('assets/js/company-details-settings.js') }}?v={{ @filemtime(public_path('assets/js/company-details-settings.js')) }}"></script>
@endpush
