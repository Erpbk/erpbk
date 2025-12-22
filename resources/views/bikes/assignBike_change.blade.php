@php
$bike = DB::table('bikes')->where('id', $id)->first();
$vehicleTypeName = '';
$rider = DB::table('riders')->where('id', $bike->rider_id)->first();

if ($bike && $bike->vehicle_type) {
$vehicleModel = DB::table('vehicle_models')->where('id', $bike->vehicle_type)->first();
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
    <input type="hidden" name="bike_id" value="{{$id}}" />
    <div class="row">
        @if($bike->warehouse != 'Absconded')
        <div class="col-md-3 form-group">
            <label>Change Status</label>
            <select class="form-control warehouse form-select" name="warehouse" id="warehouse">
                {!! App\Helpers\General::get_warehouse(1) !!}
            </select>
        </div>
        @else
        <div class="col-md-3 form-group">
            <label>Change Status</label>
            <input type="text" class="form-control" name="warehouse" id="warehouse" value="Return" readonly/>
        </div>
        @endif
        <div class="col-md-3 form-group">
            <label>Rider</label>
            <input type="text" name="rider" class="form-control" readonly placeholder="Rider Not Found" value="{{ $rider->rider_id . '-' . $rider->name}}">
        </div>
        <div class="col-md-3 form-group">
            <label>Designation</label>
            <input type="text" name="designation" class="form-control" readonly value="{{ $selectedDesignation }}">
        </div>
        <div class="col-md-3 form-group">
            <label>Project</label>
            <input type="text" name="customer_id" class="form-control" readonly value="{{ App\Models\Customers::find($bike->customer_id)->name ?? 'N/A' }}">
        </div>
        <div class="form-group col-md-3" id="return_date">
            <label for="exampleInputEmail1">Return Date</label>
            <input type="date" name="return_date" class="form-control" placeholder="Return Date">
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
            <button type="submit" class="btn btn-primary pull-right">Save</button>

        </div>
    </div>
</form>
<!--row-->

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
        updateDesignationBasedOnVehicleType();
    });
</script>