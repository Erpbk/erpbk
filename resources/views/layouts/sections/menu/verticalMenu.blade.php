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
    <a href="{{ $homeLink }}" class="app-brand-link">
      <span class="app-brand-logo ">
        @if($tenantLogo)
          <img src="{{ $tenantLogo }}" width="50" height="50" alt="{{ $tenantName }}" style="object-fit:contain;" />
        @else
          @include('_partials.macros', ['height' => 22])
        @endif
      </span>
      <span class="app-brand-text demo menu-text fw-bold fs-5">{{ $tenantName }}</span>
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
