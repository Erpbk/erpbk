<div data-rfp-entity="account">
<div class="alert alert-warning"> Select <b>'All'</b> option in Branch list if this account will be used by all or multiple branches</div>

<!-- Account Type Field -->
<div class="row" data-rfp-entity="account">
@fieldVisible('account', 'account_type')
<div class="form-group col-sm-6">
  {!! Form::label('account_type', 'Account Type:') !!}
  {!! Form::select('account_type', App\Helpers\Accounts::AccountTypes(),null, ['class' => 'form-control form-select select2'] + field_lock('account', 'account_type', 'select')) !!}
</div>
@endfieldVisible
<!-- Branch Field -->
@fieldVisible('account', 'branch_id')
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2'] + field_lock('account', 'branch_id', 'select')) !!}
</div>
@endfieldVisible
<!-- Account Name Field -->
@fieldVisible('account', 'name')
<div class="form-group col-sm-6">
  {!! Form::label('name', 'Account Name:') !!}
  {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 100, 'maxlength' => 100] + field_lock('account', 'name')) !!}
</div>
@endfieldVisible
<!-- Account Code Field -->
@if(Route::currentRouteName() == 'accounts.edit' && isset($accounts->id))
@fieldVisible('account', 'account_code')
<div class="form-group col-sm-6">
  {!! Form::label('account_code', 'Account Code:') !!}
  {!! Form::text('account_code', $accounts->account_code, ['class' => 'form-control'] + field_lock('account', 'account_code')) !!}
</div>
@endfieldVisible
@endif

<!-- Parent Account Id Field -->
@fieldVisible('account', 'parent_id')
<div class="form-group col-sm-6">
  {!! Form::label('parent_id', 'Parent Account:') !!}
  <select name="parent_id" class="form-control form-select select2" @fieldReadonly('account', 'parent_id')>
    <option value="">Select</option>
    {!! App\Helpers\Accounts::dropdown($parents, isset($accounts) ? $accounts->parent_id : null) !!}
  </select>
  {{-- {!! Form::select('parent_account_id', $parents,null, ['class' => 'form-control form-select select2']) !!} --}}
</div>
@endfieldVisible

<!-- Opening Balance Field -->
@fieldVisible('account', 'opening_balance')
<div class="form-group col-sm-6">
  {!! Form::label('opening_balance', 'Opening Balance:') !!}
  {!! Form::number('opening_balance', null, ['class' => 'form-control','step'=>'any'] + field_lock('account', 'opening_balance')) !!}
</div>
@endfieldVisible

<div class="form-group col-sm-6"></div>
<!-- Status Field -->
@fieldVisible('account', 'status')
<div class="form-group col-sm-6">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2" />
    <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($accounts) @if($accounts->status == 1) checked @endif @else checked @endisset @fieldReadonly('account', 'status')/>
    <label for="status" class="pt-0">Is Active</label>

  </div>
</div>
@endfieldVisible

@if(\Illuminate\Support\Facades\Auth::guard('admin')->check())
<div class="form-group col-sm-6">
  <label>Fixed Account</label>
  <div class="form-check">
    <input type="hidden" name="is_fixed" value="0" />
    <input type="checkbox" name="is_fixed" id="is_fixed" class="form-check-input" value="1" @isset($accounts) @if($accounts->is_fixed) checked @endif @endisset />
    <label for="is_fixed" class="pt-0">Share this account with all companies</label>
  </div>
</div>
@endif

@fieldVisible('account', 'notes')
<div class="form-group col-sm-12">
  {!! Form::label('notes', 'Notes:') !!}
  {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 4] + field_lock('account', 'notes')) !!}

</div>
@endfieldVisible

@isset($customFields)
@foreach($customFields as $field)
@continue(! field_visible('account', 'cf_' . $field->id))
@php
$value = isset($accounts) ? (data_get($accounts->custom_field_values, $field->id) ?? $field->default_value) : ($field->default_value ?? '');
$config = $field->config ?? [];
$cfKey = 'cf_' . $field->id;
$cfEditable = field_editable('account', $cfKey);
$cfRequired = (field_required('account', $cfKey) || $field->is_mandatory) && $cfEditable;
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
</div>
{{-- <div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
  <label class="form-check-label" for="flexSwitchCheckDefault">Default switch checkbox input</label>
</div> --}}

<script>
  (function () {
    if (window.jQuery && $.fn.select2) {
      $('#modalTopbody .select2').select2({
        dropdownParent: $('#modalTop'),
        allowClear: true
      });
    }
  })();
</script>