@php $vf = static fn (string $f): bool => field_visible('item', $f); @endphp
@if($vf('name'))
<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $items->name }}</p>
</div>
@endif

@if($vf('detail'))
<!-- Detail Field -->
<div class="col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    <p>{{ $items->detail }}</p>
</div>
@endif

@if($vf('price'))
<!-- Price Field -->
<div class="col-sm-12">
    {!! Form::label('price', 'Price:') !!}
    <p>{{ $items->price }}</p>
</div>
@endif

@if($vf('cost'))
<!-- Cost Field -->
<div class="col-sm-12">
    {!! Form::label('cost', 'Cost:') !!}
    <p>{{ $items->cost }}</p>
</div>
@endif

@if($vf('vat'))
<!-- Vat Field -->
<div class="col-sm-12">
    {!! Form::label('vat', 'Vat:') !!}
    <p>{{ $items->vat }}</p>
</div>
@endif

@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $items->status }}</p>
</div>
@endif
