@php
$value = null;
$spec = [];
if (($item->kind ?? '') === 'fixed') {
    if ($item->field_key === 'branch_id') {
        $value = $employee->branch ? $employee->branch->name . ' (' . $employee->branch->code . ')' : null;
    } elseif ($item->field_key === 'department_id') {
        $value = $employee->department?->name;
    } elseif ($item->field_key === 'nationality_id') {
        $value = $employee->nationality?->name;
    } else {
        $value = $employee->{$item->field_key} ?? null;
    }
    $spec = $item->spec ?? [];
} else {
    $cfValues = is_array($employee->custom_field_values ?? null) ? $employee->custom_field_values : [];
    $value = $cfValues[$item->field->id] ?? $item->field->default_value ?? null;
}

$displayValue = $value;
$isDate = (($item->kind ?? '') === 'fixed' && in_array($spec['type'] ?? '', ['date', 'datetime'], true))
    || (($item->kind ?? '') === 'custom' && in_array($item->field->data_type ?? '', ['date', 'datetime'], true));
$fieldKey = (string) (($item->kind ?? '') === 'fixed' ? ($item->field_key ?? '') : '');
$isExpiry = \App\Support\EntityExpiry::isExpiryKey($fieldKey)
    || ((($item->kind ?? '') === 'custom') && \App\Support\EntityExpiry::isExpiryKey((string) ($item->field->label ?? '')));

if ($value !== null && $value !== '' && $isDate) {
    try {
        $displayValue = \App\Helpers\General::DateFormat($value);
    } catch (\Throwable $e) {
        $displayValue = $value;
    }
} elseif ($value === null || $value === '') {
    $displayValue = '—';
}

$rfpField = ($item->kind ?? '') === 'fixed' ? $item->field_key : ('cf_' . $item->field->id);
$label = ($item->kind ?? '') === 'fixed' ? $item->label : $item->field->label;
$expiryBadge = $isExpiry ? \App\Support\EntityExpiry::badgeForDate($value, $label) : null;
@endphp
@if (field_visible('employees', (string) $rfpField))
<div class="col-md-3 form-group col-3 mb-3 entity-info-field">
    <label>{{ $label }}</label>
    <p class="mb-0">
        @if ($expiryBadge)
            @include('riders._document_expiry_badge', ['badge' => $expiryBadge])
        @else
            {{ $displayValue }}
        @endif
    </p>
</div>
@endif
