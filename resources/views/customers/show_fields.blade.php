<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $customers->name }}</p>
</div>

<!-- Company Name Field -->
<div class="col-sm-12">
    {!! Form::label('company_name', 'Company Name:') !!}
    <p>{{ $customers->company_name }}</p>
</div>

<!-- Company Email Field -->
<div class="col-sm-12">
    {!! Form::label('company_email', 'Company Email:') !!}
    <p>{{ $customers->company_email }}</p>
</div>

<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $customers->contact_number }}</p>
</div>

<!-- Address Field -->
<div class="col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    <p>{{ $customers->address }}</p>
</div>

<!-- Tax Number Field -->
<div class="col-sm-12">
    {!! Form::label('tax_number', 'Tax Number:') !!}
    <p>{{ $customers->tax_number }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $customers->status }}</p>
</div>

<!-- Tax Percentage Field -->
<div class="col-sm-12">
    {!! Form::label('tax_percentage', 'Tax Percentage:') !!}
    <p>{{ $customers->tax_percentage }}</p>
</div>

