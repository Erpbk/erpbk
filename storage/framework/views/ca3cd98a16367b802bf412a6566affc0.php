<?php $__env->startSection('title','Salik Details'); ?>
<?php $__env->startSection('content'); ?>
<div class="container">
    <h3>Salik Details</h3>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th>ID</th>
                    <td><?php echo e($salik->id); ?></td>
                </tr>
                <tr>
                    <th>Transaction ID</th>
                    <td><?php echo e($salik->transaction_id); ?></td>
                </tr>
                <tr>
                    <th>Trip Date</th>
                    <td><?php echo e($salik->trip_date); ?></td>
                </tr>
                <tr>
                    <th>Trip Time</th>
                    <td><?php echo e($salik->trip_time); ?></td>
                </tr>
                <tr>
                    <th>Post Date</th>
                    <td><?php echo e($salik->transaction_post_date); ?></td>
                </tr>
                <tr>
                    <th>Toll Gate</th>
                    <td><?php echo e($salik->toll_gate); ?></td>
                </tr>
                <tr>
                    <th>Direction</th>
                    <td><?php echo e($salik->direction); ?></td>
                </tr>
                <tr>
                    <th>Tag Number</th>
                    <td><?php echo e($salik->tag_number); ?></td>
                </tr>
                <tr>
                    <th>Plate</th>
                    <td><?php echo e($salik->plate); ?></td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>AED <?php echo e(number_format($salik->amount, 2)); ?></td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td><?php echo e($salik->status); ?></td>
                </tr>
                <tr>
                    <th>Created By</th>
                    <td><?php echo e($salik->created_by); ?></td>
                </tr>
                <tr>
                    <th>Updated By</th>
                    <td><?php echo e($salik->updated_by); ?></td>
                </tr>
            </table>
            <a href="<?php echo e(route('salik.index')); ?>" class="btn btn-default">Back</a>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/salik/show.blade.php ENDPATH**/ ?>