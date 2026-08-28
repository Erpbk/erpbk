@php
  /** @var string $module */
  /** @var int|string $recordId */
  /** @var string|null $recordLabel */
  $contractService = app(\App\Services\Agreements\AgreementModuleService::class);
  $agreementItems = $contractService->actionMenuItemsForModule($module);
  $permissions = $contractService->permissionsFor($module);
@endphp
@if($agreementItems !== [])
@canany($permissions)
@foreach($agreementItems as $agreementItem)
@php
  $href = $agreementItem['record_preview_pattern']
    ? str_replace('__RECORD__', (string) $recordId, $agreementItem['record_preview_pattern'])
    : ($agreementItem['preview_url'] ?? $agreementItem['show_url']);
@endphp
@if($href)
<a href="{{ $href }}" target="_blank" rel="noopener"
   data-agreement-action="1"
   class="dropdown-item waves-effect">
  <i class="ti ti-file-certificate me-1"></i> {{ $agreementItem['name'] }}
</a>
@endif
@endforeach
@endcanany
@endif
