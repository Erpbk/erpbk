<?php echo Form::open(['route' => 'VisaExpense.store','id'=>'formajax']); ?>



<input type="hidden" id="reload_page" value="1">
<input type="hidden" name="rider_id" value="<?php echo e($data->id); ?>">
<div class="row">
    <?php echo $__env->make('visa_expenses.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>



<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>

</div>

<?php echo Form::close(); ?><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/visa_expenses/create.blade.php ENDPATH**/ ?>