<?php
$bike = DB::table('bikes')->where('id', $id)->first();
$selectedRider = $bike && $bike->rider_id ? $bike->rider_id : null;
$selectedWarehouse = $bike && $bike->warehouse ? $bike->warehouse : null;
$selectedCustomer = $bike && $bike->customer_id ? $bike->customer_id : null;
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
?>
<script src="<?php echo e(asset('js/modal_custom.js')); ?>"></script>
<form action="<?php echo e(route('bikes.assign_rider', $id)); ?>" method="post" id="formajax">
    <input type="hidden" name="bike_id" value="<?php echo e($id); ?>" />
    <div class="row">

        <div class="col-md-3 form-group">
            <label>Change Status</label>
            <select class="form-control warehouse form-select" name="warehouse" id="warehouse" onchange="bike_status()">
                <?php echo App\Helpers\General::get_warehouse('Active'); ?>

            </select>
        </div>
        <div class="col-md-3 form-group" id="rider_select">
            <label>Change Rider</label>
            <?php echo Form::select('rider_id',\App\Models\Riders::dropdown(),$selectedRider ,['class' => 'form-select select2 ','id'=>'rider_id']); ?>

        </div>
        <div class="col-md-3 form-group">
            <label>Designation</label>
            <input type="text" name="designation" class="form-control" disabled placeholder="Designation" value="<?php echo e($selectedDesignation); ?>">
        </div>
        <div class="col-md-3 form-group">
            <?php echo Form::label('customer_id', 'Project'); ?>

            <?php echo Form::select('customer_id',App\Models\Customers::dropdown(),$selectedCustomer,
            ['class' => 'form-select select2', 'id' => 'customer_id']); ?>

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
    function bike_status() {
        var status = $('.warehouse').find(":selected").val();
        if (status == 'Active') {
            $("#rider_select").show("fast");
        } else {
            $("#rider_select").hide("fast");
        }
    }
</script>

<script>
    // Pass vehicle type name to JavaScript
    var vehicleTypeName = '<?php echo e($vehicleTypeName); ?>';

    function bike_status() {
        var status = $('.warehouse').find(":selected").val();
        if (status === 'Active' || status === 'Absconded') {
            $("#rider_select").show("fast");
            $("#active_date").show("fast");
            $("#return_date").hide("fast");
        } else {
            $("#rider_select").hide("fast");
            $("#active_date").hide("fast");
            $("#return_date").show("fast");
        }
    }

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
</script><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/bikes/assign_rider.blade.php ENDPATH**/ ?>