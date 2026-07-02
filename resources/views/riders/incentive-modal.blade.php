{!! Form::open(['route' => 'riders.storeincentive','id'=>'formajax']) !!}

<input type="hidden" id="reload_page" value="1">
<input type="hidden" name="branch_id" value="{{ $rider->branch_id }}">

<div class="row">
    @include('vouchers.incentive_fields', ['rider' => $rider])
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