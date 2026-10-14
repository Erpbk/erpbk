{!! Form::model($payment, ['route' => 'payments.store','id'=>'formajax', 'enctype' => 'multipart/form-data']) !!}

@php
    $isRiderPaymentCreate = ($invoiceType ?? null) === 'rider';
@endphp

<div class="{{ $isRiderPaymentCreate ? 'px-3 pt-2 pb-1' : 'card-body' }}">
    <div class="row">
        @include('payments.fields')
    </div>
    <div class="row mt-2">
        @include('vouchers._custom_fields_section')
    </div>
</div>

<div class="action-btn {{ $isRiderPaymentCreate ? 'rpf-actions' : '' }}">
    <button type="button" class="btn {{ $isRiderPaymentCreate ? 'btn-outline-primary' : 'btn-default' }}" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit($isRiderPaymentCreate ? 'Save Payment' : 'Save', [
        'class' => 'btn btn-primary' . ($isRiderPaymentCreate ? ' rpf-save-btn' : ''),
    ]) !!}
</div>

@if($isRiderPaymentCreate)
<style>
.rpf-actions {
    display: flex;
    justify-content: flex-end;
    gap: 0.6rem;
    padding: 0.75rem 1rem 0.25rem;
    border-top: 1px solid #d7e2f2;
    margin-top: 0.75rem;
}
.rpf-actions .btn-outline-primary {
    border-color: #3a6db5;
    color: #1e4b8f;
    background: #fff;
    min-width: 96px;
}
.rpf-save-btn {
    background: #1e4b8f !important;
    border-color: #1e4b8f !important;
    min-width: 130px;
    font-weight: 600;
}
</style>
@endif

{!! Form::close() !!}
