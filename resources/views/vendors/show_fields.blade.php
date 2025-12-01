<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $vendors->name }}</p>
</div>

<!-- Email Field -->
<div class="col-sm-12">
    {!! Form::label('email', 'Email:') !!}
    <p>{{ $vendors->email }}</p>
</div>

<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $vendors->contact_number }}</p>
</div>

<!-- Address Field -->
<div class="col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    <p>{{ $vendors->address }}</p>
</div>

<!-- Tax Number Field -->
<div class="col-sm-12">
    {!! Form::label('tax_number', 'Tax Number:') !!}
    <p>{{ $vendors->tax_number }}</p>
</div>

<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $vendors->status }}</p>
</div>

<!-- Account Id Field -->
<div class="col-sm-12">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $vendors->account_id }}</p>
</div>

<!-- Created By Field -->
<div class="col-sm-12">
    {!! Form::label('created_by', 'Created By:') !!}
    <p>{{ $vendors->created_by }}</p>
</div>

<!-- Updated By Field -->
<div class="col-sm-12">
    {!! Form::label('updated_by', 'Updated By:') !!}
    <p>{{ $vendors->updated_by }}</p>
</div>

