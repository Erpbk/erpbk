@php $vf = static fn (string $f): bool => field_visible('salik', $f); @endphp
@if($vf('trip_date'))
<!-- Trip Date Field -->
<div class="col-sm-12">
    {!! Form::label('trip_date', 'Trip Date:') !!}
    <p>{{ $salik->trip_date }}</p>
</div>
@endif
@if($vf('trip_time'))
<!-- Trip Time Field -->
<div class="col-sm-12">
    {!! Form::label('trip_time', 'Trip Time:') !!}
    <p>{{ $salik->trip_time }}</p>
</div>
@endif
@if($vf('toll_gate'))
<!-- Toll Gate Field -->
<div class="col-sm-12">
    {!! Form::label('toll_gate', 'Toll Gate:') !!}
    <p>{{ $salik->toll_gate }}</p>
</div>
@endif
@if($vf('direction'))
<!-- Direction Field -->
<div class="col-sm-12">
    {!! Form::label('direction', 'Direction:') !!}
    <p>{{ $salik->direction }}</p>
</div>
@endif
@if($vf('tag_number'))
<!-- Tag Number Field -->
<div class="col-sm-12">
    {!! Form::label('tag_number', 'Tag Number:') !!}
    <p>{{ $salik->tag_number }}</p>
</div>
@endif
@if($vf('plate'))
<!-- Plate Field -->
<div class="col-sm-12">
    {!! Form::label('plate', 'Plate:') !!}
    <p>{{ $salik->plate }}</p>
</div>
@endif
@if($vf('total_amount'))
<!-- Amount Field -->
<div class="col-sm-12">
    {!! Form::label('amount', 'Amount:') !!}
    <p>{{ \App\Helpers\Currency::format($salik->amount, 2) }}</p>
</div>
@endif
@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>
        @if(\App\Models\salik::normalizePaymentStatus($salik->status, !empty($salik->payment_voucher_id)) === 'paid')
        <span class="badge bg-success">Paid</span>
        @else
        <span class="badge bg-warning text-dark">Unpaid</span>
        @endif
    </p>
</div>
@endif
