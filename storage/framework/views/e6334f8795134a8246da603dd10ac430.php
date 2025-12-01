<?php echo Form::open(['route' => 'riders.storeadvanceloan','id'=>'formajax']); ?>



<input type="hidden" id="reload_page" value="1">
<div class="row">
    <?php echo $__env->make('riders.loan_fields', ['rider' => $rider, 'vt' => 'AL' , 'account' => $account, 'bank_accounts' => $bank_accounts], \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?>
</div>


<div class="card-footer">
    <?php echo Form::submit('Save', ['class' => 'btn btn-primary','onclick'=>'getTotal();']); ?>

</div>

<?php echo Form::close(); ?>

<script>
    $(document).ready(function() {
        getTotal();

        // Auto-copy amount from first field to second field
        $('input[name="dr_amount[]"]').on('input', function() {
            var amount = $(this).val();
            // Copy to the second dr_amount field (credit account)
            $('input[name="dr_amount[]"]').eq(1).val(amount);
            getTotal();
        });
    });
</script><?php /**PATH D:\xammp1\htdocs\erpbk\resources\views/riders/advanceloan-modal.blade.php ENDPATH**/ ?>