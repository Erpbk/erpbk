<!-- Bank ID Field -->
<div class="col-sm-12">
    {!! Form::label('bank_id', 'Bank ID:') !!}
    <p>{{ $payment->bank_id }}</p>
</div>
<!-- Account Type Field -->
<div class="col-sm-12">
    {!! Form::label('account_type', 'Account Type:') !!}
    <p>{{ $payment->account_type }}</p>
</div>
<!-- Head Account ID Field -->
<div class="col-sm-12">
    {!! Form::label('head_account_id', 'Head Account ID:') !!}
    <p>{{ $payment->head_account_id }}</p>
</div>
<!-- Account ID Field -->
<div class="col-sm-12">
    {!! Form::label('account_id', 'Account ID:') !!}
    <p>{{ $payment->account_id }}</p>
</div>
<!-- Amount Field -->
<div class="col-sm-12">
    {!! Form::label('amount', 'Amount:') !!}
    <p>AED {{ number_format($payment->amount, 2) }}</p>
</div>
<!-- Date of Invoice Field -->
<div class="col-sm-12">
    {!! Form::label('date_of_invoice', 'Date of Invoice:') !!}
    <p>{{ $payment->date_of_invoice }}</p>
</div>
<!-- Date of Payment Field -->
<div class="col-sm-12">
    {!! Form::label('date_of_payment', 'Date of Payment:') !!}
    <p>{{ $payment->date_of_payment }}</p>
</div>
<!-- Billing Month Field -->
<div class="col-sm-12">
    {!! Form::label('billing_month', 'Billing Month:') !!}
    <p>{{ $payment->billing_month }}</p>
</div>
<!-- Voucher No Field -->
<div class="col-sm-12">
    {!! Form::label('voucher_no', 'Voucher No:') !!}
    <p>{{ $payment->voucher_no }}</p>
</div>
<!-- Voucher Type Field -->
<div class="col-sm-12">
    {!! Form::label('voucher_type', 'Voucher Type:') !!}
    <p>{{ $payment->voucher_type }}</p>
</div>
<!-- Description Field -->
<div class="col-sm-12">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $payment->description }}</p>
</div>
<!-- Created By Field -->
<div class="col-sm-12">
    {!! Form::label('created_by', 'Created By:') !!}
    <p>{{ $payment->created_by }}</p>
</div>
<!-- Updated By Field -->
<div class="col-sm-12">
    {!! Form::label('updated_by', 'Updated By:') !!}
    <p>{{ $payment->updated_by }}</p>
</div>
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $payment->status }}</p>
</div>