{!! Form::open(['route' => 'riders.storeadvanceloan','id'=>'formajax']) !!}


<input type="hidden" id="reload_page" value="1">
<div class="row">
    @include('riders.loan_fields', ['rider' => $rider, 'vt' => 'AL' , 'account' => $account, 'bank_accounts' => $bank_accounts])
</div>


<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary','onclick'=>'getTotal();']) !!}
</div>

{!! Form::close() !!}
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
</script>