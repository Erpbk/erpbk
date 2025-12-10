<?php $__env->startSection('page_content'); ?>

<div class="card card-action mb-1">
  <div class="card-header align-items-center bg-primary">
    <h5 class="card-action-title mb-0 text-white">Bike Detail</h5>
  </div>
  <?php echo Form::model($bikes, ['route' => ['bikes.update', $bikes->id], 'method' => 'patch','id'=>'formajax']); ?>



  <input type="hidden" name="updated_by" value="<?php echo e(Auth::user()->id); ?>">
  <div class="card-body">
    <div class="row">
      <div class="col-md-3 form-group col-3">
        <label>Select Vehicle Model</label>
        <select class="form-control select2" name="vehicle_type" id="vehicle_type">
          <option value="">Select Model</option>
          <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = DB::table('vehicle_models')->where('status', 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $model): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
          <option value="<?php echo e($model->id); ?>" <?php if($bikes->vehicle_type == $model->id): ?> selected <?php endif; ?>><?php echo e($model->name); ?></option>
          <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </select>
      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('bike_code', 'Bike Code:'); ?>

        <?php echo Form::text('bike_code', $bikes->bike_code ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3">
        <?php echo Form::label('plate', 'Number Plate:',['class'=>'required']); ?>

        <?php echo Form::text('plate', $bikes->plate ?? ' ', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('chassis_number', 'Chassis Number:',['class'=>'required']); ?>

        <?php echo Form::text('chassis_number', $bikes->chassis_number ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('engine', 'Engine:',['class'=>'required']); ?>

        <?php echo Form::text('engine', $bikes->engine ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3">
        <?php echo Form::label('color', 'Color:',['class'=>'required']); ?>

        <?php echo Form::text('color', $bikes->color ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3">
        <?php echo Form::label('model', 'Model:',['class'=>'required']); ?>

        <?php echo Form::text('model', $bikes->model ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('model_type', 'Model Type:',['class'=>'required']); ?>

        <?php echo Form::text('model_type', $bikes->model_type ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3">
        <?php
        $selectedCompany = $bikes && $bikes->company ? $bikes->company : null;
        ?>

        <?php echo Form::label('company', 'Leasing Company:',['class'=>'required']); ?>

        <?php echo Form::select(
        'company',
        \App\Models\LeasingCompanies::dropdown(),
        $selectedCompany,
        ['class' => 'form-control select2', 'required']
        ); ?>

      </div>
      <div class="col-md-3 form-group col-3">
        <?php echo Form::label('warehouse', 'Location:'); ?>

        <?php echo Form::text('warehouse', $bikes->warehouse ?? '', ['class' => 'form-control', 'maxlength' => 50, 'maxlength' => 50]); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('traffic_file_number', 'Traffic File Number:'); ?>

        <?php echo Form::text('traffic_file_number', $bikes->traffic_file_number ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="col-md-3 form-group col-3">
        <?php echo Form::label('emirate_hub', 'Emirate Hub:', ['class' => 'required']); ?>

        <?php echo Form::select(
        'emirate_hub',
        Common::Dropdowns('emirates-hub'),
        old('emirate_hub', $bikes->emirate_hub ?? ''), // ✅ selected value
        ['class' => 'form-select select2', 'required']
        ); ?>

      </div>
      <div class="col-md-3 form-group col-3">
        <?php echo Form::label('registration_date', 'Registration Date:'); ?>

        <?php echo Form::date('registration_date', \Carbon\Carbon::parse($bikes->registration_date)->format('d M Y') ?? '', ['class' => 'form-control','id'=>'registration_date']); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('expiry_date', 'Expiry Date:'); ?>

        <?php echo Form::date('expiry_date', \Carbon\Carbon::parse($bikes->expiry_date)->format('d M Y') ?? '', ['class' => 'form-control','id'=>'expiry_date']); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('insurance_expiry', 'Insurance Expiry:'); ?>

        <?php echo Form::date('insurance_expiry', \Carbon\Carbon::parse($bikes->insurance_expiry)->format('d M Y') ?? '', ['class' => 'form-control','id'=>'insurance_expiry']); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('insurance_co', 'Insurance Co:'); ?>

        <?php echo Form::text('insurance_co', $bikes->insurance_co ?? '', ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]); ?>

      </div>
      <div class="col-md-3 form-group col-3 hide-if-cyclist">
        <?php echo Form::label('policy_no', 'Policy No:'); ?>

        <?php echo Form::text('policy_no', $bikes->policy_no ?? '', ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

      </div>
      <div class="form-group col-sm-3">
        <?php echo Form::label('customer_id', 'Project:',['class'=>'required']); ?>

        <?php echo Form::select('customer_id',App\Models\Customers::dropdown(),null,
        ['class' => 'form-select select2', 'required']); ?>

      </div>
      <div class="col-md-12 form-group col-12">
        <?php echo Form::label('notes', 'Notes:'); ?>

        <?php echo Form::textarea('notes', $bikes->notes ?? '', ['class' => 'form-control', 'rows' => 3]); ?>

      </div>
      <div class="form-group col-sm-6 mt-3 mb-3">
        <label>Status</label>
        <div class="form-check">
          <input type="hidden" name="status" value="2" />
          <input type="checkbox" name="status" id="status" class="form-check-input" value="1" <?php if(isset($bikes)): ?> <?php if($bikes->status == 1): ?> checked <?php endif; ?> <?php else: ?> checked <?php endif; ?>/>
          <label for="status" class="pt-0">Is Active</label>
        </div>
      </div>
      <div class="form-group col-sm-12 mt-3 text-end">
        <button class="btn btn-primary" type="submit">Save</button>
      </div>
    </div>
  </div>
  <?php echo Form::close(); ?>

</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>

<script>
  $(document).ready(function() {
    function toggleCyclistFields() {
      let selectedText = $("#vehicle_type option:selected").text().toLowerCase();

      if (selectedText === "cyclist") {
        $(".hide-if-cyclist").hide();
      } else {
        $(".hide-if-cyclist").show();
      }
    }

    // Run on page load
    toggleCyclistFields();

    // Run when vehicle type changes
    $("#vehicle_type").change(function() {
      toggleCyclistFields();
    });
  });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('bikes.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/bikes/edit.blade.php ENDPATH**/ ?>