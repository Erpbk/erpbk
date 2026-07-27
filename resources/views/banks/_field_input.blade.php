@php
  $fieldKey = $fieldKey ?? $key;
  $fieldLabel = $label ?? \App\Support\BankFormLayout::labelForFieldKey($fieldKey);
  $rfpEntity = 'bank';
  $rfpVisible = field_visible($rfpEntity, (string) $fieldKey);
  $rfpEditable = field_editable($rfpEntity, (string) $fieldKey);
  $rfpRequired = field_required($rfpEntity, (string) $fieldKey);
  $req = $rfpRequired && $rfpEditable;
  $lock = field_lock($rfpEntity, (string) $fieldKey);
  $selectLock = field_lock($rfpEntity, (string) $fieldKey, 'select');
@endphp
@if ($rfpVisible)
@switch($fieldKey)
  @case('name')
    <div class="form-group col-sm-6">
      {!! Form::label('name', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('branch')
    <div class="form-group col-sm-6">
      {!! Form::label('branch', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text('branch', null, ['class' => 'form-control', 'maxlength' => 255] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('account_type')
    <div class="form-group col-sm-6">
      {!! Form::label('account_type', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text('account_type', null, ['class' => 'form-control', 'maxlength' => 100] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('title')
    <div class="form-group col-sm-6">
      {!! Form::label('title', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text('title', null, ['class' => 'form-control', 'maxlength' => 255] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('account_no')
    <div class="form-group col-sm-6">
      {!! Form::label('account_no', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text('account_no', null, ['class' => 'form-control', 'maxlength' => 255] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('iban')
    <div class="form-group col-sm-6">
      {!! Form::label('iban', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text('iban', null, ['class' => 'form-control', 'maxlength' => 255] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('swift')
    <div class="form-group col-sm-6">
      {!! Form::label('swift', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text('swift', null, ['class' => 'form-control', 'maxlength' => 255] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('balance')
    <div class="form-group col-sm-6">
      {!! Form::label('balance', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::number('balance', null, ['class' => 'form-control', 'step' => '0.01'] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @case('branch_id')
    <div class="form-group col-sm-6">
      {!! Form::label('branch_id', $fieldLabel, ['class' => 'required']) !!}
      {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2'] + $selectLock) !!}
    </div>
    <div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in Branch list if this account will be used by all or multiple company branches</div>
    @break
  @case('status')
    <div class="form-group col-sm-6 mt-3">
      <label>{{ $fieldLabel }}</label>
      <div class="form-check">
        <input type="hidden" name="status" value="2"/>
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($banks) @if($banks->status == 1) checked @endif @else checked @endisset {{ $rfpEditable ? '' : 'disabled' }}/>
        <label for="status" class="pt-0">{{ __('Is Active') }}</label>
      </div>
    </div>
    @break
  @case('notes')
    <div class="form-group col-sm-12">
      {!! Form::label('notes', $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::textarea('notes', null, ['class' => 'form-control','rows'=>3] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
    @break
  @default
    <div class="form-group col-sm-6">
      {!! Form::label($fieldKey, $fieldLabel . ($req ? ':' : ''), $req ? ['class' => 'required'] : []) !!}
      {!! Form::text($fieldKey, null, ['class' => 'form-control'] + ($req ? ['required' => true] : []) + $lock) !!}
    </div>
@endswitch
@endif
