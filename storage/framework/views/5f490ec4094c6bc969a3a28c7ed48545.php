<div class="table-responsive text-nowrap">
    <table class="table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Item Code</th>
                <th>Quantity</th>
                <th>Price/Unit</th>
                <th>Avg Price</th>
                <th>Total Amount</th>
                <th>Supplier</th>
                <th>Status</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($item->name); ?></td>
                <td><?php echo e($item->item_code); ?></td>
                <td><?php echo e($item->qty); ?></td>
                <td><?php echo e(number_format($item->price, 2)); ?></td>
                <td><?php echo e(number_format($item->avg_price, 2)); ?></td>
                <td><?php echo e(number_format($item->total_amount, 2)); ?></td>
                <td><?php echo e($item->supplier ? $item->supplier->name : 'N/A'); ?></td>
                <td>
                    <?php if($item->status == 'In Stock'): ?>
                    <span class="badge bg-success"><?php echo e($item->status); ?></span>
                    <?php elseif($item->status == 'Low Stock'): ?>
                    <span class="badge bg-warning"><?php echo e($item->status); ?></span>
                    <?php else: ?>
                    <span class="badge bg-danger"><?php echo e($item->status); ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <div class="btn-group">
                        <a href="<?php echo e(route('garage-items.show', $item->id)); ?>" class="btn btn-info btn-sm" title="View">
                            <i class="ti ti-eye"></i>
                        </a>
                        <a href="<?php echo e(route('garage-items.vouchers', $item->id)); ?>" class="btn btn-secondary btn-sm" title="Vouchers">
                            <i class="ti ti-receipt"></i>
                        </a>
                        <a href="<?php echo e(route('garage-items.edit', $item->id)); ?>" class="btn btn-primary btn-sm" title="Edit">
                            <i class="ti ti-pencil"></i>
                        </a>
                        <form action="<?php echo e(route('garage-items.destroy', $item->id)); ?>" method="POST" onsubmit="return confirm('Are you sure you want to delete this item?')">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('DELETE'); ?>
                            <button type="submit" class="btn btn-danger btn-sm">
                                <i class="ti ti-trash"></i>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>

            <?php if(count($data) == 0): ?>
            <tr>
                <td colspan="9" class="text-center">No garage items found</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/garage_items/table.blade.php ENDPATH**/ ?>