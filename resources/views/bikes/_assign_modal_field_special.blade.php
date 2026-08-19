@php
$fieldKey = $field->field_key;
$spec = $field->resolvedInputSpec();
$assignGroup = $spec['assign_group'] ?? null;
$label = $field->resolvedLabel();
$required = (bool) ($spec['required'] ?? false);
$requiredMark = $required ? '<span class="text-danger">*</span>' : '';
$colClass = in_array($spec['type'] ?? '', ['textarea'], true) ? 'col-md-12' : 'col-md-3';
$groupClass = $assignGroup ? ' hidden-field assign-group-' . $assignGroup : '';
$wrapperId = 'assign-field-' . $fieldKey;
$branchScopedOptions = $branchScopedOptions ?? ($assignBranchScopedOptions ?? []);
$selectOpts = $field->resolvedSelectOptions($branchScopedOptions);
$garageOpts = $branchScopedOptions['garage_customer_id'] ?? \App\Models\BikeRentCompany::garageAssignDropdown();
$assignContext = $assignContext ?? 'active';
$bikeModel = $bike ?? null;
$lockedWarehouse = $bikeModel && in_array((string) $bikeModel->warehouse, ['Absconded', 'Theft'], true);
$allowTypeSelection = (bool) ($allowTypeSelection ?? false);
$defaultAssignType = $defaultAssignType ?? '';
$assignTargets = $assignTargets ?? [];
$assignTypeLabels = $assignTypeLabels ?? \App\Support\CompanyModuleVisibility::bikeAssignTypeLabels();
$hasRider = !empty($rider);
@endphp

@if($fieldKey === 'warehouse' && $assignContext === 'active')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="warehouse">
    <label>{{ $label }}{!! $requiredMark !!}</label>
    <input type="text" name="warehouse" class="form-control" readonly value="Active">
</div>
@elseif($fieldKey === 'warehouse' && $assignContext === 'change')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="warehouse">
    <label>{{ $label }}{!! $requiredMark !!}</label>
    @if($lockedWarehouse)
    <input type="text" class="form-control" name="warehouse" value="Return" readonly>
    @else
    <select class="form-control warehouse form-select select2" name="warehouse" id="warehouse">
        {!! App\Helpers\General::get_warehouse(1) !!}
    </select>
    @endif
</div>
@elseif($fieldKey === 'assign_type')
    @if($allowTypeSelection)
    <div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="assign_type">
        <label>{{ $label }}{!! $requiredMark !!}</label>
        {!! Form::select('assign_type', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'assign_type', 'required' => $required]) !!}
    </div>
    @elseif($defaultAssignType !== '')
    <input type="hidden" name="assign_type" value="{{ $defaultAssignType }}" id="assign_type">
    @endif
@elseif($fieldKey === 'rider_id')
@if(in_array('rider', $assignTargets, true))
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="rider_select" data-assign-field="rider_id">
    <label>{{ \App\Support\CompanyModuleVisibility::customizedMenuLabel('riders') ?? $label }}{!! $requiredMark !!}</label>
    {!! Form::select('rider_id', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'rider_id', 'required' => $required]) !!}
</div>
@endif
@elseif($fieldKey === 'rental_company_id')
@if(in_array('rental', $assignTargets, true))
<div class="{{ $colClass }} form-group hidden-field assign-group-rental" id="rental_customer_select" data-assign-field="rental_company_id">
    <label>Rental customer{!! $requiredMark !!}</label>
    {!! Form::select('rental_company_id', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'rental_company_id', 'required' => $required, 'disabled' => true]) !!}
</div>
@endif
@if(in_array('garage', $assignTargets, true))
<div class="{{ $colClass }} form-group hidden-field assign-group-garage" id="garage_customer_select" data-assign-field="garage_customer_id">
    <label>{{ $assignTypeLabels['garage'] ?? 'Garage customer' }}{!! $requiredMark !!}</label>
    {!! Form::select('rental_company_id', $garageOpts, '', ['class' => 'form-select select2', 'id' => 'garage_company_id', 'required' => $required, 'disabled' => true]) !!}
</div>
@endif
@elseif($fieldKey === 'designation')
@if(in_array('rider', $assignTargets, true))
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="designation_field" data-assign-field="designation">
    <label>{{ $label }}{!! $requiredMark !!}</label>
    <input type="text" name="designation" id="designation" class="form-control" readonly value="{{ $selectedDesignation ?? '' }}">
</div>
@endif
@elseif($fieldKey === 'customer_id')
@if(in_array('rider', $assignTargets, true))
<div class="{{ $colClass }} form-group hidden-field assign-group-rider" id="project_field" data-assign-field="customer_id">
    <label for="customer_id">{{ $label }}{!! $requiredMark !!}</label>
    {!! Form::select('customer_id', $selectOpts, '', ['class' => 'form-select select2', 'id' => 'customer_id', 'required' => $required]) !!}
</div>
@endif
@elseif($fieldKey === 'visa_sponsor' && $assignContext === 'change' && $hasRider)
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="visa_sponsor">
    <label>{{ $label }}</label>
    <input type="text" name="visa_sponsor" class="form-control" readonly value="{{ $rider->visa_sponsor ?? 'N/A' }}">
</div>
@endif
