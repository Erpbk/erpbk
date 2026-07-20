@php $vf = static fn (string $f): bool => field_visible('vendor', $f); @endphp
@if($vf('name'))
<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $vendors->name }}</p>
</div>
@endif

@if($vf('email'))
<!-- Email Field -->
<div class="col-sm-12">
    {!! Form::label('email', 'Email:') !!}
    <p>{{ $vendors->email }}</p>
</div>
@endif

@if($vf('contact_number'))
<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $vendors->contact_number }}</p>
</div>
@endif

@if($vf('address'))
<!-- Address Field -->
<div class="col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    <p>{{ $vendors->address }}</p>
</div>
@endif

@if($vf('tax_number'))
<!-- Tax Number Field -->
<div class="col-sm-12">
    {!! Form::label('tax_number', 'Tax Number:') !!}
    <p>{{ $vendors->tax_number }}</p>
</div>
@endif

@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $vendors->status }}</p>
</div>
@endif

@if($vf('account_id'))
<!-- Account Id Field -->
<div class="col-sm-12">
    {!! Form::label('account_id', 'Account Id:') !!}
    <p>{{ $vendors->account_id }}</p>
</div>
@endif

@if($vf('created_by'))
<!-- Created By Field -->
<div class="col-sm-12">
    {!! Form::label('created_by', 'Created By:') !!}
    <p>{{ $vendors->created_by }}</p>
</div>
@endif

@if($vf('updated_by'))
<!-- Updated By Field -->
<div class="col-sm-12">
    {!! Form::label('updated_by', 'Updated By:') !!}
    <p>{{ $vendors->updated_by }}</p>
</div>
@endif
