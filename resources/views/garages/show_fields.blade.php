@php $vf = static fn (string $f): bool => field_visible('garage', $f); @endphp
@if($vf('garage_type'))
<!-- Type -->
<div class="col-sm-12">
    {!! Form::label('garage_type', 'Type:') !!}
    <p>{{ ($garages->garage_type ?? 'external') === 'internal' ? 'Internal' : 'External' }}</p>
</div>
@endif

@if($vf('name'))
<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $garages->name }}</p>
</div>
@endif

@if($vf('contact_person'))
<!-- Contact Person Field -->
<div class="col-sm-12">
    {!! Form::label('contact_person', 'Contact Person:') !!}
    <p>{{ $garages->contact_person }}</p>
</div>
@endif

@if($vf('address'))
<!-- Address Field -->
<div class="col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    <p>{{ $garages->address }}</p>
</div>
@endif

@if($vf('contact_number'))
<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $garages->contact_number }}</p>
</div>
@endif

@if($vf('detail'))
<!-- Detail Field -->
<div class="col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    <p>{{ $garages->detail }}</p>
</div>
@endif
