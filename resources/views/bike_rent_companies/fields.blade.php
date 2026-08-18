@php
    $customerType = $type ?? ($bikeRentCompany->customer_type ?? 'bike_rental');
    $partyType = old('party_type', isset($bikeRentCompany) ? ($bikeRentCompany->party_type ?? 'company') : 'company');
    $nationalityOptions = ['' => 'Select'] + \App\Models\Countries::query()->orderBy('name')->pluck('name', 'name')->all();
@endphp
<input type="hidden" name="customer_type" value="{{ $customerType }}">
@if($customerType === 'garage')
    <input type="hidden" name="party_type" value="company">
@else
    <div class="form-group col-sm-12">
        <label class="d-block mb-2">Customer type</label>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="party_type" id="party_type_company" value="company" {{ $partyType !== 'individual' ? 'checked' : '' }}>
            <label class="form-check-label" for="party_type_company">Company</label>
        </div>
        <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="party_type" id="party_type_individual" value="individual" {{ $partyType === 'individual' ? 'checked' : '' }}>
            <label class="form-check-label" for="party_type_individual">Individual</label>
        </div>
    </div>
@endif

<div class="form-group col-sm-6">
    {!! Form::label('name', 'Name:') !!}
    {!! Form::text('name', null, ['class' => 'form-control', 'maxlength' => 255, 'required' => true]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('company_contact', 'Contact:') !!}
    {!! Form::text('company_contact', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<div class="form-group col-sm-6">
    {!! Form::label('email', 'Email:') !!}
    {!! Form::email('email', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div>

<div class="form-group col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    {!! Form::textarea('address', null, ['class' => 'form-control', 'rows' => 2, 'maxlength' => 500]) !!}
</div>

<div id="individual-fields" class="col-sm-12 {{ $customerType === 'bike_rental' && $partyType === 'individual' ? '' : 'd-none' }}">
    <div class="row">
    <div class="form-group col-sm-6">
        {!! Form::label('emirates_id', 'Emirates ID:') !!}
        {!! Form::text('emirates_id', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('emirates_expiry', 'Emirates ID expiry:') !!}
        {!! Form::date('emirates_expiry', null, ['class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('passport_no', 'Passport no:') !!}
        {!! Form::text('passport_no', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('passport_expiry', 'Passport expiry:') !!}
        {!! Form::date('passport_expiry', null, ['class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('dob', 'Date of birth:') !!}
        {!! Form::date('dob', null, ['class' => 'form-control']) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('nationality', 'Nationality:') !!}
        {!! Form::select('nationality', $nationalityOptions, null, ['class' => 'form-select select2']) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('license_no', 'License no:') !!}
        {!! Form::text('license_no', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
    </div>
    <div class="form-group col-sm-6">
        {!! Form::label('license_expiry', 'License expiry:') !!}
        {!! Form::date('license_expiry', null, ['class' => 'form-control']) !!}
    </div>
    </div>
</div>

<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Branch:',['class'=>'required']) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-select select2']) !!}
</div>
<div class="mt-2 col-sm-12 alert alert-warning">Select <b>All</b> in Branch if this customer applies to all branches.</div>

<div class="form-group col-sm-4 mt-3">
    <label>Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2" />
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" @isset($bikeRentCompany) @if($bikeRentCompany->status == 1) checked @endif @else checked @endisset/>
        <label for="status" class="pt-0">Is Active</label>
    </div>
</div>

@if($customerType === 'bike_rental')
<script>
    (function () {
        function toggleIndividualFields() {
            var isIndividual = document.getElementById('party_type_individual') && document.getElementById('party_type_individual').checked;
            var wrap = document.getElementById('individual-fields');
            if (!wrap) {
                return;
            }
            wrap.classList.toggle('d-none', !isIndividual);
        }
        document.querySelectorAll('input[name="party_type"]').forEach(function (el) {
            el.addEventListener('change', toggleIndividualFields);
        });
        toggleIndividualFields();
    })();
</script>
@endif
