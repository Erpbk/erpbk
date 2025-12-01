<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Transation Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Transation Date: activate to sort column ascending">Billing Month</th>
         <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
         <th title="Voucher IDs" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Amount</th>
         <th title="Visa Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Visa Status: activate to sort column ascending" aria-sort="descending">Visa Status</th>
         <th title="Payment Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Payment Status: activate to sort column ascending" aria-sort="descending">Payment Status</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);"> <i class="fa fa-filter"></i></a>
         </th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center">
         <td><?php echo e(\Carbon\Carbon::parse($r->billing_month)->format('M Y')); ?></td>
         <td><?php echo e(\Carbon\Carbon::parse($r->date)->format('d M Y')); ?></td>
         <td>
            <span id="voucher_ids_display_<?php echo e($r->id); ?>">
               <?php if($r->payment_status === 'paid'): ?>
               <?php if($r->vouchers->isNotEmpty()): ?>
               <?php $__currentLoopData = $r->vouchers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $voucher): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
               <?php
               $voucherNumber = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
               ?>
               <a href="<?php echo e(route('vouchers.show', $voucher->id)); ?>" target="_blank"><?php echo e($voucherNumber); ?></a><?php if(!$loop->last): ?>, <?php endif; ?>
               <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
               <?php else: ?>
               <span class="text-muted">No voucher</span>
               <?php endif; ?>
               <?php else: ?>
               <span class="text-muted">-</span>
               <?php endif; ?>
            </span>
         </td>
         <td><?php echo e(number_format($r->amount, 2)); ?></td>
         <td>
            <span class="badge bg-primary"><?php echo e($r->visa_status); ?></span>
         </td>
         <td>
            <?php if($r->payment_status == 'paid'): ?>
            <span class="badge bg-success">Paid</span>
            <?php else: ?>
            <span class="badge bg-danger">Unpaid</span>
            <?php endif; ?>
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                  <a href="<?php echo e(route('VisaExpense.viewvoucher', $r->id)); ?>" class='dropdown-item waves-effect'>
                     View Expense Detail
                  </a>
                  <a href="javascript:void(0);" data-action="<?php echo e(route('VisaExpense.edit' , $r->id)); ?>" data-size="lg" data-title="New Fine" class='dropdown-item waves-effect show-modal'>
                     Edit
                  </a>
                  <a href="javascript:void(0);" onclick='confirmDelete("<?php echo e(route('VisaExpense.delete', $r->id)); ?>")' class='dropdown-item confirm-modal' data-size="lg" data-title="Delete Sim">
                     delete
                  </a>
               </div>
            </div>
         </td>
         <td></td>
      </tr>
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
   </tbody>
</table>
<?php if(method_exists($data, 'links')): ?>
<?php echo $data->links('components.global-pagination'); ?>

<?php endif; ?>
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
</div><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/visa_expenses/table.blade.php ENDPATH**/ ?>