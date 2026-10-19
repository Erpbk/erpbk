@php
$currency = \App\Helpers\Currency::code();
$hideBillingMonthForRiderPayment = ! isset($payment) && ($defaultCreatePaymentType === 'payment');
$banks = $banks ?? \App\Models\Banks::active()->get();

$selectedPayeeId = old(
'payee_account_id',
isset($payment) ? $payment->payee_account_id : ($preferredPayeeAccountId ?? '')
);
$payeeOptions = collect($payeeOptions ?? []);
$lockedPayee = $lockedPayee ?? null;
if (!$selectedPayeeId && $lockedPayee) {
$selectedPayeeId = $lockedPayee['account_id'] ?? null;
}
if (!$selectedPayeeId && $payeeOptions->count() === 1) {
$selectedPayeeId = $payeeOptions->first()['account_id'] ?? null;
}

$payeeName = $lockedPayee['name'] ?? null;
$payeeCode = $lockedPayee['entity_code'] ?? null;
if (! $payeeCode && ! empty($lockedPayee['label'])) {
// label may be "CODE - Name" or "ACC - RIDERID - Name"
if (preg_match('/\b(RD[\w\-]+|\d{4,}[\w\-]*)\b/', (string) ($lockedPayee['label'] ?? ''), $m)) {
$payeeCode = $m[1];
}
}

@endphp

