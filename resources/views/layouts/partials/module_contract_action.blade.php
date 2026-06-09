@php
  /** @var string $module */
  /** @var int|string $recordId */
  /** @var string|null $recordLabel */
  $contractService = app(\App\Services\Agreements\AgreementModuleService::class);
  $showContract = $contractService->moduleHasContracts($module);
  $permissions = $contractService->permissionsFor($module);
  $companySlug = request()->route('company_slug');
  $modalParams = ['module' => $module, 'record' => $recordId];
  if (!empty($companySlug)) {
    $modalParams['company_slug'] = $companySlug;
  }
@endphp
@if($showContract)
@canany($permissions)
<a href="javascript:void(0);"
   data-action="{{ route('module-contracts.modal', $modalParams) }}"
   data-size="lg"
   data-title="{{ $recordLabel ?? 'Contracts' }}"
   class="dropdown-item waves-effect show-modal">
  <i class="ti ti-file-certificate me-1"></i> Contract
</a>
@endcanany
@endif
