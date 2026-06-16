<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="form-group col-sm-6">
    {!! Form::label('reference_number', 'Reference Number:', ['class' => 'required']) !!}
    {!! Form::text('reference_number', $LicenseExpenses->reference_number ?? '', ['class' => 'form-control', 'placeholder' => 'Reference Number', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:' , ['class' => 'required']) !!}
    {!! Form::date('date', $LicenseExpenses->date ?? 'null', ['class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    <label class="">License Status:</label>
    <select class="form-control select2" id="license_status" name="license_status" required>
        <option value="">Select Status</option>
        @foreach($licenseStatuses as $status)
        <option value="{{ $status->name }}"
            data-fee="{{ $status->default_fee }}"
            {{ (isset($LicenseExpenses) && $LicenseExpenses->license_status == $status->name) ? 'selected' : '' }}>
            {{ $status->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'required']) !!}
    {!! Form::number('amount', $LicenseExpenses->amount ?? '', ['id' => 'amount', 'step' =>'any' ,'class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
    {!! Form::month('billing_month', isset($LicenseExpenses) && $LicenseExpenses->billing_month ? \Carbon\Carbon::parse($LicenseExpenses->billing_month)->format('Y-m') : null, ['class' => 'form-control' , 'required']) !!}
</div>
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'required']) !!}
    {!! Form::textarea('detail', $LicenseExpenses->detail ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'required']) !!}
</div>
@push('scripts')
<script>
    $(document).ready(function() {

        function getLicenseStatusFee() {
            let fee = $('#license_status option:selected').data('fee');
            console.log(fee);
            $('#amount').val(fee ? fee : '');
        }

        // bind change
        $('#license_status').on('change', function() {
            getLicenseStatusFee();
        });

        // initial load (edit case)
        getLicenseStatusFee();
    });
</script>

@endpush
