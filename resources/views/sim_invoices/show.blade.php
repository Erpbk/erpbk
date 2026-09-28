<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SIM Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</title>
    @include('invoices.partials.tax_invoice_styles')
</head>

<body>
    @php
        $settings = company_table('settings')->pluck('value', 'name')->toArray();
        $currency = \App\Helpers\Currency::code();
        $defaults = \App\Support\InvoiceModuleDefaults::all('sim_invoices');
        $party = $invoice->company;
        $invoiceTitle = $defaults['title'] ?: 'SIM INVOICE';
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
        $invoiceNumber = $invoice->invoice_number ?? ('SIMI-' . str_pad($invoice->id, 4, '0', STR_PAD_LEFT));
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
        $billingLabel = $invoice->billing_month
            ? date('M Y', strtotime($invoice->billing_month))
            : '';
        $pivotColumns = $pivotColumns ?? collect();
        $pivotRows = $pivotRows ?? [];
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        <button type="button" class="action-btn js-print-modal-content">
            <i class="ti ti-file-description"></i><span>PDF/Print</span>
        </button>
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
                    <h3 class="party-title">Vendor</h3>
                    <p class="party-name">{{ $party->name ?? 'N/A' }}</p>
                    <div class="party-grid">
                        <div class="party-line">
                            <span class="k">Contact</span>
                            <span class="v">{{ $party->contact_number ?? ($party->phone ?? '—') }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Email</span>
                            <span class="v">{{ $party->email ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Reference</span>
                            <span class="v">{{ $invoice->reference_number ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="party alt">
                    <h3 class="party-title">Billing</h3>
                    <div class="party-grid" style="margin-top: 4px;">
                        <div class="party-line">
                            <span class="k">Month</span>
                            <span class="v">{{ $billingLabel !== '' ? $billingLabel : '—' }}</span>
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

            @if(!empty($pivotRows) && count($pivotRows) > 0)
            <div class="tbl-wrap">
                <table class="items">
                    <thead>
                        <tr>
                            <th class="col-desc">SIM Number</th>
                            @foreach($pivotColumns as $col)
                            <th>
                                {{ $col->name }}
                                <div style="font-size:10px;font-weight:500;opacity:.85;margin-top:2px;text-transform:none;letter-spacing:0;">
                                    Rate: {{ number_format((float) ($col->price ?? 0), 2) }}
                                </div>
                            </th>
                            @endforeach
                            <th>VAT</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pivotRows as $row)
                        <tr>
                            <td class="col-desc">{{ $row['sim']->number ?? 'N/A' }}</td>
                            @foreach($pivotColumns as $col)
                            @php $charge = $row['charges'][(int) $col->id] ?? 0; @endphp
                            <td>{{ number_format($charge, 2) }}</td>
                            @endforeach
                            <td>{{ number_format($row['vat'], 2) }}</td>
                            <td class="total-cell">{{ number_format($row['total'], 2) }}</td>
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
