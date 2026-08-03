@php
    $isRiderPayment = ($invoiceType ?? null) === 'rider';
    $defaultBillingMonth = old(
        'billing_month',
        isset($payment) && $payment->billing_month
            ? \Carbon\Carbon::parse($payment->billing_month)->format('Y-m')
            : date('Y-m')
    );
@endphp
<div class="card-body px-4" @if($isRiderPayment) data-rider-payment="1" @endif>
    <!-- Basic Payment Information -->
    <div class="row">
        @if($isRiderPayment)
        {{-- Billing month at the top filters which pending invoices are shown --}}
        <div class="form-group col-md-3">
            {!! Form::label('billing_month', 'Billing Month:') !!}
            {!! Form::month('billing_month', $defaultBillingMonth, ['class' => 'form-control', 'id' => 'billing_month', 'maxlength' => 255, 'required' => true]) !!}
        </div>
        @endif

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

        @if($isRiderPayment)
        {!! Form::hidden('date_of_invoice', old('date_of_invoice', isset($payment) ? $payment->date_of_invoice : null), ['id' => 'date_of_invoice']) !!}
        <div class="form-group col-md-3">
            {!! Form::label('created_by_display', 'Created By:') !!}
            {!! Form::text('created_by_display', isset($payment) && $payment->created_by
                ? \App\Helpers\Common::UserName($payment->created_by)
                : (auth()->user()->name ?? '-'), ['class' => 'form-control bg-light', 'readonly' => true]) !!}
        </div>
        <div class="form-group col-md-3">
            {!! Form::label('updated_by_display', 'Updated By:') !!}
            {!! Form::text('updated_by_display', isset($payment) && $payment->updated_by
                ? \App\Helpers\Common::UserName($payment->updated_by)
                : (isset($payment) ? (auth()->user()->name ?? '-') : '-'), ['class' => 'form-control bg-light', 'readonly' => true]) !!}
        </div>
        @else
        <div class="form-group col-md-3">
            {!! Form::label('date_of_invoice', 'Date of Invoice:') !!}
            {!! Form::date('date_of_invoice', null, ['class' => 'form-control']) !!}
        </div>

        <!-- Billing Month Field -->
        <div class="form-group col-md-3">
            {!! Form::label('billing_month', 'Billing Month:') !!}
            {!! Form::month('billing_month', null, ['class' => 'form-control', 'id' => 'billing_month', 'maxlength' => 255]) !!}
        </div>
        @endif
    </div>

    <!-- Transaction Details Section -->
    <div class="row mt-3">
        <div class="col-md-12">
            <h6 class="bg-light p-2 mb-3">Transaction Details</h6>
        </div>
        
        <!-- Paying Account (Credit) -->
        <div class="form-group col-md-3">
            {!! Form::label('bank_id', 'Sending Account:') !!}
            @php
                $banks = $banks ?? \App\Models\Banks::active()->get();
            @endphp
            @if(isset($bank) && $bank)
                {!! Form::hidden('bank_id', $bank->id)!!}
                {!! Form::text('bank-name', ($bank->account->account_code ?? '').'-'.($bank->account->name ?? $bank->name), ['class' => 'form-control bg-light', 'readonly' => true]) !!}
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
            @php
                $payeeFieldLabel = match ($invoiceType ?? null) {
                    'employee' => 'Payee (Employee):',
                    'rider' => 'Payee (Rider):',
                    'sim' => 'Payee (Vendor):',
                    'supplier' => 'Payee (Supplier):',
                    'leasingCompany' => 'Payee (Leasing Company):',
                    'customer' => 'Payee (Customer):',
                    default => 'Receiving Account:',
                };
                $selectedPayeeId = old('payee_account_id', isset($payment) ? $payment->payee_account_id : '');
                $payeeOptions = collect($payeeOptions ?? []);
                $lockedPayee = $lockedPayee ?? null;

                // Backward-compatible fallbacks from older controller variables
                if (!$lockedPayee && !empty($leasingCompany) && !empty($leasingCompany->account_id)) {
                    $lockedPayee = [
                        'account_id' => $leasingCompany->account_id,
                        'entity_id' => $leasingCompany->id,
                        'name' => $leasingCompany->name ?: '-',
                        'account_code' => optional($leasingCompany->account)->account_code,
                        'label' => trim((optional($leasingCompany->account)->account_code ? optional($leasingCompany->account)->account_code . ' - ' : '') . ($leasingCompany->name ?: '-')),
                    ];
                }
                if (!$lockedPayee && !empty($customer) && !empty($customer->account_id)) {
                    $lockedPayee = [
                        'account_id' => $customer->account_id,
                        'entity_id' => $customer->id,
                        'name' => $customer->name ?: '-',
                        'account_code' => optional($customer->account)->account_code,
                        'label' => trim((optional($customer->account)->account_code ? optional($customer->account)->account_code . ' - ' : '') . ($customer->name ?: '-')),
                    ];
                }

                if (!$selectedPayeeId && $lockedPayee) {
                    $selectedPayeeId = $lockedPayee['account_id'] ?? null;
                }
                if (!$selectedPayeeId && $payeeOptions->count() === 1) {
                    $selectedPayeeId = $payeeOptions->first()['account_id'] ?? null;
                }
            @endphp
            {!! Form::label('account_id', $payeeFieldLabel) !!}
            @if(!empty($lockedPayee))
                {!! Form::hidden('payee_account_id', $lockedPayee['account_id'], [
                    'data-customer-id' => $lockedPayee['entity_id'] ?? '',
                    'data-customer-name' => $lockedPayee['name'] ?? '',
                ]) !!}
                {!! Form::text('payee-name', $lockedPayee['label'] ?? ($lockedPayee['name'] ?? '-'), ['class' => 'form-control bg-light', 'readonly' => true]) !!}
            @elseif($payeeOptions->isNotEmpty())
                <select name="payee_account_id" class="form-control select2" required>
                    <option value="">-- Select Payee --</option>
                    @foreach($payeeOptions as $payee)
                        <option data-customerId="{{ $payee['entity_id'] }}"
                                data-customerName="{{ $payee['name'] }}"
                                value="{{ $payee['account_id'] }}"
                                {{ (string) $selectedPayeeId === (string) $payee['account_id'] ? 'selected' : '' }}>
                            {{ $payee['label'] ?? $payee['name'] }}
                        </option>
                    @endforeach
                </select>
            @elseif(isset($accountIds))
                @php
                    $payeesForSelect = \App\Models\Accounts::withoutGlobalScope('branch')
                        ->whereIn('id', array_filter((array) $accountIds))
                        ->orderBy('name')
                        ->get();
                    if ($selectedPayeeId && !$payeesForSelect->contains('id', (int) $selectedPayeeId)) {
                        $currentPayee = \App\Models\Accounts::withoutGlobalScope('branch')->find($selectedPayeeId);
                        if ($currentPayee) {
                            $payeesForSelect = $payeesForSelect->prepend($currentPayee);
                        }
                    }
                @endphp
                <select name="payee_account_id" class="form-control select2" required>
                    <option value="">-- Select Receiving Account --</option>
                    @foreach($payeesForSelect as $payee)
                        <option data-customerId="{{ $payee->ref_id }}"
                                data-customerName="{{ $payee->name }}"
                                value="{{ $payee->id }}"
                                {{ (string) $selectedPayeeId === (string) $payee->id ? 'selected' : '' }}>
                            {{ trim(($payee->account_code ? $payee->account_code . ' - ' : '') . ($payee->name ?: '-')) }}
                        </option>
                    @endforeach
                </select>
            @else
                @php
                    $payeesForSelect = \App\Models\Accounts::active()->orderBy('name')->get();
                    if ($selectedPayeeId && !$payeesForSelect->contains('id', (int) $selectedPayeeId)) {
                        $currentPayee = \App\Models\Accounts::withoutGlobalScope('branch')->find($selectedPayeeId);
                        if ($currentPayee) {
                            $payeesForSelect = $payeesForSelect->prepend($currentPayee);
                        }
                    }
                @endphp
                <select name="payee_account_id" class="form-control select2" required>
                    <option value="">-- Select Receiving Account --</option>
                    @foreach($payeesForSelect as $payee)
                        <option data-customerId="{{ $payee->ref_id }}"
                                data-customerName="{{ $payee->name }}"
                                value="{{ $payee->id }}"
                                {{ (string) $selectedPayeeId === (string) $payee->id ? 'selected' : '' }}>
                            {{ trim(($payee->account_code ? $payee->account_code . ' - ' : '') . ($payee->name ?: '-')) }}
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
                    <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                </div>
                {!! Form::number('amount', isset($payment) ? ($payment->amount - $payment->bank_charges) : null, ['class' => 'form-control cr_amount', 'step' => 'any', 'placeholder' => 'Enter amount', 'id' => 'payment_amount']) !!}
            </div>
        </div>
        
        <div class="form-group col-md-3">
            {!! Form::label('bank_charges', 'Bank Charges:') !!}
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                </div>
                {!! Form::number('bank_charges', null, ['class' => 'form-control bank_charges', 'step' => 'any', 'placeholder' => 'Enter bank charges', 'id' => 'bank_charges', 'min' => '0']) !!}
            </div>
        </div>
    </div>

    <!-- Invoice Selection Section -->
    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
    <input type="hidden" value="{{ $invoiceType ?? null }}" name="invoice_type">
    <div class="row mt-4">
        <div class="col-md-12">
            <h6 class="bg-light p-2 mb-3">{{ $isRiderPayment ? 'Select Invoice for Payment' : 'Select Invoices for Payment' }}</h6>
            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                <table class="table table-bordered table-hover" id="invoices-table">
                    <thead>
                        <tr style="color: black !important;">
                            <th width="50px">
                                <input type="checkbox" id="select-all-invoices">
                            </th>
                            <th>Invoice #</th>
                            @if($invoiceType == 'supplier')
                                <th>Supplier</th>
                            @elseif($invoiceType == 'employee')
                                <th>Employee</th>
                            @elseif($invoiceType == 'rider')
                                <th>Rider</th>
                            @elseif($invoiceType == 'sim')
                                <th>Vendor</th>
                            @else
                                <th>Leasing Company</th>
                            @endif
                            @unless($isRiderPayment)
                                <th>Billing Month</th>
                            @endunless
                            <th>Total Amount</th>
                            @unless($isRiderPayment)
                                <th>Paid Amount</th>
                            @endunless
                            <th>Balance Due</th>
                            @unless($isRiderPayment)
                                <th>Payment Amount</th>
                            @endunless
                        </tr>
                    </thead>
                    <tbody>
                        @if(isset($existingInvoices) && $existingInvoices->count() > 0)
                            @foreach($existingInvoices as $invoice)
                            @php
                                $existingBalance = ($invoice->balance ?? 0) + ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                                $existingPaid = ($invoice->paid_amount ?? 0) - ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                                $existingPaymentAmt = $invoice->partial_paid_amount[optional($payment)->id] ?? 0;
                                $invoiceDate = optional($invoice->inv_date)->format('Y-m-d')
                                    ?? (isset($invoice->inv_date) ? date('Y-m-d', strtotime($invoice->inv_date)) : '');
                                $invoiceBillingMonth = $invoice->billing_month
                                    ? date('Y-m', strtotime($invoice->billing_month))
                                    : '';
                            @endphp
                            <tr data-invoice-id="{{ $invoice->id }}"
                                data-balance="{{ $existingBalance }}"
                                data-reference="{{ $invoice->invoice_number ?? $invoice->id }}"
                                data-old-payment="{{ $existingPaymentAmt }}"
                                data-invoice-date="{{ $invoiceDate }}"
                                data-billing-month="{{ $invoiceBillingMonth }}"
                                data-customer-id="{{ optional($invoice->customer)->id ?? optional($invoice->leasingCompany)->id ?? optional($invoice->supplier)->id ?? optional($invoice->employee)->id ?? optional($invoice->rider)->id ?? optional($invoice->vendor)->id }}"
                                data-customer-name="{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->vendor)->name }}">
                                <td class="text-center">
                                    <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" checked>
                                </td>
                                <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                                <td>{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->vendor)->name ?? '-' }}</td>
                                @unless($isRiderPayment)
                                    <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                                @endunless
                                <td class="text-right">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                                @unless($isRiderPayment)
                                    <td class="text-right">{{ number_format($existingPaid, 2) }}</td>
                                @endunless
                                <td class="text-right text-danger">
                                    {{ number_format($existingBalance, 2) }}
                                    @if($isRiderPayment)
                                        <input type="hidden" name="payment_amounts[{{ $invoice->id }}]" class="payment-amount" value="{{ $existingPaymentAmt }}" data-max="{{ $existingBalance }}">
                                    @endif
                                </td>
                                @unless($isRiderPayment)
                                <td>
                                    <input type="number" name="payment_amounts[{{ $invoice->id }}]"
                                        class="form-control payment-amount"
                                        step="any"
                                        placeholder="Amount"
                                        data-max="{{ $invoice->total ?? $invoice->total_amount }}"
                                        value="{{ $existingPaymentAmt }}">
                                </td>
                                @endunless
                            </tr>
                            @endforeach
                        @endif
                        @foreach($invoices as $invoice)
                        @php
                            $invoiceDate = optional($invoice->inv_date)->format('Y-m-d')
                                ?? (isset($invoice->inv_date) ? date('Y-m-d', strtotime($invoice->inv_date)) : '');
                            $invoiceBillingMonth = $invoice->billing_month
                                ? date('Y-m', strtotime($invoice->billing_month))
                                : '';
                        @endphp
                        <tr data-invoice-id="{{ $invoice->id }}"
                            data-balance="{{ $invoice->balance }}"
                            data-reference="{{ $invoice->invoice_number }}"
                            data-invoice-date="{{ $invoiceDate }}"
                            data-billing-month="{{ $invoiceBillingMonth }}"
                            data-customer-id="{{ optional($invoice->customer)->id ?? optional($invoice->leasingCompany)->id ?? optional($invoice->supplier)->id ?? optional($invoice->employee)->id ?? optional($invoice->rider)->id ?? optional($invoice->vendor)->id }}"
                            data-customer-name="{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->vendor)->name }}">
                            <td class="text-center">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox">
                            </td>
                            <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                            <td>{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->vendor)->name ?? '-' }}</td>
                            @unless($isRiderPayment)
                                <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                            @endunless
                            <td class="text-right">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                            @unless($isRiderPayment)
                                <td class="text-right">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            @endunless
                            <td class="text-right text-danger">
                                {{ number_format($invoice->balance, 2) }}
                                @if($isRiderPayment)
                                    <input type="hidden" name="payment_amounts[{{ $invoice->id }}]" class="payment-amount" value="0" data-max="{{ $invoice->balance }}" disabled>
                                @endif
                            </td>
                            @unless($isRiderPayment)
                            <td>
                                <input type="number" name="payment_amounts[{{ $invoice->id }}]"
                                       class="form-control payment-amount"
                                       step="any"
                                       placeholder="Amount"
                                       data-max="{{ $invoice->balance }}"
                                       disabled>
                            </td>
                            @endunless
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light">
                        <tr>
                            <th colspan="{{ $isRiderPayment ? 4 : 6 }}" class="text-right">Total Selected Payment:</th>
                            <th class="text-right" id="total-selected-payment">0.00</th>
                            @unless($isRiderPayment)
                                <th></th>
                            @endunless
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

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
                            {{ \App\Helpers\Currency::code() }} <span id="display_amount">0.00</span>
                        </div>
                    </div>
                    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Total Invoice Payment:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            {{ \App\Helpers\Currency::code() }} <span id="total_invoice_payment">0.00</span>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Difference:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            <span id="payment_difference" class="text-success">{{ \App\Helpers\Currency::code() }} 0.00</span>
                        </div>
                    </div>
                    @endif
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <strong>Bank Charges:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            {{ \App\Helpers\Currency::code() }} <span id="display_charges">0.00</span>
                        </div>
                    </div>
                    <hr class="my-2">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total:</strong>
                        </div>
                        <div class="col-md-6 text-right">
                            {{ \App\Helpers\Currency::code() }} <span id="total_debit">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Boot marker only: JS lives in public/js/payment-fields.js (avoids jQuery DOMEval of large inline scripts in modals) --}}
<div data-payment-fields-init data-currency="{{ \App\Helpers\Currency::code() }}" hidden></div>

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