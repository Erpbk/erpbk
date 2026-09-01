@php
$bike = $bike ?? \App\Models\Bikes::find($id);
$assignBranchScopedOptions = $assignBranchScopedOptions ?? [];
$vehicleTypeName = $bike?->vehicle_type ?? '';
if ($bike && $bike->vehicle_type) {
    $vehicleModel = \App\Models\VehicleModels::find($bike->vehicle_type);
    if ($vehicleModel && ! empty($vehicleModel->name)) {
        $vehicleTypeName = $vehicleModel->name;
    }
}

$selectedDesignation = \App\Models\Riders::designationFromVehicleType($bike->vehicle_type ?? null) ?? '';

$assignFields = $assignFields ?? \App\Models\BikeCustomField::assignModalFields('active');
$assignTargets = $assignTargets ?? \App\Support\CompanyModuleVisibility::bikeAssignTargets();
$assignFormLocked = (bool) ($assignFormLocked ?? ($assignTargets === []));
$allowTypeSelection = (bool) ($allowTypeSelection ?? (count($assignTargets) >= 2));
$defaultAssignType = $defaultAssignType ?? (count($assignTargets) === 1 ? $assignTargets[0] : '');
$assignTypeLabels = $assignTypeLabels ?? \App\Support\CompanyModuleVisibility::bikeAssignTypeLabels();
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
    <i class="ti ti-lock me-1"></i> No assignable resources found in the system.
</div>
@else
<form action="{{ route('bikes.assign_rider', $id) }}" method="post" id="formajax" novalidate data-bike-assign-modal="1">
    @csrf
    <input type="hidden" name="bike_id" value="{{ $id }}" />
    <div class="row">
        @foreach($inlineFields as $field)
            @include('bikes._assign_modal_field', [
                'field' => $field,
                'assignContext' => 'active',
                'bike' => $bike,
                'selectedDesignation' => $selectedDesignation,
                'branchScopedOptions' => $assignBranchScopedOptions,
                'assignBranchScopedOptions' => $assignBranchScopedOptions,
            ])
        @endforeach
    </div>

    @if($wideFields->isNotEmpty())
    <div class="row mt-3">
        @foreach($wideFields as $field)
            @include('bikes._assign_modal_field', [
                'field' => $field,
                'assignContext' => 'active',
                'bike' => $bike,
                'selectedDesignation' => $selectedDesignation,
                'branchScopedOptions' => $assignBranchScopedOptions,
                'assignBranchScopedOptions' => $assignBranchScopedOptions,
            ])
        @endforeach
    </div>
    @endif

    <div class="row">
        <div class="col-md-12 mt-2">
            <button type="submit" class="btn btn-primary pull-right">Save</button>
        </div>
    </div>
</form>
@endif

@unless($assignFormLocked)
<style>
    .hidden-field {
        display: none !important;
    }
</style>

<script>
    var vehicleTypeName = String(@json($vehicleTypeName) || '');
    var selectedDesignation = @json($selectedDesignation);

    function updateDesignationBasedOnVehicleType() {
        if (selectedDesignation) {
            $('input[name="designation"]').val(selectedDesignation);
        }
    }

    function initAssignModalSelect2($scope) {
        var $root = $scope && $scope.length ? $scope : $('#formajax');
        $root.find('select.select2').each(function() {
            var $el = $(this);
            if ($el.prop('disabled') || ($el.closest('.hidden-field').length && !$el.is(':visible'))) {
                return;
            }
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                allowClear: true,
                dropdownParent: $('#modalTopbody').length ? $('#modalTopbody') : $el.closest('.modal'),
                width: '100%'
            });
        });
    }

    function setAssignGroupEnabled(selector, enabled) {
        var $group = $(selector);
        $group.toggleClass('hidden-field', !enabled);
        if (enabled) {
            $group.show();
        } else {
            $group.hide();
        }
        $group.find('input, select, textarea').each(function() {
            var $el = $(this);
            if ($el.attr('type') === 'hidden' && !$el.is('select')) {
                return;
            }
            if (enabled) {
                $el.prop('disabled', false);
                if ($el.data('assignRequired')) {
                    $el.attr('required', 'required');
                }
            } else {
                if ($el.prop('required')) {
                    $el.data('assignRequired', true);
                }
                $el.removeAttr('required');
                $el.prop('disabled', true);
                if ($el.is('select') || $el.is('input:not([readonly]):not([type="checkbox"])') || $el.is('textarea')) {
                    if ($el.is('select')) {
                        $el.val(null);
                    }
                }
            }
        });
        if (enabled) {
            initAssignModalSelect2($group);
        }
    }

    function toggleAssignmentFields() {
        var assignType = $('#assign_type').val();
        if (assignType === 'company') {
            assignType = 'rental';
        }

        setAssignGroupEnabled('.assign-group-rider', assignType === 'rider');
        setAssignGroupEnabled('.assign-group-rental', assignType === 'rental');
        setAssignGroupEnabled('.assign-group-garage', assignType === 'garage');

        if (assignType === 'rider') {
            $('#rider_id, #customer_id').attr('required', 'required').data('assignRequired', true);
            $('#rental_company_id, #garage_company_id').val(null);
        } else if (assignType === 'rental') {
            $('#rental_company_id').attr('required', 'required').data('assignRequired', true);
            $('#rider_id, #customer_id, #garage_company_id').val(null);
        } else if (assignType === 'garage') {
            $('#garage_company_id').attr('required', 'required').data('assignRequired', true);
            $('#rider_id, #customer_id, #rental_company_id').val(null);
        } else {
            $('#rider_id, #customer_id, #rental_company_id, #garage_company_id').val(null);
        }
    }

    function firstMissingAssignField($form) {
        var missing = null;
        $form.find('input, select, textarea').filter(':enabled').each(function() {
            var $el = $(this);
            if ($el.closest('.hidden-field').length) {
                return;
            }
            if ($el.attr('type') === 'hidden') {
                return;
            }
            if (!$el.prop('required') && $el.attr('data-assign-required') !== '1') {
                return;
            }
            var val = $el.val();
            if (val === null || val === undefined || String(val).trim() === '') {
                missing = $el;
                return false;
            }
        });
        return missing;
    }

    $(document).ready(function() {
        updateDesignationBasedOnVehicleType();
        initAssignModalSelect2();
        if ($('#assign_type').length) {
            $('#assign_type').on('change', toggleAssignmentFields);
            toggleAssignmentFields();
        }

        $('#formajax').on('click', 'button[type="submit"]', function(e) {
            var $form = $('#formajax');
            var $missing = firstMissingAssignField($form);
            if (!$missing || !$missing.length) {
                return;
            }
            e.preventDefault();
            e.stopImmediatePropagation();
            var label = $missing.closest('.form-group').find('label').first().text().replace(/\*/g, '').trim() || 'This field';
            if (typeof toastr !== 'undefined') {
                toastr.error(label + ' is required.');
            } else {
                alert(label + ' is required.');
            }
            try {
                if ($missing.hasClass('select2-hidden-accessible')) {
                    $missing.select2('open');
                } else {
                    $missing.trigger('focus');
                }
            } catch (err) {}
            return false;
        });
    });
</script>
@endunless
