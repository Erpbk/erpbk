@php
  $moduleKey = $module ?? null;
  $configured = array_key_exists($moduleKey, config('agreement_modules.modules', []));
  $hasAgreements = $configured && in_array($moduleKey, $agreementMenuModules ?? [], true);
  $companySlug = $menuCompanySlug ?? \App\Support\CompanyRouteContext::slug() ?? request()->route('company_slug');
  $menuPermissions = config("agreement_modules.modules.{$moduleKey}.permissions", ['agreement_view']);
  $routeParams = ['module' => $moduleKey];
  if (!empty($companySlug)) {
    $routeParams['company_slug'] = $companySlug;
  }
  $agreementUrl = $hasAgreements && $moduleKey
    ? route('module-agreements.index', $routeParams)
    : null;
@endphp
@if($hasAgreements && $agreementUrl)
@canany($menuPermissions)
<li class="menu-item {{ Route::is('module-agreements.*') && request()->route('module') === $moduleKey ? 'active' : '' }}">
  <a href="{{ $agreementUrl }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-file-certificate"></i>
    <div>Agreements</div>
  </a>
</li>
@endcanany
@endif
