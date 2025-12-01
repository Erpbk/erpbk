<?php echo Form::open(['route' => 'bikes.store','id'=>'formajax']); ?>


<div class="card-body">
  <input type="hidden" name="created_by" value="<?php echo e(Auth::user()->id); ?>">
  <div class="row">
    <?php echo $__env->make('bikes.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
  </div>

</div>

<div class="action-btn pt-3">
  <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
  <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>

</div>

<?php echo Form::close(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/bikes/create.blade.php ENDPATH**/ ?>