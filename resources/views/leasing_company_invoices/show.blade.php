<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Leasing Company Invoice #{{ $invoice->invoice_number ?? $invoice->id }} Month: {{ date('M-Y', strtotime($invoice->billing_month)) }}</title>
    @include('invoices.partials.tax_invoice_styles')
    <style>
        .invoice-box .balance-lines {
            display: flex;
            flex-direction: column;
            gap: 6px;
            margin: 8px 0 16px;
            align-items: flex-end;
            font-size: 12.5px;
        }

        .invoice-box .balance-lines .line {
            display: flex;
            gap: 24px;
            min-width: 260px;
            justify-content: space-between;
        }

        .invoice-box .balance-lines .k {
            color: #64748b;
            font-weight: 500;
        }

        .invoice-box .balance-lines .v {
            font-weight: 700;
            color: #0f172a;
        }
    </style>
</head>

<body>
    @php
    $settings = company_table('settings')->pluck('value', 'name')->toArray();
    $currency = \App\Helpers\Currency::code();
    $defaults = \App\Support\InvoiceModuleDefaults::all('leasing_company_invoices');
    $party = $invoice->leasingCompany;
    $invoiceTitle = $defaults['title'] ?: 'LEASING COMPANY INVOICE';
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
    $invoiceNumber = $invoice->invoice_number ?? ('LCI' . str_pad($invoice->id, 8, '0', STR_PAD_LEFT));
    $fmo = strtoupper(date("M'y", strtotime($invoice->billing_month)));
    $subtotalAmount = (float) ($invoice->subtotal ?? 0);
    $vatAmt = (float) ($invoice->vat ?? 0);
    $totalAmt = (float) ($invoice->total_amount ?? 0);
    $paidAmount = (float) ($invoice->paid_amount ?? 0);
    $balanceDue = $totalAmt - $paidAmount;
    $isPaid = (int) ($invoice->status ?? 0) === 1;
    $companySlug = request()->route('company_slug');
    $noteCards = collect([
    $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
    $invoice->notes ? ['title' => 'Internal Notes', 'body' => $invoice->notes] : null,
    ])->filter()->values();
    $noteGridClass = match ($noteCards->count()) {
    1 => 'one',
    3 => 'three',
    default => '',
    };
    $invoiceDateLabel = optional($invoice->inv_date)->format('d M Y')
    ?? optional($invoice->created_at)->format('d M Y')
    ?? '';
    $billingLabel = date('M Y', strtotime($invoice->billing_month));
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @php
        $companySlug = request()->route('company_slug');
        $isPaid = (int) ($invoice->status ?? 0) === 1;
        $isPartial = (int) ($invoice->status ?? 0) === 3 || (! $isPaid && (float) ($invoice->paid_amount ?? 0) > 0);
        @endphp
        @include('invoices.partials.action_toolbar', [
        'isPaid' => $isPaid,
        'isPartial' => $isPartial && ! $isPaid,
        'editUrl' => route('leasingCompanyInvoices.edit', $invoice->id),
        'editTitle' => 'Edit Invoice',
        'editCan' => 'leasing_companies_invoices_edit',
        'downloadUrl' => route('leasingCompanyInvoices.show', $invoice->id),
        'showPayment' => ! $isPaid,
        'paymentUrl' => route('payments.create', array_filter(['company_slug' => $companySlug]))
        . '?leasing_company_id=' . $invoice->leasing_company_id . '&invoice_id=' . $invoice->id,
        'paymentTitle' => 'Record Payment',
        'paymentCan' => 'leasing_companies_payments_create',
        'cloneUrl' => route('leasingCompanyInvoices.createFromClone', $invoice->id),
        'cloneTitle' => 'Clone Invoice',
        'cloneCan' => 'leasing_companies_invoices_create',
        'deleteFormRoute' => ['leasingCompanyInvoices.destroy', $invoice->id],
        'deleteCan' => 'leasing_companies_invoices_delete',
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
                    <h3 class="party-title">Leasing Company</h3>
                    <p class="party-name">{{ $party->name ?? 'N/A' }}</p>
                    <div class="party-grid">
                        <div class="party-line">
                            <span class="k">TRN</span>
                            <span class="v">{{ $party->trn_number ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Contact</span>
                            <span class="v">{{ $party->contact_person ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Phone</span>
                            <span class="v">{{ $party->contact_number ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">LC Inv #</span>
                            <span class="v">{{ $invoice->leasing_company_invoice_number ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="party alt">
                    <h3 class="party-title">Service Period</h3>
                    <div class="party-grid" style="margin-top: 4px;">
                        <div class="party-line">
                            <span class="k">From</span>
                            <span class="v">{{ date('d M Y', strtotime($invoice->billing_month)) }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">To</span>
                            <span class="v">{{ date('t M Y', strtotime($invoice->billing_month)) }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Reference</span>
                            <span class="v">{{ $invoice->reference_number ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Bikes</span>
                            <span class="v">{{ $invoice->items ? $invoice->items->count() : 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($invoice->descriptions)
            <div class="desc">
                <span class="t">Description</span>
                <p>{{ $invoice->descriptions }}</p>
            </div>
            @endif

            @if($invoice->items && $invoice->items->count() > 0)
            @php $runningTotal = 0; $exclTotal = 0; $taxTotal = 0; @endphp
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-sr">#</th>
                            <th class="col-desc">Description</th>
                            <th>FMO</th>
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
                        $vatRate = (float) ($item->tax_rate ?? 0);
                        $vatAmtRow = (float) ($item->tax_amount ?? 0);
                        $rowTotal = (float) ($item->total_amount ?? (($item->rental_amount ?? 0) + $vatAmtRow));
                        $exclAmount = $rowTotal - $vatAmtRow;
                        $runningTotal += $rowTotal;
                        $exclTotal += $exclAmount;
                        $taxTotal += $vatAmtRow;
                        $bike = $item->bike;
                        $plate = $bike?->plate ?? 'N/A';
                        $emirates = $bike?->emirates ?? '';
                        @endphp
                        <tr>
                            <td class="col-sr">{{ $key + 1 }}</td>
                            <td class="col-desc">Bike # {{ $plate }}@if($emirates) ({{ $emirates }})@endif</td>
                            <td>{{ $fmo }}</td>
                            <td>1</td>
                            <td>{{ $item->days ?? 1 }}</td>
                            <td>{{ number_format($item->rental_amount ?? 0, 2) }}</td>
                            <td>{{ number_format($exclAmount, 2) }}</td>
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
            'subtotalAmount' => $subtotalAmount ?: $exclTotal,
            'vatAmount' => $vatAmt ?: $taxTotal,
            'totalAmount' => $totalAmt ?: $runningTotal,
            'currency' => $currency,
            'paidAmount' => $paidAmount,
            'balanceAmount' => $balanceDue,
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