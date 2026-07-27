<div data-rfp-entity="vendor">
<!-- Name Field -->
@fieldVisible('vendor', 'name')
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255] + field_lock('vendor', 'name')) !!}
</div>
@endfieldVisible

<!-- Email Field -->
@fieldVisible('vendor', 'email')
<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'maxlength' => 100] + field_lock('vendor', 'email')) !!}
</div>
@endfieldVisible

<!-- Contact Number Field -->
@fieldVisible('vendor', 'contact_number')
<div class="form-group col-sm-6">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    {!! Form::text('contact_number', null, ['class' => 'form-control', 'maxlength' => 100] + field_lock('vendor', 'contact_number')) !!}
</div>
@endfieldVisible

<!-- Address Field -->
@fieldVisible('vendor', 'address')
<div class="form-group col-sm-6">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 200] + field_lock('vendor', 'address')) !!}
</div>
@endfieldVisible

<!-- Branch Field -->
@fieldVisible('vendor', 'branch_id')
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2'] + field_lock('vendor', 'branch_id', 'select')) !!}
</div>
<div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in Branch list if this vendor will be used by all or multiple company branches</div>
@endfieldVisible

<!-- Status Field -->
@fieldVisible('vendor', 'status')
<div class="form-group col-sm-4 mt-3">
  <label>Status</label>
  <div class="form-check">
    <input type="hidden" name="status" value="2"/>
     <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($banks) @if($banks->status == 1) checked @endif @else checked  @endisset @fieldReadonly('vendor', 'status')/>
     <label for="status" class="pt-0">Is Active</label>

  </div>
</div>
@endfieldVisible
</div>
