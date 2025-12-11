<?php echo Form::open(['route' => 'files.store','id'=>'formajax','enctype'=>'multipart/form-data']); ?>



<div class="row">
    <?php echo $__env->make('files.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>


<div class="action-btn pt-3">
    <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>

</div>

<?php echo Form::close(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/files/create.blade.php ENDPATH**/ ?>