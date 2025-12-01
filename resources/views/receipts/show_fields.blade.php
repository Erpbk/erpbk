<!-- Transaction Number Field -->
<div class="col-sm-12">
    {!! Form::label('transaction_number', 'Transaction Number:') !!}
    <p>{{ $receipt->transaction_number }}</p>
</div>
<!-- Sender ID Field -->
<div class="col-sm-12">
    {!! Form::label('sender_id', 'Sender ID:') !!}
    <p>{{ $receipt->sender_id }}</p>
</div>
<!-- Bank ID Field -->
<div class="col-sm-12">
    {!! Form::label('bank_id', 'Bank ID:') !!}
    <p>{{ $receipt->bank_id }}</p>
</div>
<!-- Amount Field -->
<div class="col-sm-12">
    {!! Form::label('amount', 'Amount:') !!}
    <p>AED {{ number_format($receipt->amount, 2) }}</p>
</div>
<!-- Date of Receipt Field -->
<div class="col-sm-12">
    {!! Form::label('date_of_receipt', 'Date of Receipt:') !!}
    <p>{{ $receipt->date_of_receipt }}</p>
</div>
<!-- Billing Month Field -->
<div class="col-sm-12">
    {!! Form::label('billing_month', 'Billing Month:') !!}
    <p>{{ $receipt->billing_month }}</p>
</div>
<!-- Description Field -->
<div class="col-sm-12">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $receipt->description }}</p>
</div>
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $receipt->status }}</p>
</div>
<!-- Created By Field -->
<div class="col-sm-12">
    {!! Form::label('created_by', 'Created By:') !!}
    <p>{{ $receipt->created_by }}</p>
</div>
<!-- Updated By Field -->
<div class="col-sm-12">
    {!! Form::label('updated_by', 'Updated By:') !!}
    <p>{{ $receipt->updated_by }}</p>
</div>