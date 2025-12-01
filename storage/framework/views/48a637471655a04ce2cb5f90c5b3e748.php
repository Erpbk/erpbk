<?php $__env->startSection('page_content'); ?>

<div class="card card-action mb-6">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-chart-bar ti-lg text-body me-4"></i>Timeline</h5>
  </div>
  <div class="card-body pt-3 px-5">
    <ul class="timeline mb-0">
      <?php if(isset($job_status)): ?>
      <?php $__currentLoopData = $job_status; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <li class="timeline-item timeline-item-transparent">
        <span class="timeline-point timeline-point-primary"></span>
        <div class="timeline-event">
          <div class="timeline-header mb-3">
            <h6 class="mb-0"><a href="#"><?php echo e($row->user->name); ?></a> added reason </h6>
            <small class="text-muted"><?php echo e($row->created_at->diffForHumans()); ?><br /><?php echo e(App\Helpers\General::DateTimeFormat($row->created_at)); ?></small>
          </div>
          <p class="mb-2">
            <?php echo e($row->reason); ?>

          </p>
          
        </div>
      </li>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
      <?php endif; ?>

    </ul>
  </div>
</div>

<?php $__env->stopSection(); ?>
<?php echo $__env->make('riders.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/riders/timeline.blade.php ENDPATH**/ ?>