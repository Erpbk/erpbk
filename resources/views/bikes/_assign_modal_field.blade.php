@php
$fieldKey = $field->field_key;
$config = is_array($field->input_config) ? $field->input_config : [];
$assignGroup = $config['assign_group'] ?? null;
$isReadonly = !empty($config['readonly']);
$label = $field->resolvedLabel();
$required = $field->is_required ?? false;
$colClass = ($field->input_type ?? '') === 'textarea' ? 'col-md-12' : 'col-md-3';
$groupClass = $assignGroup ? ' hidden-field assign-group-' . $assignGroup : '';
$wrapperId = $fieldKey ? 'assign-field-' . $fieldKey : 'assign-custom-' . ($field->custom_field_id ?? $field->id);
@endphp

@if($field->kind === 'custom' && $field->customField)
@php
$cf = $field->customField;
$name = 'custom_field_values[' . $cf->id . ']';
$value = old('custom_field_values.' . $cf->id, $cf->default_value);
@endphp
<div class="{{ $colClass }} form-group {{ $groupClass }}" id="{{ $wrapperId }}" data-assign-field="custom">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @if($cf->data_type === 'textarea')
    <textarea name="{{ $name }}" class="form-control" rows="4" @if($required) required @endif placeholder="{{ $cf->help_text }}">{{ $value }}</textarea>
    @elseif($cf->data_type === 'dropdown')
    @php
    $opts = ['' => 'Select'];
    $lines = $cf->config['options'] ?? '';
    if (is_string($lines)) {
        foreach (preg_split('/\r\n|\r|\n/', $lines) as $line) {
            $line = trim($line);
            if ($line !== '') { $opts[$line] = $line; }
        }
    }
    @endphp
    {!! Form::select($name, $opts, $value, ['class' => 'form-select select2', 'required' => $required]) !!}
    @elseif($cf->data_type === 'date')
    <input type="date" name="{{ $name }}" class="form-control" value="{{ $value }}" @if($required) required @endif>
    @else
    <input type="text" name="{{ $name }}" class="form-control" value="{{ $value }}" @if($required) required @endif placeholder="{{ $cf->help_text }}">
    @endif
    @if($cf->help_text)
    <small class="text-muted">{{ $cf->help_text }}</small>
    @endif
</div>
@elseif($fieldKey === 'warehouse' && ($assignContext ?? 'active') === 'active')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="warehouse">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <input type="text" name="warehouse" class="form-control" readonly value="Active">
</div>
@elseif($fieldKey === 'warehouse' && ($assignContext ?? '') === 'change')
@php
$bikeModel = $bike ?? null;
$lockedWarehouse = $bikeModel && in_array((string) $bikeModel->warehouse, ['Absconded', 'Theft'], true);
@endphp
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="warehouse">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @if($lockedWarehouse)
    <input type="text" class="form-control" name="warehouse" value="Return" readonly>
    @else
    <select class="form-control warehouse form-select select2" name="warehouse" id="warehouse">
        {!! App\Helpers\General::get_warehouse(1) !!}
    </select>
    @endif
</div>
@elseif($fieldKey === 'assign_type')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="assign_type">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <select name="assign_type" id="assign_type" class="form-select select2" @if($required) required @endif>
        <option value="">Select Type</option>
        <option value="rider">Rider</option>
        <option value="company">Company</option>
    </select>
</div>
@elseif($fieldKey === 'rider_id')
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="rider_select" data-assign-field="rider_id">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    {!! Form::select('rider_id', \App\Models\Riders::dropdown(), '', ['class' => 'form-select select2', 'id' => 'rider_id']) !!}
</div>
@elseif($fieldKey === 'rental_company_id')
<div class="{{ $colClass }} form-group hidden-field assign-group-company" id="company_select" data-assign-field="rental_company_id">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    {!! Form::select('rental_company_id', \App\Models\BikeRentCompany::pluck('name', 'id'), '', ['class' => 'form-select select2', 'id' => 'company_id']) !!}
</div>
@elseif($fieldKey === 'designation')
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="designation_field" data-assign-field="designation">
    <label>{{ $label }}</label>
    <input type="text" name="designation" id="designation" class="form-control" readonly value="{{ $selectedDesignation ?? '' }}">
</div>
@elseif($fieldKey === 'customer_id')
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="project_field" data-assign-field="customer_id">
    {!! Form::label('customer_id', $label . ($required ? ':' : '')) !!}
    {!! Form::select('customer_id', \App\Models\Customers::dropdown(), '', ['class' => 'form-select select2', 'id' => 'customer_id']) !!}
</div>
@elseif($fieldKey === 'note_date')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="note_date">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <input type="date" name="note_date" class="form-control" @if($required) required @endif>
</div>
@elseif($fieldKey === 'return_date')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="return_date">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <input type="date" name="return_date" class="form-control" placeholder="Return Date" @if($required) required @endif>
</div>
@elseif($fieldKey === 'visa_sponsor' && ($assignContext ?? '') === 'change')
@if(!empty($rider))
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="visa_sponsor">
    <label>{{ $label }}</label>
    <input type="text" name="visa_sponsor" class="form-control" readonly value="{{ $rider->visa_sponsor ?? 'N/A' }}">
</div>
@endif
@elseif($fieldKey === 'notes')
<div class="{{ ($assignContext ?? 'active') === 'active' ? 'col-md-8' : 'col-md-8' }} form-group mt-3" id="{{ $wrapperId }}" data-assign-field="notes">
    <textarea class="form-control" placeholder="Note....." name="notes" rows="3" @if($required) required @endif></textarea>
</div>
@endif
