<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr>
         <th colspan="12" class="text-start">
            <div class="d-flex justify-content-between align-items-center">
               <h5 class="mb-0">Rider Invoices</h5>
               <div class="d-flex align-items-center">
                  <button id="deleteSelectedBtn" class="btn btn-danger btn-sm me-2" style="display: none;" onclick="deleteSelectedInvoices()">
                     <i class="fa fa-trash"></i> Delete Selected
                  </button>
                  <span id="current-month-total" class="badge bg-primary fs-6">
                     Current Month Total: <?php echo e(number_format($currentMonthTotal, 1)); ?>

                  </span>
               </div>
            </div>
         </th>
      </tr>
      <tr role="row">
         <th title="Select All" class="sorting_disabled" rowspan="1" colspan="1" width="50px">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
         </th>
         <th title="Id" class="sorting" rowspan="1" colspan="1">Id</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1">Inv Date</th>
         <th title="Billing Month" class="sorting" rowspan="1" colspan="1">Billing Month</th>
         <th title="Rider" class="sorting" rowspan="1" colspan="1">Rider</th>
         <th title="Descriptions" class="sorting" rowspan="1" colspan="1">Descriptions</th>
         <th title="Zone" class="sorting" rowspan="1" colspan="1">Project</th>
         <th title="Subtotal" class="sorting" rowspan="1" colspan="1">Subtotal</th>
         <th title="Vat" class="sorting" rowspan="1" colspan="1">Vat</th>
         <th title="Total Amount" class="sorting" rowspan="1" colspan="1">Total Amount</th>
         <th title="Total Amount" class="sorting" rowspan="1" colspan="1">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);"> <i class="fa fa-filter"></i></a>
         </th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center">
         <td>
            <input type="checkbox" class="invoice-checkbox" value="<?php echo e($r->id); ?>" onchange="updateDeleteButton()">
         </td>
         <td><?php echo e($r->id); ?></td>
         <td><?php echo e(\Carbon\Carbon::parse($r->inv_date)->format('d M Y')); ?></td>
         <td><?php echo e(\Carbon\Carbon::parse($r->billing_month)->format('M Y')); ?></td>
         <?php
         $rider = DB::Table('riders')->where('id' , $r->rider_id)->first();
         ?>
         <td><?php echo e($rider->rider_id . '-' . $rider->name); ?></td>
         <td><?php echo e($r->descriptions); ?></td>
         <td>
            <?php echo e(DB::table('customers')->where('id' , $rider->customer_id)->first()->name ?? '-'); ?>

         </td>
         <td>AED <?php echo e(number_format($r->subtotal, 2)); ?></td>
         <td><?php echo e($r->vat); ?></td>
         <td>AED <?php echo e(number_format($r->total_amount, 2)); ?></td>
         <td>
            <?php if($r->status == 1): ?>
            <span class="badge  bg-success">Paid</span>
            <?php else: ?>
            <span class="badge  bg-danger">Unpaid</span>
            <?php endif; ?>
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_view')): ?>
                  <a href="<?php echo e(route('riderInvoices.show', $r->id)); ?>" class='dropdown-item waves-effect' target="_blank">
                     <i class="fa fa-eye mx-1"></i> View
                  </a>
                  <?php endif; ?>
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_edit')): ?>
                  <a href="javascript:void(0);" data-action="<?php echo e(route('riderInvoices.edit', $r->id)); ?>" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Update Invoice">
                     <i class="fa fa-edit mx-1"></i> Update
                  </a>
                  <?php endif; ?>
                  <?php if($r->status == 0): ?>
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_edit')): ?>
                  <a href="<?php echo e(route('riderInvoices.markAsPaid', $r->id)); ?>" class='dropdown-item waves-effect'>
                     <i class="fa fa-money-bill mx-1 text-success"></i> Mark as Paid
                  </a>
                  <?php endif; ?>
                  <?php endif; ?>
                  <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('riderinvoice_delete')): ?>
                  <a href="javascript:void(0);" onclick="confirmDelete('<?php echo e(route('riderInvoices.delete', $r->id)); ?>')" class='dropdown-item waves-effect'>
                     <i class="fa fa-trash mx-1"></i> Delete
                  </a>
                  <?php endif; ?>
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
</div><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_invoices/table.blade.php ENDPATH**/ ?>