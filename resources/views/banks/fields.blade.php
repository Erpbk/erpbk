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
<div class="form-group col-sm-6">
    {!! Form::label('name', $labelFor('name', 'Bank Name:')) !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
@endif
<!-- Branch Field -->
@if($showField('branch'))
<div class="form-group col-sm-6">
  {!! Form::label('branch', $labelFor('branch', 'Bank Branch:')) !!}
  {!! Form::text('branch', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
@endif
<!-- Account Type Field -->
@if($showField('account_type'))
<div class="form-group col-sm-6">
  {!! Form::label('account_type', $labelFor('account_type', 'Account Type:')) !!}
  {!! Form::text('account_type', null, ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
</div>
@endif
<!-- Title Field -->
@if($showField('title'))
<div class="form-group col-sm-6">
    {!! Form::label('title', $labelFor('title', 'Account Title:')) !!}
    {!! Form::text('title', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
@endif

<!-- Account No Field -->
@if($showField('account_no'))
<div class="form-group col-sm-6">
    {!! Form::label('account_no', $labelFor('account_no', 'Account No:')) !!}
    {!! Form::text('account_no', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
@endif

<!-- Iban Field -->
@if($showField('iban'))
<div class="form-group col-sm-6">
    {!! Form::label('iban', $labelFor('iban', 'IBAN:')) !!}
    {!! Form::text('iban', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
@endif

<!-- Swift Field -->
@if($showField('swift'))
<div class="form-group col-sm-6">
    {!! Form::label('swift', $labelFor('swift', 'Swift:')) !!}
    {!! Form::text('swift', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
@endif



<!-- Balance Field -->
@if($showField('balance'))
<div class="form-group col-sm-6">
    {!! Form::label('balance', $labelFor('balance', 'Opening Balance:')) !!}
    {!! Form::number('balance', null, ['class' => 'form-control']) !!}
</div>
@endif
<!-- Branch Field -->
@if($showField('branch_id'))
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', $labelFor('branch_id', 'Company Branch:'),['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2']) !!}
</div>
<div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in  Branch list if this account will be used by all or multiple company branches</div>
@endif

<!-- Status Field -->
@if($showField('status'))
<div class="form-group col-sm-6 mt-3">
  <label>{{ $labelFor('status', 'Status') }}</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2"/>
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($banks) @if($banks->status == 1) checked @endif @else checked  @endisset/>
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>
@endif

<!-- Notes Field -->
@if($showField('notes'))
<div class="form-group col-sm-12">
    {!! Form::label('notes', $labelFor('notes', 'Notes:')) !!}
    {!! Form::textarea('notes', null, ['class' => 'form-control','rows'=>3]) !!}
</div>
@endif
