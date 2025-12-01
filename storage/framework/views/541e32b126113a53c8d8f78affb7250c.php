<?php $__env->startSection('page_content'); ?>


          <div class=" card-action mb-0">

            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('bank_document')): ?>

            <div class="card-header align-items-center">
              <h5 class="card-action-title mb-0"><i class="ti ti-file-upload ti-lg text-body me-2"></i>Files</h5>
              <a class="btn btn-primary show-modal action-btn"
                       href="javascript:void(0);" data-action="<?php echo e(route('files.create',['type_id'=>request()->segment(3),'type'=>'bank'])); ?>" data-size="sm" data-title="Upload File">
                        Upload File
                    </a>
            </div>
            <div class="card-body pt-0 px-2">
              <?php $__env->startPush('third_party_stylesheets'); ?>
              <?php echo $__env->make('layouts.datatables_css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
          <?php $__env->stopPush(); ?>

          <div class="card-body px-0 pt-0" >
              <?php echo $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']); ?>

          </div>

          <?php $__env->startPush('third_party_scripts'); ?>
              <?php echo $__env->make('layouts.datatables_js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
              <?php echo $dataTable->scripts(); ?>

          <?php $__env->stopPush(); ?>
            </div>

            <?php else: ?>
            <div class="alert alert-warning  text-center m-3"><i class="fa fa-warning"></i> You don't have permission.</div>
            <?php endif; ?>

          </div>








<?php $__env->stopSection(); ?>

<?php echo $__env->make('banks.view', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/banks/document.blade.php ENDPATH**/ ?>