<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Number: activate to sort column ascending" >Number</th>
         <th title="Company" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Company: activate to sort column ascending" >Company</th>
         <th title="Emi" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Emi: activate to sort column ascending" >Emi</th>
         <th title="fleetsupervisor" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Fleet SuperVisor: activate to sort column ascending" >Fleet SuperVisor</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);" > <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);" > <i class="fa fa-filter"></i></a> 
         </th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center">
         <td><?php echo e($r->number); ?></td>
         <td><?php echo e($r->company); ?></td>
         <td><?php echo e($r->emi); ?></td>
         <td><?php echo e($r->fleet_supervisor); ?></td>
         <td>
            <div class='btn-group'>
               <!-- <a href="<?php echo e(route('sims.show', $r->id)); ?>" class='btn btn-default btn-xs'>
                        <i class="fa fa-eye"></i>
                    </a> -->
               <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sim_edit')): ?>
               <a href="javascript:void(0);" data-size="lg" data-title="Update Sim" data-action="<?php echo e(route('sims.edit', $r->id)); ?>" class='btn btn-info btn-sm show-modal'>
                    <i class="fa fa-edit"></i>
                </a>
               <?php endif; ?>
               <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('sim_delete')): ?>
               <a href="javascript:void(0);"  onclick='confirmDelete("<?php echo e(route('sims.delete', $r->id)); ?>")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Sim">
               <i class="fa fa-trash"></i>
               </a>
               <?php endif; ?>
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
<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
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
</div><?php /**PATH /var/www/laravel/resources/views/sims/table.blade.php ENDPATH**/ ?>