<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Rider ID</th>
         <th style="width: 180px;" title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Name</th>
         <th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Rider Status</th>
         <th title="Person Code" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Person Code</th>
         <th title="Person Code" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Labour Card #</th>
         <th title="Person Code" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Policy Number</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Balance</th>
         <th title="Person Code" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Created By</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Updated By</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);"> <i class="fa fa-filter"></i></a>
         </th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <?php
      // Check if rider has an active bike assignment
      $hasActiveBike = DB::table('bikes')
      ->where('rider_id', $r->ref_id)
      ->where('warehouse', 'Active')
      ->exists();

      // Determine status based on bike assignment
      $isActive = $hasActiveBike;
      $badgeClass = $isActive ? 'bg-label-success' : 'bg-label-danger';

      $rider = DB::table('riders')->where('id' , $r->ref_id)->first();
      ?>
      <tr class="text-center">
         <td class=""><?php echo e($rider->rider_id ?? ''); ?></td>
         <td class="text-start"><a href="<?php echo e(route('VisaExpense.generatentries' , $r->id)); ?>"><?php echo e($r->name); ?></a><br> </td>
         <td>
            <span class="badge <?php echo e($badgeClass); ?>">
               <?php if($isActive): ?>Active
               <?php else: ?> Inactive
               <?php endif; ?>
            </span>
         </td>
         <td><?php echo e($rider->person_code ?? '-'); ?></td>
         <td><?php echo e($rider->labor_card_number ?? '-'); ?></td>
         <td><?php echo e($rider->policy_no ?? '-'); ?></td>
         <?php
         $balance = DB::table('visa_expenses')->where('rider_id' , $r->id)->sum('amount')
         ?>
         <td><?php if($balance == ''): ?> - <?php else: ?> AED <?php echo e($balance ?? '-'); ?> <?php endif; ?></td>
         <td><?php echo e(\App\Helpers\Common::UserName($r->Created_By)); ?></td>
         <td><?php echo e(\App\Helpers\Common::UserName($r->Updated_By)); ?></td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                  <a href="<?php echo e(route('VisaExpense.generatentries' , $r->id)); ?>" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye"></i> View
                  </a>
                  <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount<?php echo e($r->id); ?>" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit"></i> Edit
                  </a>
                  <a href="javascript:void(0);" onclick='confirmDelete("<?php echo e(route('VisaExpense.deleteaccount', $r->id)); ?>")' class='dropdown-item waves-effect confirm-modal' data-size="lg" data-title="Delete Account">
                     <i class="fa fa-trash"></i> Delete
                  </a>
               </div>
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
                  <form action="<?php echo e(route('VisaExpense.editaccount')); ?>" method="POST">
                     <?php echo csrf_field(); ?>
                     <input type="hidden" name="id" name="id" value="<?php echo e($r->id); ?>">
                     <div class="row">
                        <div class="form-group col-md-12">
                           <label for="rider">Select Rider</label>
                           <select class="form-control rider-select" id="rider" name="rider_id">
                              <option value="" selected>Select</option>
                              <?php $__currentLoopData = DB::table('riders')->where('status' , 1)->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $ri): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                              <option value="<?php echo e($ri->id); ?>" <?php if($ri->id == $r->ref_id): ?> selected <?php endif; ?>><?php echo e($ri->rider_id); ?> - <?php echo e($ri->name); ?></option>
                              <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                           </select>
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
      <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
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
</div><?php /**PATH /var/www/laravel/resources/views/visa_expenses/account_table.blade.php ENDPATH**/ ?>