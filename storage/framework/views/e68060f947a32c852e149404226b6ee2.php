<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Name</th>
            <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Balance</th>
            <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Admin Charges</th>
            <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Status</th>
            <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
            <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
                <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);"> <i class="fa fa-filter"></i></a>
            </th>
        </tr>
    </thead>
    <tbody>
        <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <tr class="text-center">
            <td> <a href="<?php echo e(route('salik.tickets' , $r->id)); ?>"><?php echo e($r->name); ?></a><br> </td>
            <?php
            $balance = DB::table('saliks')->where('salik_account_id' , $r->id)->sum('total_amount')
            ?>
            <td><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($balance == ''): ?> - <?php else: ?> AED <?php echo e($balance ?? '-'); ?> <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
            <td><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->admin_charges == ''): ?> - <?php else: ?> AED <?php echo e($r->admin_charges); ?><?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?></td>
            <td>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($r->status == 1): ?>
                <span class="badge  bg-success">Active</span>
                <?php else: ?>
                <span class="badge  bg-danger">Inactive</span>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            </td>
            <td>
                <div class='btn-group'>
                    <a href="<?php echo e(route('salik.tickets' , $r->id)); ?>" class='btn btn-default btn-xs'>
                        <i class="fa fa-eye"></i>
                    </a>
                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount<?php echo e($r->id); ?>" class='btn btn-default btn-xs'>
                        <i class="fa fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" onclick='confirmDelete("<?php echo e(route('salik.deleteaccount', $r->id)); ?>")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Account">
                        <i class="fa fa-trash"></i>
                    </a>
                </div>
            </td>
            <td></td>
        </tr>

        <div class="modal modal-default filtetmodal fade" id="editaccount<?php echo e($r->id); ?>" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Update Account</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body" id="searchTopbody">
                        <form action="<?php echo e(route('salik.editaccount')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="id" name="id" value="<?php echo e($r->id); ?>">
                            <div class="row">
                                <div class="form-group col-md-12">
                                    <label for="name">Name</label>
                                    <input type="text" name="name" class="form-control" placeholder="Enter Your Account Name" value="<?php echo e($r->name); ?>">
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="name">Traffic Code Number</label>
                                    <input type="text" name="traffic_code_number" class="form-control" placeholder="Enter Your Account Name" value="<?php echo e($r->traffic_code_number); ?>">
                                </div>
                                <div class="form-group col-md-12">
                                    <label for="admin_charges">Admin Charges</label>
                                    <input type="number" name="admin_charges" class="form-control" placeholder="Enter Your Admin Charges" value="<?php echo e($r->admin_charges); ?>" step="0.01">
                                </div>
                                <div class="col-md-12 form-group text-center">
                                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Submit</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
    </tbody>
</table>
<?php echo $data->links('pagination'); ?>

<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Filter Riders</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="searchTopbody">
                <div style="display: none;" class="loading-overlay" id="loading-overlay">
                    <div class="spinner-border text-primary" role="status"></div>
                </div>
                <form id="filterForm" action="<?php echo e(route('banks.index')); ?>" method="GET">
                    <div class="row">
                        <div class="form-group col-md-12">
                            <input type="number" name="search" class="form-control" placeholder="Search">
                        </div>
                        <div class="col-md-12 form-group text-center">
                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/salik/account_table.blade.php ENDPATH**/ ?>