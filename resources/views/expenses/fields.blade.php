<script src="{{ asset('js/modal_custom.js') }}"></script>

<!-- Account Type: fixed to Expense for expense module -->
<div class="form-group col-sm-6">
  {!! Form::label('account_type', 'Account Type:') !!}
  {!! Form::text('account_type', isset($accounts) ? $accounts->account_type : 'Expense', ['class' => 'form-control', 'readonly' => 'readonly']) !!}
  <input type="hidden" name="account_type" value="Expense" />
</div>

<!-- Account Name Field -->
<div class="form-group col-sm-6">
  {!! Form::label('name', 'Account Name:') !!}
  {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 100]) !!}
</div>

<!-- Account Code Field (edit only) -->
@if(isset($accounts) && $accounts->id)
<div class="form-group col-sm-6">
  {!! Form::label('account_code', 'Account Code:') !!}
  {!! Form::text('account_code', $accounts->account_code, ['class' => 'form-control']) !!}
</div>
@endif

{{-- Parent Account: only Expense-related accounts (roots and children from Expense tree); main parents in bold --}}
<div class="form-group col-sm-6">
  {!! Form::label('parent_id', 'Parent Account:') !!}
  <input type="hidden" value="{{ $parent->id }}" name="parent_id">
  {!! Form::text('parent', $parent->name , ['class' => 'form-control', 'readonly']) !!}
</div>

<!-- Opening Balance Field -->
<div class="form-group col-sm-6">
  {!! Form::label('opening_balance', 'Opening Balance:') !!}
  {!! Form::number('opening_balance', null, ['class' => 'form-control', 'step' => 'any']) !!}
</div>

<!-- Branch Field -->
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Company Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2']) !!}
</div>
<div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in  Branch list if this account will be used by all or multiple company branches</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2" />
    <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($accounts) @if($accounts->status == 1) checked @endif @else checked @endisset/>
    <label for="status" class="pt-0">Is Active</label>
  </div>
</div>

<div class="form-group col-sm-12">
  {!! Form::label('notes', 'Notes:') !!}
  {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 4]) !!}
</div>

@isset($customFields)
@foreach($customFields as $field)
@continue(! field_visible('account', 'cf_' . $field->id))
@php
$value = isset($accounts) ? (data_get($accounts->custom_field_values, $field->id) ?? $field->default_value) : ($field->default_value ?? '');
$config = $field->config ?? [];
$cfKey = 'cf_' . $field->id;
$cfEditable = field_editable('account', $cfKey);
$cfRequired = field_required('account', $cfKey) && $cfEditable;
if ($field->data_type === 'checkbox') {
$checked = isset($accounts)
? ($value === '1' || $value === true || $value === 'on')
: (data_get($config, 'default_checked') || $value === '1' || $value === true);
}
$options = [];
if (!empty($config['options'])) {
$options = is_array($config['options']) ? $config['options'] : array_filter(array_map('trim', explode("\n", (string)$config['options'])));
}
$name = 'custom_field_values[' . $field->id . ']';
@endphp
<div class="form-group col-sm-6">
  <label for="custom_field_{{ $field->id }}">{{ $field->label }}@if($cfRequired)<span class="text-danger">*</span>@endif</label>
  @if($field->data_type === 'text')
  <input type="text" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" placeholder="{{ $config['placeholder'] ?? '' }}" maxlength="{{ $config['max_length'] ?? 255 }}" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @elseif($field->data_type === 'textarea')
  <textarea name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" rows="{{ $config['rows'] ?? 4 }}" placeholder="{{ $config['placeholder'] ?? '' }}" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>{{ old($name, is_scalar($value) ? $value : '') }}</textarea>
  @elseif($field->data_type === 'number')
  <input type="number" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" step="{{ $config['step'] ?? 1 }}" @isset($config['min']) min="{{ $config['min'] }}" @endisset @isset($config['max']) max="{{ $config['max'] }}" @endisset @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @elseif($field->data_type === 'decimal')
  <input type="number" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" step="{{ isset($config['decimals']) ? '0.' . str_repeat('0', $config['decimals'] - 1) . '1' : '0.01' }}" @isset($config['min']) min="{{ $config['min'] }}" @endisset @isset($config['max']) max="{{ $config['max'] }}" @endisset @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @elseif($field->data_type === 'date')
  <input type="date" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @elseif($field->data_type === 'datetime')
  <input type="datetime-local" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @elseif($field->data_type === 'dropdown')
  <select name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control form-select" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
    <option value="">Select</option>
    @foreach($options as $opt)
    <option value="{{ $opt }}" @if((string)old($name, is_scalar($value) ? $value : '' )===(string)$opt) selected @endif>{{ $opt }}</option>
    @endforeach
  </select>
  @elseif($field->data_type === 'checkbox')
  <div class="form-check">
    <input type="hidden" name="{{ $name }}" value="0">
    <input type="checkbox" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-check-input" value="1" @if($checked ?? false) checked @endif @fieldReadonly('account', $cfKey)>
    <label for="custom_field_{{ $field->id }}" class="form-check-label pt-0">Yes</label>
  </div>
  @elseif($field->data_type === 'email')
  <input type="email" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" placeholder="{{ $config['placeholder'] ?? '' }}" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @elseif($field->data_type === 'url')
  <input type="url" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" placeholder="{{ $config['placeholder'] ?? '' }}" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @else
  <input type="text" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" @if($cfRequired) required @endif @fieldReadonly('account', $cfKey)>
  @endif
  @if($field->help_text)
  <p class="form-text text-muted small mb-0">{{ $field->help_text }}</p>
  @endif
</div>
@endforeach
@endisset

<script>
$(document).ready(function() {
    
    // Initialize select2
    $('.select2').select2({
        allowClear: true,
        dropdownParent: $('#modalTopbody')
    });
});
</script>