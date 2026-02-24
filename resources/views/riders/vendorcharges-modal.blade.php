{!! Form::open(['route' => 'riders.storevendorcharges','id'=>'formajax']) !!}

<input type="hidden" id="reload_page" value="1">


<div class="row">
    @include('vouchers.vendorcharges_fields', ['bank_accounts' => $bank_accounts])
</div>
@include('vouchers._custom_fields_section')

<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary','onclick'=>'getTotal();']) !!}
</div>

{!! Form::close() !!}
<script>
    $(document).ready(function() {
        getTotal();
    });
</script>