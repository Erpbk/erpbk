<div class="card-body px-4">
    <!-- Basic Payment Information -->
    <div class="row">
        <!-- reference Field -->
        <div class="form-group col-md-3">
            {!! Form::label('reference', 'Reference:') !!}
            {!! Form::text('reference', null, ['class' => 'form-control', 'maxlength' => 255, 'placeholder' => 'Enter reference number', 'id' => 'reference']) !!}
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
                {!! Form::text('bank-name', $bank->account->account_code.'-'.$bank->account->name, ['class' => 'form-control bg-light', 'readonly' => true]) !!}
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
                {!! Form::text('customer-name', $customer->name ?? '-', ['class' => 'form-control bg-light', 'readonly' => true]) !!}
            @elseif(isset($accountIds))
                <select name="payee_account_id" class="form-control" required>
                    <option value="">-- Select Receiving Account --</option>
                    @foreach(\App\Models\Accounts::whereIn('id', $accountIds)->get() as $payee)
                        <option data-customerId="{{ $payee->ref_id }}"
                                data-customerName="{{ $payee->name }}"
                                value="{{ $payee->id }}"
                                {{ old('payee_account_id', isset($payment) ? $payment->payee_account_id : '') == $payee->id ? 'selected' : '' }}>
                            {{ $payee->account_code }} - {{ $payee->name }}
                        </option>
                    @endforeach
                </select>
            @else
                <select name="payee_account_id" class="form-control select2" required>
                    <option value="">-- Select Receiving Account --</option>
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
                {!! Form::number('amount', isset($payment) ? ($payment->amount - $payment->bank_charges) : null, ['class' => 'form-control cr_amount', 'step' => 'any', 'placeholder' => 'Enter amount', 'id' => 'payment_amount']) !!}
            </div>
        </div>
    </div>

    <!-- Invoice Selection Section -->
    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
    <input type="hidden" value="{{ $invoiceType ?? null }}" name="invoice_type">
    <div class="row mt-4">
        <div class="col-md-12">
            <h6 class="bg-light p-2 mb-3">Select Invoices for Payment</h6>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-hover" id="invoices-table">
                    <thead>
                        <tr style="color: black !important;">
                            <th width="50px">
                                <input type="checkbox" id="select-all-invoices">
                            </th>
                            <th>Invoice #</th>
                            @if($invoiceType == 'supplier')<th>Supplier</th> @else <th>Leasing Company</th>@endif
                            <th>Billing Month</th>
                            <th>Total Amount</th>
                            <th>Paid Amount</th>
                            <th>Balance Due</th>
                            <th>Payment Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($existingInvoices) && $existingInvoices->count() > 0)
                            @foreach($existingInvoices as $invoice)
                            <tr data-invoice-id="{{ $invoice->id }}"
                                data-balance="{{ $invoice->balance + ($invoice->partial_paid_amount[$payment->id] ?? 0) }}" 
                                data-reference="{{ $invoice->invoice_number }}" 
                                data-old-payment="{{ $invoice->partial_paid_amount[$payment->id] ?? 0 }}"
                                data-customer-id="{{ optional($invoice->customer)->id ?? optional($invoice->leasingCompany)->id ?? optional($invoice->supplier)->id }}"
                                data-customer-name="{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name }}">
                                <td class="text-center">
                                    <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" checked>
                                </td>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? '-' }}</td>
                                <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                                <td class="text-right">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                                <td class="text-right">{{ number_format(($invoice->paid_amount ?? 0) - ($invoice->partial_paid_amount[$payment->id] ?? 0), 2) }}</td>
                                <td class="text-right text-danger">{{ number_format(($invoice->balance ?? 0) + ($invoice->partial_paid_amount[$payment->id] ?? 0), 2) }}</td>
                                <td>
                                    <input type="number" name="payment_amounts[{{ $invoice->id }}]" 
                                        class="form-control payment-amount" 
                                        step="any" 
                                        placeholder="Amount"
                                        data-max="{{ $invoice->total ?? $invoice->total_amount }}"
                                        value="{{ $invoice->partial_paid_amount[$payment->id] ?? 0 }}">
                                </td>
                            </tr>
                            @endforeach
                        @endif
                        @foreach($invoices as $invoice)
                        <tr data-invoice-id="{{ $invoice->id }}" 
                            data-balance="{{ $invoice->balance }}" 
                            data-reference="{{ $invoice->invoice_number }}"
                            data-customer-id="{{ optional($invoice->customer)->id ?? optional($invoice->leasingCompany)->id ?? optional($invoice->supplier)->id }}"
                            data-customer-name="{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name }}">
                            <td class="text-center">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox">
                            </td>
                            <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                            <td>{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? '-' }}</td>
                            <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                            <td class="text-right">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            <td class="text-right text-danger">{{ number_format($invoice->balance, 2) }}</td>
                            <td>
                                <input type="number" name="payment_amounts[{{ $invoice->id }}]" 
                                       class="form-control payment-amount" 
                                       step="any" 
                                       placeholder="Amount"
                                       data-max="{{ $invoice->balance }}"
                                       disabled>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="6" class="text-right">Total Selected Payment:</th>
                            <th class="text-right" id="total-selected-payment">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

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
                    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Total Invoice Payment:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            AED <span id="total_invoice_payment">0.00</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Difference:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <span id="payment_difference" class="text-success">AED 0.00</span>
                        </div>
                    </div>
                    @endif
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

    // Function to get selected customer ID from payee select
    function getSelectedCustomerId() {
        var $payeeSelect = $('#payee_account_id, [name="payee_account_id"]');
        var selectedOption = $payeeSelect.find('option:selected');
        
        var customerId = selectedOption.data('customerid') || 
                        selectedOption.data('customer-id') || 
                        selectedOption.data('customerId');
        
        if (!customerId && $('input[name="payee_account_id"]').length) {
            customerId = $('input[name="payee_account_id"]').data('customer-id') || 
                        $('input[name="payee_account_id"]').data('customerId');
        }
        
        return customerId;
    }

    function getSelectedCustomerName() {
        var $payeeSelect = $('#payee_account_id, [name="payee_account_id"]');
        var selectedOption = $payeeSelect.find('option:selected');
        if (!selectedOption.val()) return null;
        
        var customerName = selectedOption.data('customername') || 
                          selectedOption.data('customer-name') || 
                          selectedOption.data('customerName');
        
        if (!customerName && selectedOption.text()) {
            var text = selectedOption.text();
            customerName = text.split(' - ')[1] || text;
        }
        
        return customerName;
    }

    // Payee/Customer selection change handler
    $('#payee_account_id, [name="payee_account_id"]').on('change', function() {
        var selectedCustomerId = getSelectedCustomerId();
        var selectedCustomerName = getSelectedCustomerName();
        
        if (selectedCustomerId || selectedCustomerName) {
            var visibleCount = 0;
            $('#invoices-table tbody tr').each(function() {
                var $row = $(this);
                var invoiceCustomerId = $row.data('customer-id');
                var invoiceCustomerName = $row.data('customer-name');
                
                var matches = false;
                
                if (selectedCustomerId && invoiceCustomerId) {
                    matches = (String(invoiceCustomerId) === String(selectedCustomerId));
                }
                
                if (!matches && selectedCustomerName && invoiceCustomerName) {
                    matches = (invoiceCustomerName.toLowerCase() === selectedCustomerName.toLowerCase());
                }
                
                if (matches) {
                    $row.show();
                    visibleCount++;
                } else {
                    var $checkbox = $row.find('.invoice-checkbox');
                    if ($checkbox.prop('checked')) {
                        $checkbox.prop('checked', false).trigger('change');
                    }
                    $row.hide();
                }
            });
            
            if (visibleCount === 0 && (selectedCustomerId || selectedCustomerName)) {
                toastr.info('No invoices found for this customer');
            }
        } else {
            $('#invoices-table tbody tr').show();
        }
        
        updateSelectAllState();
        updateTotalPayment();
        validatePaymentDistribution();
    });

    // Invoice checkbox change handler
    $('.invoice-checkbox').on('change', function() {
        var $row = $(this).closest('tr');
        var $paymentInput = $row.find('.payment-amount');
        var $paymentAmount = parseFloat($('#payment_amount').val()) || 0;
        var $totalSelectedPayment = parseFloat($('#total-selected-payment').text()) || 0;
        var $difference = ($paymentAmount > 0) ? $paymentAmount - $totalSelectedPayment : 0;
        var rowRef = $row.data('reference') || '';
        var Reference = $('#reference').val() || '';
        
        if ($(this).prop('checked')) {
            $paymentInput.prop('disabled', false);
            
            if (!Reference.includes(rowRef)) {
                $('#reference').val((Reference + ' ' + rowRef).trim());
            }
            
            if (!$paymentInput.val() || parseFloat($paymentInput.val()) === 0) {
                var maxAllowed = parseFloat($paymentInput.data('max')) || 0;
                if (maxAllowed > $difference && $difference > 0) {
                    $paymentInput.val($difference.toFixed(2));
                    toastr.warning('Total Selected Payment cannot exceed Payment Amount.');
                } else if (maxAllowed > 0) {
                    $paymentInput.val(maxAllowed.toFixed(2));
                }
                $paymentInput.trigger('change');
            }
            
            var invoiceCustomerId = $row.data('customer-id');
            var $payeeSelect = $('#payee_account_id, [name="payee_account_id"]');
            var currentSelection = $payeeSelect.val();
            
            if (invoiceCustomerId && !currentSelection) {
                var matchedOption = null;
                $payeeSelect.find('option').each(function() {
                    var optionCustomerId = $(this).data('customerid') || 
                                          $(this).data('customer-id') || 
                                          $(this).data('customerId');
                    if (optionCustomerId && String(optionCustomerId) === String(invoiceCustomerId)) {
                        matchedOption = $(this);
                        return false;
                    }
                });
                
                if (matchedOption && matchedOption.length) {
                    $payeeSelect.val(matchedOption.val()).trigger('change');
                    toastr.info('Customer "' + $row.data('customer-name') + '" selected automatically');
                }
            }
        } else {
            Reference = Reference.replace(rowRef, '').trim();
            $('#reference').val(Reference);
            $paymentInput.prop('disabled', true);
            $paymentInput.val(0);
            $paymentInput.trigger('change');
        }
        
        updateTotalPayment();
        validatePaymentDistribution();
        updateSelectAllState();
    });

    // Payment amount change handler
    $('.payment-amount').on('keyup change', function() {
        var $row = $(this).closest('tr');
        var maxAmount = parseFloat($(this).data('max')) || 0;
        var enteredAmount = parseFloat($(this).val()) || 0;
        var paymentAmount = parseFloat($('#payment_amount').val()) || 0;
        var total = updateTotalPayment();
        var difference = (paymentAmount > 0) ? (paymentAmount - total) : 0;
        
        if (enteredAmount > maxAmount) {
            $(this).val(maxAmount);
            enteredAmount = maxAmount;
            toastr.warning('Payment amount cannot exceed invoice balance');
        }
        
        if (total > paymentAmount && paymentAmount > 0) {
            var excess = total - paymentAmount;
            var newVal = enteredAmount - excess;
            if (newVal > 0) {
                $(this).val(newVal);
                enteredAmount = newVal;
                toastr.warning('Total Selected Payment cannot exceed Payment Amount. Adjusted to fit remaining amount.');
            } else {
                $(this).val(0);
                enteredAmount = 0;
            }
        }
        
        if (enteredAmount < 0) {
            $(this).val(0);
            enteredAmount = 0;
        }
        
        updateTotalPayment();
        validatePaymentDistribution();
    });

    // Payment amount (main) change handler
    $('#payment_amount').on('keyup change', function() {
        var amount = parseFloat($(this).val()) || 0;
        var bankCharges = parseFloat($('#bank_charges').val()) || 0;
        
        $('#display_amount').text(amount.toFixed(2));
        $('#total_debit').text((amount + bankCharges).toFixed(2));
        
        updateTotalPayment();
        validatePaymentDistribution();
        adjustPaymentsToPaymentAmount(amount);
    });

    // Bank charges change handler
    $('#bank_charges').on('keyup change', function() {
        var bankCharges = parseFloat($(this).val()) || 0;
        var paymentAmount = parseFloat($('#payment_amount').val()) || 0;
        
        $('#display_charges').text(bankCharges.toFixed(2));
        $('#total_debit').text((paymentAmount + bankCharges).toFixed(2));
        
        calculateTotals();
    });

    // Select All Invoices
    $('#select-all-invoices').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('#invoices-table tbody tr:visible .invoice-checkbox').prop('checked', isChecked).trigger('change');
    });

    function updateSelectAllState() {
        var totalVisible = $('#invoices-table tbody tr:visible').length;
        var checkedVisible = $('#invoices-table tbody tr:visible .invoice-checkbox:checked').length;
        
        if (totalVisible === 0) {
            $('#select-all-invoices').prop('checked', false).prop('disabled', true);
        } else {
            $('#select-all-invoices').prop('disabled', false);
            $('#select-all-invoices').prop('checked', checkedVisible === totalVisible);
        }
    }

    function updateTotalPayment() {
        var total = 0;
        $('.payment-amount:not(:disabled)').each(function() {
            var val = parseFloat($(this).val()) || 0;
            total += val;
        });
        $('#total_invoice_payment').text(total.toFixed(2));
        $('#total-selected-payment').text(total.toFixed(2));
        return total;
    }
    
    function validatePaymentDistribution() {
        var paymentAmount = parseFloat($('#payment_amount').val()) || 0;
        var totalPayment = parseFloat($('#total_invoice_payment').text()) || 0;
        var difference = paymentAmount - totalPayment;
        
        $('#payment_difference').text('AED ' + difference.toFixed(2));
        
        if (Math.abs(difference) < 0.01) {
            $('#payment_difference').removeClass('text-danger').removeClass('text-warning').addClass('text-success');
        } else if (difference > 0) {
            $('#payment_difference').removeClass('text-success').removeClass('text-danger').addClass('text-warning');
        } else if (difference < 0) {
            $('#payment_difference').removeClass('text-success').removeClass('text-warning').addClass('text-danger');
        }
    }

    function adjustPaymentsToPaymentAmount(paymentAmount) {
        var totalPayment = parseFloat($('#total_invoice_payment').text()) || 0;
        
        if (totalPayment > paymentAmount && paymentAmount > 0) {
            var excess = totalPayment - paymentAmount;
            var $lastCheckedInput = $('.payment-amount:not(:disabled)').last();
            
            if ($lastCheckedInput.length) {
                var currentVal = parseFloat($lastCheckedInput.val()) || 0;
                var newVal = currentVal - excess;
                
                if (newVal < 0) {
                    newVal = 0;
                }
                
                $lastCheckedInput.val(newVal.toFixed(2));
                $lastCheckedInput.trigger('change');
                toastr.warning('Total payment adjusted to match payment amount');
            }
        }
    }

    function calculateTotals() {
        var paymentAmount = parseFloat($('#payment_amount').val()) || 0;
        var bankCharges = parseFloat($('#bank_charges').val()) || 0;
        $('#display_charges').text(bankCharges.toFixed(2));
    }

    // Initial triggers
    setTimeout(function() {
        var $payeeSelect = $('#payee_account_id, [name="payee_account_id"]');
        if ($payeeSelect.val()) {
            $payeeSelect.trigger('change');
        }
        $('#payment_amount').trigger('change');
        updateSelectAllState();
        calculateTotals();
    }, 100);
});

