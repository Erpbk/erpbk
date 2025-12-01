
            <?php echo Form::model($roles, ['route' => ['roles.update', $roles->id], 'method' => 'patch','id'=>'formajax']); ?>


         
                    <?php echo $__env->make('roles.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
              
            <div class="action-btn">
                <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>

              
            </div>

            <?php echo Form::close(); ?>

<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/roles/edit.blade.php ENDPATH**/ ?>