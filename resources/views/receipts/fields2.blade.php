<div class="card-body px-2">
    <!-- Basic Receipt Information -->
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
                ['' => 'Select Payment Mode', 'Cash' => 'Cash', 'Online' => 'Online', 'Credit' => 'Credit'], 
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
                        <option value="{{ $bank->id }}" {{ old('bank_id', isset($receipt) ? $receipt->bank_id : '') == $bank->id ? 'selected' : '' }}>{{ $bank->name }}</option>
                    @endforeach
                </select>
            </div>
        @endif
        
        @if(isset($leasingCompany))
            <div class="form-group col-md-3">
                {!! Form::label('leasing_company', 'Sender Account:') !!}
                {!! Form::hidden('payer_account_id', $leasingCompany->account_id)!!}
                {!! Form::text('leasing-company-name', $leasingCompany->name ?? $receipt->leasingCompany->name ?? '-', ['class' => 'form-control bg-light', 'readonly' => true]) !!}
            </div>
        @else
            <div class="form-group col-md-3">
                {!! Form::label('payer_account_id', 'Sending Account:') !!}
                <select name="payer_account_id" class="form-control select2" required>
                    <option value="">-- Select --</option>
                    @if(isset($customerIds))
                        @foreach(\App\Models\Accounts::whereIn('id',$customerIds)->get() as $payer)
                            <option data-customerId="{{ $payer->ref_id }}"
                                    data-customerName="{{ $payer->name }}"
                                    value="{{ $payer->id }}"
                                    {{ old('payer_account_id', isset($receipt) ? $receipt->payer_account_id : '') == $payer->id ? 'selected' : '' }} {{ $payer->ref_id == $customerId ? 'selected' : '' }}>
                                {{ $payer->account_code }} - {{ $payer->name }}
                            </option>
                        @endforeach
                    @else
                        @foreach(\App\Models\Accounts::where('status', 1)->get() as $payer)
                            <option value="{{ $payer->id }}" {{ old('payer_account_id', isset($receipt) ? $receipt->payer_account_id : '') == $payer->id ? 'selected' : '' }}>
                                {{ $payer->account_code }} - {{ $payer->name }}
                            </option>
                        @endforeach
                    @endif
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

    <!-- Invoice Selection Section -->
    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
    <input type="hidden" value="{{ $invoiceType }}" name="invoice_type">
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
                            @if($invoiceType == 'customer')<th>Customer</th>@else<th>Leasing Company</th>@endif
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
                                data-balance="{{ $invoice->balance + $invoice->partial_paid_amount[$receipt->id] }}" 
                                data-reference="{{ $invoice->invoice_number }}" 
                                data-old-payment="{{ $invoice->partial_paid_amount[$receipt->id] ?? 0 }}"
                                data-customer-id="{{ $invoice->customer->id ?? $invoice->leasingCompany->id }}"
                                data-customer-name="{{ $invoice->customer->name ?? $invoice->leasingCompany->name }}">
                                <td class="text-center">
                                    <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" checked>
                                </td>
                                <td>{{ $invoice->invoice_number }}</td>
                                <td>{{ $invoice->customer->name ?? $invoice->leasingCompany->name ?? '-' }}</td>
                                <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                                <td class="text-right">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                                <td class="text-right">{{ number_format($invoice->paid_amount - $invoice->partial_paid_amount[$receipt->id], 2) }}</td>
                                <td class="text-right text-danger">{{ number_format($invoice->balance + $invoice->partial_paid_amount[$receipt->id], 2) }}</td>
                                <td>
                                    <input type="number" name="payment_amounts[{{ $invoice->id }}]" 
                                        class="form-control payment-amount" 
                                        step="any" 
                                        placeholder="Amount"
                                        data-max="{{ $invoice->total ?? $invoice->total_amount }}"
                                        value="{{ $invoice->partial_paid_amount[$receipt->id] ?? 0 }}">
                                </td> 
                            </tr>
                            @endforeach
                        @endif
                        @foreach($invoices as $invoice)
                        <tr data-invoice-id="{{ $invoice->id }}" 
                            data-balance="{{ $invoice->balance }}" 
                            data-reference="{{ $invoice->invoice_number }}"
                            data-customer-id="{{ $invoice->customer->id ?? $invoice->leasingCompany->id }}"
                            data-customer-name="{{ $invoice->customer->name ?? $invoice->leasingCompany->name }}">
                            <td class="text-center">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox">
                            </td>
                            <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                            <td>{{ $invoice->customer->name ?? $invoice->leasingCompany->name ?? '-' }}</td>
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
                            AED <span id="display_amount">0.00</span>
                        </div>
                    </div>
                    @if(isset($invoices) && $invoices->count() > 0)
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

    // Function to get selected customer ID from payer select
    function getSelectedCustomerId() {
        var $payerSelect = $('#payer_account_id, [name="payer_account_id"]');
        var selectedOption = $payerSelect.find('option:selected');
        
        // Try to get customer ID from different data attributes
        var customerId = selectedOption.data('customerid') || 
                        selectedOption.data('customer-id') || 
                        selectedOption.data('customerId');
        
        
        // For leasing company case with hidden input
        if (!customerId && $('input[name="payer_account_id"]').length) {
            customerId = $('input[name="payer_account_id"]').data('customer-id') || 
                        $('input[name="payer_account_id"]').data('customerId');
        }
        
        return customerId;
    }

    // Function to get selected customer name from payer select
    function getSelectedCustomerName() {
        var $payerSelect = $('#payer_account_id, [name="payer_account_id"]');
        var selectedOption = $payerSelect.find('option:selected');
        if (!selectedOption.val()) return null; // placeholder has no value
        var customerName = selectedOption.data('customername') || 
                          selectedOption.data('customer-name') || 
                          selectedOption.data('customerName');
        
        
        // Try to get from text if not found
        if (!customerName && selectedOption.text()) {
            var text = selectedOption.text();
            customerName = text.split(' - ')[1] || text;
        }
        
        return customerName;
    }

    // Payer/Customer selection change handler
    $('#payer_account_id, [name="payer_account_id"]').on('change', function() {
        var selectedCustomerId = getSelectedCustomerId();
        var selectedCustomerName = getSelectedCustomerName();
        
        console.log('Selected Customer ID:', selectedCustomerId);
        console.log('Selected Customer Name:', selectedCustomerName);
        
        if (selectedCustomerId || selectedCustomerName) {
            // Filter invoices based on selected payer/customer
            var visibleCount = 0;
            $('#invoices-table tbody tr').each(function() {
                var $row = $(this);
                var invoiceCustomerId = $row.data('customer-id');
                var invoiceCustomerName = $row.data('customer-name');
                console.log('invoice Customer ID:', invoiceCustomerId);
                console.log('invoice Customer Name:', invoiceCustomerName);
                
                var matches = false;
                
                // Compare by customer ID if both exist
                if (selectedCustomerId && invoiceCustomerId) {
                    matches = (String(invoiceCustomerId) === String(selectedCustomerId));
                }
                
                // If not matched by ID, try by name
                if (!matches && selectedCustomerName && invoiceCustomerName) {
                    matches = (invoiceCustomerName.toLowerCase() === selectedCustomerName.toLowerCase());
                }
                
                if (matches) {
                    $row.show();
                    visibleCount++;
                } else {
                    // Uncheck and disable if hidden
                    var $checkbox = $row.find('.invoice-checkbox');
                    if ($checkbox.prop('checked')) {
                        $checkbox.prop('checked', false).trigger('change');
                    }
                    $row.hide();
                }
            });
            
            if (visibleCount === 0) {
                toastr.info('No invoices found for this customer');
            }
        } else {
            // Show all invoices if no payer selected
            $('#invoices-table tbody tr').show();
        }
        
        // Update select all checkbox state
        updateSelectAllState();
        updateTotalPayment();
        validatePaymentDistribution();
    });

    // Invoice checkbox change handler
    $('.invoice-checkbox').on('change', function() {
        var $row = $(this).closest('tr');
        var $paymentInput = $row.find('.payment-amount');
        var $receiptAmount = parseFloat($('#receipt_amount').val()) || 0;
        var $totalSelectedPayment = parseFloat($('#total-selected-payment').text()) || 0;
        var $difference = ($receiptAmount > 0 ) ? $receiptAmount - $totalSelectedPayment : 0;
        var rowRef = $row.data('reference') || '';
        var Reference = $('#reference').val() || '';
        
        if ($(this).prop('checked')) {
            $paymentInput.prop('disabled', false);
            
            // Add reference to receipt reference field
            if (!Reference.includes(rowRef)) {
                $('#reference').val((Reference + ' ' + rowRef).trim());
            }
            
            // Auto-fill with balance if empty
            if (!$paymentInput.val() || parseFloat($paymentInput.val()) === 0) {
                var maxAllowed = parseFloat($paymentInput.data('max')) || 0;
                if (maxAllowed > $difference && $difference > 0) {
                    $paymentInput.val($difference.toFixed(2));
                    toastr.warning('Total Selected Payment cannot exceed Receipt Amount.');
                } else if (maxAllowed > 0) {
                    $paymentInput.val(maxAllowed.toFixed(2));
                }
                $paymentInput.trigger('change');
            }
            
            // Auto-select the payer if not already selected
            var invoiceCustomerId = $row.data('customer-id');
            var $payerSelect = $('#payer_account_id, [name="payer_account_id"]');
            var currentSelection = $payerSelect.val();
            
            if (invoiceCustomerId && !currentSelection) {
                // Try to find option with matching customer ID in data attributes
                var matchedOption = null;
                $payerSelect.find('option').each(function() {
                    var optionCustomerId = $(this).data('customerid') || 
                                          $(this).data('customer-id') || 
                                          $(this).data('customerId');
                    if (optionCustomerId && String(optionCustomerId) === String(invoiceCustomerId)) {
                        matchedOption = $(this);
                        return false;
                    }
                });
                
                if (matchedOption && matchedOption.length) {
                    $payerSelect.val(matchedOption.val()).trigger('change');
                    toastr.info('Customer "' + $row.data('customer-name') + '" selected automatically');
                } else {
                    // If no matching option found, try to set by value if it's the customer ID
                    var optionByValue = $payerSelect.find('option[value="' + invoiceCustomerId + '"]');
                    if (optionByValue.length) {
                        $payerSelect.val(invoiceCustomerId).trigger('change');
                        toastr.info('Customer "' + $row.data('customer-name') + '" selected automatically');
                    }
                }
            }
        } else {
            // Remove reference from receipt reference field
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
        var receiptAmount = parseFloat($('#receipt_amount').val()) || 0;
        var total = updateTotalPayment();
        var difference = (receiptAmount > 0) ? (receiptAmount - total) : 0;
        
        // Validate amount against invoice balance
        if (enteredAmount > maxAmount) {
            $(this).val(maxAmount);
            enteredAmount = maxAmount;
            toastr.warning('Payment amount cannot exceed invoice balance');
        }
        
        // Validate against total receipt amount
        if (total > receiptAmount && receiptAmount > 0) {
            var excess = total - receiptAmount;
            var newVal = enteredAmount - excess;
            if (newVal > 0) {
                $(this).val(newVal);
                enteredAmount = newVal;
                toastr.warning('Total Selected Payment cannot exceed Receipt Amount. Adjusted to fit remaining amount.');
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

    // Receipt amount change handler
    $('#receipt_amount').on('keyup change', function() {
        var amount = parseFloat($(this).val()) || 0;
        $('#display_amount').text(amount.toFixed(2));
        $('#amount_in_words').text(numberToWords(amount));
        updateTotalPayment();
        validatePaymentDistribution();
        
        // Adjust payment amounts if they exceed new receipt amount
        adjustPaymentsToReceiptAmount(amount);
    });

    // Select All Invoices (only visible ones)
    $('#select-all-invoices').on('change', function() {
        var isChecked = $(this).prop('checked');
        $('#invoices-table tbody tr:visible .invoice-checkbox').prop('checked', isChecked).trigger('change');
    });

    // Function to update select all checkbox state
    function updateSelectAllState() {
        var totalVisible = $('#invoices-table tbody tr:visible').length;
        var checkedVisible = $('#invoices-table tbody tr:visible .invoice-checkbox:checked').length;
        
        if (totalVisible === 0) {
            $('#select-all-invoices').prop('checked', false).prop('disabled', true);
        } else {
            $('#select-all-invoices').prop('disabled', false);
            if (checkedVisible === totalVisible) {
                $('#select-all-invoices').prop('checked', true);
            } else {
                $('#select-all-invoices').prop('checked', false);
            }
        }
    }

    // Update total payment from invoices
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
    
    // Validate payment distribution
    function validatePaymentDistribution() {
        var receiptAmount = parseFloat($('#receipt_amount').val()) || 0;
        var totalPayment = parseFloat($('#total_invoice_payment').text()) || 0;
        var difference = receiptAmount - totalPayment;
        
        $('#payment_difference').text('AED ' + difference.toFixed(2));
        
        if (Math.abs(difference) < 0.01) {
            $('#payment_difference').removeClass('text-danger').removeClass('text-warning').addClass('text-success');
        } else if (difference > 0) {
            $('#payment_difference').removeClass('text-success').removeClass('text-danger').addClass('text-warning');
        } else if (difference < 0) {
            $('#payment_difference').removeClass('text-success').removeClass('text-warning').addClass('text-danger');
        }
    }

    // Adjust payment amounts when receipt amount changes
    function adjustPaymentsToReceiptAmount(receiptAmount) {
        var totalPayment = parseFloat($('#total_invoice_payment').text()) || 0;
        
        if (totalPayment > receiptAmount && receiptAmount > 0) {
            var excess = totalPayment - receiptAmount;
            var $lastCheckedInput = $('.payment-amount:not(:disabled)').last();
            
            if ($lastCheckedInput.length) {
                var currentVal = parseFloat($lastCheckedInput.val()) || 0;
                var newVal = currentVal - excess;
                
                if (newVal < 0) {
                    newVal = 0;
                }
                
                $lastCheckedInput.val(newVal.toFixed(2));
                $lastCheckedInput.trigger('change');
                toastr.warning('Total payment adjusted to match receipt amount');
            }
        }
    }

    // Also trigger filter when leasing company is preset (hidden input case)
    if ($('input[name="payer_account_id"]').length && $('input[name="payer_account_id"]').val()) {
        setTimeout(function() {
            $('#payer_account_id, [name="payer_account_id"]').trigger('change');
        }, 100);
    }

    // Trigger initial calculations and set up initial filter if payer is pre-selected
    setTimeout(function() {
        var $payerSelect = $('#payer_account_id, [name="payer_account_id"]');
        if ($payerSelect.val()) {
            $payerSelect.trigger('change');
        }
        $('#receipt_amount').trigger('change');
        updateSelectAllState();
    }, 100);
});

// Simple number to words converter
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
#invoices-table thead{
    position: sticky;
    top: 0;
    z-index: 10;
}
#invoices-table tfoot{
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
    border-right: none;
}

hr {
    border-top: 1px solid rgba(0,0,0,0.1);
}

#amount_in_words {
    font-style: italic;
    word-break: break-word;
}

/* Invoice table styles */
#invoices-table th, #invoices-table td {
    vertical-align: middle;
}

#invoices-table tfoot th {
    font-weight: bold;
}

.payment-amount {
    width: 120px;
    margin: 0 auto;
}

/* Responsive adjustments */
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
</style>