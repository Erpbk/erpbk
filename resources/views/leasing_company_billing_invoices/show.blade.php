<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leasing Company Billing Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
    @include('invoices.partials.tax_invoice_styles')
</head>

<body>
    @php
    $settings = company_table('settings')->pluck('value', 'name')->toArray();
    $currency = \App\Helpers\Currency::code();
    $defaults = \App\Support\InvoiceModuleDefaults::all('leasing_company_billing_invoices');
    $party = $invoice->customer;
    $invoiceTitle = $defaults['title'] ?: 'RENTAL BILL';
    $partyNote = $invoice->customer_note
    ?: ($party->invoice_note ?? null)
    ?: ($defaults['notes'] ?: null);
    $termsAndConditions = $invoice->terms_and_conditions
    ?: ($party->terms_and_conditions ?? null)
    ?: ($defaults['terms_and_conditions'] ?: null);
    $partyNoteLabel = $party
    ? $party->resolvedInvoiceNoteLabel()
    : 'Invoice Note';
    $termsAndConditionsLabel = $party
    ? $party->resolvedTermsAndConditionsLabel()
    : 'Terms & Conditions';
    $invoiceNumber = $invoice->invoice_number ?? ('LBI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT));
    $subtotalAmount = $invoice->subtotal ?? 0;
    $vatAmt = $invoice->vat ?? 0;
    $totalAmt = $invoice->total_amount ?? ($subtotalAmount + $vatAmt);
    $noteCards = collect([
    $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
    $invoice->notes ? ['title' => 'Internal Notes', 'body' => $invoice->notes] : null,
    ])->filter()->values();
    $noteGridClass = match ($noteCards->count()) {
    1 => 'one',
    3 => 'three',
    default => '',
    };
    $invoiceDateLabel = $invoice->inv_date
    ? (is_object($invoice->inv_date) ? $invoice->inv_date->format('d M Y') : date('d M Y', strtotime($invoice->inv_date)))
    : '';
    $billingLabel = date('M Y', strtotime($invoice->billing_month));
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @php
        $lbiStatus = (int) ($invoice->status ?? 0);
        $lbiIsPaid = $lbiStatus === 1;
        $lbiIsPartial = $lbiStatus === 3 || (! $lbiIsPaid && (float) ($invoice->paid_amount ?? 0) > 0);
        @endphp
        @include('invoices.partials.action_toolbar', [
        'isPaid' => $lbiIsPaid,
        'isPartial' => $lbiIsPartial && ! $lbiIsPaid,
        'editUrl' => route('leasingCompanyBillingInvoices.edit', $invoice->id),
        'editTitle' => 'Edit Invoice',
        'editCan' => 'bike_on_rent_invoices_edit',
        'downloadUrl' => route('leasingCompanyBillingInvoices.show', $invoice->id),
        'showPayment' => ! $lbiIsPaid,
        'paymentUrl' => route('payments.create')
        . '?customer_id=' . ($invoice->customer_id ?? '')
        . '&invoice_id=' . $invoice->id,
        'paymentTitle' => 'Record Payment',
        'paymentCan' => ['customers_payments_create', 'bike_on_rent_invoices_edit'],
        'cloneUrl' => route('leasingCompanyBillingInvoices.createFromClone', $invoice->id),
        'cloneTitle' => 'Clone Invoice',
        'cloneCan' => 'bike_on_rent_invoices_create',
        'deleteFormRoute' => ['leasingCompanyBillingInvoices.destroy', $invoice->id],
        'deleteCan' => 'bike_on_rent_invoices_delete',
        ])
    </div>
    @endif

    <div class="invoice-box">
        <div class="band"></div>
        <div class="sheet">
            @include('invoices.partials.tax_invoice_header', [
            'settings' => $settings,
            'invoiceTitle' => $invoiceTitle,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDateLabel' => $invoiceDateLabel,
            'billingLabel' => $billingLabel,
            ])

            <div class="parties">
                <div class="party">
                    <h3 class="party-title">Bill To</h3>
                    <p class="party-name">{{ $party->company_name ?? ($party->name ?? 'N/A') }}</p>
                    <div class="party-grid">
                        <div class="party-line">
                            <span class="k">Customer</span>
                            <span class="v">{{ $party->name ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Contact</span>
                            <span class="v">{{ $party->company_contact ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Email</span>
                            <span class="v">{{ $party->email ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Cust. Inv #</span>
                            <span class="v">{{ $invoice->customer_invoice_number ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="party alt">
                    <h3 class="party-title">Billing Period</h3>
                    <div class="party-grid" style="margin-top: 4px;">
                        <div class="party-line">
                            <span class="k">Reference</span>
                            <span class="v">{{ $invoice->reference_number ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Month</span>
                            <span class="v">{{ date('F Y', strtotime($invoice->billing_month)) }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Currency</span>
                            <span class="v">{{ $currency }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if(!empty($invoice->descriptions))
            <div class="desc">
                <span class="t">Description</span>
                <p>{{ $invoice->descriptions }}</p>
            </div>
            @endif

            @if($invoice->items && $invoice->items->count() > 0)
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">#</th>
                            <th class="col-desc">Description</th>
                            <th>Qty</th>
                            <th>Days</th>
                            <th>Rate</th>
                            <th>Amount</th>
                            <th>VAT %</th>
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->items as $key => $item)
                        @php
                        $vatRate = $item->tax_rate ?? 0;
                        $vatAmtRow = $item->tax_amount ?? 0;
                        $rowTotal = $item->total_amount ?? (($item->rental_amount ?? 0) + $vatAmtRow);
                        $proratedAmount = $rowTotal - $vatAmtRow;
                        $bikePlate = $item->bike?->plate ?? 'N/A';
                        $bikeEmirates = $item->bike?->emirates ?? '';
                        @endphp
                        <tr>
                            <td class="col-sr">{{ $key + 1 }}</td>
                            <td class="col-desc">Bike # {{ $bikePlate }}@if($bikeEmirates) ({{ $bikeEmirates }})@endif</td>
                            <td>{{ number_format($item->qty ?? 1, 2) }}</td>
                            <td>{{ number_format($item->days ?? 1, 2) }}</td>
                            <td>{{ number_format($item->rental_amount ?? 0, 2) }}</td>
                            <td>{{ number_format($proratedAmount, 2) }}</td>
                            <td>{{ number_format($vatRate, 0) }}%</td>
                            <td>{{ number_format($vatAmtRow, 2) }}</td>
                            <td class="total-cell">{{ number_format($rowTotal, 2) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @include('invoices.partials.tax_invoice_totals_notes', [
            'partyNote' => $partyNote,
            'partyNoteLabel' => $partyNoteLabel,
            'subtotalAmount' => $subtotalAmount,
            'vatAmount' => $vatAmt,
            'totalAmount' => $totalAmt,
            'currency' => $currency,
            'paidAmount' => $invoice->paid_amount ?? 0,
            'balanceAmount' => $invoice->balance ?? (($totalAmt ?? 0) - ($invoice->paid_amount ?? 0)),
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