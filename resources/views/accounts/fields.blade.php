<script src="{{ asset('js/modal_custom.js') }}"></script>

<!-- Account Type Field -->
<div class="form-group col-sm-6">
  {!! Form::label('account_type', 'Account Type:') !!}
  {!! Form::select('account_type', App\Helpers\Accounts::AccountTypes(),null, ['class' => 'form-control form-select select2']) !!}
</div>
<div class="form-group col-sm-6"></div>
<!-- Account Name Field -->
<div class="form-group col-sm-6">
  {!! Form::label('name', 'Account Name:') !!}
  {!! Form::text('name', null, ['class' => 'form-control', 'required', 'maxlength' => 100, 'maxlength' => 100]) !!}
</div>
<div class="form-group col-sm-6"></div>
<!-- Account Code Field -->
@if(Route::currentRouteName() == 'accounts.edit' && isset($accounts->id))
<div class="form-group col-sm-6">
  {!! Form::label('account_code', 'Account Code:') !!}
  {!! Form::text('account_code', $accounts->account_code, ['class' => 'form-control']) !!}
</div>
@endif

<!-- Parent Account Id Field -->
<div class="form-group col-sm-8">
  {!! Form::label('parent_id', 'Parent Account:') !!}
  <select name="parent_id" class="form-control form-select select2">
    <option value="">Select</option>
    {!! App\Helpers\Accounts::dropdown($parents, isset($accounts) ? $accounts->parent_id : null) !!}
  </select>
  {{-- {!! Form::select('parent_account_id', $parents,null, ['class' => 'form-control form-select select2']) !!} --}}
</div>

<!-- Opening Balance Field -->
<div class="form-group col-sm-6">
  {!! Form::label('opening_balance', 'Opening Balance:') !!}
  {!! Form::number('opening_balance', null, ['class' => 'form-control','step'=>'any']) !!}
</div>

<div class="form-group col-sm-6"></div>
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
@php
  $value = isset($accounts) ? (data_get($accounts->custom_field_values, $field->id) ?? $field->default_value) : ($field->default_value ?? '');
  $config = $field->config ?? [];
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
  <label for="custom_field_{{ $field->id }}">{{ $field->label }}@if($field->is_mandatory)<span class="text-danger">*</span>@endif</label>
  @if($field->data_type === 'text')
    <input type="text" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" placeholder="{{ $config['placeholder'] ?? '' }}" maxlength="{{ $config['max_length'] ?? 255 }}" @if($field->is_mandatory) required @endif>
  @elseif($field->data_type === 'textarea')
    <textarea name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" rows="{{ $config['rows'] ?? 4 }}" placeholder="{{ $config['placeholder'] ?? '' }}" @if($field->is_mandatory) required @endif>{{ old($name, is_scalar($value) ? $value : '') }}</textarea>
  @elseif($field->data_type === 'number')
    <input type="number" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" step="{{ $config['step'] ?? 1 }}" @isset($config['min']) min="{{ $config['min'] }}" @endisset @isset($config['max']) max="{{ $config['max'] }}" @endisset @if($field->is_mandatory) required @endif>
  @elseif($field->data_type === 'decimal')
    <input type="number" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" step="{{ isset($config['decimals']) ? '0.' . str_repeat('0', $config['decimals'] - 1) . '1' : '0.01' }}" @isset($config['min']) min="{{ $config['min'] }}" @endisset @isset($config['max']) max="{{ $config['max'] }}" @endisset @if($field->is_mandatory) required @endif>
  @elseif($field->data_type === 'date')
    <input type="date" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" @if($field->is_mandatory) required @endif>
  @elseif($field->data_type === 'datetime')
    <input type="datetime-local" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" @if($field->is_mandatory) required @endif>
  @elseif($field->data_type === 'dropdown')
    <select name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control form-select" @if($field->is_mandatory) required @endif>
      <option value="">Select</option>
      @foreach($options as $opt)
        <option value="{{ $opt }}" @if((string)old($name, is_scalar($value) ? $value : '') === (string)$opt) selected @endif>{{ $opt }}</option>
      @endforeach
    </select>
  @elseif($field->data_type === 'checkbox')
    <div class="form-check">
      <input type="hidden" name="{{ $name }}" value="0">
      <input type="checkbox" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-check-input" value="1" @if($checked ?? false) checked @endif>
      <label for="custom_field_{{ $field->id }}" class="form-check-label pt-0">Yes</label>
    </div>
  @elseif($field->data_type === 'email')
    <input type="email" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" placeholder="{{ $config['placeholder'] ?? '' }}" @if($field->is_mandatory) required @endif>
  @elseif($field->data_type === 'url')
    <input type="url" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" placeholder="{{ $config['placeholder'] ?? '' }}" @if($field->is_mandatory) required @endif>
  @else
    <input type="text" name="{{ $name }}" id="custom_field_{{ $field->id }}" class="form-control" value="{{ old($name, is_scalar($value) ? $value : '') }}" @if($field->is_mandatory) required @endif>
  @endif
  @if($field->help_text)
    <p class="form-text text-muted small mb-0">{{ $field->help_text }}</p>
  @endif
</div>
@endforeach
@endisset
{{-- <div class="form-check form-switch mb-2">
  <input class="form-check-input" type="checkbox" id="flexSwitchCheckDefault">
  <label class="form-check-label" for="flexSwitchCheckDefault">Default switch checkbox input</label>
</div> --}}