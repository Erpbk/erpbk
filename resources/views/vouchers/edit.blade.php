
{!! Form::model($vouchers, ['route' => ['vouchers.update', $vouchers->id], 'method' => 'patch', 'id'=>'formajax']) !!}

<div class=" p-3">
    @include('vouchers.fields')
</div>


<div class="card-footer">
{!! Form::submit('Save', ['class' => 'btn btn-primary','onclick'=>'getTotal();']) !!}
</div>

{!! Form::close() !!}



<script>
$(document).on('ready',function(){
    $(".append-line").empty();
});

</script>

