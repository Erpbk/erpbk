<!-- Action Buttons Component -->
<div class="row mt-3">
    <div class="col-12">
        <div class="d-flex justify-content-start">
            <?php if(isset($result)): ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('rider_edit')): ?>
            <a href="<?php echo e(route('riders.edit', $result['id'])); ?>" class="btn btn-outline-primary btn-sm waves-effect waves-light me-1"><i class="fa fa-edit"></i>&nbsp;Edit</a>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('email_create')): ?>
            <a href="javascript:void();" data-action="<?php echo e(route('rider.sendemail', $result['id'])); ?>" data-size="md"
                data-title="<?php echo e($result['name'] . ' (' . $result['rider_id']); ?>')" class="btn btn-outline-warning btn-sm show-modal text-nowrap"><i class="fas fa-envelope"></i>&nbsp;Send Email</a>
            <?php endif; ?>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('timeline_create')): ?>
            <a href="javascript:void(0);" data-action="<?php echo e(url('riders/job_status/' . $result['id'])); ?>" data-size="md" data-title="Add Timeline" class="btn btn-outline-success btn-sm text-nowrap show-modal mx-1"><i class="fas fa-chart-bar"></i>&nbsp;Add Timeline</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div><?php /**PATH /var/www/laravel/resources/views/riders/action-buttons.blade.php ENDPATH**/ ?>