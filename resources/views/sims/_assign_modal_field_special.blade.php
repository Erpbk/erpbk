@php
$fieldKey = $field->field_key;
$spec = $field->resolvedInputSpec();
$assignGroup = $spec['assign_group'] ?? null;
$label = $field->resolvedLabel();
$required = (bool) ($spec['required'] ?? false);
$isReadonly = !empty($spec['readonly']);
$colClass = in_array($spec['type'] ?? '', ['textarea'], true) ? 'col-md-12' : 'col-sm-6';
$groupClass = $assignGroup ? ' d-none assignee-field assignee-field-' . $assignGroup : '';
$wrapperId = 'assign-field-' . $fieldKey;
$branchOpts = $branchScopedOptions ?? [];
$simsModel = $sims ?? null;
$defaultAssigneeType = old('assignee_type', ($simsModel->assign_type ?? 'rider') === 'employee' ? 'employee' : 'rider');
$riderSelected = old('assign_to', ($simsModel->assign_type ?? 'rider') === 'rider' ? $simsModel->assign_to : null);
$employeeSelected = old('assign_to', ($simsModel->assign_type ?? '') === 'employee' ? $simsModel->assign_to : null);
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
<div class="{{ $colClass }} form-group" id="{{ $wrapperId }}" data-assign-field="assignee_type">
    <label class="d-block mb-2">{{ $label }}@if($required)<span class="text-danger">*</span>@endif</label>
    <div class="btn-group w-100" role="group" aria-label="Assignee type">
        <input type="radio" class="btn-check" name="assignee_type" id="assignee_type_rider" value="rider"
            {{ $defaultAssigneeType === 'rider' ? 'checked' : '' }} autocomplete="off" @if($required) required @endif>
        <label class="btn btn-outline-primary" for="assignee_type_rider">Rider</label>

        <input type="radio" class="btn-check" name="assignee_type" id="assignee_type_employee" value="employee"
            {{ $defaultAssigneeType === 'employee' ? 'checked' : '' }} autocomplete="off">
        <label class="btn btn-outline-primary" for="assignee_type_employee">Employee</label>
    </div>
</div>
@elseif($fieldKey === 'assign_to_rider')
@php $riderOpts = $field->resolvedSelectOptions($branchOpts) ?: ($branchOpts['assign_to_rider'] ?? []); @endphp
<div class="{{ $colClass }} form-group assignee-field assignee-field-rider{{ $defaultAssigneeType === 'employee' ? ' d-none' : '' }}" id="{{ $wrapperId }}" data-assign-field="assign_to_rider">
    {!! Form::label('assign_to', $label . ':') !!}
    {!! Form::select('assign_to', $riderOpts, $riderSelected, [
        'class' => 'form-select select2 assignee-select',
        'id' => 'assign_to_rider',
        'data-assignee' => 'rider',
        'required' => $required && $defaultAssigneeType === 'rider',
    ]) !!}
</div>
@elseif($fieldKey === 'assign_to_employee')
@php $empOpts = $field->resolvedSelectOptions($branchOpts) ?: ($branchOpts['assign_to_employee'] ?? []); @endphp
<div class="{{ $colClass }} form-group assignee-field assignee-field-employee{{ $defaultAssigneeType === 'rider' ? ' d-none' : '' }}" id="{{ $wrapperId }}" data-assign-field="assign_to_employee">
    {!! Form::label('assign_to_employee', $label . ':') !!}
    <select class="form-select select2 assignee-select" id="assign_to_employee" data-assignee="employee"
        name="{{ $defaultAssigneeType === 'employee' ? 'assign_to' : '' }}"
        @if($defaultAssigneeType !== 'employee') disabled @endif
        @if($required && $defaultAssigneeType === 'employee') required @endif>
        @foreach($empOpts as $empId => $empLabel)
        <option value="{{ $empId }}" @selected((string) $employeeSelected === (string) $empId)>{{ $empLabel }}</option>
        @endforeach
    </select>
</div>
@endif
