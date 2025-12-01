

<?php $__env->startSection('content'); ?>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Visa Status Management</h1>
            </div>
            <div class="col-sm-6">
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaexpense_create')): ?>
                <a class="btn btn-primary float-end" href="<?php echo e(route('visa-statuses.create')); ?>">
                    Add New Status
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
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="dataTableBuilder">
                    <thead>
                        <tr>
                            <th class="sorting">ID</th>
                            <th class="sorting">Code</th>
                            <th class="sorting">Name</th>
                            <th class="sorting">Category</th>
                            <th class="sorting">Default Fee</th>
                            <th class="sorting">Required</th>
                            <th class="sorting">Status</th>
                            <th class="sorting">Display Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $visaStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($status->id); ?></td>
                            <td><?php echo e($status->code ?? 'N/A'); ?></td>
                            <td><?php echo e($status->name); ?></td>
                            <td><?php echo e($status->category); ?></td>
                            <td><?php echo e(number_format($status->default_fee, 2)); ?></td>
                            <td>
                                <span class="badge bg-<?php echo e($status->is_required ? 'primary' : 'secondary'); ?>">
                                    <?php echo e($status->is_required ? 'Yes' : 'No'); ?>

                                </span>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo e($status->is_active ? 'success' : 'danger'); ?>">
                                    <?php echo e($status->is_active ? 'Active' : 'Inactive'); ?>

                                </span>
                            </td>
                            <td><?php echo e($status->display_order); ?></td>
                            <td>
                                <div class='btn-group'>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaexpense_edit')): ?>
                                    <a href="<?php echo e(route('visa-statuses.edit', $status->id)); ?>" class='btn btn-sm btn-primary'>
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="<?php echo e(route('visa-statuses.toggle-active', $status->id)); ?>" class='btn btn-sm btn-<?php echo e($status->is_active ? 'warning' : 'success'); ?>' title="<?php echo e($status->is_active ? 'Deactivate' : 'Activate'); ?>">
                                        <i class="fas fa-<?php echo e($status->is_active ? 'ban' : 'check'); ?>"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('visaexpense_delete')): ?>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('<?php echo e(route('visa-statuses.destroy', $status->id)); ?>')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-<?php echo e($status->id); ?>" action="<?php echo e(route('visa-statuses.destroy', $status->id)); ?>" method="POST" style="display: none;">
                                        <?php echo csrf_field(); ?>
                                        <?php echo method_field('DELETE'); ?>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('page-script'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '<?php echo e(csrf_token()); ?>';
                form.appendChild(csrfToken);

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/visa_statuses/index.blade.php ENDPATH**/ ?>