<?php $__env->startSection('title', 'Bank Detail'); ?>

<?php $__env->startSection('content'); ?>
<?php
  $banks = App\Models\Banks::find(request()->segment(3));
?>

<div class="row" style="">
  <div class="col-xl-3 col-md-3 col-lg-5 order-1 order-md-0">
    <!-- User Card -->
    <div class="card mb-6">
      <div class="card-body pt-12">
        <div class="user-avatar-section">
          <div class=" d-flex align-items-center flex-column">

            <div class="user-info text-center">
              <h6><?php echo e($banks->name); ?></h6>
              <span class="badge bg-label-primary"><?php echo e($banks->branch); ?></span>

            </div>
          </div>
        </div>

        <h5 class="pb-4 border-bottom mb-4"></h5>
        <div class="info-container">
          <ul class="list-unstyled mb-6">

                      <ul class="p-0 mb-3" >
                        <li class="list-group-item pb-1" >
                            <b>Bank ID:</b> <span class="float-right"><?php echo e($banks->id); ?></span>
                         </li>

                         <li class="list-group-item pb-1" >
                            <b>Account Type:</b> <span class="float-right"><?php echo e($banks->account_type); ?></span>
                         </li>
                         <li class="list-group-item pb-1" >
                            <b>Account Title:</b> <span class="float-right"><?php echo e($banks->account_title); ?></span>
                         </li>
                          <li class="list-group-item pb-1" >
                            <b>Account No:</b> <span class="float-right"><?php echo e($banks->account_no); ?></span>
                         </li>
                          <li class="list-group-item pb-1" >
                            <b>Details:</b> <span class="float-right"><?php echo e($banks->notes); ?></span>
                         </li>
                         <li class="list-group-item pb-1" >
                            <b>Status:</b> <span class="float-right"><?php
                                if ($banks->status == 1) {
          echo '<span class="badge  bg-success">Active</span>';
        } else {
          echo '<span class="badge  bg-danger">Inactive</span>';
        }
                            ?></span>
                         </li>

                      </ul>

          </ul>
          <div class="d-flex justify-content-center">

            <a href="javascript:void(0);" data-title="Edit" data-action="<?php echo e(route('banks.edit', $banks->id)); ?>" class="btn btn-outline-primary btn-sm waves-effect waves-light btn-block me-1 show-modal"><i class="fa fa-edit"></i>&nbsp;Edit</a>
          </div>
        </div>
      </div>
    </div>
    <!-- /User Card -->

  </div>
  <div class="col-xl-9 col-md-9 col-lg-7 order-0 order-md-1">
    <div class="nav-align-top">
      <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-3 row-gap-2">

        <li class="nav-item"><a class="nav-link <?php if(request()->segment(2) =='files'): ?> active <?php endif; ?> " href="<?php echo e(route('bank.files',$banks->id)); ?>"><i class="ti ti-file-upload ti-sm me-1_5"></i>Files</a></li>
        <li class="nav-item"><a class="nav-link <?php if(request()->segment(2) =='ledger'): ?> active <?php endif; ?>" href="<?php echo e(route('bank.ledger',$banks->id)); ?>"><i class="ti ti-file ti-sm me-1_5"></i>Ledger</a></li>


      </ul>
    </div>

    <div class="card mb-5" id="cardBody" style="height:660px !important;overflow: auto;">
      <?php echo $__env->yieldContent('page_content'); ?>
    </div>



  </div>
</div>



<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH /var/www/laravel/resources/views/banks/view.blade.php ENDPATH**/ ?>