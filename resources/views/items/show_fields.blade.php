<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $items->name }}</p>
</div>

<!-- Detail Field -->
<div class="col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    <p>{{ $items->detail }}</p>
</div>

<!-- Price Field -->
<div class="col-sm-12">
    {!! Form::label('price', 'Price:') !!}
    <p>{{ $items->price }}</p>
</div>

<!-- Cost Field -->
<div class="col-sm-12">
    {!! Form::label('cost', 'Cost:') !!}
    <p>{{ $items->cost }}</p>
</div>

<!-- Vat Field -->
<div class="col-sm-12">
    {!! Form::label('vat', 'Vat:') !!}
    <p>{{ $items->vat }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $items->status }}</p>
</div>

