{!! Form::open(['route' => 'riders.storevisaloan','id'=>'formajax']) !!}


<input type="hidden" id="reload_page" value="1">
<div class="row">
    @include('riders.loan_fields', ['rider' => $rider, 'vt' => 'VL' , 'account' => $account, 'bank_accounts' => $bank_accounts])
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