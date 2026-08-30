@php
  /** @var string $module */
  /** @var int|string $recordId */
  $variant = $variant ?? 'dropdown';
  $agreementItems = [];
  try {
    $agreementItems = app(\App\Services\Agreements\AgreementModuleService::class)->actionMenuItemsForModule($module);
  } catch (\Throwable) {
    $agreementItems = [];
  }
  $pattern = $agreementItems[0]['record_preview_pattern'] ?? $agreementItems[0]['index_url'] ?? null;
  $href = $pattern ? str_replace('__RECORD__', (string) ($recordId ?? ''), $pattern) : null;
@endphp
@if($href && ($recordId ?? '') !== '')
@if($variant === 'btn-group')
<a href="{{ $href }}" class="btn btn-default btn-sm" title="Agreements" data-agreement-action="1">
  <i class="ti ti-file-certificate"></i>
</a>
@else
<a href="{{ $href }}" data-agreement-action="1" class="dropdown-item waves-effect">
  <i class="ti ti-file-certificate me-1"></i> Agreements
</a>
@endif
@endif
