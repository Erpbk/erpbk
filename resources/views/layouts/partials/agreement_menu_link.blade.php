@php
  $moduleKey = $module ?? null;
  $configured = array_key_exists($moduleKey, config('agreement_modules.modules', []));
  $hasAgreements = $configured && in_array($moduleKey, $agreementMenuModules ?? [], true);
  $companySlug = $menuCompanySlug ?? request()->route('company_slug');
  $menuPermissions = config("agreement_modules.modules.{$moduleKey}.permissions", ['agreement_view']);
@endphp
@if($hasAgreements)
@canany($menuPermissions)
<li class="menu-item {{ Route::is('module-agreements.*') && request()->route('module') === $moduleKey ? 'active' : '' }}">
  <a href="{{ route('module-agreements.index', ['company_slug' => $companySlug, 'module' => $moduleKey]) }}" class="menu-link">
    <i class="menu-icon tf-icons ti ti-file-certificate"></i>
    <div>Agreements</div>
  </a>
</li>
@endcanany
@endif
