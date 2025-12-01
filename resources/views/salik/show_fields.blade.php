<!-- Trip Date Field -->
<div class="col-sm-12">
    {!! Form::label('trip_date', 'Trip Date:') !!}
    <p>{{ $salik->trip_date }}</p>
</div>
<!-- Trip Time Field -->
<div class="col-sm-12">
    {!! Form::label('trip_time', 'Trip Time:') !!}
    <p>{{ $salik->trip_time }}</p>
</div>
<!-- Toll Gate Field -->
<div class="col-sm-12">
    {!! Form::label('toll_gate', 'Toll Gate:') !!}
    <p>{{ $salik->toll_gate }}</p>
</div>
<!-- Direction Field -->
<div class="col-sm-12">
    {!! Form::label('direction', 'Direction:') !!}
    <p>{{ $salik->direction }}</p>
</div>
<!-- Tag Number Field -->
<div class="col-sm-12">
    {!! Form::label('tag_number', 'Tag Number:') !!}
    <p>{{ $salik->tag_number }}</p>
</div>
<!-- Plate Field -->
<div class="col-sm-12">
    {!! Form::label('plate', 'Plate:') !!}
    <p>{{ $salik->plate }}</p>
</div>
<!-- Amount Field -->
<div class="col-sm-12">
    {!! Form::label('amount', 'Amount:') !!}
    <p>AED {{ number_format($salik->amount, 2) }}</p>
</div>
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $salik->status }}</p>
</div>