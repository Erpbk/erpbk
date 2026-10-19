{!! Form::model($payment, ['route' => ['payments.update', $payment->id], 'method' => 'patch','id'=>'formajax', 'enctype' => 'multipart/form-data']) !!}

<div class="px-3 pt-2 pb-1">
    <div class="row">
        @include('payments.fields')
    </div>
    <div class="row mt-2">
        @include('vouchers._custom_fields_section')
    </div>
</div>

<div class="action-btn rpf-actions">
    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save Payment', [
        'class' => 'btn btn-primary rpf-save-btn',
    ]) !!}
</div>

@include('partials.uvf_styles')

{!! Form::close() !!}
