@php
$banks = \App\Models\Banks::orderBy('name')->pluck('name', 'id')->prepend('Select Bank', '');
$branchDropdown = auth()->user()->branchDropdown(true);
$interestMethods = \App\Models\Loan::interestCalculationMethods();
$selectedMethod = old('interest_calculation_method', isset($loan) ? $loan->interest_calculation_method : \App\Models\Loan::INTEREST_REDUCING);
@endphp

<div class="form-group col-sm-4">
    {!! Form::label('bank_name', 'Lender Bank Name', ['class' => 'required']) !!}
    {!! Form::text('bank_name', null, ['class' => 'form-control', 'maxlength' => 255, 'required' => true, 'placeholder' => 'Enter bank name']) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('receiving_bank_id', 'Receiving Bank (Disbursement)') !!}
    {!! Form::select('receiving_bank_id', $banks, null, ['class' => 'form-select select2']) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('paying_bank_id', 'Paying Bank (Repayments)') !!}
    {!! Form::select('paying_bank_id', $banks, null, ['class' => 'form-select select2']) !!}
</div>

{{-- <div class="form-group col-sm-6">
    {!! Form::label('agreement_ref', 'Agreement Reference') !!}
    {!! Form::text('agreement_ref', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
</div> --}}

<div class="form-group col-sm-4">
    {!! Form::label('principal_amount', 'Principal Amount', ['class' => 'required']) !!}
    {!! Form::number('principal_amount', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0.01', 'required' => true]) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('processing_fee', 'Processing Fee') !!}
    {!! Form::number('processing_fee', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0']) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('interest_rate', 'Annual Interest Rate (%)', ['class' => 'required']) !!}
    {!! Form::number('interest_rate', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0', 'required' => true]) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('term_months', 'Term (Months)', ['class' => 'required']) !!}
    {!! Form::number('term_months', null, ['class' => 'form-control', 'min' => '1', 'max' => '600', 'required' => true]) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('first_payment_date', 'First Payment Date', ['class' => 'required']) !!}
    {!! Form::date('first_payment_date', isset($loan) && $loan->first_payment_date ? $loan->first_payment_date->format('Y-m-d') : null, ['class' => 'form-control', 'required' => true]) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('branch_id', 'Branch') !!}
    {!! Form::select('branch_id', $branchDropdown, null, ['class' => 'form-select select2']) !!}
</div>

<div class="form-group col-sm-4">
    {!! Form::label('interest_calculation_method', 'Interest Calculation', ['class' => 'required']) !!}
    {!! Form::select('interest_calculation_method', $interestMethods, $selectedMethod, ['class' => 'form-select', 'required' => true]) !!}
    <small class="text-muted d-block mt-1">
        <strong>Flat:</strong> interest on original principal each month.<br>
        <strong>Reducing:</strong> interest on outstanding balance (EMI).
    </small>
</div>

<div class="form-group col-sm-12">
    {!! Form::label('notes', 'Notes') !!}
    {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]) !!}
</div>
