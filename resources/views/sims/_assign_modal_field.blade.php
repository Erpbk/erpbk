@php
$fieldKey = $field->field_key;
$config = is_array($field->input_config) ? $field->input_config : [];
$spec = $field->resolvedInputSpec();
$inputType = $spec['type'] ?? 'text';
$assignGroup = $spec['assign_group'] ?? ($config['assign_group'] ?? null);
$isReadonly = !empty($spec['readonly']);
$label = $field->resolvedLabel();
$required = (bool) ($spec['required'] ?? false);
$colClass = in_array($inputType, ['textarea'], true) ? 'col-md-12' : 'col-sm-6';
if ($fieldKey === 'notes' && $inputType === 'textarea') {
    $colClass = 'col-md-8';
}
$groupClass = $assignGroup ? ' d-none assignee-field assignee-field-' . $assignGroup : '';
$wrapperId = $fieldKey ? 'assign-field-' . $fieldKey : 'assign-custom-' . ($field->custom_field_id ?? $field->id);
$branchOpts = $branchScopedOptions ?? [];
$simsModel = $sims ?? null;
$assignTargets = $assignTargets ?? \App\Support\CompanyModuleVisibility::simAssignTargets();
$allowTypeSelection = $allowTypeSelection ?? (count($assignTargets) >= 2);
$defaultAssigneeType = $defaultAssigneeType ?? (count($assignTargets) === 1 ? $assignTargets[0] : 'rider');
@endphp

@if($field->kind === 'custom' && $field->customField)
@php
$cf = $field->customField;
$name = 'custom_field_values[' . $cf->id . ']';
$value = old('custom_field_values.' . $cf->id, $cf->default_value);
@endphp
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="custom">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @if($cf->help_text)
    <small class="text-muted d-block mb-1">{{ $cf->help_text }}</small>
    @endif
    @if($inputType === 'textarea')
    <textarea name="{{ $name }}" class="form-control" rows="{{ $cf->config['rows'] ?? 4 }}" @if($required) required @endif placeholder="{{ $cf->help_text }}">{{ $value }}</textarea>
    @elseif($inputType === 'select')
    @php $opts = $field->resolvedSelectOptions($branchOpts); @endphp
    {!! Form::select($name, $opts, $value, ['class' => 'form-select select2', 'required' => $required]) !!}
    @elseif($inputType === 'date')
    <input type="date" name="{{ $name }}" class="form-control" value="{{ $value }}" @if($required) required @endif>
    @else
    <input type="text" name="{{ $name }}" class="form-control" value="{{ $value }}" @if($required) required @endif @if($isReadonly) readonly @endif>
    @endif
</div>
@elseif($field->usesAssignSpecialRenderer())
@include('sims._assign_modal_field_special', [
    'field' => $field,
    'assignContext' => $assignContext ?? 'assign',
    'sims' => $simsModel,
    'branchScopedOptions' => $branchOpts,
    'assignee_name' => $assignee_name ?? null,
    'assignTargets' => $assignTargets ?? null,
    'allowTypeSelection' => $allowTypeSelection ?? null,
    'defaultAssigneeType' => $defaultAssigneeType ?? null,
])
@else
<div class="{{ $colClass }} form-group{{ $groupClass }}" id="{{ $wrapperId }}" data-assign-field="{{ $fieldKey }}">
    <label for="assign_{{ $fieldKey }}">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @php
    $defaultVal = match ($fieldKey) {
        'note_date' => old('note_date', date('Y-m-d')),
        'return_date' => old('return_date', date('Y-m-d')),
        'number' => old('number', $simsModel->number ?? ''),
        default => old($fieldKey),
    };
    @endphp
    @if($inputType === 'select')
    @php $opts = $field->resolvedSelectOptions($branchOpts); @endphp
    {!! Form::select($fieldKey, $opts, $defaultVal, ['class' => 'form-select select2', 'id' => 'assign_' . $fieldKey, 'required' => $required]) !!}
    @elseif($inputType === 'textarea')
    <textarea class="form-control" name="{{ $fieldKey }}" id="assign_{{ $fieldKey }}" rows="3" placeholder="{{ $label }}" @if($required) required @endif @if($isReadonly) readonly @endif>{{ $defaultVal }}</textarea>
    @elseif($inputType === 'date')
    <input type="date" name="{{ $fieldKey }}" id="assign_{{ $fieldKey }}" class="form-control" value="{{ $defaultVal }}" @if($required) required @endif @if($isReadonly) readonly @endif>
    @else
    <input type="text" name="{{ $fieldKey }}" id="assign_{{ $fieldKey }}" class="form-control" value="{{ $defaultVal }}" @if($required) required @endif @if($isReadonly) readonly @endif>
    @endif
</div>
@endif
