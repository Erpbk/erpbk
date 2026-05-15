@php
$fieldKey = $field->field_key;
$config = is_array($field->input_config) ? $field->input_config : [];
$spec = $field->resolvedInputSpec();
$inputType = $spec['type'] ?? 'text';
$assignGroup = $spec['assign_group'] ?? ($config['assign_group'] ?? null);
$isReadonly = !empty($spec['readonly']);
$label = $field->resolvedLabel();
$required = (bool) ($spec['required'] ?? false);
$colClass = in_array($inputType, ['textarea'], true) ? 'col-md-12' : 'col-md-3';
if ($fieldKey === 'notes' && $inputType === 'textarea') {
    $colClass = ($assignContext ?? 'active') === 'active' ? 'col-md-8' : 'col-md-8';
}
$groupClass = $assignGroup ? ' hidden-field assign-group-' . $assignGroup : '';
$wrapperId = $fieldKey ? 'assign-field-' . $fieldKey : 'assign-custom-' . ($field->custom_field_id ?? $field->id);
$name = $fieldKey ?: 'custom_field_values[' . ($field->custom_field_id ?? $field->id) . ']';
$inputName = ($fieldKey === 'notes') ? 'note' : $fieldKey;
@endphp

@if($field->kind === 'custom' && $field->customField)
@php
$cf = $field->customField;
$name = 'custom_field_values[' . $cf->id . ']';
$value = old('custom_field_values.' . $cf->id, $cf->default_value);
@endphp
<div class="{{ $colClass }} form-group {{ $groupClass }}" id="{{ $wrapperId }}" data-assign-field="custom">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @if($cf->help_text)
    <small class="text-muted d-block mb-1">{{ $cf->help_text }}</small>
    @endif
    @if($inputType === 'textarea')
    <textarea name="{{ $name }}" class="form-control" rows="{{ $cf->config['rows'] ?? 4 }}" @if($required) required @endif placeholder="{{ $cf->help_text }}">{{ $value }}</textarea>
    @elseif($inputType === 'select')
    @php $opts = $field->resolvedSelectOptions(); @endphp
    {!! Form::select($name, $opts, $value, ['class' => 'form-select select2', 'required' => $required]) !!}
    @elseif($inputType === 'date')
    <input type="date" name="{{ $name }}" class="form-control" value="{{ $value }}" @if($required) required @endif>
    @elseif($inputType === 'datetime')
    <input type="datetime-local" name="{{ $name }}" class="form-control" value="{{ $value }}" @if($required) required @endif>
    @elseif($inputType === 'number' || $inputType === 'decimal')
    <input type="number" name="{{ $name }}" class="form-control" value="{{ $value }}" step="{{ $inputType === 'decimal' ? '0.01' : '1' }}" @if($required) required @endif placeholder="{{ $cf->help_text }}">
    @elseif($inputType === 'checkbox')
    <div class="form-check mt-2">
        <input type="hidden" name="{{ $name }}" value="0">
        <input type="checkbox" name="{{ $name }}" value="1" class="form-check-input" @if(filter_var($value, FILTER_VALIDATE_BOOLEAN)) checked @endif @if($required) required @endif>
    </div>
    @else
    <input type="text" name="{{ $name }}" class="form-control" value="{{ $value }}" @if($required) required @endif @if($isReadonly) readonly @endif placeholder="{{ $cf->help_text }}">
    @endif
</div>
@elseif($field->usesAssignSpecialRenderer())
@include('bikes._assign_modal_field_special')
@else
<div class="{{ $colClass }} form-group {{ $groupClass }}" id="{{ $wrapperId }}" data-assign-field="{{ $fieldKey }}">
    <label for="assign_{{ $fieldKey }}">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @if($inputType === 'select')
    @php $opts = $field->resolvedSelectOptions(); @endphp
    {!! Form::select($fieldKey, $opts, '', ['class' => 'form-select select2', 'id' => 'assign_' . $fieldKey, 'required' => $required]) !!}
    @elseif($inputType === 'textarea')
    <textarea class="form-control" name="{{ $inputName }}" id="assign_{{ $fieldKey }}" rows="3" placeholder="{{ $label }}" @if($required) required @endif @if($isReadonly) readonly @endif></textarea>
    @elseif($inputType === 'date')
    <input type="date" name="{{ $fieldKey }}" id="assign_{{ $fieldKey }}" class="form-control" @if($required) required @endif @if($isReadonly) readonly @endif>
    @elseif($inputType === 'datetime')
    <input type="datetime-local" name="{{ $fieldKey }}" id="assign_{{ $fieldKey }}" class="form-control" @if($required) required @endif @if($isReadonly) readonly @endif>
    @elseif($inputType === 'checkbox')
    <div class="form-check mt-2">
        <input type="hidden" name="{{ $fieldKey }}" value="0">
        <input type="checkbox" name="{{ $fieldKey }}" value="1" class="form-check-input" id="assign_{{ $fieldKey }}" @if($required) required @endif>
    </div>
    @else
    <input type="{{ in_array($inputType, ['number', 'decimal', 'email', 'url'], true) ? $inputType : 'text' }}" name="{{ $fieldKey }}" id="assign_{{ $fieldKey }}" class="form-control" @if($required) required @endif @if($isReadonly) readonly @endif>
    @endif
</div>
@endif
