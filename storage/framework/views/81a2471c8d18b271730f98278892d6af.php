<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Date: activate to sort column ascending">Date</th>
         <th title="ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="ID: activate to sort column ascending">ID</th>
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending">Name</th>
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Name: activate to sort column ascending">Fleet/Zone</th>
         <th title="Fleet Supr" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Fleet Supr">Fleet Supr</th>
         <th title="Delivered" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Delivered: activate to sort column ascending">Delivered</th>
         <th title="Ontime%" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ontime%: activate to sort column ascending">Ontime%</th>
         <th title="Rejected" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rejected: activate to sort column ascending">Rejected</th>
         <th title="HR" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="HR: activate to sort column ascending">HR</th>
         <th title="Rating" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rating: activate to sort column ascending">Valid Day</th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center">
         <td><?php echo e(\Carbon\Carbon::parse($r->date)->format('d M Y')); ?></td>
         <td><?php echo e($r->d_rider_id); ?></td>
         <?php
         $rider = DB::Table('riders')->where('id' , $r->rider_id)->first();
         ?>
         <td> <a href="<?php echo e(route('rider.activities',$r->rider_id)); ?>"><?php echo e($rider->name); ?></a> </td>
         <td><?php echo e($rider->emirate_hub); ?></td>
         <td><?php echo e($rider->fleet_supervisor); ?></td>
         <td><?php echo e($r->delivered_orders); ?></td>
         <td><?php if($r->ontime_orders_percentage): ?><?php echo e($r->ontime_orders_percentage * 100); ?>% <?php else: ?> - <?php endif; ?></td>
         <td><?php echo e($r->rejected_orders); ?></td>
         <td><?php echo e($r->login_hr); ?></td>
         <td>
            <?php echo e($r->delivery_rating); ?>

         </td>
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
</div><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_live_activities/table.blade.php ENDPATH**/ ?>