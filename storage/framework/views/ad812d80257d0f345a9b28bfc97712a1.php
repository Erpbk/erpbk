<script src="<?php echo e(asset('js/modal_custom.js')); ?>"></script>
<!-- Trip Date Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('trip_date', 'Trip Date:' , ['class' => 'required']); ?>

    <?php echo Form::date('trip_date', $rtaFines->trip_date ?? 'null', ['class' => 'form-control', 'required']); ?>

</div>

<!-- Trip Time Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('trip_time', 'Trip Time:', ['class' => 'required']); ?>

    <?php echo Form::time('trip_time', $rtaFines->trip_time ?? 'null', ['class' => 'form-control', 'required']); ?>

</div>

<!-- Billing Month Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('billing_month', 'Billing Month:', ['class' => 'required']); ?>

    <?php echo Form::month('billing_month', isset($rtaFines) && $rtaFines->billing_month ? \Carbon\Carbon::parse($rtaFines->billing_month)->format('Y-m') : null, ['class' => 'form-control' , 'required']); ?>

</div>


<!-- Ticket No Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('ticket_no', 'Ticket No:', ['class' => 'required']); ?>

    <?php echo Form::text('ticket_no', $rtaFines->ticket_no ?? '', ['class' => 'form-control', 'maxlength' => 50, 'required']); ?>

</div>

<!-- Rider Id Field -->
<div class="form-group col-sm-6">
    <label class="">Bike:</label>
    <select class="form select select2" required onchange="selectbike(this.value)" id="bike_id" name="bike_id">
        <option value=""></option>
        <?php $__currentLoopData = DB::table('bikes')->where('status', 1)->orderBy('id', 'desc')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $b): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
        $company = DB::table('leasing_companies')->where('id', $b->company)->first();
        ?>
        <option value="<?php echo e($b->id); ?>" <?php echo e((isset($rtaFines) && $rtaFines->bike_id == $b->id) ? 'selected' : ''); ?>>
            <?php echo e($b->plate); ?> - <?php echo e($company ? $company->name : 'N/A'); ?>

        </option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<div class="form-group col-sm-6">
    <label class="">Debit Account:</label>
    <select class="form select select2" required id="debit_account" name="debit_account">
        <option value=""></option>
        <?php if(isset($rtaFines)): ?>
        <?php
        $bikes = DB::table('bikes')->where('id', $rtaFines->bike_id)->first();
        $rider = $bikes ? DB::table('riders')->where('id', $rtaFines->rider_id)->first() : null;
        ?>

        <?php $__currentLoopData = DB::table('riders')->where('status' , 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <?php
        $account = DB::table('accounts')
        ->where('ref_id', $r->id)
        ->where('ref_name', 'Rider')
        ->first();
        ?>

        <?php if($account): ?>
        <option value="<?php echo e($account->ref_id); ?>"
            <?php echo e(isset($rtaFines, $rtaFines->bike_id, $rider) && $r->id == $rider->id ? 'selected' : ''); ?>>
            <?php echo e($r->rider_id); ?> - <?php echo e($r->name ?? 'N/A'); ?>

        </option>
        <?php endif; ?>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

        <?php endif; ?>


    </select>
</div>
<div class="form-group col-sm-6">
    <label class="">Credit Account:</label>
    <select class="form select select2" required id="rta_account_id" name="rta_account_id">
        <option value=""></option>
        <?php $__currentLoopData = DB::table('accounts')->where('status' , 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $a): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <option value="<?php echo e($a->id); ?>" <?php if($data->id == $a->id): ?> selected <?php endif; ?>><?php echo e($a->name); ?></option>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </select>
</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('reference_number', 'Reference Number:', ['class' => '']); ?>

    <?php echo Form::text('reference_number', $rtaFines->reference_number ?? '' , ['class' => 'form-control','step'=>'any']); ?>

</div>
<div class="form-group col-sm-6">
    <?php echo Form::label('attachment', 'Attachment:', ['class' => '']); ?>

    <?php echo Form::file('attachment', ['class' => 'form-control', '']); ?>

</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('admin_fee', 'Admin Charges:', ['class' => '']); ?>

    <?php echo Form::number('admin_fee', $rtaFines->admin_fee ?? $data->admin_charges, ['class' => 'form-control','step'=>'any', 'readonly']); ?>

</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('service_charges', 'Service Charges:', ['class' => '']); ?>

    <?php echo Form::number('service_charges', $rtaFines->service_charges ?? $data->account_tax, ['class' => 'form-control','step'=>'any']); ?>

</div>
<!-- Amount Field -->
<div class="form-group col-sm-6">
    <?php echo Form::label('amount', 'Amount:', ['class' => 'required']); ?>

    <?php echo Form::number('amount', $rtaFines->amount ?? '', ['class' => 'form-control','step'=>'any', 'required']); ?>

</div>


<!-- Detail Field -->
<div class="form-group col-sm-12">
    <?php echo Form::label('detail', 'Detail:', ['class' => 'required']); ?>

    <?php echo Form::textarea('detail', $rtaFines->detail ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'required']); ?>

</div>
<script type="text/javascript">
    function selectbike(id) {
        if (id) {
            $.ajax({
                type: 'get',
                url: '<?php echo e(url("rtaFines/getrider")); ?>/' + id,
                success: function(res) {
                    $('#debit_account').html(res);
                }
            });
        } else {
            $('#debit_account').html('required');
        }
    }
</script><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rta_fines/fields.blade.php ENDPATH**/ ?>