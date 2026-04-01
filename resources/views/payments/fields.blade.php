
    <div class="card-body px-4">
        <!-- Basic Payment Information -->
        <div class="row">
            <!-- reference Field -->
            <div class="form-group col-md-3">
                {!! Form::label('reference', 'Reference:') !!}
                {!! Form::text('reference', null, ['class' => 'form-control', 'maxlength' => 255, 'placeholder' => 'Enter reference number']) !!}
            </div>

            <!-- Payment Type Field -->
            <div class="form-group col-md-3">
                {!! Form::label('amount_type', 'Payment Mode:') !!}
                {!! Form::select('amount_type', 
                    ['' => 'Select Payment Mode', 'Cash' => 'Cash', 'Online' => 'Online', 'Cheque' => 'Cheque', 'Credit' => 'Credit'], 
                    old('amount_type', isset($payment) ? $payment->amount_type : ''), 
                    ['class' => 'form-control select2']
                ) !!}
            </div>

            <!-- Voucher Attachment Field -->
            <div class="form-group col-md-3">
                {!! Form::label('attachment', 'Attachment:') !!}
                {!! Form::file('attachment', ['class' => 'form-control']) !!}
            </div>

            <!-- Date of Payment Field -->
            <div class="form-group col-md-3">
                {!! Form::label('date_of_payment', 'Date of Payment:') !!}
                {!! Form::date('date_of_payment', null, ['class' => 'form-control']) !!}
            </div>

            <div class="form-group col-md-3">
                {!! Form::label('date_of_invoice', 'Date of Invoice:') !!}
                {!! Form::date('date_of_invoice', null, ['class' => 'form-control']) !!}
            </div>

            <!-- Billing Month Field -->
            <div class="form-group col-md-3">
                {!! Form::label('billing_month', 'Billing Month:') !!}
                {!! Form::month('billing_month', null, ['class' => 'form-control', 'maxlength' => 255]) !!}
            </div>
        </div>

        <!-- Transaction Details Section -->
        <div class="row mt-3">
            <div class="col-md-12">
                <h6 class="bg-light p-2 mb-3">Transaction Details</h6>
            </div>
            
            <!-- Paying Account (Credit) -->
            <div class="form-group col-md-3">
                {!! Form::label('bank_id', 'Sending Account:') !!}
                @if(isset($bank))
                    {!! Form::hidden('bank_id', $bank->id)!!}
                    {!! Form::text('bank-name', $bank->account->account_code.'-'.$bank->account->name, ['class' => 'form-control']) !!}
                @else
                    <select name="bank_id" class="form-control select2" required>
                        <option value="">-- Select Paying Account --</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_id', isset($payment) ? $payment->bank_id : '') == $bank->id ? 'selected' : '' }}>
                                {{ $bank->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Payee Account (Debit) -->
            <div class="form-group col-md-3">
                {!! Form::label('account_id', 'Receiving Account:') !!}
                 @if(isset($leasingCompany))
                    {!! Form::hidden('payee_account_id', $leasingCompany->account_id)!!}
                    {!! Form::text('leasing-company-name', $leasingCompany->name ?? '-', ['class' => 'form-control bg-light', 'readonly' => true]) !!}
                @elseif(isset($customer))
                    {!! Form::hidden('payee_account_id', $customer->account_id)!!}
                    {!! Form::text('leasing-company-name', $customer->name ?? '-', ['class' => 'form-control bg-light', 'readonly' => true]) !!}
                @else
                    <select name="payee_account_id" class="form-control select2" required>
                        <option value="">-- Select Payee Account --</option>
                        @foreach(\App\Models\Accounts::active()->get() as $payee)
                            <option value="{{ $payee->id }}" {{ old('payee_account_id', isset($payment) ? $payment->payee_account_id : '') == $payee->id ? 'selected' : '' }}>
                                {{ $payee->account_code.'-'.$payee->name }}
                            </option>
                        @endforeach
                    </select>
                @endif
            </div>

            <!-- Payment Amount Field (Credit) -->
            <div class="form-group col-md-3">
                {!! Form::label('amount', 'Payment Amount:') !!}
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">AED</span>
                    </div>
                    {!! Form::number('amount', null, ['class' => 'form-control cr_amount', 'step' => 'any', 'placeholder' => 'Enter amount', 'id' => 'payment_amount']) !!}
                </div>
            </div>
        </div>

        <!-- Bank Charges Section -->
        <div class="row mt-3">
            <div class="col-md-12">
                <h6 class="bg-light p-2 mb-3">Bank Charges (Optional)</h6>
            </div>
            
            <div class="form-group col-md-3">
                {!! Form::label('bank_charges', 'Bank Charges Amount:') !!}
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">AED</span>
                    </div>
                    {!! Form::number('bank_charges', null, ['class' => 'form-control bank_charges', 'step' => 'any', 'placeholder' => 'Enter bank charges', 'id' => 'bank_charges', 'min' => '0']) !!}
                </div>
            </div>

            <div class="form-group col-md-4">
                {!! Form::label('bank_charges_account', 'Bank Charges Account:') !!}
                <select name="bank_charges_account" class="form-control select2">
                    <option value="">-- Select Bank Charges Account --</option>
                    @foreach(\App\Models\Accounts::active()->where('account_type', 'expense')->get() as $expenseAccount)
                        <option value="{{ $expenseAccount->id }}" {{ old('bank_charges_account', isset($payment) ? $payment->bank_charges_account : '') == $expenseAccount->id ? 'selected' : '' }}>
                            {{ $expenseAccount->account_code.'-'.$expenseAccount->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Main Narration Field -->
        <div class="row mt-4">
            <div class="form-group col-md-7">
                {!! Form::label('description', 'Main Narration / Description:') !!}
                {!! Form::textarea('description', null, [
                    'class' => 'form-control', 
                    'rows' => 4, 
                    'placeholder' => 'Enter main description or notes about this payment...'
                ]) !!}
            </div>
        </div>

        <!-- Summary Section -->
        <div class="row mt-4">
            <div class="col-md-8 offset-md-4">
                <div class="card bg-light">
                    <div class="card-body p-3">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Payment Amount:</strong>
                            </div>
                            <div class="col-md-6 text-right">
                                AED <span id="display_amount">0.00</span>
                            </div>
                        </div>
                        <div class="row mt-2">
                            <div class="col-md-6">
                                <strong>Bank Charges:</strong>
                            </div>
                            <div class="col-md-6 text-right">
                                AED <span id="display_charges">0.00</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Total:</strong>
                            </div>
                            <div class="col-md-6 text-right">
                                AED <span id="total_debit">0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<script>
$(document).ready(function() {
    // Initialize Select2
    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });

    // Calculate totals on amount changes
    $('#payment_amount, #bank_charges').on('keyup change', function() {
        calculateTotals();
    });

    // Initial calculation
    calculateTotals();

}); // End of $(document).ready

function validatePaymentForm() {
    var totalCredit = parseFloat($('#payment_amount').val()) || 0;
    var bankCharges = parseFloat($('#bank_charges').val()) || 0;
    var totalDebit = totalCredit + bankCharges;

    // Check if payment amount is greater than zero
    if (totalCredit <= 0) {
        alert('Please enter a valid payment amount greater than zero');
        $('#payment_amount').addClass('is-invalid');
        return false;
    }

    // Check if bank charges account is selected when bank charges > 0
    if (bankCharges > 0) {
        var chargesAccount = $('select[name="bank_charges_account"]').val();
        if (!chargesAccount) {
            alert('Please select a bank charges account');
            $('select[name="bank_charges_account"]').addClass('is-invalid');
            return false;
        }
    }

    // Remove error styling
    $('.is-invalid').removeClass('is-invalid');
    
    return true;
}

function calculateTotals() {
    var paymentAmount = parseFloat($('#payment_amount').val()) || 0;
    var bankCharges = parseFloat($('#bank_charges').val()) || 0;
    
    var totalDebit = paymentAmount + bankCharges;

    // Update display
    $('#display_amount').text(paymentAmount.toFixed(2));
    $('#display_charges').text(bankCharges.toFixed(2));
    $('#total_debit').text(totalDebit.toFixed(2));
}
</script>

<style>
.card {
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: none;
    margin-bottom: 20px;
}

.card-header {
    border-bottom: none;
    font-weight: 500;
}

.form-group label {
    font-weight: 500;
    color: #495057;
    margin-bottom: 5px;
}

.form-control:focus, .select2-container--default .select2-selection--single:focus {
    border-color: #80bdff;
    box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25);
}

.bg-light {
    background-color: #f8f9fa !important;
}

.input-group-text {
    background-color: #e9ecef;
}

hr {
    border-top: 1px solid rgba(0,0,0,0.1);
}

.badge {
    font-size: 14px;
    padding: 5px 10px;
}

.is-invalid {
    border-color: #dc3545;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-8.offset-md-4 {
        margin-left: 0;
        margin-right: 0;
    }
}

.text-muted {
    font-size: 11px;
    margin-top: 3px;
}
</style>