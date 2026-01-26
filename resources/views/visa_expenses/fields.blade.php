<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="form-group col-sm-6">
    {!! Form::label('reference_number', 'Reference Number:', ['class' => 'required']) !!}
    {!! Form::text('reference_number', $visaExpenses->reference_number ?? '', ['class' => 'form-control', 'placeholder' => 'Reference Number', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:' , ['class' => 'required']) !!}
    {!! Form::date('date', $visaExpenses->date ?? 'null', ['class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    <label class="">Visa Status:</label>
    <select class="form-control select2" id="visa_status" name="visa_status" required>
        <option value="">Select Status</option>
        @foreach($visaStatuses as $status)
        <option value="{{ $status->name }}"
            data-fee="{{ $status->default_fee }}"
            {{ (isset($visaExpenses) && $visaExpenses->visa_status == $status->name) ? 'selected' : '' }}>
            {{ $status->name }}
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'required']) !!}
    {!! Form::number('amount', $visaExpenses->amount ?? '', ['id' => 'amount', 'step' =>'any' ,'class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
    {!! Form::month('billing_month', isset($visaExpenses) && $visaExpenses->billing_month ? \Carbon\Carbon::parse($visaExpenses->billing_month)->format('Y-m') : null, ['class' => 'form-control' , 'required']) !!}
</div>
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'required']) !!}
    {!! Form::textarea('detail', $visaExpenses->detail ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'required']) !!}
</div>
@push('scripts')
<script>
    $(document).ready(function() {

        function getVisaStatusFee() {
            let fee = $('#visa_status option:selected').data('fee');
            console.log(fee);
            $('#amount').val(fee ? fee : '');
        }

        // bind change
        $('#visa_status').on('change', function() {
            getVisaStatusFee();
        });

        // initial load (edit case)
        getVisaStatusFee();
    });
</script>

@endpush