{!! Form::model($vouchers, ['route' => ['vouchers.update', $vouchers->id], 'method' => 'patch', 'id'=>'formajax', 'enctype' => 'multipart/form-data']) !!}

<div class="px-3 pt-2 pb-1">
    @include('vouchers.fields')
</div>
<div class="action-btn rpf-actions">
    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save Voucher', ['class' => 'btn btn-primary rpf-save-btn', 'onclick' => 'if(typeof getTotal===\'function\'){getTotal();}']) !!}
</div>
@include('partials.uvf_styles')

{!! Form::close() !!}

<script>
$(document).on('ready',function(){
    $(".append-line").empty();
});
</script>
