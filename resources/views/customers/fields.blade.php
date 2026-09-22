<!-- Company Name Field -->
@fieldVisible('customer', 'company_name')
<div class="form-group col-sm-6">
    {!! Form::label('company_name', 'Company Name:',['class'=>'fw-bold']) !!}
    {!! Form::text('company_name', null, ['class' => 'form-control', 'maxlength' => 255] + field_lock('customer', 'company_name')) !!}
</div>
@endfieldVisible
<!-- Name Field -->
@fieldVisible('customer', 'name')
<div class="form-group col-sm-6">
    {!! Form::label('name', 'Project Name:',['class'=>'required fw-bold']) !!}
    {!! Form::text('name', null, ['class' => 'form-control','maxlength' => 255, 'required'] + field_lock('customer', 'name')) !!}
</div>
@endfieldVisible

<!-- Contact Number Field -->
@fieldVisible('customer', 'contact_number')
<div class="form-group col-sm-6">
    {!! Form::label('contact_number', 'Contact Number:',['class'=>'required fw-bold']) !!}
    {!! Form::text('contact_number', null, ['class' => 'form-control', 'maxlength' => 100, 'required'] + field_lock('customer', 'contact_number')) !!}
</div>
@endfieldVisible


<!-- Company Email Field -->
@fieldVisible('customer', 'company_email')
<div class="form-group col-sm-6">
    {!! Form::label('company_email', 'Company Email:',['class'=>'fw-bold']) !!}
    {!! Form::text('company_email', null, ['class' => 'form-control', 'maxlength' => 100] + field_lock('customer', 'company_email')) !!}
</div>
@endfieldVisible


<!-- Address Field -->
@fieldVisible('customer', 'address')
<div class="form-group col-sm-12">
    {!! Form::label('address', 'Address:',['class'=>'fw-bold']) !!}
    {!! Form::text('address', null, ['class' => 'form-control', 'maxlength' => 200] + field_lock('customer', 'address')) !!}
</div>
@endfieldVisible

<!-- Tax Number Field -->
@fieldVisible('customer', 'tax_number')
<div class="form-group col-sm-6">
    {!! Form::label('tax_number', 'Tax Number:',['class'=>'required fw-bold']) !!}
    {!! Form::text('tax_number', null, ['class' => 'form-control', 'maxlength' => 100, 'required'] + field_lock('customer', 'tax_number')) !!}
</div>
@endfieldVisible


<!-- Tax Percentage Field -->
@fieldVisible('customer', 'tax_percentage')
<div class="form-group col-sm-6">
    {!! Form::label('tax_percentage', 'Tax Percentage:',['class'=>'required fw-bold']) !!}
    {!! Form::number('tax_percentage', null, ['class' => 'form-control','step'=>'any', 'required'] + field_lock('customer', 'tax_percentage')) !!}
</div>
@endfieldVisible

<!-- Branch Field -->
@fieldVisible('customer', 'branch_id')
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Company Branch:',['class'=>'required fw-bold']) !!}
    @php
    $branchOptions = auth()->user()->branchDropdown(true);
    @endphp
    {!! Form::select('branch_id', $branchOptions , null, ['class' => 'form-select select2'] + field_lock('customer', 'branch_id', 'select')) !!}
</div>
<div class="mt-4 col-sm-12 alert alert-warning">Select <b>'All'</b> option in Branch list if this account will be used by all or multiple company branches</div>
@endfieldVisible

<!-- Status Field -->
@fieldVisible('customer', 'status')
<div class="form-group col-sm-6 mt-3">
    <label class="fw-bold">Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2" />
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($customers) @if($customers->status == 1) checked @endif @else checked @endisset @fieldReadonly('customer', 'status')/>
        <label for="status" class="pt-0">Is Active</label>

    </div>
</div>
@endfieldVisible

@php
$customerInvoiceDefaults = \App\Support\CustomerInvoiceDefaults::all();
$customerTermsDefault = old('terms_and_conditions', isset($customers) ? ($customers->terms_and_conditions ?? '') : $customerInvoiceDefaults['terms_and_conditions']);
$customerNotesDefault = old('customer_note', isset($customers) ? ($customers->customer_note ?? '') : $customerInvoiceDefaults['customer_notes']);
@endphp

<!-- Terms & Conditions Field -->
@fieldVisible('customer', 'terms_and_conditions')
<div class="form-group col-sm-12">
    {!! Form::label('terms_and_conditions', 'Terms & Conditions:', ['class' => 'fw-bold']) !!}
    {!! Form::textarea('terms_and_conditions', $customerTermsDefault, ['class' => 'form-control', 'rows' => 4, 'placeholder' => 'Terms & conditions shown on customer invoices...'] + field_lock('customer', 'terms_and_conditions')) !!}
</div>
@endfieldVisible

<!-- Customer Notes Field -->
@fieldVisible('customer', 'customer_note')
<div class="form-group col-sm-12">
    {!! Form::label('customer_note', 'Customer Notes:', ['class' => 'fw-bold']) !!}
    {!! Form::textarea('customer_note', $customerNotesDefault, ['class' => 'form-control', 'rows' => 3, 'placeholder' => 'Thanks for your business.'] + field_lock('customer', 'customer_note')) !!}
    <small class="text-muted">Will be displayed on the invoice</small>
</div>
@endfieldVisible