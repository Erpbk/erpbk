@php
$assignFields = $assignFields ?? \App\Support\SimAssignFields::assignModalFields('assign');
$branchScopedOptions = $branchScopedOptions ?? [
    'assign_to_rider' => $riders ?? [],
    'assign_to_employee' => $employees ?? [],
];
$assignTargets = $assignTargets ?? \App\Support\CompanyModuleVisibility::simAssignTargets();
$allowTypeSelection = (bool) ($allowTypeSelection ?? (count($assignTargets) >= 2));
$defaultAssigneeType = $defaultAssigneeType ?? (count($assignTargets) === 1 ? $assignTargets[0] : 'rider');
$assignFormLocked = (bool) ($assignFormLocked ?? ($assignTargets === []));
$assignHint = match (true) {
    in_array('rider', $assignTargets, true) && in_array('employee', $assignTargets, true) => 'Showing all riders and employees (including inactive).',
    in_array('rider', $assignTargets, true) => 'Showing all riders (including inactive).',
    in_array('employee', $assignTargets, true) => 'Showing all employees (including inactive).',
    default => '',
};
$inlineFields = $assignFields->filter(function ($f) {
    if (($f->field_key ?? '') === 'notes') {
        return false;
    }
    if ($f->kind === 'custom' && ($f->resolvedInputSpec()['type'] ?? '') === 'textarea') {
        return false;
    }

    return true;
});
$wideFields = $assignFields->filter(function ($f) {
    if (($f->field_key ?? '') === 'notes') {
        return true;
    }
    if ($f->kind === 'custom' && ($f->resolvedInputSpec()['type'] ?? '') === 'textarea') {
        return true;
    }

    return false;
});
@endphp

@if($assignFormLocked)
<div class="alert alert-warning mb-0" role="alert">
    <i class="ti ti-lock me-1"></i> No assignable user module is enabled for this company.
</div>
@else
{!! Form::model($sims, ['url' => route('sims.assign', $sims->id), 'method' => 'post', 'id' => 'formajax']) !!}

<div class="card-body">
    @if($assignHint !== '')
    <p class="text-muted small mb-3">
        <i class="ti ti-users me-1"></i>{{ $assignHint }}
    </p>
    @endif

    <div class="row">
        @foreach($inlineFields as $field)
            @include('sims._assign_modal_field', [
                'field' => $field,
                'assignContext' => 'assign',
                'sims' => $sims,
                'branchScopedOptions' => $branchScopedOptions,
                'assignTargets' => $assignTargets,
                'allowTypeSelection' => $allowTypeSelection,
                'defaultAssigneeType' => $defaultAssigneeType,
            ])
        @endforeach
    </div>

    @if($wideFields->isNotEmpty())
    <div class="row mt-3">
        @foreach($wideFields as $field)
            @include('sims._assign_modal_field', [
                'field' => $field,
                'assignContext' => 'assign',
                'sims' => $sims,
                'branchScopedOptions' => $branchScopedOptions,
                'assignTargets' => $assignTargets,
                'allowTypeSelection' => $allowTypeSelection,
                'defaultAssigneeType' => $defaultAssigneeType,
            ])
        @endforeach
    </div>
    @endif
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}

<script>
(function() {
    function initAssigneeSelect2(selectEl) {
        if (!selectEl || typeof $ === 'undefined' || !$.fn.select2) {
            return;
        }

        const $select = $(selectEl);
        if ($select.hasClass('select2-hidden-accessible')) {
            $select.select2('destroy');
        }

        $select.select2({
            dropdownParent: $('#modalTopbody'),
            placeholder: 'Search...',
            allowClear: true,
            width: '100%'
        });
    }

    function currentAssigneeType() {
        const checked = document.querySelector('input[name="assignee_type"]:checked');
        if (checked) {
            return checked.value;
        }

        return document.querySelector('input[name="assignee_type"]')?.value || 'rider';
    }

    function syncAssigneeFields() {
        const type = currentAssigneeType();
        const riderWrap = document.querySelector('.assignee-field-rider');
        const employeeWrap = document.querySelector('.assignee-field-employee');
        const riderSelect = document.getElementById('assign_to_rider');
        const employeeSelect = document.getElementById('assign_to_employee');

        if (type === 'employee') {
            riderWrap?.classList.add('d-none');
            employeeWrap?.classList.remove('d-none');
            if (riderSelect) {
                riderSelect.removeAttribute('name');
                riderSelect.disabled = true;
                riderSelect.removeAttribute('required');
            }
            if (employeeSelect) {
                employeeSelect.setAttribute('name', 'assign_to');
                employeeSelect.disabled = false;
                employeeSelect.setAttribute('required', 'required');
                initAssigneeSelect2(employeeSelect);
            }
        } else {
            employeeWrap?.classList.add('d-none');
            riderWrap?.classList.remove('d-none');
            if (employeeSelect) {
                employeeSelect.removeAttribute('name');
                employeeSelect.disabled = true;
                employeeSelect.removeAttribute('required');
            }
            if (riderSelect) {
                riderSelect.setAttribute('name', 'assign_to');
                riderSelect.disabled = false;
                riderSelect.setAttribute('required', 'required');
                initAssigneeSelect2(riderSelect);
            }
        }
    }

    document.querySelectorAll('input[name="assignee_type"]').forEach(function(el) {
        el.addEventListener('change', syncAssigneeFields);
    });

    syncAssigneeFields();
})();
</script>
@endif
