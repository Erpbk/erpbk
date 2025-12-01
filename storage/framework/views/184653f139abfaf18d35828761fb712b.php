<?php $__env->startSection('page_content'); ?>

<div class="card card-action mb-1">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0">Bike Detail</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-md-3 form-group col-3">
        <label>Bike Code:</label>
        <p><?php echo e($bikes->bike_code ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Plate #:</label>
        <p><?php echo e($bikes->plate ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Chassis #:</label>
        <p><?php echo e($bikes->chassis_number ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Color</label>
        <p><?php echo e($bikes->color ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Model #:</label>
        <p><?php echo e($bikes->model ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Model Type:</label>
        <p><?php echo e($bikes->model_type ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Engine #:</label>
        <p><?php echo e($bikes->engine ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Bike Traffic File #:</label>
        <p><?php echo e($bikes->traffic_file_number ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Emirates:</label>
        <p><?php echo e($bikes->emirates ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Company:</label>
        <p><?php echo e(DB::table('leasing_companies')->where('id' , $bikes->company)->first()->name ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Registration Date:</label>
        <p><?php echo e(\Carbon\Carbon::parse($bikes->registration_date)->format('d M Y') ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Expiry Date:</label>
        <p><?php echo e(\Carbon\Carbon::parse($bikes->expiry_date)->format('d M Y') ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Insurance Expiry:</label>
        <p><?php echo e(\Carbon\Carbon::parse($bikes->insurance_expiry)->format('d M Y') ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Insurance Co:</label>
        <p><?php echo e($bikes->insurance_co ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Policy No:</label>
        <p><?php echo e($bikes->policy_no ?? 'N/A'); ?></p>
      </div>
      <div class="col-md-3 form-group col-3">
        <label>Contract No:</label>
        <p><?php echo e($bikes->contract_number ?? 'N/A'); ?></p>
      </div>
    </div>
  </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('bikes.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/bikes/show.blade.php ENDPATH**/ ?>