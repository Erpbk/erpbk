@php
    $isEmployeePayment = ($invoiceType ?? null) === 'employee';
    $isRiderPayment = ($invoiceType ?? null) === 'rider';
    // Shared salary-invoice UX: payee-scoped invoices, multi-invoice allocation, deduction-aware balance.
    $isSalaryInvoicePayment = $isRiderPayment || $isEmployeePayment;
    $openedInvoiceId = request()->input('invoice_id');
    $openedInvoice = null;
    if ($openedInvoiceId) {
        $openedInvoice = collect($invoices ?? [])->firstWhere('id', (int) $openedInvoiceId)
            ?? collect($existingInvoices ?? [])->firstWhere('id', (int) $openedInvoiceId);
    }
    $rawBillingMonth = old(
        'billing_month',
        isset($payment) && $payment->billing_month
            ? $payment->billing_month
            : ($openedInvoice && $openedInvoice->billing_month
                ? $openedInvoice->billing_month
                : date('Y-m'))
    );
    // type="month" only accepts YYYY-MM (never YYYY-MM-DD)
    try {
        $defaultBillingMonth = \Carbon\Carbon::parse($rawBillingMonth)->format('Y-m');
    } catch (\Throwable $e) {
        $defaultBillingMonth = date('Y-m');
    }
    $savedPrimaryAmount = null;
    if (isset($payment) && $payment) {
        $savedPrimaryAmount = round((float) $payment->amount - (float) ($payment->bank_charges ?? 0), 2);
    }
    $defaultCreatePaymentType = old('create_payment_type', 'payment');
@endphp
@if($isRiderPayment)
    @include('payments.partials.rider_payment_form')
@else
@php
    $payeeFieldLabelShort = match ($invoiceType ?? null) {
        'employee' => 'Payee (Employee)',
        'sim' => 'Payee (Vendor)',
        'supplier' => 'Payee (Supplier)',
        'leasingCompany' => 'Payee (Leasing Company)',
        'customer' => 'Payee (Customer)',
        default => 'Receiving Account',
    };
