{!! Form::model($vouchers, ['route' => ['vouchers.update', $vouchers->id], 'method' => 'patch', 'id'=>'formajax', 'enctype' => 'multipart/form-data']) !!}

<div class="px-1 pt-2 pb-1">
    @include('vouchers.fields')
</div>
<div class="voucher-form-actions">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary', 'onclick' => 'getTotal();']) !!}
</div>
<style>
.voucher-form-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
    padding: 0.75rem 1rem 0.25rem;
    border-top: 1px solid #d7e2f2;
    margin-top: 0.75rem;
}
</style>

{!! Form::close() !!}

<script>
$(document).on('ready',function(){
    $(".append-line").empty();
});

</script>
