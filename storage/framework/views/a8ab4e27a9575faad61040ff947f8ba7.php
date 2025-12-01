<?php $__env->startSection('title','Ledger'); ?>
<?php $__env->startSection('content'); ?>
<div class="">
    <h2>Ledger</h2>
<form action="" method="get">
    <div class="row mb-3">
        <div class="col-md-3">
          <?php echo Form::select('account', App\Models\Accounts::dropdown(null), request('account'), ['class' => 'form-select form-select-sm select2']); ?>

        </div>
        <div class="col-md-3">
            <input type="month" name="month" value="<?php echo e(request('month')); ?>" class="form-control" placeholder="Billing Month">
        </div>
        <div class="col-md-3">
            <button id="filter" class="btn btn-primary">Generate Ledger</button>
        </div>
    </div>
  </form>

    <div class="content">

      <?php echo $__env->make('flash::message', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>

      <div class="clearfix"></div>

      <div class="card">
        

    <?php $__env->startPush('third_party_stylesheets'); ?>
    <?php echo $__env->make('layouts.datatables_css', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
<?php $__env->stopPush(); ?>

<div class="card-body px-4" style="overflow-x: auto !important;">
    <?php echo $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']); ?>

</div>

<?php $__env->startPush('third_party_scripts'); ?>
    <?php echo $__env->make('layouts.datatables_js', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
    <?php echo $dataTable->scripts(); ?>

<?php $__env->stopPush(); ?>
      </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/ledger/index.blade.php ENDPATH**/ ?>