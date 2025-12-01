<!-- Rider Field -->
<div class="col-sm-12">
    {!! Form::label('rider', 'Rider:') !!}
    <p>{{ $cod->rider ? $cod->rider->rider_id . ' - ' . $cod->rider->name : 'N/A' }}</p>
</div>

<!-- Transaction Date Field -->
<div class="col-sm-12">
    {!! Form::label('transaction_date', 'Transaction Date:') !!}
    <p>{{ App\Helpers\General::DateFormat($cod->transaction_date) }}</p>
</div>

<!-- Transaction Time Field -->
<div class="col-sm-12">
    {!! Form::label('transaction_time', 'Transaction Time:') !!}
    <p>{{ $cod->transaction_time ?? 'N/A' }}</p>
</div>

<!-- Billing Month Field -->
<div class="col-sm-12">
    {!! Form::label('billing_month', 'Billing Month:') !!}
    <p>{{ $cod->billing_month ? \Carbon\Carbon::parse($cod->billing_month)->format('M Y') : 'N/A' }}</p>
</div>

<!-- Amount Field -->
<div class="col-sm-12">
    {!! Form::label('amount', 'Amount:') !!}
    <p>AED {{ number_format($cod->amount, 2) }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>
        <span class="badge badge-{{ $cod->status == 'paid' ? 'success' : ($cod->status == 'unpaid' ? 'danger' : 'warning') }}">
            {{ ucfirst($cod->status) }}
        </span>
    </p>
</div>

<!-- Description Field -->
<div class="col-sm-12">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $cod->description ?? 'N/A' }}</p>
</div>

<!-- Created By Field -->
<div class="col-sm-12">
    {!! Form::label('created_by', 'Created By:') !!}
    <p>{{ $cod->created_by ?? 'N/A' }}</p>
</div>

<!-- Created At Field -->
<div class="col-sm-12">
    {!! Form::label('created_at', 'Created At:') !!}
    <p>{{ $cod->created_at ? App\Helpers\General::DateTimeFormat($cod->created_at) : 'N/A' }}</p>
</div>

<!-- Updated At Field -->
<div class="col-sm-12">
    {!! Form::label('updated_at', 'Updated At:') !!}
    <p>{{ $cod->updated_at ? App\Helpers\General::DateTimeFormat($cod->updated_at) : 'N/A' }}</p>
</div>