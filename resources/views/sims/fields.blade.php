@php
use App\Support\ModuleFieldSettings;
$simFieldRequired = static fn (string $key) => ModuleFieldSettings::isSchemaFieldRequired('sims', $key);
$reqAttrs = static fn (string $key) => $simFieldRequired($key) ? ['required' => true] : [];
$reqLabel = static fn (string $key) => $simFieldRequired($key) ? ['class' => 'required fw-bold'] : [];
@endphp

<!-- Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('number', 'Number' . ($simFieldRequired('number') ? ':' : ''), $reqLabel('number')) !!}
    {!! Form::text('number', old('number', $sims->number ?? ''), ['class' => 'form-control', 'readonly' => isset($sims)] + $reqAttrs('number')) !!}
</div>

<!-- Emi Field -->
<div class="form-group col-sm-6">
    {!! Form::label('emi', 'Emi' . ($simFieldRequired('emi') ? ':' : ''), $reqLabel('emi')) !!}
    {!! Form::text('emi', old('emi', $sims->emi ?? ''), ['class' => 'form-control'] + $reqAttrs('emi')) !!}
</div>

<!-- Vendor Field -->
<div class="form-group col-sm-6">
    {!! Form::label('vendor', 'Company' . ($simFieldRequired('vendor') ? ':' : ''), $reqLabel('vendor')) !!}
    {!! Form::select('vendor', \App\Models\SimCompany::dropdown(), old('vendor', $sims->vendor ?? ''), ['class' => 'form-control select2'] + $reqAttrs('vendor')) !!}
</div>

<!-- Branch Field -->
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Branch' . ($simFieldRequired('branch_id') ? ':' : ''), $reqLabel('branch_id')) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(), old('branch_id', $sims->branch_id ?? null), ['class' => 'form-select select2'] + $reqAttrs('branch_id')) !!}
</div>
