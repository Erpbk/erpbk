<!-- Name Field -->
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Bank Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<!-- Branch Field -->
<div class="form-group col-sm-6">
  {!! Form::label('branch', 'Bank Branch:') !!}
  {!! Form::text('branch', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>
<!-- Account Type Field -->
<div class="form-group col-sm-6">
  {!! Form::label('account_type', 'Account Type:') !!}
  {!! Form::text('account_type', null, ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]) !!}
</div>
<!-- Title Field -->
<div class="form-group col-sm-6">
    {!! Form::label('title', 'Account Title:') !!}
    {!! Form::text('title', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Account No Field -->
<div class="form-group col-sm-6">
    {!! Form::label('account_no', 'Account No:') !!}
    {!! Form::text('account_no', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Iban Field -->
<div class="form-group col-sm-6">
    {!! Form::label('iban', 'IBAN:') !!}
    {!! Form::text('iban', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>

<!-- Swift Field -->
<div class="form-group col-sm-6">
    {!! Form::label('swift', 'Swift:') !!}
    {!! Form::text('swift', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]) !!}
</div>



<!-- Balance Field -->
<div class="form-group col-sm-6">
    {!! Form::label('balance', 'Opening Balance:') !!}
    {!! Form::number('balance', null, ['class' => 'form-control']) !!}
</div>
<!-- Branch Field -->
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Company Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2']) !!}
</div>
<div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in  Branch list if this account will be used by all or multiple company branches</div>

<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2"/>
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($banks) @if($banks->status == 1) checked @endif @else checked  @endisset/>
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>

<!-- Notes Field -->
<div class="form-group col-sm-12">
    {!! Form::label('notes', 'Notes:') !!}
    {!! Form::textarea('notes', null, ['class' => 'form-control','rows'=>3]) !!}
</div>
