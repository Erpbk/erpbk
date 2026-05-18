@php
$assignFields = $assignFields ?? \App\Support\SimAssignFields::assignModalFields('assign');
$branchScopedOptions = $branchScopedOptions ?? [
    'assign_to_rider' => $riders ?? [],
    'assign_to_employee' => $employees ?? [],
];
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

{!! Form::model($sims, ['url' => route('sims.assign', $sims->id), 'method' => 'post', 'id' => 'formajax']) !!}

<div class="card-body">
    @if(!empty($simBranchName))
    <p class="text-muted small mb-3">
        <i class="ti ti-building me-1"></i>Showing only users from branch: <strong>{{ $simBranchName }}</strong>
    </p>
    @endif

    <div class="row">
        @foreach($inlineFields as $field)
            @include('sims._assign_modal_field', [
                'field' => $field,
                'assignContext' => 'assign',
                'sims' => $sims,
                'branchScopedOptions' => $branchScopedOptions,
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
    function syncAssigneeFields() {
        const type = document.querySelector('input[name="assignee_type"]:checked')?.value || 'rider';
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
            }
        }

        if (typeof $ !== 'undefined' && $.fn.select2) {
            $('#assign_to_rider, #assign_to_employee').trigger('change.select2');
        }
    }

    document.querySelectorAll('input[name="assignee_type"]').forEach(function(el) {
        el.addEventListener('change', syncAssigneeFields);
    });

    syncAssigneeFields();
})();
</script>
