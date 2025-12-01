<script src="<?php echo e(asset('js/modal_custom.js')); ?>"></script>
<!-- Trip Date Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('trip_date', 'Trip Date:' , ['class' => 'readonly']); ?>

    <?php echo Form::date('trip_date', $data->trip_date, ['class' => 'form-control', 'readonly']); ?>

</div>

<!-- Trip Time Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('trip_time', 'Trip Time:', ['class' => 'readonly']); ?>

    <?php echo Form::time('trip_time', $data->trip_time, ['class' => 'form-control', 'readonly']); ?>

</div>

<!-- Billing Month Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('billing_month', 'Billing Month:', ['class' => 'readonly']); ?>

    <?php echo Form::date('billing_month', $data->billing_month, ['class' => 'form-control', 'readonly']); ?>

</div>


<!-- Ticket No Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('ticket_no', 'Ticket No:', ['class' => 'readonly']); ?>

    <?php echo Form::text('ticket_no', $data->ticket_no, ['class' => 'form-control', 'maxlength' => 50, 'readonly']); ?>

</div>

<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    <label class="readonly">Bike:</label>
    <select class="form select select2" readonly id="bike_id" name="bike_id" readonly>
        <option value=""></option>
        <?php $__currentLoopData = DB::table('bikes')->where('status', 1)->orderBy('id', 'desc')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
        $company = DB::table('leasing_companies')->where('id', $b->company)->first();
        ?>
        <option value="<?php echo e($b->id); ?>" <?php if($data->bike_id == $b->id): ?> selected <?php endif; ?>>
            <?php echo e($b->plate); ?> - <?php echo e($company ? $company->name : 'N/A'); ?>

        </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="readonly">Debit Account:</label>
    <select class="form select select2" id="rta_account_id" name="rta_account_id" readonly>
        <option value=""></option>
        <?php $__currentLoopData = DB::table('accounts')->where('status' , 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($a->id); ?>" <?php if($data->rta_account_id == $a->id): ?> selected <?php endif; ?>><?php echo e($a->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Credit Account:</label>
    <select class="form select select2" id="account_id" name="account" required>
        <option value=""></option>
        <?php
        $bank = DB::table('accounts')->where('id', 994)->first();
        $cash = DB::table('accounts')->where('id', 1643)->first();
        ?>

        <?php $__currentLoopData = DB::table('accounts')
        ->where('status', 1)
        ->whereIn('parent_id', [$bank->id, $cash->id])
        ->orderBy('id', 'asc')
        ->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $acc): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($acc->id); ?>">
            <?php echo e($acc->name); ?>

        </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Attachment</label>
    <input type="file" name="attach_file" class="form-control" required>
</div>

<!-- Amount Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('service_charges', 'Service Charges:', ['class' => 'readonly']); ?>

    <?php echo Form::number('service_charges', $data->service_charges, ['class' => 'form-control','step'=>'any', 'readonly']); ?>

</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('amount', 'Amount:', ['class' => 'readonly']); ?>

    <?php echo Form::text('', 'AED ' . number_format($data->amount, 2), ['class' => 'form-control', 'readonly']); ?>

</div>


<!-- Detail Field -->
<div class="form-group col-sm-12">
    <?php echo Form::label('detail', 'Detail:', ['class' => 'readonly']); ?>

    <?php echo Form::textarea('detail', $data->detail, ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'readonly']); ?>

</div><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rta_fines/voucherfield.blade.php ENDPATH**/ ?>