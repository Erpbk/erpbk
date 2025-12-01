<?php $__env->startSection('page_content'); ?>

<div class="card card-action mb-0">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-calendar-check ti-lg text-body me-2"></i>Emails</h5>
  </div>
  <div class="card-body pt-0 px-2">
    <?php $__env->startPush('third_party_stylesheets'); ?>
    <?php echo $__env->make('layouts.datatables_css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopPush(); ?>

<div class="card-body px-0 pt-0" >
    <?php echo $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']); ?>

</div>

<?php $__env->startPush('third_party_scripts'); ?>
    <?php echo $__env->make('layouts.datatables_js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $dataTable->scripts(); ?>

<?php $__env->stopPush(); ?>
  </div>
</div>

    <?php $__env->stopSection(); ?>



<?php echo $__env->make('riders.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/riders/emails.blade.php ENDPATH**/ ?>