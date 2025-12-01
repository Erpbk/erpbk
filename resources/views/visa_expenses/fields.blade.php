<script src="{{ asset('js/modal_custom.js') }}"></script>

<div class="form-group col-sm-6">
    {!! Form::label('date', 'Date:' , ['class' => 'required']) !!}
    {!! Form::date('date', $visaExpenses->date ?? 'null', ['class' => 'form-control', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    <label class="">Visa Status:</label>
    <select class="form-control" id="visa_status" name="visa_status" required>
        <option value="">Select Status</option>
        @foreach($visaStatuses as $status)
        <option value="{{ $status->name }}"
            data-fee="{{ $status->default_fee }}"
            {{ (isset($visaExpenses) && $visaExpenses->visa_status == $status->name) ? 'selected' : '' }}>
            {{ $status->name }} ({{ $status->code }})
        </option>
        @endforeach
    </select>
</div>
<div class="form-group col-sm-6">
    {!! Form::label('amount', 'Amount:', ['class' => 'required']) !!}
    {!! Form::number('amount', $visaExpenses->amount ?? '', ['id' => 'amount', 'class' => 'form-control','step'=>'any', 'required']) !!}
</div>
<div class="form-group col-sm-6">
    {!! Form::label('billing_month', 'Billing Month:', ['class' => 'required']) !!}
    {!! Form::month('billing_month', isset($visaExpenses) && $visaExpenses->billing_month ? \Carbon\Carbon::parse($visaExpenses->billing_month)->format('Y-m') : null, ['class' => 'form-control' , 'required']) !!}
</div>
<div class="form-group col-sm-12">
    {!! Form::label('detail', 'Detail:', ['class' => 'required']) !!}
    {!! Form::textarea('detail', $visaExpenses->detail ?? '', ['class' => 'form-control', 'maxlength' => 500,'rows'=>3, 'required']) !!}
</div>

<script>
    $(document).ready(function() {
        // Function to update amount based on selected visa status
        function updateAmountFromVisaStatus() {
            var selectedOption = $('#visa_status option:selected');
            var defaultFee = selectedOption.data('fee');

            console.log('Selected Option:', selectedOption);
            console.log('Default Fee (data-fee):', defaultFee);
            console.log('Default Fee (type):', typeof defaultFee);

            // Ensure defaultFee is a valid number
            if (defaultFee !== undefined && !isNaN(parseFloat(defaultFee))) {
                var parsedFee = parseFloat(defaultFee).toFixed(2);
                console.log('Parsed Fee:', parsedFee);
                $('#amount').val(parsedFee);
                console.log('Amount Field Value:', $('#amount').val());
            } else {
                console.warn('Invalid default fee:', defaultFee);
            }
        }

        // Trigger amount update on visa status change
        $('#visa_status').on('change', updateAmountFromVisaStatus);

        // Trigger on page load if a status is already selected
        if ($('#visa_status').val()) {
            updateAmountFromVisaStatus();
        }
    });
</script>