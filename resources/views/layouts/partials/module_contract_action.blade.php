@php
  /** @var string $module */
  /** @var int|string $recordId */
  /** @var string|null $recordLabel */
  $variant = $variant ?? 'dropdown';
  $agreementItems = [];
  try {
    $contractService = app(\App\Services\Agreements\AgreementModuleService::class);
    $agreementItems = $contractService->actionMenuItemsForModule($module);
  } catch (\Throwable) {
    $agreementItems = [];
  }
@endphp
@if($agreementItems !== [])
@if($variant === 'btn-group')
<div class="dropdown d-inline-block">
  <button type="button" class="btn btn-default btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" title="Agreements">
    <i class="ti ti-file-certificate"></i>
  </button>
  <div class="dropdown-menu">
@endif
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
@if($variant === 'btn-group')
  </div>
</div>
@endif
@endif
