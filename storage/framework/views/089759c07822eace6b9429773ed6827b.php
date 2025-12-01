
            <?php echo Form::model($visaExpenses, ['route' => ['VisaExpense.update'], 'method' => 'patch']); ?>

            <input type="hidden" name="id" value="<?php echo e($visaExpenses->id); ?>">
            <input type="hidden"  id="reload_page" value="1">
                <div class="row">
                    <?php echo $__env->make('visa_expenses.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>

            <div class="action-btn">
                <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>

            </div>

            <?php echo Form::close(); ?>

<?php /**PATH /var/www/laravel/resources/views/visa_expenses/edit.blade.php ENDPATH**/ ?>