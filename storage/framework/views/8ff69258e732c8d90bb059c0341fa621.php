

        <?php echo $__env->make('adminlte-templates::common.errors', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>



            <?php echo Form::model($user, ['route' => ['users.update', $user->id], 'method' => 'patch','id'=>'formajax']); ?>


                <div class="row">
                    <?php echo $__env->make('users.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
<br>
<button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>


            <?php echo Form::close(); ?>


<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/users/edit.blade.php ENDPATH**/ ?>