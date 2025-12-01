<!-- Rider Field -->
<div class="col-sm-12">
    {!! Form::label('rider', 'Rider:') !!}
    <p>{{ $penalty->rider ? $penalty->rider->rider_id . ' - ' . $penalty->rider->name : 'N/A' }}</p>
</div>

<!-- Transaction Date Field -->
<div class="col-sm-12">
    {!! Form::label('transaction_date', 'Transaction Date:') !!}
    <p>{{ App\Helpers\General::DateFormat($penalty->transaction_date) }}</p>
</div>

<!-- Transaction Time Field -->
<div class="col-sm-12">
    {!! Form::label('transaction_time', 'Transaction Time:') !!}
    <p>{{ $penalty->transaction_time ?? 'N/A' }}</p>
</div>

<!-- Billing Month Field -->
<div class="col-sm-12">
    {!! Form::label('billing_month', 'Billing Month:') !!}
    <p>{{ $penalty->billing_month ? \Carbon\Carbon::parse($penalty->billing_month)->format('M Y') : 'N/A' }}</p>
</div>

<!-- Amount Field -->
<div class="col-sm-12">
    {!! Form::label('amount', 'Amount:') !!}
    <p>AED {{ number_format($penalty->amount, 2) }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>
        <span class="badge badge-{{ $penalty->status == 'paid' ? 'success' : ($penalty->status == 'unpaid' ? 'danger' : 'warning') }}">
            {{ ucfirst($penalty->status) }}
        </span>
    </p>
</div>

<!-- Description Field -->
<div class="col-sm-12">
    {!! Form::label('description', 'Description:') !!}
    <p>{{ $penalty->description ?? 'N/A' }}</p>
</div>

<!-- Created By Field -->
<div class="col-sm-12">
    {!! Form::label('created_by', 'Created By:') !!}
    <p>{{ $penalty->created_by ?? 'N/A' }}</p>
</div>

<!-- Created At Field -->
<div class="col-sm-12">
    {!! Form::label('created_at', 'Created At:') !!}
    <p>{{ $penalty->created_at ? App\Helpers\General::DateTimeFormat($penalty->created_at) : 'N/A' }}</p>
</div>

<!-- Updated At Field -->
<div class="col-sm-12">
    {!! Form::label('updated_at', 'Updated At:') !!}
    <p>{{ $penalty->updated_at ? App\Helpers\General::DateTimeFormat($penalty->updated_at) : 'N/A' }}</p>
</div>