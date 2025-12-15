<?php $__env->startSection('page_content'); ?>
<?php
?>
    <div>
        <?php echo $__env->make('bike_histories.table2', ['bikeHistory' => $bikeHistory], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('bikes.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/bike_histories/index.blade.php ENDPATH**/ ?>