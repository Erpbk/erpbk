@php
$bike = $bike ?? \App\Models\Bikes::find($id);
$assignBranchScopedOptions = $assignBranchScopedOptions ?? [];
$vehicleTypeName = $bike->vehicle_type ?? '';
if ($bike && $bike->vehicle_type) {
    $vehicleModel = \App\Models\VehicleModels::find($bike->vehicle_type);
}

$selectedDesignation = '';
if (strpos($vehicleTypeName, 'bike') !== false) {
    $selectedDesignation = 'Rider';
} elseif (strpos($vehicleTypeName, 'car') !== false || strpos($vehicleTypeName, 'van') !== false) {
    $selectedDesignation = 'Driver';
} elseif (strpos($vehicleTypeName, 'cyclist') !== false) {
    $selectedDesignation = 'Cyclist';
}

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
<form action="{{ route('bikes.assign_rider', $id) }}" method="post" id="formajax">
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
    var vehicleTypeName = @json($vehicleTypeName);

    function updateDesignationBasedOnVehicleType() {
        var designation = '';
        if (vehicleTypeName && vehicleTypeName.includes('bike')) {
            designation = 'Rider';
        } else if (vehicleTypeName && (vehicleTypeName.includes('car') || vehicleTypeName.includes('van'))) {
            designation = 'Driver';
        } else if (vehicleTypeName && vehicleTypeName.includes('cyclist')) {
            designation = 'Cyclist';
        }
        if (designation) {
            $('input[name="designation"]').val(designation);
        }
    }

    function initAssignModalSelect2($scope) {
        var $root = $scope && $scope.length ? $scope : $('#modalTopbody');
        $root.find('select.select2').each(function() {
            var $el = $(this);
            if ($el.closest('.hidden-field').length && !$el.is(':visible')) {
                return;
            }
            if ($el.hasClass('select2-hidden-accessible')) {
                $el.select2('destroy');
            }
            $el.select2({
                allowClear: true,
                dropdownParent: $('#modalTopbody'),
                width: '100%'
            });
        });
    }

    function toggleAssignmentFields() {
        var assignType = $('#assign_type').val();
        if (assignType === 'company') {
            assignType = 'rental';
        }
        var $rentalSelect = $('#rental_company_id');
        var $garageSelect = $('#garage_company_id');
        $rentalSelect.prop('disabled', true);
        $garageSelect.prop('disabled', true);

        if (assignType === 'rider') {
            $('#rider_select, #designation_field, #project_field').removeClass('hidden-field').show();
            $('#rental_customer_select, #garage_customer_select, #company_select').addClass('hidden-field').hide();
            $rentalSelect.add($garageSelect).val('').trigger('change');
            initAssignModalSelect2($('#rider_select, #designation_field, #project_field'));
        } else if (assignType === 'rental') {
            $('#rental_customer_select').removeClass('hidden-field').show();
            $('#rider_select, #designation_field, #project_field, #garage_customer_select, #company_select').addClass('hidden-field').hide();
            $('#rider_id').val('').trigger('change');
            $garageSelect.val('').trigger('change');
            $rentalSelect.prop('disabled', false);
            initAssignModalSelect2($('#rental_customer_select'));
        } else if (assignType === 'garage') {
            $('#garage_customer_select').removeClass('hidden-field').show();
            $('#rider_select, #designation_field, #project_field, #rental_customer_select, #company_select').addClass('hidden-field').hide();
            $('#rider_id').val('').trigger('change');
            $rentalSelect.val('').trigger('change');
            $garageSelect.prop('disabled', false);
            initAssignModalSelect2($('#garage_customer_select'));
        } else {
            $('#rider_select, #rental_customer_select, #garage_customer_select, #company_select, #designation_field, #project_field').addClass('hidden-field').hide();
            $('#rider_id').val('').trigger('change');
            $rentalSelect.add($garageSelect).val('').trigger('change');
        }
    }

    $(document).ready(function() {
        updateDesignationBasedOnVehicleType();
        initAssignModalSelect2();
        if ($('#assign_type').length) {
            $('#assign_type').on('change', toggleAssignmentFields);
            toggleAssignmentFields();
        }
    });
</script>
@endunless