function validatePaymentForm() {
    var totalCredit = parseFloat($('#payment_amount').val()) || 0;
    var bankCharges = parseFloat($('#bank_charges').val()) || 0;
    var totalInvoicePayment = parseFloat($('#total_invoice_payment').text()) || 0;

    if (totalCredit <= 0) {
        alert('Please enter a valid payment amount greater than zero');
        $('#payment_amount').addClass('is-invalid');
        return false;
    }

    if (Math.abs(totalCredit - totalInvoicePayment) > 0.01) {
        alert('Payment amount must equal total selected invoice payments');
        return false;
    }

    if (bankCharges > 0) {
        var chargesAccount = $('select[name="bank_charges_account"]').val();
        if (!chargesAccount) {
            alert('Please select a bank charges account');
            $('select[name="bank_charges_account"]').addClass('is-invalid');
            return false;
        }
    }

    $('.is-invalid').removeClass('is-invalid');
    return true;
}
</script>

<style>
#invoices-table thead th {
    background-color: #f1f1f1;
    color: black !important;
    font-weight: 600;
}
#invoices-table tfoot th {
    background-color: #f1f1f1;
    color: black !important;
    font-weight: 600;
}
#invoices-table thead {
    position: sticky;
    top: 0;
    z-index: 10;
}
#invoices-table tfoot {
    position: sticky;
    bottom: 0;
    z-index: 10;
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
.is-invalid {
    border-color: #dc3545;
}
#invoices-table th, #invoices-table td {
    vertical-align: middle;
}
.payment-amount {
    width: 120px;
    margin: 0 auto;
}
@media (max-width: 768px) {
    .col-md-8.offset-md-4 {
        margin-left: 0;
        margin-right: 0;
    }
    #invoices-table {
        font-size: 12px;
    }
    .payment-amount {
        width: 100%;
        min-width: 80px;
    }
}
.text-muted {
    font-size: 11px;
    margin-top: 3px;
}
</style>