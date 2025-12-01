<?php $__env->startSection('title','Users'); ?>
<?php $__env->startSection('content'); ?>
<?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user_edit')): ?>
<h4 class="mb-4">Top 5 Roles</h4>

<p class="mb-4">A role provided access to predefined menus and features so that depending on <br> assigned role an administrator can have access to what user needs.</p>
<!-- Role cards -->
<div class="row g-4">
    <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <?php if($role->name != 'Super Admin'): ?>
    <div class="col-xl-4 col-lg-6 col-md-6">

    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between">
          <h6 class="fw-normal mb-2">Total <?php echo e($role->users->count()); ?> users</h6>

        </div>
        <div class="d-flex justify-content-between align-items-end mt-1">
          <div class="role-heading">
            <h4 class="mb-1"><?php echo e($role->name); ?></h4>
            <a href="javascript:;" class="role-edit-modal show-modal" data-title="Edit Role" data-size="lg" data-action="<?php echo e(route('roles.edit', $role->id)); ?>"><span>Edit Permissions</span></a>
          </div>
          <a href="javascript:void(0);" class="text-muted"><i class="ti ti-user ti-md"></i></a>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>

<?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

  <div class="col-xl-4 col-lg-6 col-md-6">
    <div class="card h-100">
      <div class="row h-100">
        <div class="col-sm-5">
          <div class="d-flex align-items-end h-100 justify-content-center mt-sm-0 mt-3 ">
            <img src="<?php echo e(asset('assets/img/illustrations/add-new-roles.png')); ?>" class="img-fluid mt-sm-4 mt-md-0 mb-4" alt="add-new-roles" width="83">
          </div>
        </div>
        <div class="col-sm-7">
          <div class="card-body text-sm-end text-center ps-sm-0">
            <button data-action="<?php echo e(route('roles.create')); ?>" data-title="Create New Role" data-size="lg" class="btn btn-primary mb-2 text-nowrap add-new-role show-modal">Add New Role</button>
            <a href="<?php echo e(route('roles.index')); ?>" class="btn btn-light mb-2 text-nowrap add-new-role ">Manage Roles</a>

          </div>
        </div>
      </div>
    </div>
  </div>
  <?php endif; ?>
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>Users</h3>
                </div>
                <div class="col-sm-6">
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('user_create')): ?>
                    <a class="btn btn-primary float-right show-modal" style="float:right;" data-action="<?php echo e(route('users.create')); ?>"
                       href="javascript:void(0)" data-title="Add User Account" data-size="xl">
                        Add User
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <div class="content px-md-3">

        <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

        <div class="clearfix"></div>

        <div class="card">
            <?php echo $__env->make('users.table', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
        </div>
    </div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/users/index.blade.php ENDPATH**/ ?>