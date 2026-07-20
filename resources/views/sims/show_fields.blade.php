@php $vf = static fn (string $f): bool => field_visible('sim', $f); @endphp
@if($vf('number'))
<!-- Number Field -->
<div class="col-sm-12">
    {!! Form::label('number', 'Number:') !!}
    <p>{{ $sims->number }}</p>
</div>
@endif

@if($vf('company'))
<!-- Company Field -->
<div class="col-sm-12">
    {!! Form::label('company', 'Company:') !!}
    <p>{{ $sims->company }}</p>
</div>
@endif

@if($vf('assign_to'))
<!-- Assign To Field -->
<div class="col-sm-12">
    {!! Form::label('assign_to', 'Assign To:') !!}
    <p>{{ $sims->assign_to }}</p>
</div>
@endif

@if($vf('created_by'))
<!-- Created By Field -->
<div class="col-sm-12">
    {!! Form::label('created_by', 'Created By:') !!}
    <p>{{ $sims->created_by }}</p>
</div>
@endif

@if($vf('updated_by'))
<!-- Updated By Field -->
<div class="col-sm-12">
    {!! Form::label('updated_by', 'Updated By:') !!}
    <p>{{ $sims->updated_by }}</p>
</div>
@endif

@if($vf('fleet_supervisor'))
<!-- Fleet Supervisor Field -->
<div class="col-sm-12">
    {!! Form::label('fleet_supervisor', 'Fleet Supervisor:') !!}
    <p>{{ $sims->fleet_supervisor }}</p>
</div>
@endif

@if($vf('status'))
<!-- Status Field -->
<div class="col-sm-12">
    {!! Form::label('status', 'Status:') !!}
    <p>{{ $sims->status }}</p>
</div>
@endif

@if($vf('emi'))
<!-- Emi Field -->
<div class="col-sm-12">
    {!! Form::label('emi', 'Emi:') !!}
    <p>{{ $sims->emi }}</p>
</div>
@endif

@if($vf('vendor'))
<!-- Vendor Field -->
<div class="col-sm-12">
    {!! Form::label('vendor', 'Vendor:') !!}
    <p>{{ $sims->vendor }}</p>
</div>
@endif
