<?php if($errors->any()): ?>
<div class="alert alert-danger">
    <ul>
        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <li><?php echo e($error); ?></li>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </ul>
</div>
<?php endif; ?>

<?php if(session('success')): ?>
<div class="alert alert-success">
    <?php echo e(session('success')); ?>

</div>
<?php endif; ?>

<form action="<?php echo e(route('rider.keeta_activities_import')); ?>" method="POST" enctype="multipart/form-data" id="formajax">
    <?php echo csrf_field(); ?>
    <div class="row">
        <div class="col-12">
            <a href="<?php echo e(url('sample/keeta_activity_sample.csv')); ?>" class="text-success w-100" download="Keeta Activities Sample">
                <i class="fa fa-file-download text-success"></i> &nbsp; Download Sample File
            </a>
            <p class="text-muted mt-2">
                <small>Note: The file should have headers including courier_id, date, delivered_orders, etc. See sample file for format.</small>
            </p>
        </div>
        <div class="col-12 mt-3 mb-3">
            <label class="mb-3 pl-2">Select file</label>
            <input type="file" name="file" class="form-control mb-3" style="height: 40px;" accept=".csv,.xlsx,.xls" />
        </div>
    </div>
    <button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Start Import</button>
</form><?php /**PATH /var/www/laravel/resources/views/rider_activities/import_keeta.blade.php ENDPATH**/ ?>