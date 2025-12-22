<!-- Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('number', 'Number:') !!}
    {!! Form::text('number', old('number', $sims->number ?? ''), ['class' => 'form-control', 'readonly' => isset($sims) ]) !!}
</div>

<!-- Company Field -->
<div class="form-group col-sm-6">
    {!! Form::label('company', 'Company:') !!}
    {!! Form::text('company', old('company', $sims->company ?? ''), ['class' => 'form-control', ]) !!}
</div>

{{-- <!-- Assign To Field -->
<!-- Created By Field -->
<div class="form-group col-sm-6">
    {!! Form::label('created_by', 'Created By:') !!}
    {!! Form::number('created_by', null, ['class' => 'form-control']) !!}
</div>

<!-- Updated By Field -->
<div class="form-group col-sm-6">
    {!! Form::label('updated_by', 'Updated By:') !!}
    {!! Form::number('updated_by', null, ['class' => 'form-control']) !!}
</div>

<!-- Fleet Supervisor Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fleet_supervisor', 'Fleet Supervisor:') !!}
    {!! Form::text('fleet_supervisor', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
</div>

<!-- Status Field -->
<div class="form-group col-sm-6">
    {!! Form::label('status', 'Status:') !!}
    {!! Form::text('status', null, ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]) !!}
</div> --}}

<!-- Emi Field -->
<div class="form-group col-sm-6">
    {!! Form::label('emi', 'Emi:') !!}
    {!! Form::text('emi', old('emi', $sims->emi ?? ''), ['class' => 'form-control']) !!}
</div>

<!-- Vendor Field -->
<div class="form-group col-sm-6">
    {!! Form::label('vendor', 'Vendor:') !!}
    {!! Form::select('vendor', \App\Models\Vendors::dropdown(), old('vendor', $sims->vendor ?? ''), ['class' => 'form-control select2']) !!}
</div>

