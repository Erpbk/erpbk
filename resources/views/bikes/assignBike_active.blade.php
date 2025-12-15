@php
$bike = DB::table('bikes')->where('id', $id)->first(); 
$vehicleTypeName = '';
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
<form action="{{ route('bikes.assign_rider', $id) }}" method="post" id="formajax">
    @csrf
    <input type="hidden" name="bike_id" value="{{$id}}" />
    <div class="row">

        <div class="col-md-3 form-group">
            <label>Status</label>
            <input type="text" name="warehouse" class="form-control" readonly placeholder="Active" value="Active">
        </div>
        <div class="col-md-3 form-group" id="rider_select">
            <label>Change Rider</label>
            {!! Form::select('rider_id',\App\Models\Riders::dropdown(), '' ,['class' => 'form-select select2 ','id'=>'rider_id']) !!}
        </div>
        <div class="col-md-3 form-group">
            <label>Designation</label>
            <input type="text" name="designation" class="form-control" readonly placeholder="Designation" value="{{ $selectedDesignation }}">
        </div>
        <div class="col-md-3 form-group">
            {!! Form::label('customer_id', 'Project') !!}
            {!! Form::select('customer_id',App\Models\Customers::dropdown(),'',
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

<script>

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