<?php $__env->startSection('page_content'); ?>

<?php
    $authorized = false;
    if(request('type') == 'bike' && auth()->user()->can('bike_document')){
      $authorized = true;
    }

?>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($authorized): ?>
<?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<div class="card">
    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bike_document')): ?>
    <div class="card-header">
        <a class="btn btn-primary show-modal action-btn"
          href="javascript:void(0);" data-action="<?php echo e(route('files.create',['type_id'=>request('type_id')??1,'type'=>request('type')??1])); ?>" data-size="sm" data-title="Upload File">
           Upload File
        </a>
    </div>
    <?php endif; ?>
    <?php echo $__env->make('files.table', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>
<?php else: ?>
<div class="alert alert-warning  text-center m-3"><i class="fa fa-warning"></i> You don't have permission. &nbsp;<a href="<?php echo e(url()->previous()); ?>"> Go Back</a></div>
<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('bikes.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/files/index.blade.php ENDPATH**/ ?>