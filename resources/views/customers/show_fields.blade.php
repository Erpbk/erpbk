@php $vf = static fn (string $f): bool => field_visible('customer', $f); @endphp
@if($vf('name'))
<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $customers->name }}</p>
</div>
@endif

@if($vf('company_name'))
<!-- Company Name Field -->
<div class="col-sm-12">
    {!! Form::label('company_name', 'Company Name:') !!}
    <p>{{ $customers->company_name }}</p>
</div>
@endif

@if($vf('company_email'))
<!-- Company Email Field -->
<div class="col-sm-12">
    {!! Form::label('company_email', 'Company Email:') !!}
    <p>{{ $customers->company_email }}</p>
</div>
@endif

@if($vf('contact_number'))
<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $customers->contact_number }}</p>
</div>
@endif

@if($vf('address'))
<!-- Address Field -->
<div class="col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    <p>{{ $customers->address }}</p>
</div>
@endif

@if($vf('tax_number'))
<!-- Tax Number Field -->
<div class="col-sm-12">
    {!! Form::label('tax_number', 'Tax Number:') !!}
    <p>{{ $customers->tax_number }}</p>
</div>
@endif

@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $customers->status }}</p>
</div>
@endif

@if($vf('tax_percentage'))
<!-- Tax Percentage Field -->
<div class="col-sm-12">
    {!! Form::label('tax_percentage', 'Tax Percentage:') !!}
    <p>{{ $customers->tax_percentage }}</p>
</div>
@endif
