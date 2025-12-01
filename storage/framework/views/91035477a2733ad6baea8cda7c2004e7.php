<?php $__env->startSection('title','Dropdowns'); ?>
<?php $__env->startSection('content'); ?>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Dropdowns</h3>
                </div>
                <div class="col-sm-6">
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('dropdown_create')): ?>
                    <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="lg" data-title="New Dropdown" data-action="<?php echo e(route('dropdowns.create')); ?>">
                        Add New
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="clearfix"></div>

        <div class="card">
            <?php echo $__env->make('dropdowns.table', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/dropdowns/index.blade.php ENDPATH**/ ?>