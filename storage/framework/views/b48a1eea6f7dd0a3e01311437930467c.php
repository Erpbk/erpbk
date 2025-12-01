<?php $__env->startPush('third_party_stylesheets'); ?>
<?php $__env->stopPush(); ?>
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Name: activate to sort column ascending">Name</th>
         <th title="Title" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Title: activate to sort column ascending">Title</th>
         <th title="Account No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Account No: activate to sort column ascending">Account No</th>
         <th title="Balance" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Balance: activate to sort column ascending">Balance</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);" > <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);" > <i class="fa fa-filter"></i></a> 
         </th>
      </tr>
   </thead>
   <tbody>
      <?php $__currentLoopData = $data; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $r): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
      <tr class="text-center">
         <td><a href="<?php echo e(route('bank.files' , $r->id)); ?>"><?php echo e($r->name); ?></a><br/></td>
         <td><?php echo e($r->title); ?></td>
         <td><?php echo e($r->account_no); ?></td>
         <td><?php echo e($r->balance); ?></td>
         <td>
            <?php if($r->status == 1): ?>
                <span class="badge  bg-success">Active</span>
            <?php else: ?>
                <span class="badge  bg-danger">Inactive</span>
            <?php endif; ?>
         </td>
         <td>
            <div class='btn-group'>
                <!-- <a href="javascript:void(0);" data-action="<?php echo e(route('banks.show', $r->id)); ?>" class='btn btn-default btn-sm show-modal' data-size="lg" data-title="View">
                    <i class="fa fa-eye"></i>
                </a> -->
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bank_edit')): ?>
                <a href="javascript:void(0);" data-action="<?php echo e(route('banks.edit', $r->id)); ?>" class='btn btn-info btn-sm show-modal' data-size="lg" data-title="Update Bank">
                    <i class="fa fa-edit"></i>
                </a>
                <?php endif; ?>
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bank_delete')): ?>
                <a href="javascript:void(0);"  onclick='confirmDelete("<?php echo e(route('bank.delete', $r->id)); ?>")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Bank">
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
</div><?php /**PATH /var/www/laravel/resources/views/banks/table.blade.php ENDPATH**/ ?>