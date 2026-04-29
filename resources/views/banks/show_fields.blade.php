@php
  $visibleFieldMap = \App\Support\ModuleFieldSettings::visibleFieldMap('cash_banks');
  $showField = function (string $key) use ($visibleFieldMap): bool {
    return array_key_exists($key, $visibleFieldMap);
  };
  $labelFor = function (string $key, string $fallback) use ($visibleFieldMap): string {
    return $visibleFieldMap[$key] ?? $fallback;
  };
@endphp

<!-- Name Field -->
@if($showField('name'))
<div class="col-sm-12">
    {!! Form::label('name', $labelFor('name', 'Name:')) !!}
    <p>{{ $banks->name }}</p>
</div>
@endif

<!-- Title Field -->
@if($showField('title'))
<div class="col-sm-12">
    {!! Form::label('title', $labelFor('title', 'Title:')) !!}
    <p>{{ $banks->title }}</p>
</div>
@endif

<!-- Account No Field -->
@if($showField('account_no'))
<div class="col-sm-12">
    {!! Form::label('account_no', $labelFor('account_no', 'Account No:')) !!}
    <p>{{ $banks->account_no }}</p>
</div>
@endif

<!-- Iban Field -->
@if($showField('iban'))
<div class="col-sm-12">
    {!! Form::label('iban', $labelFor('iban', 'Iban:')) !!}
    <p>{{ $banks->iban }}</p>
</div>
@endif

<!-- Swift Field -->
@if($showField('swift'))
<div class="col-sm-12">
    {!! Form::label('swift', $labelFor('swift', 'Swift:')) !!}
    <p>{{ $banks->swift }}</p>
</div>
@endif

<!-- Branch Field -->
@if($showField('branch'))
<div class="col-sm-12">
    {!! Form::label('branch', $labelFor('branch', 'Branch:')) !!}
    <p>{{ $banks->branch }}</p>
</div>
@endif

<!-- Account Type Field -->
@if($showField('account_type'))
<div class="col-sm-12">
    {!! Form::label('account_type', $labelFor('account_type', 'Account Type:')) !!}
    <p>{{ $banks->account_type }}</p>
</div>
@endif

<!-- Balance Field -->
@if($showField('balance'))
<div class="col-sm-12">
    {!! Form::label('balance', $labelFor('balance', 'Balance:')) !!}
    <p>{{ $banks->balance }}</p>
</div>
@endif

<!-- Status Field -->
@if($showField('status'))
<div class="col-sm-12">
    {!! Form::label('status', $labelFor('status', 'Status:')) !!}
    <p>{{ $banks->status }}</p>
</div>
@endif

<!-- Notes Field -->
@if($showField('notes'))
<div class="col-sm-12">
    {!! Form::label('notes', $labelFor('notes', 'Notes:')) !!}
    <p>{{ $banks->notes }}</p>
</div>
@endif

