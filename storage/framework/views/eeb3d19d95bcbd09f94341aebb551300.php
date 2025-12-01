<script src="<?php echo e(asset('js/modal_custom.js')); ?>"></script>
<!-- Trip Date Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('date', 'Date:' , ['class' => 'required']); ?>

    <?php echo Form::date('date', $data->date, ['class' => 'form-control']); ?>

</div>

<!-- Billing Month Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('billing_month', 'Billing Month:', ['class' => 'required']); ?>

    <?php echo Form::month('billing_month', \Carbon\Carbon::parse($data->billing_month)->format('Y-m'), ['class' => 'form-control']); ?>


</div>


<div class="form-group col-sm-6">
    <label class="">Visa Status:</label>
    <select class="form select select2" id="visa_status" name="visa_status" readonly>
        <option value=""></option>
        <option value="Job offer Letter" <?php if($data->visa_status == 'Job offer Letter'): ?> selected <?php endif; ?>>Job offer Letter</option>
        <option value="Labor Insurance" <?php if($data->visa_status == 'Labor Insurance'): ?> selected <?php endif; ?>>Labor Insurance</option>
        <option value="Work Permit" <?php if($data->visa_status == 'Work Permit'): ?> selected <?php endif; ?>>Work Permit</option>
        <option value="Work Man Insurance" <?php if($data->visa_status == 'Work Man Insurance'): ?> selected <?php endif; ?>>Work Man Insurance</option>
        <option value="Entry Permit (Inside)" <?php if($data->visa_status == 'Entry Permit (Inside)'): ?> selected <?php endif; ?>>Entry Permit (Inside)</option>
        <option value="Entry Permit (Outside)" <?php if($data->visa_status == 'Entry Permit (Outside)'): ?> selected <?php endif; ?>>Entry Permit (Outside)</option>
        <option value="Status Change" <?php echo e((isset($visaExpenses) && $visaExpenses->visa_status == 'Status Change') ? 'selected' : ''); ?>>Status Change</option>
        <option value="Medical" <?php if($data->visa_status == 'Tawjeeh'): ?> selected <?php endif; ?>>Tawjeeh</option>
        <option value="Medical" <?php if($data->visa_status == 'Medical'): ?> selected <?php endif; ?>>Medical</option>
        <option value="Emirates ID + Residency" <?php if($data->visa_status == 'Emirates ID + Residency'): ?> selected <?php endif; ?>>Emirates ID + Residency</option>
        <option value="Emirates ID" <?php if($data->visa_status == 'Emirates ID'): ?> selected <?php endif; ?>>Emirates ID</option>
        <option value="Residency" <?php if($data->visa_status == 'Residency'): ?> selected <?php endif; ?>>Residency</option>
        <option value="Bike License" <?php if($data->visa_status == 'Bike License'): ?> selected <?php endif; ?>>Bike License</option>
        <option value="Violation" <?php if($data->visa_status == 'Violation'): ?> selected <?php endif; ?>>Violation</option>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="">Payment Status:</label>
    <select class="form select select2" id="payment_status" name="payment_status" required>
        <option value=""></option>
        <option value="Paid" <?php if($data->payment_status == 'paid'): ?> selected <?php endif; ?>>Paid</option>
        <option value="Unpaid" <?php if($data->payment_status == 'unpaid'): ?> selected <?php endif; ?>>Unpaid</option>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="readonly">Debit Account:</label>
    <select class="form select select2" id="rider_id" name="rider_id" readonly>
        <option value=""></option>
        <?php $__currentLoopData = DB::table('accounts')->where('status' , 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($a->id); ?>" <?php if($data->rider_id == $a->id): ?> selected <?php endif; ?>><?php echo e($a->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="required">Credit Account:</label>
    <select class="form-control" id="account_id" name="account" required>
        <option value=""></option>
        <?php
        $bank = DB::table('accounts')->where('name', 'Bank')->first();
        $cash = DB::table('accounts')->where('name', 'Cash in Hand')->first();
        $recruiters = DB::table('accounts')->where('name', 'Recruiters')->first();
        ?>

        <?php $__currentLoopData = DB::table('accounts')
        ->where('status', 1)
        ->whereIn('parent_id', [$bank->id, $cash->id, $recruiters->id])
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
    <?php echo Form::label('amount', 'Amount:', ['class' => 'readonly']); ?>

    <?php echo Form::text('', 'AED ' . number_format($data->amount, 2), ['class' => 'form-control', 'readonly']); ?>

</div>


<!-- Detail Field -->
<div class="form-group col-sm-12">
    <?php echo Form::label('detail', 'Detail:', ['class' => 'readonly']); ?>

    <?php echo Form::textarea('detail', $data->detail, ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'readonly']); ?>

</div><?php /**PATH /var/www/laravel/resources/views/visa_expenses/voucherfield.blade.php ENDPATH**/ ?>