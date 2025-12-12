<?php echo Form::model($rtaFines, ['route' => ['rtaFines.update'], 'method' => 'patch', 'files' => true]); ?>

<input type="hidden" name="id" value="<?php echo e($rtaFines->id); ?>">
<input type="hidden" id="reload_page" value="1">

<div class="row">
    <?php echo $__env->make('rta_fines.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>

</div>

<?php echo Form::close(); ?><?php /**PATH C:\xampp\htdocs\erpbk\resources\views/rta_fines/edit.blade.php ENDPATH**/ ?>