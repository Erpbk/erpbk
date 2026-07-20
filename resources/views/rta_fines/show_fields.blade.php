@php $vf = static fn (string $f): bool => field_visible('rtafine', $f); @endphp
@if($vf('trans_date'))
<!-- Trans Date Field -->
<div class="col-sm-12">
    {!! Form::label('trans_date', 'Trans Date:') !!}
    <p>{{ $rtaFines->trans_date }}</p>
</div>
@endif

@if($vf('trans_code'))
<!-- Trans Code Field -->
<div class="col-sm-12">
    {!! Form::label('trans_code', 'Trans Code:') !!}
    <p>{{ $rtaFines->trans_code }}</p>
</div>
@endif

@if($vf('trip_date'))
<!-- Trip Date Field -->
<div class="col-sm-12">
    {!! Form::label('trip_date', 'Trip Date:') !!}
    <p>{{ $rtaFines->trip_date }}</p>
</div>
@endif

@if($vf('trip_time'))
<!-- Trip Time Field -->
<div class="col-sm-12">
    {!! Form::label('trip_time', 'Trip Time:') !!}
    <p>{{ $rtaFines->trip_time }}</p>
</div>
@endif

@if($vf('rider_id'))
<!-- Rider Id Field -->
<div class="col-sm-12">
    {!! Form::label('rider_id', 'Rider Id:') !!}
    <p>{{ $rtaFines->rider_id }}</p>
</div>
@endif

@if($vf('billing_month'))
<!-- Billing Month Field -->
<div class="col-sm-12">
    {!! Form::label('billing_month', 'Billing Month:') !!}
    <p>{{ $rtaFines->billing_month }}</p>
</div>
@endif

@if($vf('ticket_no'))
<!-- Ticket No Field -->
<div class="col-sm-12">
    {!! Form::label('ticket_no', 'Ticket No:') !!}
    <p>{{ $rtaFines->ticket_no }}</p>
</div>
@endif

@if($vf('bike_id'))
<!-- Bike Id Field -->
<div class="col-sm-12">
    {!! Form::label('bike_id', 'Bike Id:') !!}
    <p>{{ $rtaFines->bike_id }}</p>
</div>
@endif

@if($vf('plate_no'))
<!-- Plate No Field -->
<div class="col-sm-12">
    {!! Form::label('plate_no', 'Plate No:') !!}
    <p>{{ $rtaFines->plate_no }}</p>
</div>
@endif

@if($vf('detail'))
<!-- Detail Field -->
<div class="col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    <p>{{ $rtaFines->detail }}</p>
</div>
@endif

@if($vf('amount'))
<!-- Amount Field -->
<div class="col-sm-12">
    {!! Form::label('amount', 'Amount:') !!}
    <p>{{ \App\Helpers\Currency::format($rtaFines->amount, 2) }}</p>
</div>
@endif

@if($vf('service_charges'))
<!-- Service Charges Field -->
<div class="col-sm-12">
    {!! Form::label('service_charges', 'Service Charges:') !!}
    <p>{{ \App\Helpers\Currency::format($rtaFines->service_charges, 2) }}</p>
</div>
@endif

@if($vf('admin_fee'))
<!-- Admin Fee Field -->
<div class="col-sm-12">
    {!! Form::label('admin_fee', 'Admin Fee:') !!}
    <p>{{ \App\Helpers\Currency::format($rtaFines->admin_fee, 2) }}</p>
</div>
@endif

@if($vf('total_amount'))
<!-- Total Amount Field -->
<div class="col-sm-12">
    {!! Form::label('total_amount', 'Total Amount:') !!}
    <p>{{ \App\Helpers\Currency::format($rtaFines->total_amount, 2) }}</p>
</div>
@endif

@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $rtaFines->status }}</p>
</div>
@endif
