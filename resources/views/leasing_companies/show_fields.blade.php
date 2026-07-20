@php $vf = static fn (string $f): bool => field_visible('leasing', $f); @endphp
@if($vf('name'))
<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $leasingCompanies->name }}</p>
</div>
@endif

@if($vf('contact_person'))
<!-- Contact Person Field -->
<div class="col-sm-12">
    {!! Form::label('contact_person', 'Contact Person:') !!}
    <p>{{ $leasingCompanies->contact_person }}</p>
</div>
@endif

@if($vf('contact_number'))
<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $leasingCompanies->contact_number }}</p>
</div>
@endif

@if($vf('detail'))
<!-- Detail Field -->
<div class="col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    <p>{{ $leasingCompanies->detail }}</p>
</div>
@endif

@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $leasingCompanies->status }}</p>
</div>
@endif
