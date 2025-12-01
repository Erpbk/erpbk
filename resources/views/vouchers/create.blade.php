{!! Form::open(['route' => 'vouchers.store', 'id'=>'formajax', 'class' => 'form-with-fixed-footer']) !!}
<input type="hidden" id="reload_page" value="1">

<div class="card-body card-body-with-footer">
    @include('vouchers.fields')
</div>


<div class="card-footer fixed-footer " style="z-index: 1 !important; text-align: right;">
    {!! Form::submit('Save', ['class' => 'btn btn-primary','onclick'=>'getTotal();']) !!}
</div>

{!! Form::close() !!}
<script>
    // Wait for jQuery to be available
    (function() {
        if (typeof jQuery === 'undefined') {
            setTimeout(arguments.callee, 50);
            return;
        }

        $(document).ready(function() {
            getTotal();
        });
    })();
</script>