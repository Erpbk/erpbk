@php
$spec = [];
$value = null;

if ($item->kind === 'fixed') {
$fieldKey = (string) $item->field_key;
$spec = $item->spec ?? [];

switch ($fieldKey) {
case 'branch_id':
$value = $bikes->branch ? $bikes->branch->name . ' ( ' . ($bikes->branch->code ?? '') . ' )' : null;
break;
case 'customer_id':
$value = $bikes->customer->name ?? null;
break;
case 'company':
$value = $bikes->leasingCompany->name ?? null;
break;
case 'rider_id':
$value = $bikes->rider->name ?? null;
break;
default:
$value = $bikes->{$fieldKey} ?? null;
break;
}
} else {
// Custom fields are stored in bikes.custom_field_values.
$cfValues = is_array($bikes->custom_field_values ?? null) ? $bikes->custom_field_values : [];
$value = $cfValues[$item->field->id] ?? $item->field->default_value ?? null;
$spec = [];
}

$displayValue = $value;
if ($value === null || $value === '') {
$displayValue = '—';
} else {
if ($item->kind === 'fixed' && in_array(($spec['type'] ?? ''), ['date', 'datetime'], true)) {
try {
$displayValue = \App\Helpers\General::DateFormat($value);
} catch (\Throwable $e) {
$displayValue = $value;
}
} elseif ($item->kind === 'custom' && in_array(($item->field->data_type ?? ''), ['date', 'datetime'], true)) {
try {
$displayValue = \App\Helpers\General::DateFormat($value);
} catch (\Throwable $e) {
$displayValue = $value;
}
} elseif ($item->kind === 'fixed' && ($spec['type'] ?? '') === 'checkbox') {
$displayValue = ($value == 1 || $value === true) ? 'Yes' : 'No';
} elseif ($item->kind === 'custom' && ($item->field->data_type ?? '') === 'checkbox') {
$displayValue = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'Yes' : 'No';
}
}
@endphp

<div class="{{ !empty($fullWidth) ? 'col-md-12 col-12' : 'col-md-3 col-3' }} form-group">
    <label><b>{{ $item->kind === 'fixed' ? $item->label : $item->field->label }}</b></label>
    <p>{{ $displayValue }}</p>
</div>