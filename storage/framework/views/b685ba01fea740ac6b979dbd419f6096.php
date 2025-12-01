<?php $__env->startSection('page_content'); ?>

<div class="card card-action mb-1">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-file-stack ti-lg text-body me-2"></i>Account Ledger</h5>
    <form action="" method="get">
      <input type="month" name="month" value="<?php echo e(request('month')); ?>" class="form-control" onchange="form.submit();"/>
    </form>
  </div>
  <div class="card-body pt-0 px-2">
    <?php $__env->startPush('third_party_stylesheets'); ?>
    <?php echo $__env->make('layouts.datatables_css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopPush(); ?>

<div class="card-body px-0" >
    <?php echo $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']); ?>

</div>

<?php $__env->startPush('third_party_scripts'); ?>
    <?php echo $__env->make('layouts.datatables_js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $dataTable->scripts(); ?>

<?php $__env->stopPush(); ?>
  </div>
</div>

    <?php $__env->stopSection(); ?>



<?php echo $__env->make('riders.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/riders/ledger.blade.php ENDPATH**/ ?>