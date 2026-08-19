@php
$spec = [];
if ($item->kind === 'fixed') {
if($item->field_key === 'branch_id') {
$value = $rider->branch ? trim($rider->branch->name . ($rider->branch->code ? ' (' . $rider->branch->code . ')' : '')) : null;
} else {
$value = $rider->{$item->field_key} ?? null;
}
$spec = $item->spec ?? [];
} else {
$cfValues = is_array($rider->custom_field_values ?? null) ? $rider->custom_field_values : [];
$value = $cfValues[$item->field->id] ?? $item->field->default_value ?? null;
}
$displayValue = $value;
if ($value !== null && $value !== '') {
if ($item->kind === 'fixed' && in_array($spec['type'] ?? '', ['date', 'datetime'], true)) {
try {
$displayValue = \App\Helpers\General::DateFormat($value);
} catch (\Throwable $e) {
$displayValue = $value;
}
} elseif ($item->kind === 'custom') {
if (in_array($item->field->data_type ?? '', ['date', 'datetime'], true)) {
try {
$displayValue = \App\Helpers\General::DateFormat($value);
} catch (\Throwable $e) {
$displayValue = $value;
}
} elseif (($item->field->data_type ?? '') === 'checkbox') {
$displayValue = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
}
} elseif ($item->kind === 'fixed' && ($spec['type'] ?? '') === 'checkbox') {
$displayValue = ($value == 1 || $value === true) ? 'Yes' : 'No';
}
} else {
$displayValue = '—';
}
if ($item->kind === 'fixed' && ($spec['type'] ?? '') === 'select' && $value !== null && $value !== '') {
$dropdown = $spec['dropdown'] ?? '';
if ($dropdown === 'countries' && $rider->country) {
$displayValue = $rider->country->name;
} elseif ($dropdown === 'vendors' && $rider->vendor) {
$displayValue = $rider->vendor->name;
} elseif ($dropdown === 'recruiters' && $rider->recruiter) {
$displayValue = $rider->recruiter->name;
}
}
$rfpField = $item->kind === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
$docRider = $rider ?? $riders ?? null;
$expiryBadge = ($item->kind === 'fixed' && $docRider instanceof \App\Models\Riders)
  ? \App\Support\RiderDocumentReplacement::expiryBadgeForField($docRider, (string) ($item->field_key ?? ''), $value)
  : null;
@endphp
@if (field_visible('rider', (string) $rfpField))
<div class="col-md-3 form-group col-3 rider-info-field">
  <label>{{ $item->kind === 'fixed' ? $item->label : $item->field->label }}</label>
  <p class="mb-0">
    @if ($expiryBadge)
      @include('riders._document_expiry_badge', ['badge' => $expiryBadge])
    @else
      <span>{{ $displayValue }}</span>
    @endif
  </p>
</div>
@endif