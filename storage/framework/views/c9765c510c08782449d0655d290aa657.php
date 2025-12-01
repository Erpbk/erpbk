
<?php $__env->startSection('title', 'Bank List'); ?>
<?php $__env->startSection('content'); ?>
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6 d-flex gap-2">
                <a href="<?php echo e(route('banks.index')); ?>" class="<?php if(request()->segment(1) =='banks' && !in_array(request()->segment(2), ['receipts','payments'])): ?> btn btn-primary  <?php else: ?> btn btn-default <?php endif; ?> action-btn"><i class="fa fa-bank"></i> Banks</a>
                <a href="<?php echo e(route('receipts.index')); ?>" class="<?php if(request()->segment(1) =='receipts'): ?> btn btn-primary <?php else: ?> btn btn-default <?php endif; ?> action-btn"><i class="fa fa-receipt"></i> Receipts</a>
                <a href="<?php echo e(route('payments.index')); ?>" class="<?php if(request()->segment(1) =='payments'): ?> btn btn-primary <?php else: ?> btn btn-default <?php endif; ?> action-btn"><i class="ti ti-cash"></i> Payments</a>
            </div>
            <div class="col-sm-6">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bank_create')): ?>
                <?php if(request()->segment(1) =='banks'): ?>
                <a class="btn btn-primary float-right show-modal action-btn"
                    href="javascript:void(0);" data-action="<?php echo e(route('banks.create')); ?>" data-title="Add New" data-size="lg">
                    Add New
                </a>
                <?php elseif(request()->segment(1) =='receipts'): ?>
                <a class="btn btn-primary float-right show-modal action-btn"
                    href="javascript:void(0);" data-action="<?php echo e(route('receipts.create')); ?>" data-title="Add New" data-size="lg">
                    Add New
                </a>
                <?php elseif(request()->segment(1) =='payments'): ?>
                <a class="btn btn-primary float-right show-modal action-btn"
                    href="javascript:void(0);" data-action="<?php echo e(route('payments.create')); ?>" data-title="Add New" data-size="lg">
                    Add New
                </a>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>
<?php echo $__env->yieldContent('page_content'); ?>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/banks/viewindex.blade.php ENDPATH**/ ?>