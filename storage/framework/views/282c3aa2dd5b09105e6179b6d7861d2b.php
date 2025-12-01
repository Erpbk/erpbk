<?php $__env->startSection('title', 'Uploaded Files'); ?>

<?php $__env->startSection('content'); ?>
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3>Uploaded Files</h3>
      </div>
      <div class="col-sm-6 text-end">
        <a class="btn btn-primary action-btn show-modal"
           href="javascript:void(0);"
           data-size="lg"
           data-title="Upload File"
           data-action="<?php echo e(route('upload_files.create')); ?>">
          Upload File
        </a>
      </div>
    </div>
  </div>
</section>

<div class="content px-0">
  <div class="card">
    <?php echo $__env->make('upload_files.table', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/upload_files/index.blade.php ENDPATH**/ ?>