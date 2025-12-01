<script src="<?php echo e(asset('js/modal_custom.js')); ?>"></script>
<div class="form-group col-sm-4">
    <label>Select Vehicle Model</label>
    <select class="form-control select2" name="vehicle_type" id="vehicle_type">
        <option value="">Select Model</option>
        <?php $__currentLoopData = DB::table('vehicle_models')->where('status', 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $model): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($model->id); ?>"><?php echo e($model->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<!-- Bike Code Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('bike_code', 'Bike Code:'); ?>

    <?php echo Form::text('bike_code', null, ['class' => 'form-control']); ?>

</div>

<!-- Plate Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('plate', 'Number Plate:',['class'=>'required']); ?>

    <?php echo Form::text('plate', null, ['class' => 'form-control', 'required', 'maxlength' => 100, 'maxlength' => 100]); ?>

</div>

<!-- Chassis Number Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('chassis_number', 'Chassis Number:',['class'=>'required']); ?>

    <?php echo Form::text('chassis_number', null, ['class' => 'form-control', 'required']); ?>

</div>

<!-- Engine Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('engine', 'Engine:',['class'=>'required']); ?>

    <?php echo Form::text('engine', null, ['class' => 'form-control', 'required']); ?>

</div>
<!-- Color Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('color', 'Color:',['class'=>'required']); ?>

    <?php echo Form::text('color', null, ['class' => 'form-control', 'required', 'maxlength' => 100, 'maxlength' => 100]); ?>

</div>

<!-- Model Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('model', 'Model:',['class'=>'required']); ?>

    <?php echo Form::text('model', null, ['class' => 'form-control', 'required', 'maxlength' => 100, 'maxlength' => 100]); ?>

</div>


<!-- Model Type Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('model_type', 'Model Type:',['class'=>'required']); ?>

    <?php echo Form::text('model_type', null, ['class' => 'form-control', 'required']); ?>

</div>


<!-- Company Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('company', 'Leasing Company:',['class'=>'required']); ?>

    <?php echo Form::select('company', App\Models\LeasingCompanies::dropdown(),null, ['class' => 'form-control select2', 'required']); ?>

</div>


<!-- Traffic File Number Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('traffic_file_number', 'Traffic File Number:'); ?>

    <?php echo Form::text('traffic_file_number', null, ['class' => 'form-control', 'maxlength' => 100, 'maxlength' => 100]); ?>

</div>

<!-- Emirates Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('emirate_hub', 'Emirate Hub:',['class'=>'required']); ?>

    <?php echo Form::select('emirate_hub',Common::Dropdowns('emirates-hub'),null,
    ['class' => 'form-select', 'required']); ?>

</div>

<!-- Registration Date Field -->
<div class="form-group col-sm-4">
    <?php echo Form::label('registration_date', 'Registration Date:'); ?>

    <?php echo Form::date('registration_date', null, ['class' => 'form-control','id'=>'registration_date']); ?>

</div>

<!-- Expiry Date Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('expiry_date', 'Expiry Date:'); ?>

    <?php echo Form::date('expiry_date', null, ['class' => 'form-control','id'=>'expiry_date']); ?>

</div>
<!-- Insurance Expiry Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('insurance_expiry', 'Insurance Expiry:'); ?>

    <?php echo Form::date('insurance_expiry', null, ['class' => 'form-control','id'=>'insurance_expiry']); ?>

</div>
<!-- Insurance Co Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('insurance_co', 'Insurance Co:'); ?>

    <?php echo Form::text('insurance_co', null, ['class' => 'form-control', 'maxlength' => 255, 'maxlength' => 255]); ?>

</div>
<!-- Policy No Field -->
<div class="form-group col-sm-4 hide-if-cyclist">
    <?php echo Form::label('policy_no', 'Policy No:'); ?>

    <?php echo Form::text('policy_no', null, ['class' => 'form-control']); ?>

</div>


<div class="form-group col-sm-4">
    <?php echo Form::label('customer_id', 'Project:',['class'=>'required']); ?>

    <?php echo Form::select('customer_id',App\Models\Customers::dropdown(),null,
    ['class' => 'form-select select2', 'required']); ?>

</div>
<!-- Notes Field -->
<div class="form-group col-sm-12 col-lg-12">
    <?php echo Form::label('notes', 'Notes:'); ?>

    <?php echo Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 3]); ?>

</div>
<!-- Status Field -->
<div class="form-group col-sm-6 mt-3">
    <label>Status</label>
    <div class="form-check">
        <input type="hidden" name="status" value="2" />
        <input type="checkbox" name="status" id="status" class="form-check-input" value="1" <?php if(isset($bikes)): ?> <?php if($bikes->status == 1): ?> checked <?php endif; ?> <?php else: ?> checked <?php endif; ?>/>
        <label for="status" class="pt-0">Is Active</label>

    </div>
</div>
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
</script><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/bikes/fields.blade.php ENDPATH**/ ?>