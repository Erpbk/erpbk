<?php $__env->startSection('title','Roles'); ?>
<?php $__env->startSection('content'); ?>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Roles</h3>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-primary float-right show-modal" style="float:right;" href="javascript:void(0);"
                       data-action="<?php echo e(route('roles.create')); ?>" data-title="Create New Role" data-size="lg">
                        Add New
                    </a>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-3">

        <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="clearfix"></div>

        <div class="card">
            <?php echo $__env->make('roles.table', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/roles/index.blade.php ENDPATH**/ ?>