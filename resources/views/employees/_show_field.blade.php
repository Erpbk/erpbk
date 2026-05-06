@php
$value = null;
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
} else {
    $cfValues = is_array($employee->custom_field_values ?? null) ? $employee->custom_field_values : [];
    $value = $cfValues[$item->field->id] ?? $item->field->default_value ?? null;
}
$displayValue = ($value === null || $value === '') ? '—' : $value;
@endphp
<div class="col-md-3 form-group col-3 mb-3">
    <label><b>{{ ($item->kind ?? '') === 'fixed' ? $item->label : $item->field->label }}</b></label>
    <p>{{ $displayValue }}</p>
</div>
