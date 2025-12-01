<!-- Bike Id Field -->
<div class="col-sm-12">
    {!! Form::label('bike_id', 'Bike Id:') !!}
    <p>{{ $bikeHistory->bike_id }}</p>
</div>

<!-- Rider Id Field -->
<div class="col-sm-12">
    {!! Form::label('rider_id', 'Rider Id:') !!}
    <p>{{ $bikeHistory->rider_id }}</p>
</div>

<!-- Notes Field -->
<div class="col-sm-12">
    {!! Form::label('notes', 'Notes:') !!}
    <p>{{ $bikeHistory->notes }}</p>
</div>

<!-- Note Date Field -->
<div class="col-sm-12">
    {!! Form::label('note_date', 'Note Date:') !!}
    <p>{{ $bikeHistory->note_date }}</p>
</div>

<!-- Warehouse Field -->
<div class="col-sm-12">
    {!! Form::label('warehouse', 'Warehouse:') !!}
    <p>{{ $bikeHistory->warehouse }}</p>
</div>

<!-- Contract Field -->
<div class="col-sm-12">
    {!! Form::label('contract', 'Contract:') !!}
    <p>{{ $bikeHistory->contract }}</p>
</div>

