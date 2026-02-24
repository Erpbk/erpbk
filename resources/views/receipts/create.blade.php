{!! Form::open(['route' => 'receipts.store','id'=>'formajax', 'enctype' => 'multipart/form-data']) !!}

<div class="card-body">

    <div class="row">
        @include('receipts.fields2')
    </div>
    <div class="row mt-2">
        @include('vouchers._custom_fields_section')
    </div>

</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}