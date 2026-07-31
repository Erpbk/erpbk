@php
$simEntity = 'sim';
$simFieldRequired = static function (string $key) use ($simEntity) {
    return field_required($simEntity, $key);
};
$reqAttrs = static function (string $key) use ($simEntity, $simFieldRequired) {
    $attrs = [];
    if ($simFieldRequired($key) && field_editable($simEntity, $key)) {
        $attrs['required'] = true;
    }
    return $attrs + field_lock($simEntity, $key);
};
$reqSelectAttrs = static function (string $key) use ($simEntity, $simFieldRequired) {
    $attrs = [];
    if ($simFieldRequired($key) && field_editable($simEntity, $key)) {
        $attrs['required'] = true;
    }
    return $attrs + field_lock($simEntity, $key, 'select');
};
$reqLabel = static fn (string $key) => $simFieldRequired($key) ? ['class' => 'required fw-bold'] : [];
@endphp

<div data-rfp-entity="sim">
<!-- Number Field -->
@fieldVisible('sim', 'number')
<div class="form-group col-sm-6">
    {!! Form::label('number', 'Number' . ($simFieldRequired('number') ? ':' : ''), $reqLabel('number')) !!}
    {!! Form::text('number', old('number', $sims->number ?? ''), ['class' => 'form-control', 'readonly' => isset($sims)] + $reqAttrs('number')) !!}
</div>
@endfieldVisible

<!-- Emi Field -->
@fieldVisible('sim', 'emi')
<div class="form-group col-sm-6">
    {!! Form::label('emi', 'Emi' . ($simFieldRequired('emi') ? ':' : ''), $reqLabel('emi')) !!}
    {!! Form::text('emi', old('emi', $sims->emi ?? ''), ['class' => 'form-control'] + $reqAttrs('emi')) !!}
</div>
@endfieldVisible

<!-- Company Field -->
@fieldVisible('sim', 'company')
<div class="form-group col-sm-6">
    {!! Form::label('company', 'Company' . ($simFieldRequired('company') ? ':' : ''), $reqLabel('company')) !!}
    {!! Form::select('company', \App\Models\SimCompany::dropdown(), old('company', $sims->company ?? ''), ['class' => 'form-control select2'] + $reqSelectAttrs('company')) !!}
</div>
@endfieldVisible

<!-- Vendor Field -->
@fieldVisible('sim', 'vendor')
<div class="form-group col-sm-6">
    {!! Form::label('vendor', 'Vendor' . ($simFieldRequired('vendor') ? ':' : ''), $reqLabel('vendor')) !!}
    {!! Form::select('vendor', \App\Models\Customers::dropdown(), old('vendor', $sims->vendor ?? ''), ['class' => 'form-control select2'] + $reqSelectAttrs('vendor')) !!}
</div>
@endfieldVisible

<!-- Branch Field -->
@fieldVisible('sim', 'branch_id')
<div class="form-group col-sm-6">
    {!! Form::label('branch_id', 'Branch' . ($simFieldRequired('branch_id') ? ':' : ''), $reqLabel('branch_id')) !!}
    {!! Form::select('branch_id', auth()->user()->branchDropdown(true), old('branch_id', $sims->branch_id ?? null), ['class' => 'form-select select2'] + $reqSelectAttrs('branch_id')) !!}
</div>
@endfieldVisible
</div>
