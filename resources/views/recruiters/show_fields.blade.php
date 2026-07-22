@php $vf = static fn (string $f): bool => field_visible('recruiter', $f); @endphp
@if($vf('name'))
<!-- Name Field -->
<div class="col-sm-12">
    {!! Form::label('name', 'Name:') !!}
    <p>{{ $recruiters->name }}</p>
</div>
@endif

@if($vf('email'))
<!-- Email Field -->
<div class="col-sm-12">
    {!! Form::label('email', 'Email:') !!}
    <p>{{ $recruiters->email }}</p>
</div>
@endif

@if($vf('contact_number'))
<!-- Contact Number Field -->
<div class="col-sm-12">
    {!! Form::label('contact_number', 'Contact Number:') !!}
    <p>{{ $recruiters->contact_number }}</p>
</div>
@endif

@if($vf('address'))
<!-- Address Field -->
<div class="col-sm-12">
    {!! Form::label('address', 'Address:') !!}
    <p>{{ $recruiters->address }}</p>
</div>
@endif

@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $recruiters->status == 1 ? 'Active' : 'Inactive' }}</p>
</div>
@endif

<!-- Created At Field -->
<div class="col-sm-12">
    {!! Form::label('created_at', 'Created At:') !!}
    <p>{{ $recruiters->created_at }}</p>
</div>

<!-- Updated At Field -->
<div class="col-sm-12">
    {!! Form::label('updated_at', 'Updated At:') !!}
    <p>{{ $recruiters->updated_at }}</p>
</div>
