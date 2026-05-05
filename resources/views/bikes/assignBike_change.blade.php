@php
$bike = \App\Models\Bikes::find($id);
$vehicleTypeName = '';
$rider = null;
$company = null;
if ($bike && $bike->rider_id) {
    $rider = $bike->rider;
}
if($bike && $bike->rental_company_id){
    $company = $bike->rentalCompany;
}
if ($bike && $bike->vehicle_type) {
    $vehicleModel = \App\Models\VehicleModels::find($bike->vehicle_type);
    $vehicleTypeName = $vehicleModel ? strtolower($vehicleModel->name) : '';
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

<script src="{{ asset('js/modal_custom.js') }}"></script>
<form action="{{ route('bikes.assignrider', $id) }}" method="post" id="formajax">
    @csrf
    <input type="hidden" name="bike_id" value="{{ $id }}" />
    
    <div class="row">
        @if($bike->warehouse != 'Absconded')
            <div class="col-md-3 form-group">
                <label>Change Status</label>
                <select class="form-control warehouse form-select select2" name="warehouse" id="warehouse">
                    {!! App\Helpers\General::get_warehouse(1) !!}
                </select>
            </div>
        @else
            <div class="col-md-3 form-group">
                <label>Change Status</label>
                <input type="text" class="form-control" name="warehouse" id="warehouse" value="Return" readonly/>
            </div>
        @endif
        @if($rider)
            <div class="col-md-3 form-group">
                <label>Rider</label>
                <input type="text" name="rider" class="form-control" readonly placeholder="Rider Not Found" value="{{ $rider ? $rider->rider_id . '-' . $rider->name : 'N/A' }}">
            </div>

            <div class="col-md-3 form-group">
                <label>Designation</label>
                <input type="text" name="designation" class="form-control" readonly value="{{ $selectedDesignation }}">
            </div>

            <div class="col-md-3 form-group">
                <label>Project</label>
                <input type="text" name="customer_id" class="form-control" readonly value="{{ App\Models\Customers::find($bike->customer_id)->name ?? 'N/A' }}">
            </div>
        @endif
        @if($company)
            <div class="col-md-3 form-group">
                <label>Rental Company</label>
                <input type="text" name="rider" class="form-control" readonly placeholder="Company Not Found" value="{{ $company ? $company->name : 'N/A' }}">
            </div>
        @endif
        <div class="form-group col-md-3" id="return_date">
            <label for="exampleInputEmail1">Date</label>
            <input type="date" name="return_date" class="form-control" placeholder="Return Date">
        </div>
        @if($rider)
        <div class="form-group col-md-5" id="return_date">
            <label for="exampleInputEmail1">Visa Sponsor</label>
            <input type="text" name="visa_sponsor" class="form-control" readonly value="{{ $rider->visa_sponsor ?? 'N/A' }}">
        </div>
        @endif
    </div>
    
    <!--col-->
    <div class="row mt-3">
        <div class="col-md-8">
            <textarea class="form-control" placeholder="Note....." name="notes"></textarea>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12 mt-2">
            <button type="submit" class="btn btn-primary pull-right">Save</button>
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
    // Pass vehicle type name to JavaScript
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

    // Update designation on page load
    $(document).ready(function() {
        console.log('Document ready');
        updateDesignationBasedOnVehicleType();
        
        // Initialize select2 for dropdowns if needed
        $('.select2').select2({
            allowClear: true,
            dropdownParent: $('#modalTopbody')
        });
    });
</script>