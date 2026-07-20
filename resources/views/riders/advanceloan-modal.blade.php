{!! Form::open(['route' => 'riders.storeadvanceloan','id'=>'formajax']) !!}

<input type="hidden" id="reload_page" value="1">

<div class="row">
    @include('riders.loan_fields', ['rider' => $rider, 'vt' => 'AL', 'account' => $account, 'bank_accounts' => $bank_accounts])
</div>
@include('vouchers._custom_fields_section')

<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary', 'onclick' => 'getTotal();']) !!}
</div>

{!! Form::close() !!}
<script>
    $(document).ready(function() {
        if (typeof getTotal === 'function') {
            getTotal();
        }
    });
</script>