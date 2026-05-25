@php
$fieldKey = $field->field_key;
$spec = $field->resolvedInputSpec();
$assignGroup = $spec['assign_group'] ?? null;
$label = $field->resolvedLabel();
$required = (bool) ($spec['required'] ?? false);
$colClass = in_array($spec['type'] ?? '', ['textarea'], true) ? 'col-md-12' : 'col-md-3';
$groupClass = $assignGroup ? ' hidden-field assign-group-' . $assignGroup : '';
$wrapperId = 'assign-field-' . $fieldKey;
$branchScopedOptions = $branchScopedOptions ?? ($assignBranchScopedOptions ?? []);
$selectOpts = $field->resolvedSelectOptions($branchScopedOptions);
@endphp

@if($fieldKey === 'warehouse' && ($assignContext ?? 'active') === 'active')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="warehouse">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <input type="text" name="warehouse" class="form-control" readonly value="Active">
</div>
@elseif($fieldKey === 'warehouse' && ($assignContext ?? '') === 'change')
@php
$bikeModel = $bike ?? null;
$lockedWarehouse = $bikeModel && in_array((string) $bikeModel->warehouse, ['Absconded', 'Theft'], true);
@endphp
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="warehouse">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    @if($lockedWarehouse)
    <input type="text" class="form-control" name="warehouse" value="Return" readonly>
    @else
    <select class="form-control warehouse form-select select2" name="warehouse" id="warehouse">
        {!! App\Helpers\General::get_warehouse(1) !!}
    </select>
    @endif
</div>
@elseif($fieldKey === 'assign_type')
    @if($allowTypeSelection ?? false)
    <div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="assign_type">
        <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
        {!! Form::select('assign_type', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'assign_type', 'required' => $required]) !!}
    </div>
    @else
    <input type="hidden" name="assign_type" value="rider" onload="toggleAssignmentFields()" id="assign_type">
    @endif
@elseif($fieldKey === 'rider_id')
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="rider_select" data-assign-field="rider_id">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    {!! Form::select('rider_id', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'rider_id', 'required' => $required]) !!}
</div>
@elseif($fieldKey === 'rental_company_id')
<div class="{{ $colClass }} form-group hidden-field assign-group-company" id="company_select" data-assign-field="rental_company_id">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    {!! Form::select('rental_company_id', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'company_id', 'required' => $required]) !!}
</div>
@elseif($fieldKey === 'designation')
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="designation_field" data-assign-field="designation">
    <label>{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <input type="text" name="designation" id="designation" class="form-control" readonly value="{{ $selectedDesignation ?? '' }}">
</div>
@elseif($fieldKey === 'customer_id')
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="project_field" data-assign-field="customer_id">
    <label for="customer_id">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    {!! Form::select('customer_id', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'customer_id', 'required' => $required]) !!}
</div>
@elseif($fieldKey === 'visa_sponsor' && ($assignContext ?? '') === 'change')
@if(!empty($rider))
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="visa_sponsor">
    <label>{{ $label }}</label>
    <input type="text" name="visa_sponsor" class="form-control" readonly value="{{ $rider->visa_sponsor ?? 'N/A' }}">
</div>
@endif
@endif
