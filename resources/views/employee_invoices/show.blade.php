<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Employee Invoice #{{ $employeeInvoice->id }} Month: {{ date('M-Y', strtotime($employeeInvoice->billing_month)) }}</title>
    @include('invoices.partials.tax_invoice_styles')
    @include('invoices.partials.tax_invoice_pdf_styles')
</head>

<body>
    @php
    $settings = $settings ?? company_table('settings')->pluck('value', 'name')->toArray();
    $currency = \App\Helpers\Currency::code();
    $defaults = \App\Support\InvoiceModuleDefaults::all('employee_invoices');
    $party = $employeeInvoice->employee;
    $invoiceTitle = $defaults['title'] ?: 'EMPLOYEE INVOICE';
    $partyNote = $employeeInvoice->customer_note
    ?: ($party->invoice_note ?? null)
    ?: ($defaults['notes'] ?: null);
    $termsAndConditions = $employeeInvoice->terms_and_conditions
    ?: ($party->terms_and_conditions ?? null)
    ?: ($defaults['terms_and_conditions'] ?: null);
    $partyNoteLabel = $party
    ? $party->resolvedInvoiceNoteLabel()
    : 'Invoice Note';
    $termsAndConditionsLabel = $party
    ? $party->resolvedTermsAndConditionsLabel()
    : 'Terms & Conditions';
    $invoiceNumber = $invoiceNumber
    ?? $employeeInvoice->invoice_number
    ?? ('EI-' . str_pad($employeeInvoice->id, 6, '0', STR_PAD_LEFT));
    $subtotalAmount = $totalBeforeTax ?? $employeeInvoice->subtotal ?? 0;
    $vatAmt = $vatAmount ?? $employeeInvoice->vat ?? 0;
    $totalAmt = $finalAmount ?? $employeeInvoice->total_amount ?? 0;
    $noteCards = collect([
    $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
    $employeeInvoice->notes ? ['title' => 'Internal Notes', 'body' => $employeeInvoice->notes] : null,
    ])->filter()->values();
    $noteGridClass = match ($noteCards->count()) {
    1 => 'one',
    3 => 'three',
    default => '',
    };
    $ledger_deductions = $ledger_deductions ?? [];
    $ledger_additions = $ledger_additions ?? [];
    $employee_balance = $employee_balance ?? 0;
    $employee_balance_final = $employee_balance_final ?? 0;
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @php
            $companySlug = request()->route('company_slug');
            $eiStatus = (int) ($employeeInvoice->status ?? 0);
            $eiIsPaid = $eiStatus === 1;
            $eiIsPartial = $eiStatus === 3 || (! $eiIsPaid && (float) ($employeeInvoice->paid_amount ?? 0) > 0);
            $eiPaymentId = \App\Support\InvoicePaymentLink::latestId(
                $employeeInvoice,
                $employeeInvoice->employee?->account_id ? (int) $employeeInvoice->employee->account_id : null
            );
        @endphp
        @include('invoices.partials.action_toolbar', [
            'isPaid' => $eiIsPaid,
            'isPartial' => $eiIsPartial && ! $eiIsPaid,
            'editUrl' => route('employeeInvoices.edit', $employeeInvoice->id),
            'editTitle' => 'Edit Invoice',
            'editCan' => 'employees_invoice_edit',
            'editPaymentUrl' => $eiPaymentId ? route('payments.edit', $eiPaymentId) : null,
            'editPaymentTitle' => 'Edit Payment',
            'editPaymentCan' => ['employees_payments_edit', 'cash_&_banks_payments_edit'],
            'downloadUrl' => route('employeeInvoices.download', $employeeInvoice->id),
            'showPayment' => ! $eiIsPaid,
            'paymentUrl' => route('payments.create', array_filter(['company_slug' => $companySlug]))
                . '?employee_payment=1&invoice_id=' . $employeeInvoice->id,
            'paymentTitle' => 'Record Payment',
            'paymentCan' => 'employees_payments_create',
            'markSettledUrl' => route('employeeInvoices.markAsSettled', $employeeInvoice->id),
            'markSettledCan' => 'employees_invoice_edit',
            'deleteUrl' => route('employeeInvoices.delete', $employeeInvoice->id),
            'deleteCan' => 'employees_invoice_delete',
        ])
    </div>
    @include('delete_requests._confirm_delete_script', [
    'entityName' => 'Employee Invoice',
    'confirmText' => 'This will submit a delete request or move the invoice to the Recycle Bin.',
    'method' => 'GET',
    ])
    @endif

    <div class="invoice-box">
        <div class="band"></div>
        <div class="sheet">
            @include('invoices.partials.tax_invoice_header', [
            'settings' => $settings,
            'invoiceTitle' => $invoiceTitle,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDateLabel' => \Carbon\Carbon::parse($employeeInvoice->inv_date)->format('d M Y'),
            'billingLabel' => \Carbon\Carbon::parse($employeeInvoice->billing_month)->format('M Y'),
            ])

            <table class="parties" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="party" width="50%" valign="top">
                        <h3 class="party-title">Employee</h3>
                        <p class="party-name">{{ $party->name ?? 'N/A' }}</p>
                        <div class="party-grid">
                            <div class="party-line">
                                <span class="k">Employee ID</span>
                                <span class="v">{{ $party->employee_id ?? '—' }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Designation</span>
                                <span class="v">{{ $party->designation ?? '—' }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Department</span>
                                <span class="v">{{ $party->department?->name ?? '—' }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Contact</span>
                                <span class="v">{{ $party->company_contact ?? $party->personal_contact ?? '—' }}</span>
                            </div>
                        </div>
                    </td>
                    <td class="party alt" width="50%" valign="top">
                        <h3 class="party-title">Pay Period</h3>
                        <div class="party-grid" style="margin-top: 4px;">
                            <div class="party-line">
                                <span class="k">From</span>
                                <span class="v">{{ \Carbon\Carbon::parse($employeeInvoice->billing_month)->startOfMonth()->format('d M Y') }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">To</span>
                                <span class="v">{{ \Carbon\Carbon::parse($employeeInvoice->billing_month)->endOfMonth()->format('d M Y') }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Month</span>
                                <span class="v">{{ \Carbon\Carbon::parse($employeeInvoice->billing_month)->format('F Y') }}</span>
                            </div>
                            <div class="party-line">
                                <span class="k">Currency</span>
                                <span class="v">{{ $currency }}</span>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            @if($employeeInvoice->descriptions)
            <div class="desc">
                <span class="t">Description</span>
                <p>{{ $employeeInvoice->descriptions }}</p>
            </div>
            @endif

            @if($employeeInvoice->items && $employeeInvoice->items->count() > 0)
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">#</th>
                            <th class="col-desc">Description</th>
                            <th>Qty</th>
                            <th>Rate</th>
                            <th>Discount</th>
                            <th>VAT %</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $items_subtotal = 0; $items_vat = 0; @endphp
                        @foreach($employeeInvoice->items as $key => $item)
                        @php
                        $subtotal = ($item->rate * $item->qty) - $item->discount;
                        $vat = ($item->tax / 100) * $subtotal;
                        $items_subtotal += $subtotal;
                        $items_vat += $vat;
                        @endphp
                        <tr>
                            <td class="col-sr">{{ $key + 1 }}</td>
                            <td class="col-desc">{{ optional(\App\Models\Items::find($item->item_id))->name ?? 'N/A' }}</td>
                            <td>{{ number_format($item->qty, 0) }}</td>
                            <td>{{ number_format($item->rate, 2) }}</td>
                            <td>{{ number_format($item->discount, 2) }}</td>
                            <td>{{ number_format($item->tax, 2) }}%</td>
                            <td>{{ number_format($vat, 2) }}</td>
                            <td class="total-cell">{{ number_format($item->amount, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="tbl-wrap" style="margin-top: 12px;">
                <table class="items ledger">
                    <thead>
                        <tr>
                            <th class="col-desc">Deductions</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @if($employee_balance > 0)
                        <tr>
                            <td class="col-desc">Previous Balance (Deduction)</td>
                            <td>-{{ number_format(abs($employee_balance), 2) }}</td>
                        </tr>
                        @endif
                        @forelse($ledger_deductions as $deduction)
                        <tr>
                            <td class="col-desc">{{ $deduction['label'] }}</td>
                            <td>-{{ number_format($deduction['amount'], 2) }}</td>
                        </tr>
                        @empty
                        @if($employee_balance <= 0)
                            <tr>
                            <td class="col-desc" colspan="2">No ledger deductions for this billing month</td>
                            </tr>
                            @endif
                            @endforelse
                            <tr>
                                <td class="col-desc"><strong>Total Deductions</strong></td>
                                <td class="total-cell">-{{ number_format($total_deductions ?? 0, 2) }}</td>
                            </tr>
                    </tbody>
                </table>
            </div>

            @if(($employee_balance < 0) || count($ledger_additions)> 0)
                <div class="tbl-wrap" style="margin-top: 12px;">
                    <table class="items ledger">
                        <thead>
                            <tr>
                                <th class="col-desc">Additions</th>
                                <th>Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($employee_balance < 0)
                                <tr>
                                <td class="col-desc">Previous Balance (Addition)</td>
                                <td>+{{ number_format(abs($employee_balance), 2) }}</td>
                                </tr>
                                @endif
                                @foreach($ledger_additions as $addition)
                                <tr>
                                    <td class="col-desc">{{ $addition['label'] }}</td>
                                    <td>+{{ number_format($addition['amount'], 2) }}</td>
                                </tr>
                                @endforeach
                                <tr>
                                    <td class="col-desc"><strong>Total Additions</strong></td>
                                    <td class="total-cell">+{{ number_format($total_additions ?? 0, 2) }}</td>
                                </tr>
                        </tbody>
                    </table>
                </div>
                @endif

                @include('invoices.partials.tax_invoice_totals_notes', [
                'partyNote' => $partyNote,
                'partyNoteLabel' => $partyNoteLabel,
                'subtotalAmount' => $subtotalAmount,
                'vatAmount' => $vatAmt,
                'totalAmount' => $totalAmt,
                'currency' => $currency,
                'paidAmount' => $paid_amount ?? 0,
                'balanceAmount' => $employee_balance_final ?? 0,
                ])
                @else
                <div class="empty">No line items on this invoice.</div>
                @endif

                @include('invoices.partials.tax_invoice_footnotes', [
                'noteCards' => $noteCards,
                'noteGridClass' => $noteGridClass,
                ])

                @include('invoices.partials.tax_invoice_footer', ['settings' => $settings])
        </div>
    </div>

    @include('invoices.partials.tax_invoice_print_script', ['isPdf' => $isPdf ?? null])
</body>

</html>