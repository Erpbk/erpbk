<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="form-group col-sm-6">
    {!! Form::label('reference_number', 'Reference Number:', ['class' => 'required']) !!}
    {!! Form::text('reference_number', $bikeRegistration->reference_number ?? '', ['class' => 'form-control', 'placeholder' => 'Reference Number', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:' , ['class' => 'required']) !!}
    {!! Form::date('date', $bikeRegistration->date ?? 'null', ['class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    <label class="">Registration Status:</label>
    <select class="form-control select2" id="registration_status" name="registration_status" required>
        <option value="">Select Status</option>
        @foreach($registrationStatuses as $status)
        <option value="{{ $status->name }}"
            data-fee="{{ $status->default_fee }}"
            {{ (isset($bikeRegistration) && $bikeRegistration->registration_status == $status->name) ? 'selected' : '' }}>
            {{ $status->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'required']) !!}
    {!! Form::number('amount', $bikeRegistration->amount ?? '', ['id' => 'amount', 'step' =>'any' ,'class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
    {!! Form::month('billing_month', isset($bikeRegistration) && $bikeRegistration->billing_month ? \Carbon\Carbon::parse($bikeRegistration->billing_month)->format('Y-m') : null, ['class' => 'form-control' , 'required']) !!}
</div>
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'required']) !!}
    {!! Form::textarea('detail', $bikeRegistration->detail ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'required']) !!}
</div>
@push('scripts')
<script>
    $(document).ready(function() {

        function getRegistrationStatusFee() {
            let fee = $('#registration_status option:selected').data('fee');
            $('#amount').val(fee ? fee : '');
        }

        $('#registration_status').on('change', function() {
            getRegistrationStatusFee();
        });

        getRegistrationStatusFee();
    });
</script>

@endpush
