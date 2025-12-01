<form action="<?php echo e(route('riders.import_rider_vouchers')); ?>" method="POST" enctype="multipart/form-data">
    <?php echo csrf_field(); ?>
    <div class="form-group">
        <label for="file">Excel File (.xlsx)</label>
        <input type="file" name="file" id="file" class="form-control <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" accept=".xlsx">
        <?php $__errorArgs = ['file'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
        <div class="invalid-feedback"><?php echo e($message); ?></div>
        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
        <small class="form-text text-muted">Columns: Rider ID, Billing Month, Date, Amount, Voucher Type, Account_id</small>
    </div>
    <div class="text-right">
        <button type="submit" class="btn btn-success"><i class="fas fa-upload"></i> Import</button>
    </div>
</form><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/riders/import_rider_voucher_modal.blade.php ENDPATH**/ ?>