<div class="rider-payment-form"
    data-salary-invoice-payment="1"
    data-rider-payment="1"
    data-modal-title="Record Rider Payment"
    data-modal-subtitle="Allocate a payment to rider invoices"
    @if($openedInvoiceId) data-opened-invoice-id="{{ $openedInvoiceId }}" @endif>

    <div class="rpf-header" data-rpf-inline-header>
        <h4 class="rpf-title">Record Rider Payment</h4>
        <p class="rpf-subtitle">Allocate a payment to rider invoices</p>
    </div>

    <div class="row g-3">
        {{-- Left: payment details --}}
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="form-group col-md-6">
                    <label for="date_of_payment" class="rpf-label">Payment Date <span class="text-danger">*</span></label>
                    {!! Form::date('date_of_payment', old('date_of_payment', isset($payment) ? optional($payment->date_of_payment)->format('Y-m-d') : date('Y-m-d')), [
                    'class' => 'form-control rpf-input',
                    'id' => 'date_of_payment',
                    'required' => true,
                    ]) !!}
                </div>

                <div class="form-group col-md-6">
                    <label for="reference" class="rpf-label">Reference <span class="text-danger">*</span></label>
                    {!! Form::text('reference', null, [
                    'class' => 'form-control rpf-input',
                    'maxlength' => 255,
                    'placeholder' => 'Enter reference number',
                    'id' => 'reference',
                    ]) !!}
                </div>

                <div class="form-group col-md-6">
                    <label class="rpf-label">Payee (Rider) <span class="text-danger">*</span></label>
                    @if(!empty($lockedPayee))
                    {!! Form::hidden('payee_account_id', $lockedPayee['account_id'], [
                    'data-customer-id' => $lockedPayee['entity_id'] ?? '',
                    'data-customer-name' => $lockedPayee['name'] ?? '',
                    'data-ledger-balance' => number_format((float) ($lockedPayee['ledger_balance'] ?? 0), 2, '.', ''),
                    'id' => 'payee_account_id',
                    ]) !!}
                    <div class="rpf-payee-box">
                        <div class="rpf-payee-name">{{ $payeeName ?: ($lockedPayee['label'] ?? '-') }}</div>
                        @if($payeeCode)
                        <div class="rpf-payee-id">{{ $payeeCode }}</div>
                        @endif
                    </div>
                    @elseif($payeeOptions->isNotEmpty())
                    <select name="payee_account_id" id="payee_account_id" class="form-control select2 rpf-input" required>
                        <option value="">-- Select Payee --</option>
                        @foreach($payeeOptions as $payee)
                        <option data-customerId="{{ $payee['entity_id'] }}"
                            data-customerName="{{ $payee['name'] }}"
                            data-ledger-balance="{{ number_format((float) ($payee['ledger_balance'] ?? 0), 2, '.', '') }}"
                            value="{{ $payee['account_id'] }}"
                            {{ (string) $selectedPayeeId === (string) $payee['account_id'] ? 'selected' : '' }}>
                            {{ $payee['label'] ?? $payee['name'] }}
                        </option>
                        @endforeach
                    </select>
                    @endif
                </div>

                <div class="form-group col-md-6">
                    <label for="amount_type" class="rpf-label">Payment Mode <span class="text-danger">*</span></label>
                    {!! Form::select('amount_type',
                    ['' => 'Select payment mode', 'Cash' => 'Cash', 'Online' => 'Online', 'Cheque' => 'Cheque', 'Credit' => 'Credit'],
                    old('amount_type', isset($payment) ? $payment->amount_type : ''),
                    ['class' => 'form-control select2 rpf-input', 'id' => 'amount_type', 'required' => true]
                    ) !!}
                </div>

                @if(!isset($payment))
                <div class="form-group col-md-6">
                    <label for="create_payment_type" class="rpf-label">Payment Type <span class="text-danger">*</span></label>
                    {!! Form::select('create_payment_type',
                    ['' => 'Select payment type', 'payment' => 'Payment', 'advance_loan' => 'Advance Loan'],
                    $defaultCreatePaymentType,
                    ['class' => 'form-control select2 rpf-input', 'id' => 'create_payment_type', 'required' => true]
                    ) !!}
                </div>
                @endif

                <div class="form-group col-md-6" id="billing-month-group" @if($hideBillingMonthForRiderPayment) style="display:none" @endif>
                    <label for="billing_month" class="rpf-label">Billing Month <span class="text-danger">*</span></label>
                    {!! Form::month('billing_month', $defaultBillingMonth, [
                    'class' => 'form-control rpf-input',
                    'id' => 'billing_month',
                    'maxlength' => 255,
                    'required' => $defaultCreatePaymentType === 'advance_loan',
                    ]) !!}
                </div>

                <div class="form-group col-md-6">
                    <label for="bank_id" class="rpf-label">Paid By <span class="text-danger">*</span></label>
                    @if(isset($bank) && $bank)
                    {!! Form::hidden('bank_id', $bank->id) !!}
                    {!! Form::text('bank-name', ($bank->account->account_code ?? '').'-'.($bank->account->name ?? $bank->name), ['class' => 'form-control rpf-input bg-light', 'readonly' => true]) !!}
                    @else
                    <select name="bank_id" id="bank_id" class="form-control select2 rpf-input" required>
                        <option value="">-- Select paying account --</option>
                        @foreach ($banks as $bankOption)
                        <option value="{{ $bankOption->id }}" {{ old('bank_id', isset($payment) ? $payment->bank_id : '') == $bankOption->id ? 'selected' : '' }}>
                            {{ $bankOption->name }}
                        </option>
                        @endforeach
                    </select>
                    @endif
                </div>

                <div class="form-group col-md-6">
                    <label for="payment_amount" class="rpf-label">Payment Amount <span class="text-danger">*</span></label>
                    <div class="input-group rpf-amount-group">
                        <span class="input-group-text">{{ $currency }}</span>
                        {!! Form::number(
                        'amount',
                        old('amount', $savedPrimaryAmount),
                        [
                        'class' => 'form-control cr_amount rpf-input',
                        'step' => 'any',
                        'placeholder' => '0.00',
                        'id' => 'payment_amount',
                        'required' => true,
                        'min' => '0',
                        'data-saved-amount' => $savedPrimaryAmount !== null ? number_format($savedPrimaryAmount, 2, '.', '') : null,
                        ]
                        ) !!}
                    </div>
                    <div class="rpf-balance-meta" id="rider_balance_due_wrap">
                        <div class="rpf-balance-due">
                            Remaining: {{ $currency }} <span id="rider_balance_due">0.00</span>
                        </div>
                        <div class="rpf-ledger-due">
                            Total Due Balance: {{ $currency }} <span id="rider_ledger_due">0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: attachments --}}
        <div class="col-lg-4">
            <div class="rpf-attach-box">
                @include('partials.universal_document_upload', [
                'name' => 'attachment',
                'label' => null,
                'required' => false,
                'inputClass' => 'form-control',
                'showHint' => true,
                'variant' => 'dropzone',
                'uploadLabel' => 'Upload File',
                'existingUrl' => !empty($payment?->attachment) ? url('storage/vouchers/' . ltrim($payment->attachment, '/')) : null,
                'existingName' => !empty($payment?->attachment) ? basename($payment->attachment) : null,
                ])
                <div class="rpf-attach-hint">Multiple pages combined into one PDF.</div>
            </div>
        </div>
    </div>

    {!! Form::hidden('date_of_invoice', old('date_of_invoice', isset($payment) ? $payment->date_of_invoice : null), ['id' => 'date_of_invoice']) !!}

    {{-- Narration --}}
    <div class="rpf-section mt-4">
        <h6 class="rpf-section-title">Narration / Description</h6>
        <div class="form-group mb-0">
            <label for="description" class="rpf-label">Main Narration / Description:</label>
            {!! Form::textarea('description', null, [
            'class' => 'form-control rpf-input',
            'id' => 'description',
            'rows' => 3,
            'placeholder' => 'Enter main description or notes about this payment...',
            'required' => true,
            ]) !!}
        </div>
    </div>

    {{-- Invoices --}}
    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
    <input type="hidden" value="rider" name="invoice_type">
    <div class="rpf-section mt-4">
        <div class="table-responsive rpf-invoice-scroll">
            <table class="table table-bordered rpf-invoice-table" id="invoices-table">
                <thead>
                    <tr>
                        <th width="44px">
                            <input type="checkbox" id="select-all-invoices" class="d-none" style="display:none" tabindex="-1" aria-hidden="true">
                        </th>
                        <th>INVOICE #</th>
                        <th>BILLING MONTH</th>
                        <th class="text-end">TOTAL AMOUNT</th>
                        <th class="text-end">REMAINING AMOUNT</th>
                        <th class="text-end">PAYMENT AMOUNT</th>
                    </tr>
                </thead>
                <tbody>
                    @if(isset($existingInvoices) && $existingInvoices->count() > 0)
                    @foreach($existingInvoices as $invoice)
                    @php
                    $existingBalance = ($invoice->balance ?? 0) + ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                    $existingPaid = ($invoice->paid_amount ?? 0) - ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                    $existingPaymentAmt = (float) ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                    // Invoice lines must exclude bank charges (stored payment.amount is net + charges).
                    if (isset($payment) && (float) ($payment->bank_charges ?? 0) > 0) {
                    $netPaymentAmt = round((float) $payment->amount - (float) $payment->bank_charges, 2);
                    if (abs($existingPaymentAmt - (float) $payment->amount) < 0.02) {
                        $existingPaymentAmt=$netPaymentAmt;
                        } elseif ($existingPaymentAmt> $netPaymentAmt + 0.009) {
                        $existingPaymentAmt = $netPaymentAmt;
                        }
                        }
                        $invoiceDate = optional($invoice->inv_date)->format('Y-m-d')
                        ?? (isset($invoice->inv_date) ? date('Y-m-d', strtotime($invoice->inv_date)) : '');
                        $invoiceBillingMonth = $invoice->billing_month
                        ? date('Y-m', strtotime($invoice->billing_month))
                        : '';
                        $rowBreakdown = $invoice->payment_breakdown ?? null;
                        @endphp
                        <tr data-invoice-id="{{ $invoice->id }}"
                            data-balance="{{ $existingBalance }}"
                            data-invoice-total="{{ $invoice->total ?? $invoice->total_amount }}"
                            data-reference="{{ $invoice->invoice_number ?? $invoice->id }}"
                            data-old-payment="{{ $existingPaymentAmt }}"
                            data-invoice-date="{{ $invoiceDate }}"
                            data-billing-month="{{ $invoiceBillingMonth }}"
                            data-customer-id="{{ optional($invoice->rider)->id }}"
                            data-customer-name="{{ optional($invoice->rider)->name }}"
                            data-payment-breakdown="{{ $rowBreakdown ? json_encode($rowBreakdown, JSON_UNESCAPED_UNICODE) : '' }}">
                            <td class="text-center">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" checked>
                            </td>
                            <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                            <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                            <td class="text-end">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($existingBalance, 2) }}</td>
                            <td>
                                <input type="number" name="payment_amounts[{{ $invoice->id }}]"
                                    class="form-control payment-amount rpf-line-amount"
                                    step="any"
                                    placeholder="0.00"
                                    data-max="{{ $invoice->total ?? $invoice->total_amount }}"
                                    value="{{ $existingPaymentAmt }}">
                            </td>
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
                        $rowBreakdown = $invoice->payment_breakdown ?? null;
                        @endphp
                        <tr data-invoice-id="{{ $invoice->id }}"
                            data-balance="{{ $invoice->balance }}"
                            data-invoice-total="{{ $invoice->total ?? $invoice->total_amount }}"
                            data-reference="{{ $invoice->invoice_number }}"
                            data-invoice-date="{{ $invoiceDate }}"
                            data-billing-month="{{ $invoiceBillingMonth }}"
                            data-customer-id="{{ optional($invoice->rider)->id }}"
                            data-customer-name="{{ optional($invoice->rider)->name }}"
                            data-payment-breakdown="{{ $rowBreakdown ? json_encode($rowBreakdown, JSON_UNESCAPED_UNICODE) : '' }}">
                            <td class="text-center">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" {{ (string) $openedInvoiceId === (string) $invoice->id ? 'checked' : '' }}>
                            </td>
                            <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                            <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                            <td class="text-end">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                            <td class="text-end text-danger">{{ number_format($invoice->balance ?? 0, 2) }}</td>
                            <td>
                                <input type="number" name="payment_amounts[{{ $invoice->id }}]"
                                    class="form-control payment-amount rpf-line-amount"
                                    step="any"
                                    placeholder="0.00"
                                    data-max="{{ $invoice->total ?? $invoice->total_amount }}"
                                    disabled>
                            </td>
                        </tr>
                        @endforeach
                </tbody>
            </table>
        </div>
        <div class="rpf-table-footer">
            Total selected payment: <strong>{{ $currency }} <span id="total-selected-payment">0.00</span></strong>
        </div>
    </div>
    @else
    <input type="hidden" value="rider" name="invoice_type">
    <div class="alert alert-light border mt-4 mb-0">No unpaid invoices found for this rider.</div>
    @endif

    {{-- Deductions / Additions (Rider Invoice table style) + Payment Summary --}}
    <div class="rpf-section mt-4" id="rpf-payment-breakdown" aria-live="polite">
        <div class="rpf-adj-stack d-none" id="rpf-adj-panels">
            <div class="rpf-inv-tbl-wrap">
                <table class="rpf-inv-items-table">
                    <thead>
                        <tr>
                            <th colspan="2" class="rpf-inv-sec-header">Deductions</th>
                        </tr>
                    </thead>
                    <tbody id="rpf-deductions-body">
                        <tr>
                            <td colspan="2" class="rpf-adj-empty">Select an invoice to load deductions.</td>
                        </tr>
                    </tbody>
                    <tfoot>
                        <tr class="rpf-inv-accent-total">
                            <td>Total Deductions</td>
                            <td class="rpf-inv-num" id="rpf-total-deductions">-0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="rpf-inv-tbl-wrap d-none" id="rpf-additions-wrap">
                <table class="rpf-inv-items-table">
                    <thead>
                        <tr>
                            <th colspan="2" class="rpf-inv-sec-header">Additions</th>
                        </tr>
                    </thead>
                    <tbody id="rpf-additions-body"></tbody>
                    <tfoot>
                        <tr class="rpf-inv-accent-total">
                            <td>Total Additions</td>
                            <td class="rpf-inv-num" id="rpf-total-additions">+0.00</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            <div class="rpf-adj-payable">
                Net Payable: <strong>{{ $currency }} <span id="rpf-breakdown-payable">0.00</span></strong>
                <span class="rpf-breakdown-month text-muted" id="rpf-breakdown-month"></span>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-lg-7"></div>
            <div class="col-lg-5">
                <div class="rpf-summary">
                    <div class="rpf-summary-row">
                        <span>Payment Amount:</span>
                        <span>{{ $currency }} <span id="display_amount">0.00</span></span>
                    </div>
                    <div class="rpf-summary-row">
                        <span>Invoice Allocation:</span>
                        <span>{{ $currency }} <span id="total_invoice_payment">0.00</span></span>
                    </div>
                    <div class="rpf-summary-row">
                        <span>Difference:</span>
                        <span id="payment_difference" class="text-success">{{ $currency }} 0.00</span>
                    </div>
                    <div class="rpf-summary-row rpf-summary-charges">
                        <span>Bank Charges:</span>
                        <div class="input-group input-group-sm rpf-charges-group">
                            <span class="input-group-text">{{ $currency }}</span>
                            {!! Form::number('bank_charges', old('bank_charges', isset($payment) ? $payment->bank_charges : 0), [
                            'class' => 'form-control bank_charges',
                            'step' => 'any',
                            'placeholder' => '0.00',
                            'id' => 'bank_charges',
                            'min' => '0',
                            ]) !!}
                        </div>
                    </div>
                    <div class="rpf-summary-total">
                        Total Payment: {{ $currency }} <span id="total_debit">0.00</span>
                    </div>
                    <span id="display_charges" class="d-none">0.00</span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Boot marker only: JS lives in public/js/payment-fields.js --}}
<div
    data-payment-fields-init
    data-currency="{{ $currency }}"
    @if($savedPrimaryAmount !==null)
    data-preserve-payment-amount="1"
    data-saved-payment-amount="{{ number_format($savedPrimaryAmount, 2, '.', '') }}"
    @endif
    hidden></div>

@include('partials.uvf_styles')