@php $vf = static fn (string $f): bool => field_visible('account', $f); @endphp
@if($vf('account_code'))
<!-- Account Code Field -->
<div class="col-sm-12">
    {!! Form::label('account_code', 'Account Code:') !!}
    <p>{{ $accounts->account_code }}</p>
</div>
@endif

@if($vf('name'))
<!-- Account Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Account Name:') !!}
    <p>{{ $accounts->name }}</p>
</div>
@endif

@if($vf('account_type'))
<!-- Account Type Field -->
<div class="col-sm-12">
    {!! Form::label('account_type', 'Account Type:') !!}
    <p>{{ $accounts->account_type }}</p>
</div>
@endif

@if($vf('parent_id'))
<!-- Parent Account Id Field -->
<div class="col-sm-12">
    {!! Form::label('parent_id', 'Parent Account Id:') !!}
    <p>{{ $accounts->parent_id }}</p>
</div>
@endif

@if($vf('opening_balance'))
<!-- Opening Balance Field -->
<div class="col-sm-12">
    {!! Form::label('opening_balance', 'Opening Balance:') !!}
    <p>{{ $accounts->opening_balance }}</p>
</div>
@endif

@isset($customFields)
@foreach($customFields as $field)
@if($vf('cf_' . $field->id))
@php
  $value = data_get($accounts->custom_field_values, $field->id);
  if ($field->data_type === 'checkbox') {
    $value = ($value === '1' || $value === true || $value === 'on') ? 'Yes' : 'No';
  } else {
    $value = $value ?? '—';
  }
@endphp
<div class="col-sm-12">
    <label class="text-muted">{{ $field->label }}</label>
    <p>{{ is_scalar($value) ? $value : '—' }}</p>
</div>
@endif
@endforeach
@endisset
