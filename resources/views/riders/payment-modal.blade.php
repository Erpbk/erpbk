{!! Form::open(['route' => 'riders.storepayment','id'=>'formajax']) !!}

<input type="hidden" id="reload_page" value="1">
<div class="row">
    @include('vouchers.payment_fields', ['bank_accounts' => $bank_accounts])
</div>

<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary','onclick'=>'getTotal();']) !!}
</div>

{!! Form::close() !!}
<script>
    $(document).ready(function() {
        getTotal();
    });
</script>