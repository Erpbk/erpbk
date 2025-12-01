<?php $__env->startSection('page_content'); ?>
<?php
?>
    <div class="card table-responsive px-2 py-0" >
        <?php echo $__env->make('bike_histories.table', ['bikeHistory' => $bikeHistory], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('bikes.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/bike_histories/index.blade.php ENDPATH**/ ?>