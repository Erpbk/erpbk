<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Transaction ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Transaction ID: activate to sort column ascending">Transaction ID</th>
            <th title="Rider Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider Name: activate to sort column ascending">Rider Name</th>
            <th title="Admin Charges" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Admin Charges: activate to sort column ascending">Billing Month</th>
            <th title="Trip Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Date: activate to sort column ascending">Trip Date</th>
            <th title="Trip Time" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Time: activate to sort column ascending">Trip Time</th>
            <th title="Toll Gate" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Toll Gate: activate to sort column ascending">Toll Gate</th>
            <th title="Direction" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Direction: activate to sort column ascending">Direction</th>
            <th title="Tag Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Tag Number: activate to sort column ascending">Tag Number</th>
            <th title="Plate No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Plate No: activate to sort column ascending">Plate No</th>
            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Total Amount</th>
            <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
        </tr>
    </thead>
    <tbody>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr class="text-center">
            <td><?php echo e($r->transaction_id); ?></td>
            <?php
            $rider = DB::table('riders')->where('id', $r->rider_id)->first();
            ?>
            <td><a href="<?php echo e(route('riders.show', $rider->id)); ?>"><?php echo e($rider->rider_id); ?> - <?php echo e($rider->name); ?></a></td>
            <td><?php echo e($r->billing_month ? \Carbon\Carbon::parse($r->billing_month)->format('M Y') : 'N/A'); ?></td>
            <td><?php echo e(App\Helpers\General::DateFormat($r->trip_date)); ?></td>
            <td><?php echo e($r->trip_time); ?></td>
            <td><?php echo e($r->toll_gate); ?></td>
            <td><?php echo e($r->direction); ?></td>
            <td><?php echo e($r->tag_number); ?></td>
            <td><?php echo e($r->plate); ?></td>
            <td>AED <?php echo e(number_format($r->total_amount, 2)); ?></td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                        <a href="<?php echo e(route('salik.show', $r->id)); ?>" class="dropdown-item waves-effect">
                            View
                        </a>
                        <a href="javascript:void(0);" data-action="<?php echo e(route('salik.edit' , $r->id)); ?>" data-size="lg" data-title="Update Salik" class='dropdown-item waves-effect show-modal'>
                            Edit
                        </a>
                        <a href="javascript:void(0);" onclick='confirmDelete("<?php echo e(route('salik.destroy', $r->id)); ?>")' class='dropdown-item confirm-modal' data-size="lg" data-title="Delete Ticket">
                            Delete
                        </a>
                    </div>
                </div>

            </td>
        </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </tbody>
</table>
<?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(method_exists($data, 'links')): ?>
    <?php echo $data->links('components.global-pagination'); ?>

<?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/salik/table.blade.php ENDPATH**/ ?>