@endphp
<div class="uvf-form rider-payment-form px-1"
     @if($isSalaryInvoicePayment) data-salary-invoice-payment="1" @endif
     @if($openedInvoiceId) data-opened-invoice-id="{{ $openedInvoiceId }}" @endif
     data-modal-title="Record Payment"
     data-modal-subtitle="Allocate a payment{{ $isSalaryInvoicePayment ? ' to invoices' : '' }}">

    <div class="rpf-header" data-rpf-inline-header>
        <h4 class="rpf-title">Record Payment</h4>
        <p class="rpf-subtitle">Allocate a payment{{ $isSalaryInvoicePayment ? ' to invoices' : '' }}</p>
    </div>

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="row g-3">
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="date_of_payment">Payment Date <span class="text-danger">*</span></label>
                    {!! Form::date('date_of_payment', null, ['class' => 'form-control rpf-input', 'id' => 'date_of_payment']) !!}
                </div>

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="reference">Reference</label>
                    {!! Form::text('reference', null, ['class' => 'form-control rpf-input', 'maxlength' => 255, 'placeholder' => 'Enter reference number', 'id' => 'reference']) !!}
                </div>

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="amount_type">Payment Mode <span class="text-danger">*</span></label>
                    {!! Form::select('amount_type',
                        [
                            '' => 'Select Payment Mode',
                            'Cash' => 'Cash',
                            'Online' => 'Online',
                            'Cheque' => 'Cheque',
                            'Credit' => 'Credit',
                            'C3 Advance Payment' => 'C3 Advance Payment',
                            'Salaries Payment' => 'Salaries Payment',
                        ],
                        old('amount_type', isset($payment) ? $payment->amount_type : ''),
                        ['class' => 'form-control select2 rpf-input', 'id' => 'amount_type']
                    ) !!}
                </div>

                @if($isSalaryInvoicePayment)
                <div class="form-group col-md-6" id="billing-month-group">
                    <label class="rpf-label" for="billing_month">Billing Month <span class="text-danger">*</span></label>
                    {!! Form::month('billing_month', $defaultBillingMonth, [
                        'class' => 'form-control rpf-input',
                        'id' => 'billing_month',
                        'maxlength' => 255,
                        'required' => true,
                    ]) !!}
                </div>
                {!! Form::hidden('date_of_invoice', old('date_of_invoice', isset($payment) ? $payment->date_of_invoice : null), ['id' => 'date_of_invoice']) !!}
                @else
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="date_of_invoice">Date of Invoice</label>
                    {!! Form::date('date_of_invoice', null, ['class' => 'form-control rpf-input', 'id' => 'date_of_invoice']) !!}
                </div>
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="billing_month">Billing Month</label>
                    {!! Form::month('billing_month', null, ['class' => 'form-control rpf-input', 'id' => 'billing_month', 'maxlength' => 255]) !!}
                </div>
                @endif

                {{-- Paying Account (Credit) --}}
                <div class="form-group col-md-6">
                    <label class="rpf-label" for="bank_id">Paid By <span class="text-danger">*</span></label>
                    @php
                        $banks = $banks ?? \App\Models\Banks::active()->get();
                    @endphp
                    @if(isset($bank) && $bank)
                        {!! Form::hidden('bank_id', $bank->id)!!}
                        {!! Form::text('bank-name', ($bank->account->account_code ?? '').'-'.($bank->account->name ?? $bank->name), ['class' => 'form-control rpf-input bg-light', 'readonly' => true]) !!}
                    @else
                        <select name="bank_id" id="bank_id" class="form-control select2 rpf-input" required>
                            <option value="">-- Select Paying Account --</option>
                            @foreach ($banks as $bankOption)
                                <option value="{{ $bankOption->id }}" {{ old('bank_id', isset($payment) ? $payment->bank_id : '') == $bankOption->id ? 'selected' : '' }}>
                                    {{ $bankOption->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>

                <div class="form-group col-md-6">
                    @php
                        $payeeFieldLabel = $payeeFieldLabelShort . ':';
                        $selectedPayeeId = old(
                            'payee_account_id',
                            isset($payment) ? $payment->payee_account_id : ($preferredPayeeAccountId ?? '')
                        );
                        $payeeOptions = collect($payeeOptions ?? []);
                        $lockedPayee = $lockedPayee ?? null;

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
                    <label class="rpf-label">{{ $payeeFieldLabelShort }} <span class="text-danger">*</span></label>
                    @if(!empty($lockedPayee))
                        {!! Form::hidden('payee_account_id', $lockedPayee['account_id'], [
                            'data-customer-id' => $lockedPayee['entity_id'] ?? '',
                            'data-customer-name' => $lockedPayee['name'] ?? '',
                        ]) !!}
                        <div class="rpf-payee-box">
                            <div class="rpf-payee-name">{{ $lockedPayee['name'] ?? ($lockedPayee['label'] ?? '-') }}</div>
                            @if(!empty($lockedPayee['account_code']))
                            <div class="rpf-payee-id">{{ $lockedPayee['account_code'] }}</div>
                            @endif
                        </div>
                    @elseif($payeeOptions->isNotEmpty())
                        <select name="payee_account_id" class="form-control select2 rpf-input" required>
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
                        <select name="payee_account_id" class="form-control select2 rpf-input" required>
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
                        <select name="payee_account_id" class="form-control select2 rpf-input" required>
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

                <div class="form-group col-md-6">
                    <label class="rpf-label" for="payment_amount">Payment Amount <span class="text-danger">*</span></label>
                    <div class="input-group rpf-amount-group">
                        <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                        {!! Form::number(
                            'amount',
                            old('amount', $savedPrimaryAmount),
                            [
                                'class' => 'form-control cr_amount rpf-input',
                                'step' => 'any',
                                'placeholder' => '0.00',
                                'id' => 'payment_amount',
                                'data-saved-amount' => $savedPrimaryAmount !== null ? number_format($savedPrimaryAmount, 2, '.', '') : null,
                            ]
                        ) !!}
                    </div>
                </div>
            </div>
        </div>

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

    <div class="rpf-section mt-4">
        <h6 class="rpf-section-title">Narration / Description</h6>
        <div class="form-group mb-0">
            <label class="rpf-label" for="description">Main Narration / Description</label>
            {!! Form::textarea('description', null, [
                'class' => 'form-control rpf-input',
                'id' => 'description',
                'rows' => 3,
                'placeholder' => 'Enter main description or notes about this payment...',
            ]) !!}
        </div>
    </div>

    {{-- Invoice Selection Section --}}
    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
    <input type="hidden" value="{{ $invoiceType ?? null }}" name="invoice_type">
    <div class="rpf-section mt-4">
        <h6 class="rpf-section-title">Select Invoices for Payment</h6>
        <div class="table-responsive rpf-invoice-scroll">
                <table class="table table-bordered rpf-invoice-table" id="invoices-table">
                    <thead>
                        <tr>
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
                            @php
                                $existingBalance = ($invoice->balance ?? 0) + ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                                $existingPaid = ($invoice->paid_amount ?? 0) - ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                                $existingPaymentAmt = (float) ($invoice->partial_paid_amount[optional($payment)->id] ?? 0);
                                // Invoice lines must exclude bank charges (stored payment.amount is net + charges).
                                if (isset($payment) && (float) ($payment->bank_charges ?? 0) > 0) {
                                    $netPaymentAmt = round((float) $payment->amount - (float) $payment->bank_charges, 2);
                                    if (abs($existingPaymentAmt - (float) $payment->amount) < 0.02) {
                                        $existingPaymentAmt = $netPaymentAmt;
                                    } elseif ($existingPaymentAmt > $netPaymentAmt + 0.009) {
                                        $existingPaymentAmt = $netPaymentAmt;
                                    }
                                }
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
                                data-customer-id="{{ optional($invoice->customer)->id ?? optional($invoice->leasingCompany)->id ?? optional($invoice->supplier)->id ?? optional($invoice->employee)->id ?? optional($invoice->rider)->id ?? optional($invoice->company)->id ?? optional($invoice->vendor)->id }}"
                                data-customer-name="{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->company)->name ?? optional($invoice->vendor)->name }}">
                                <td class="text-center">
                                    <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" checked>
                                </td>
                                <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                                <td>{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->company)->name ?? optional($invoice->vendor)->name ?? '-' }}</td>
                                <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                                <td class="text-right">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                                <td class="text-right">{{ number_format($existingPaid, 2) }}</td>
                                <td class="text-right text-danger">{{ number_format($existingBalance, 2) }}</td>
                                <td>
                                    <input type="number" name="payment_amounts[{{ $invoice->id }}]"
                                        class="form-control payment-amount"
                                        step="any"
                                        placeholder="Amount"
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
                        @endphp
                        <tr data-invoice-id="{{ $invoice->id }}"
                            data-balance="{{ $invoice->balance }}"
                            data-reference="{{ $invoice->invoice_number }}"
                            data-invoice-date="{{ $invoiceDate }}"
                            data-billing-month="{{ $invoiceBillingMonth }}"
                            data-customer-id="{{ optional($invoice->customer)->id ?? optional($invoice->leasingCompany)->id ?? optional($invoice->supplier)->id ?? optional($invoice->employee)->id ?? optional($invoice->rider)->id ?? optional($invoice->company)->id ?? optional($invoice->vendor)->id }}"
                            data-customer-name="{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->company)->name ?? optional($invoice->vendor)->name }}">
                            <td class="text-center">
                                <input type="checkbox" name="invoice_ids[]" value="{{ $invoice->id }}" class="invoice-checkbox" {{ (string) $openedInvoiceId === (string) $invoice->id ? 'checked' : '' }}>
                            </td>
                            <td>{{ $invoice->invoice_number ?? $invoice->id }}</td>
                            <td>{{ optional($invoice->customer)->name ?? optional($invoice->leasingCompany)->name ?? optional($invoice->supplier)->name ?? optional($invoice->employee)->name ?? optional($invoice->rider)->name ?? optional($invoice->company)->name ?? optional($invoice->vendor)->name ?? '-' }}</td>
                            <td>{{ $invoice->billing_month ? date('M Y', strtotime($invoice->billing_month)) : '-' }}</td>
                            <td class="text-right">{{ number_format($invoice->total ?? $invoice->total_amount, 2) }}</td>
                            <td class="text-right">{{ number_format($invoice->paid_amount ?? 0, 2) }}</td>
                            <td class="text-right text-danger">{{ number_format($invoice->balance, 2) }}</td>
                            <td>
                                <input type="number" name="payment_amounts[{{ $invoice->id }}]"
                                       class="form-control payment-amount"
                                       step="any"
                                       placeholder="Amount"
                                       data-max="{{ $invoice->total ?? $invoice->total_amount }}"
                                       disabled>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="6" class="text-end">Total Selected Payment:</th>
                            <th class="text-end" id="total-selected-payment">0.00</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
        </div>
        <div class="rpf-table-footer">Total selected payment updates as you allocate invoices.</div>
    </div>
    @endif

    <div class="rpf-section mt-4">
        <div class="row g-3">
            <div class="col-lg-7"></div>
            <div class="col-lg-5">
                <div class="rpf-summary">
                    <div class="rpf-summary-row">
                        <span>Payment Amount:</span>
                        <span>{{ \App\Helpers\Currency::code() }} <span id="display_amount">0.00</span></span>
                    </div>
                    @if((isset($invoices) && $invoices->count() > 0) || (isset($existingInvoices) && $existingInvoices->count() > 0))
                    <div class="rpf-summary-row">
                        <span>Invoice Allocation:</span>
                        <span>{{ \App\Helpers\Currency::code() }} <span id="total_invoice_payment">0.00</span></span>
                    </div>
                    <div class="rpf-summary-row">
                        <span>Difference:</span>
                        <span id="payment_difference" class="text-success">{{ \App\Helpers\Currency::code() }} 0.00</span>
                    </div>
                    @endif
                    <div class="rpf-summary-row rpf-summary-charges">
                        <span>Bank Charges:</span>
                        <div class="input-group input-group-sm rpf-charges-group">
                            <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                            {!! Form::number('bank_charges', null, [
                                'class' => 'form-control bank_charges',
                                'step' => 'any',
                                'placeholder' => '0.00',
                                'id' => 'bank_charges',
                                'min' => '0',
                            ]) !!}
                        </div>
                    </div>
                    <span id="display_charges" class="d-none">0.00</span>
                    <div class="rpf-summary-total">
                        Total Payment: {{ \App\Helpers\Currency::code() }} <span id="total_debit">0.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Boot marker only: JS lives in public/js/payment-fields.js --}}
<div
    data-payment-fields-init
    data-currency="{{ \App\Helpers\Currency::code() }}"
    @if($savedPrimaryAmount !== null)
        data-preserve-payment-amount="1"
        data-saved-payment-amount="{{ number_format($savedPrimaryAmount, 2, '.', '') }}"
    @endif
    hidden
></div>

@include('partials.uvf_styles')
@endif