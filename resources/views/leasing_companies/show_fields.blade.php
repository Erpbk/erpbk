<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $leasingCompanies->name }}</p>
</div>

<!-- Contact Person Field -->
<div class="col-sm-12">
    {!! Form::label('contact_person', 'Contact Person:') !!}
    <p>{{ $leasingCompanies->contact_person }}</p>
</div>

<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $leasingCompanies->contact_number }}</p>
</div>

<!-- Detail Field -->
<div class="col-sm-12">
    {!! Form::label('detail', 'Detail:') !!}
    <p>{{ $leasingCompanies->detail }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $leasingCompanies->status }}</p>
</div>

