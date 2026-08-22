@php
$fieldKey = $field->field_key;
$spec = $field->resolvedInputSpec();
$label = $field->resolvedLabel();
$required = (bool) ($spec['required'] ?? false);
$isReadonly = !empty($spec['readonly']);
$colClass = in_array($spec['type'] ?? '', ['textarea'], true) ? 'col-md-12' : 'col-sm-6';
$wrapperId = 'assign-field-' . $fieldKey;
$branchOpts = $branchScopedOptions ?? [];
$simsModel = $sims ?? null;
$assignTargets = $assignTargets ?? \App\Support\CompanyModuleVisibility::simAssignTargets();
$allowTypeSelection = (bool) ($allowTypeSelection ?? (count($assignTargets) >= 2));
$forcedType = count($assignTargets) === 1 ? $assignTargets[0] : null;
$defaultAssigneeType = old(
    'assignee_type',
    $forcedType ?? (($simsModel->assign_type ?? 'rider') === 'employee' ? 'employee' : 'rider')
);
$assignToLabel = 'Assign to';
$riderSelected = old('assign_to', ($simsModel->assign_type ?? 'rider') === 'rider' ? $simsModel->assign_to : null);
$employeeSelected = old('assign_to', ($simsModel->assign_type ?? '') === 'employee' ? $simsModel->assign_to : null);
$riderOpts = $branchOpts['assign_to_rider'] ?? [];
$empOpts = $branchOpts['assign_to_employee'] ?? [];
$riderHasChoices = count(array_filter(array_keys($riderOpts), fn ($k) => (string) $k !== '')) > 0;
$employeeHasChoices = count(array_filter(array_keys($empOpts), fn ($k) => (string) $k !== '')) > 0;
@endphp

@if($fieldKey === 'number')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="number">
    {!! Form::label('number', $label . ':') !!}
    {!! Form::text('number', old('number', $simsModel->number ?? ''), ['class' => 'form-control', 'readonly' => true]) !!}
</div>
@elseif($fieldKey === 'assign_to_display')
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="assign_to_display">
    {!! Form::label('assign_to_display', $label . ':') !!}
    {!! Form::text('assign_to_display', $assignee_name ?? 'N/A', ['class' => 'form-control', 'readonly' => true]) !!}
</div>
@elseif($fieldKey === 'assignee_type')
    @if($allowTypeSelection)
    <div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="assignee_type">
        <label class="d-block mb-2">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
        <div class="btn-group w-100" role="group" aria-label="Assignee type">
            @if(in_array('rider', $assignTargets, true))
            <input type="radio" class="btn-check" name="assignee_type" id="assignee_type_rider" value="rider"
                {{ $defaultAssigneeType === 'rider' ? 'checked' : '' }} autocomplete="off" @if($required) required @endif>
            <label class="btn btn-outline-primary" for="assignee_type_rider">Rider</label>
            @endif

            @if(in_array('employee', $assignTargets, true))
            <input type="radio" class="btn-check" name="assignee_type" id="assignee_type_employee" value="employee"
                {{ $defaultAssigneeType === 'employee' ? 'checked' : '' }} autocomplete="off">
            <label class="btn btn-outline-primary" for="assignee_type_employee">Employee</label>
            @endif
        </div>
    </div>
    @elseif($defaultAssigneeType !== '')
    <input type="hidden" name="assignee_type" value="{{ $defaultAssigneeType }}" id="assignee_type">
    @endif
@elseif($fieldKey === 'assign_to_rider')
@if(in_array('rider', $assignTargets, true))
<div class="{{ $colClass }} form-group assignee-field assignee-field-rider{{ $defaultAssigneeType === 'employee' ? ' d-none' : '' }}" id="{{ $wrapperId }}" data-assign-field="assign_to_rider">
    {!! Form::label('assign_to_rider', $assignToLabel . ':') !!}
    <select class="form-select select2 assignee-select" id="assign_to_rider" data-assignee="rider"
        name="{{ $defaultAssigneeType === 'rider' ? 'assign_to' : '' }}"
        @if($defaultAssigneeType !== 'rider') disabled @endif
        @if($required && $defaultAssigneeType === 'rider') required @endif>
        @foreach($riderOpts as $riderId => $riderLabel)
        <option value="{{ $riderId }}" @selected((string) $riderSelected === (string) $riderId)>{{ $riderLabel }}</option>
        @endforeach
    </select>
    @if(!$riderHasChoices)
    <small class="text-warning d-block mt-1">No riders found.</small>
    @endif
</div>
@endif
@elseif($fieldKey === 'assign_to_employee')
@if(in_array('employee', $assignTargets, true))
<div class="{{ $colClass }} form-group assignee-field assignee-field-employee{{ $defaultAssigneeType === 'rider' ? ' d-none' : '' }}" id="{{ $wrapperId }}" data-assign-field="assign_to_employee">
    {!! Form::label('assign_to_employee', $assignToLabel . ':') !!}
    <select class="form-select select2 assignee-select" id="assign_to_employee" data-assignee="employee"
        name="{{ $defaultAssigneeType === 'employee' ? 'assign_to' : '' }}"
        @if($defaultAssigneeType !== 'employee') disabled @endif
        @if($required && $defaultAssigneeType === 'employee') required @endif>
        @foreach($empOpts as $empId => $empLabel)
        <option value="{{ $empId }}" @selected((string) $employeeSelected === (string) $empId)>{{ $empLabel }}</option>
        @endforeach
    </select>
    @if(!$employeeHasChoices)
    <small class="text-warning d-block mt-1">No employees found.</small>
    @endif
</div>
@endif
@endif
