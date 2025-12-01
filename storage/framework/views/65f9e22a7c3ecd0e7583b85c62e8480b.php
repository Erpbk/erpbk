
<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title">Riders for Recruiter: <?php echo e($recruiter->name); ?></h3>
            <div class="card-tools">
                <a href="<?php echo e(route('recruiters.assign-riders', $recruiter->id)); ?>" class="btn btn-success btn-sm">
                    <i class="fa fa-plus"></i> Assign More Riders
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" id="select-all-riders">
                        </th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Contact Number</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $riders; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $rider): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                    <tr>
                        <td>
                            <input type="checkbox" name="rider_ids[]" value="<?php echo e($rider->id); ?>"
                                class="rider-checkbox">
                        </td>
                        <td><?php echo e($rider->name); ?></td>
                        <td><?php echo e($rider->email); ?></td>
                        <td><?php echo e($rider->contact_number); ?></td>
                        <td>
                            <?php if($rider->status == 1): ?>
                            <span class="badge bg-success">Active</span>
                            <?php else: ?>
                            <span class="badge bg-danger">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class='btn-group'>
                                <a href="<?php echo e(route('riders.show', $rider->id)); ?>" class='btn btn-info btn-sm'>
                                    <i class="fa fa-eye"></i>
                                </a>
                                <button class="btn btn-danger btn-sm remove-rider"
                                    data-rider-id="<?php echo e($rider->id); ?>">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <tr>
                        <td colspan="6" class="text-center">No riders found for this recruiter.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php echo e($riders->links('components.global-pagination')); ?>

        </div>
        <div class="card-footer">
            <div class="d-flex justify-content-between">
                <div>
                    <button id="remove-selected-riders" class="btn btn-danger" style="display:none;">
                        <i class="fa fa-trash"></i> Remove Selected Riders
                    </button>
                </div>
                <a href="<?php echo e(route('recruiters.index')); ?>" class="btn btn-secondary">Back to Recruiters</a>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php $__env->startSection('page-script'); ?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Select all checkboxes
        $('#select-all-riders').on('change', function() {
            $('.rider-checkbox').prop('checked', $(this).prop('checked'));
            toggleRemoveButton();
        });

        // Individual checkbox change
        $('.rider-checkbox').on('change', function() {
            toggleRemoveButton();

            // Uncheck select all if not all riders are selected
            $('#select-all-riders').prop('checked',
                $('.rider-checkbox:checked').length === $('.rider-checkbox').length
            );
        });

        // Toggle remove button visibility
        function toggleRemoveButton() {
            const selectedCount = $('.rider-checkbox:checked').length;
            $('#remove-selected-riders').toggle(selectedCount > 0);
        }

        // Remove selected riders
        $('#remove-selected-riders').on('click', function() {
            const selectedRiderIds = $('.rider-checkbox:checked').map(function() {
                return $(this).val();
            }).get();

            if (selectedRiderIds.length === 0) return;

            Swal.fire({
                title: 'Remove Riders',
                text: `Are you sure you want to remove ${selectedRiderIds.length} riders from this recruiter?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, remove them!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?php echo e(route('recruiters.remove-riders', $recruiter->id)); ?>",
                        method: 'POST',
                        data: {
                            _token: '<?php echo e(csrf_token()); ?>',
                            rider_ids: selectedRiderIds
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Riders Removed',
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON?.message || 'Failed to remove riders'
                            });
                        }
                    });
                }
            });
        });


        // Remove individual rider
        $('.remove-rider').on('click', function() {
            const riderId = $(this).data('rider-id');

            Swal.fire({
                title: 'Remove Rider',
                text: 'Are you sure you want to remove this rider from the recruiter?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                confirmButtonText: 'Yes, remove!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: "<?php echo e(route('recruiters.remove-riders', $recruiter->id)); ?>",
                        method: 'POST',
                        data: {
                            _token: '<?php echo e(csrf_token()); ?>',
                            rider_ids: [riderId]
                        },
                        success: function(response) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Rider Removed',
                                text: response.message
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: xhr.responseJSON.message || 'Failed to remove rider'
                            });
                        }
                    });
                }
            });
        });
    });
</script>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/recruiters/riders.blade.php ENDPATH**/ ?>