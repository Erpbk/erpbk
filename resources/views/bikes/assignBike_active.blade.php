@php
$bike = \App\Models\Bikes::find($id);
$vehicleTypeName = $bike->vehicle_type;
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
@endphp
<form action="{{ route('bikes.assign_rider', $id) }}" method="post" id="formajax">
    @csrf
    <input type="hidden" name="bike_id" value="{{$id}}" />
    <div class="row">

        <div class="col-md-3 form-group">
            <label>Status</label>
            <input type="text" name="warehouse" class="form-control" readonly placeholder="Active" value="Active">
        </div>
        <div class="col-md-3 form-group">
            <label>Assign To</label>
            <select name="assign_type" id="assign_type" class="form-select select2">
                <option value="">Select Type</option>
                <option value="rider">Rider</option>
                <option value="company">Company</option>
            </select>
        </div>
        <div class="col-md-3 form-group hidden-field" id="rider_select">
            <label>Rider</label>
            {!! Form::select('rider_id',\App\Models\Riders::dropdown(), '' ,['class' => 'form-select select2','id'=>'rider_id']) !!}
        </div>
        <div class="col-md-3 form-group hidden-field" id="company_select">
            <label>Company</label>
            {!! Form::select('rental_company_id',\App\Models\BikeRentCompany::pluck('name', 'id'), '' ,['class' => 'form-select select2','id'=>'company_id']) !!}
        </div>
        <div class="col-md-3 form-group hidden-field" id="designation_field">
            <label>Designation</label>
            <input type="text" name="designation" id="designation" class="form-control" readonly placeholder="Designation" value="{{ $selectedDesignation }}">
        </div>
        <div class="col-md-3 form-group hidden-field" id="project_field">
            {!! Form::label('customer_id', 'Project') !!}
            {!! Form::select('customer_id',\App\Models\Customers::dropdown(),'',
            ['class' => 'form-select select2', 'id' => 'customer_id']) !!}
        </div>
        <div class="form-group col-md-3">
            <label for="exampleInputEmail1">Date</label>
            <input type="date" name="note_date" class="form-control">
        </div>
    </div>
    <!--col-->
    <div class="row mt-3">
        <div class="col-md-8">
            <textarea class="form-control" placeholder="Note....." name="notes"></textarea>
        </div>

        <!--col-->
    </div>
    <div class="row">
        <div class="col-md-12 mt-2">
            <button type="submit" class="btn btn-primary pull-right ">Save</button>

        </div>
    </div>
</form>
<!--row-->

<style>
    .hidden-field {
        display: none !important;
    }
</style>

<script>
    var vehicleTypeName = '{{ $vehicleTypeName }}';

    function updateDesignationBasedOnVehicleType() {
        var designation = '';

        if (vehicleTypeName.includes('bike')) {
            designation = 'Rider';
        } else if (vehicleTypeName.includes('car') || vehicleTypeName.includes('van')) {
            designation = 'Driver';
        } else if (vehicleTypeName.includes('cyclist')) {
            designation = 'Cyclist';
        }

        if (designation) {
            $('input[name="designation"]').val(designation);
        }
    }

    // Toggle visibility of rider and company selects based on assign_type
    function toggleAssignmentFields() {
        var assignType = $('#assign_type').val();
        console.log('Toggle called with type:', assignType);

        if (assignType === 'rider') {
            console.log('Showing rider fields');
            $('#rider_select').removeClass('hidden-field').show();
            $('#company_select').addClass('hidden-field').hide();
            $('#designation_field').removeClass('hidden-field').show();
            $('#project_field').removeClass('hidden-field').show();
            $('#company_id').val('').trigger('change');
        } else if (assignType === 'company') {
            console.log('Showing company fields');
            $('#company_select').removeClass('hidden-field').show();
            $('#rider_select').addClass('hidden-field').hide();
            $('#designation_field').addClass('hidden-field').hide();
            $('#project_field').addClass('hidden-field').hide();
            $('#rider_id').val('').trigger('change');
        } else {
            console.log('Hiding all fields');
            $('#rider_select').addClass('hidden-field').hide();
            $('#company_select').addClass('hidden-field').hide();
            $('#designation_field').addClass('hidden-field').hide();
            $('#project_field').addClass('hidden-field').hide();
            $('#rider_id').val('').trigger('change');
            $('#company_id').val('').trigger('change');
        }
    }

    // Update designation on page load
    $(document).ready(function() {
        console.log('Document ready');
        updateDesignationBasedOnVehicleType();
        $('.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });

        // Handle assign type change - use both change and select2:select for select2
        $('#assign_type').on('change', function() {
            console.log('Change event triggered');
            toggleAssignmentFields();
        });
    });
</script>