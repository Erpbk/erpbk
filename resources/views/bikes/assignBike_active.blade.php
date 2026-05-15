@php
$bike = \App\Models\Bikes::find($id);
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

    function toggleAssignmentFields() {
        var assignType = $('#assign_type').val();
        if (assignType === 'rider') {
            $('#rider_select, #designation_field, #project_field').removeClass('hidden-field').show();
            $('#company_select').addClass('hidden-field').hide();
            $('#company_id').val('').trigger('change');
        } else if (assignType === 'company') {
            $('#company_select').removeClass('hidden-field').show();
            $('#rider_select, #designation_field, #project_field').addClass('hidden-field').hide();
            $('#rider_id').val('').trigger('change');
        } else {
            $('#rider_select, #company_select, #designation_field, #project_field').addClass('hidden-field').hide();
            $('#rider_id, #company_id').val('').trigger('change');
        }
    }

    $(document).ready(function() {
        updateDesignationBasedOnVehicleType();
        $('.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });
        if ($('#assign_type').length) {
            $('#assign_type').on('change', toggleAssignmentFields);
        }
    });
</script>
