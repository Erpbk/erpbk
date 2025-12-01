<form action="<?php echo e(route('voucher.import')); ?>" method="POST" enctype="multipart/form-data" id="formajax">
  <div class="row">
    <div class="col-12">
      <a href="<?php echo e(url('sample/voucher_debit_sample.xlsx')); ?>" class="text-success w-100" download="Voucher Debit Sample">
        <i class="fa fa-file-download text-success"></i> &nbsp; Download Sample File
      </a>
    </div>
    <div class="col-12 mt-3">
      <label for="exampleInputEmail1">Credit Account</label>
      <?php echo Form::select('payment_from',App\Models\Accounts::customDropdown([1042,1118,1238,1005,1135,2172]),null ,['class' => 'form-control form-select ','id'=>'payment_type']); ?>

      <b>Note: </b>Select credit account carefully.
    </div>
    <div class="col-12 mt-3 mb-3">
      <label class="mb-1 pl-2">Select file for Debit</label>
      <input type="file" name="file" class="form-control mb-3" style="height: 40px;" />

    </div>
  </div>
  <button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Start Import</button>

</form><?php /**PATH /var/www/laravel/resources/views/vouchers/import.blade.php ENDPATH**/ ?>