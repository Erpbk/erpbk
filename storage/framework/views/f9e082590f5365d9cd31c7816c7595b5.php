

            <?php echo Form::model($invoice, ['route' => ['riderInvoices.update', $invoice->id], 'method' => 'patch','id'=>'formajax']); ?>


            <div class="card-body">
                <div class="row">
                    <?php echo $__env->make('rider_invoices.fields', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
                </div>
            </div>

            <div class="card-footer">
                <?php echo Form::submit('Save', ['class' => 'btn btn-primary']); ?>

                <a href="<?php echo e(route('riderInvoices.index')); ?>" class="btn btn-default"> Cancel </a>
            </div>

            <?php echo Form::close(); ?>


<?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/rider_invoices/edit.blade.php ENDPATH**/ ?>