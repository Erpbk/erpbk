
    <div class="card-body px-2">
        <!-- Basic Receipt Information -->
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
                    old('amount_type', isset($receipt) ? $receipt->amount_type : ''), 
                    ['class' => 'form-control select2']
                ) !!}
            </div>

            <!-- Date of Receipt Field -->
            <div class="form-group col-md-3">
                {!! Form::label('date_of_receipt', 'Receipt Date:') !!}
                {!! Form::date('date_of_receipt', null, ['class' => 'form-control']) !!}
            </div>

            <!-- Billing Month Field -->
            <div class="form-group col-md-3">
                {!! Form::label('billing_month', 'Billing Month:') !!}
                {!! Form::month('billing_month', null, ['class' => 'form-control']) !!}
            </div>
        </div>

        <!-- Receiving Account Section (Single) -->
        <div class="row mt-3">
            <div class="col-md-12">
                <h6 class="bg-light p-2 mb-3">Transaction Details</h6>
            </div>
            @if(isset($bank))
                <div class="form-group col-md-3">
                    {!! Form::label('bank', 'Receiving Account:') !!}
                    {!! Form::hidden('bank_id', $bank->id ?? $receipt->bank_id ?? '')!!}
                    {!! Form::text('bank-name', $bank->account->account_code.'-'.$bank->account->name, ['class' => 'form-control bg-light', 'readonly' =>true]) !!}
                </div>
            @else
                <div class="form-group col-md-3">
                    {!! Form::label('bank', 'Receiving Account:') !!}
                    <select name="bank_id" class="form-control select2" required>
                        <option value="">-- Select --</option>
                        @foreach ($banks as $bank)
                            <option value="{{ $bank->id }}" {{ old('bank_id', isset($receipt) ? $receipt->bank_id : '') == $bank->id ? 'selected' : '' }}>{{ $bank->account->account_code .'-'. $bank->name }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
            
            @if(isset($leasingCompany))
                <div class="form-group col-md-3">
                    {!! Form::label('leasing_company', 'Sender (Leasing Company):') !!}
                    {!! Form::hidden('payer_account_id', $leasingCompany->account_id)!!}
                    {!! Form::text('leasing-company-name', $leasingCompany->name ?? $receipt->leasingCompany->name ?? '-', ['class' => 'form-control bg-light', 'readonly' => true]) !!}
                </div>
            @else
                <div class="form-group col-md-3">
                    {!! Form::label('payer_account_id', 'Sending Account:') !!}
                    <select name="payer_account_id" class="form-control select2" required>
                        <option value="">-- Select --</option>
                        @foreach(\App\Models\Accounts::where('status', 1)->get() as $payer)
                            <option value="{{ $payer->id }}" {{ old('payer_account_id', isset($receipt) ? $receipt->payer_account_id : '') == $payer->id ? 'selected' : '' }}>
                                {{ $payer->account_code }} - {{ $payer->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif


            <!-- Amount Field -->
            <div class="form-group col-md-3">
                {!! Form::label('amount', 'Receipt Amount:') !!}
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text">AED</span>
                    </div>
                    {!! Form::number('amount', null, ['class' => 'form-control', 'step' => 'any', 'placeholder' => 'Enter amount', 'id' => 'receipt_amount']) !!}
                </div>
            </div>

            <!-- Voucher Attachment Field -->
            <div class="form-group col-md-3">
                {!! Form::label('attachment', 'Attachment:') !!}
                {!! Form::file('attachment', ['class' => 'form-control']) !!} 
            </div>
        </div>

        <!-- Sending Account Section (Single) -->
        <div class="row mt-4">

            <!-- Narration Field -->
            <div class="form-group col-md-7">
                {!! Form::label('description', 'Narration / Description:') !!}
                {!! Form::textarea('description', null, [
                    'class' => 'form-control', 
                    'rows' => 3, 
                    'placeholder' => 'Enter description or notes about this receipt...'
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
                                <strong>Receipt Amount:</strong>
                            </div>
                            <div class="col-md-6 text-right">
                                Rs. <span id="display_amount">0.00</span>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Amount in Words:</strong>
                            </div>
                            <div class="col-md-6 text-right text-muted small" id="amount_in_words">
                                Zero only
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

    // Initialize custom file input
    $('.custom-file-input').on('change', function() {
        var fileName = $(this).val().split('\\').pop();
        $(this).next('.custom-file-label').html(fileName);
    });

    // Update amount display on change
    $('#receipt_amount').on('keyup change', function() {
        var amount = parseFloat($(this).val()) || 0;
        $('#display_amount').text(amount.toFixed(2));
        
        // Convert amount to words (you can implement this function or use a library)
        $('#amount_in_words').text(numberToWords(amount));
    });

    // Trigger initial amount display
    $('#receipt_amount').trigger('change');
});

// Simple number to words converter (basic implementation)
function numberToWords(num) {
    if (num === 0) return 'Zero only';
    
    const ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine',
        'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
    const tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];
    
    let words = '';
    let rupees = Math.floor(num);
    let paise = Math.round((num - rupees) * 100);
    
    if (rupees > 0) {
        if (rupees >= 10000000) {
            words += convertToWords(Math.floor(rupees / 10000000)) + ' Crore ';
            rupees %= 10000000;
        }
        if (rupees >= 100000) {
            words += convertToWords(Math.floor(rupees / 100000)) + ' Lakh ';
            rupees %= 100000;
        }
        if (rupees >= 1000) {
            words += convertToWords(Math.floor(rupees / 1000)) + ' Thousand ';
            rupees %= 1000;
        }
        if (rupees >= 100) {
            words += convertToWords(Math.floor(rupees / 100)) + ' Hundred ';
            rupees %= 100;
        }
        if (rupees > 0) {
            words += convertToWords(rupees);
        }
    }
    
    words += ' Dirhams';
    
    if (paise > 0) {
        words += ' and ' + convertToWords(paise) + ' Paise';
    }
    
    return words + ' only';
    
    function convertToWords(n) {
        if (n < 20) return ones[n];
        return tens[Math.floor(n / 10)] + (n % 10 ? ' ' + ones[n % 10] : '');
    }
}
</script>

<style>

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
    border-right: none;
}

hr {
    border-top: 1px solid rgba(0,0,0,0.1);
}

#amount_in_words {
    font-style: italic;
    word-break: break-word;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .col-md-8.offset-md-4 {
        margin-left: 0;
        margin-right: 0;
    }
}
</style>