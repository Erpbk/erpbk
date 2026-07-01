@php
$configData = Helper::appClasses();
$tenantCompany = view()->shared('currentCompany');
if (!$tenantCompany instanceof \App\Models\Company) {
$companyId = \App\Support\CompanyContext::id();
if ($companyId) {
$tenantCompany = \App\Models\Company::query()->find($companyId);
}
}
$tenantLogo = $companyLogoUrl ?? null;
if (!$tenantLogo && $tenantCompany) {
$tenantLogo = app(\App\Services\Email\CompanyEmailBrandingService::class)
->resolve($tenantCompany->id)['logo_url'] ?? null;
}
$tenantName = $companyDisplayName ?? ($tenantCompany?->name ?? config('app.name'));
$adminUser = auth('admin')->user();
$companySlug = request()->route('company_slug') ?? session('company_slug');
$homeLink = $adminUser
? route('admin.dashboard')
: ($companySlug ? route('home', ['company_slug' => $companySlug]) : url('/'));
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  @if(!isset($navbarFull))
  <div class="app-brand demo">
    <a href="{{ $homeLink }}" class="app-brand-link flex-grow-1 justify-content-center pe-1">
      <span class="app-brand-logo demo d-flex align-items-center justify-content-center" style="width:auto;height:auto;max-width:9.5rem;">
        @if($tenantLogo)
        <img src="{{ $tenantLogo }}" alt="{{ $tenantName }}" style="max-height:60px;max-width:100%;width:auto;height:auto;object-fit:contain;display:block;" />
        @else
        @include('_partials.macros', ['height' => 32])
        @endif
      </span>
    </a>

    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
      <i class="ti menu-toggle-icon d-none d-xl-block ti-sm align-middle"></i>
      <i class="ti ti-x d-block d-xl-none ti-sm align-middle"></i>
    </a>
  </div>
  @endif


  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">

    @include('layouts.menu')
  </ul>

</aside>