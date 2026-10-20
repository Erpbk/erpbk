{!! Form::open(['route' => 'riders.storevendorcharges', 'id' => 'formajax', 'enctype' => 'multipart/form-data']) !!}

<input type="hidden" id="reload_page" value="1">

<div class="px-3 pt-2 pb-1">
    @include('vouchers.vendorcharges_fields', ['rider' => $rider])
    <div class="row mt-2">
        @include('vouchers._custom_fields_section')
    </div>
</div>

<div class="action-btn rpf-actions">
    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save Voucher', ['class' => 'btn btn-primary rpf-save-btn', 'onclick' => 'if(typeof getTotal===\'function\'){getTotal();}']) !!}
</div>

{!! Form::close() !!}
