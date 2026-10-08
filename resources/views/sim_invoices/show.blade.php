<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>SIM Invoice #{{ $invoice->invoice_number ?? $invoice->id }}</title>

</head>



<body>

    @include('invoices.partials.tax_invoice_styles')

    @include('invoices.partials.tax_invoice_pdf_styles')
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
        @php
            $companySlug = request()->route('company_slug');
            $simStatus = (int) ($invoice->status ?? 0);
            $simIsPaid = $simStatus === 1;
            $simIsPartial = $simStatus === 3 || (! $simIsPaid && (float) ($invoice->paid_amount ?? 0) > 0);
            $simPaymentId = \App\Support\InvoicePaymentLink::latestId(
                $invoice,
                $invoice->company?->account_id ? (int) $invoice->company->account_id : null
            );
        @endphp
        @include('invoices.partials.action_toolbar', [
            'isPaid' => $simIsPaid,
            'isPartial' => $simIsPartial && ! $simIsPaid,
            'editUrl' => route('simInvoices.edit', $invoice->id),
            'editTitle' => 'Edit Invoice',
            'editCan' => 'sims_invoices_edit',
            'editPaymentUrl' => $simPaymentId ? route('payments.edit', $simPaymentId) : null,
            'editPaymentTitle' => 'Edit Payment',
            'editPaymentCan' => ['sims_payments_edit', 'cash_&_banks_payments_edit'],
            'downloadUrl' => route('simInvoices.download', $invoice->id),
            'showPayment' => ! $simIsPaid,
            'paymentUrl' => route('payments.create', array_filter(['company_slug' => $companySlug]))
                . '?invoice_type=sim&invoice_id=' . $invoice->id,
            'paymentTitle' => 'Record Payment',
            'paymentCan' => 'sims_payments_create',
            'cloneUrl' => route('simInvoices.createFromClone', $invoice->id),
            'cloneTitle' => 'Clone Invoice',
            'cloneCan' => 'sims_invoices_create',
            'deleteFormRoute' => ['simInvoices.destroy', $invoice->id],
            'deleteCan' => 'sims_invoices_delete',
        ])
    </div>
    @endif

    <div class="invoice-box"@if(!empty($invPdf['box'])) style="{{ $invPdf['box'] }}"@endif>
        <div class="band"></div>
        <div class="sheet"@if(!empty($invPdf['sheet'])) style="{{ $invPdf['sheet'] }}"@endif>
            @include('invoices.partials.tax_invoice_header', [
            'settings' => $settings,
            'invoiceTitle' => $invoiceTitle,
            'invoiceNumber' => $invoiceNumber,
            'invoiceDateLabel' => $invoiceDateLabel,
            'billingLabel' => $billingLabel,
            ])

            <div class="parties"@if(!empty($invPdf['parties'])) style="{{ $invPdf['parties'] }}"@endif>
                    <div class="party"@if(!empty($invPdf['partyFirst'])) style="{{ $invPdf['partyFirst'] }}"@endif>
                        <h3 class="party-title"@if(!empty($invPdf['partyTitle'])) style="{{ $invPdf['partyTitle'] }}"@endif>Vendor</h3>
                        <p class="party-name"@if(!empty($invPdf['partyName'])) style="{{ $invPdf['partyName'] }}"@endif>{{ $party->name ?? 'N/A' }}</p>
                        <div class="party-grid">
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Contact</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $party->contact_number ?? ($party->phone ?? '—') }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Email</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $party->email ?? '—' }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Reference</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $invoice->reference_number ?? '—' }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="party alt"@if(!empty($invPdf['partyAlt'])) style="{{ $invPdf['partyAlt'] }}"@endif>
                        <h3 class="party-title"@if(!empty($invPdf['partyTitle'])) style="{{ $invPdf['partyTitle'] }}"@endif>Billing Period</h3>
                        <div class="party-grid" style="margin-top: 4px;">
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Month</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $billingLabel !== '' ? $billingLabel : '—' }}</span>
                            </div>
                            <div class="party-line"@if(!empty($invPdf['partyLine'])) style="{{ $invPdf['partyLine'] }}"@endif>
                                <span class="k"@if(!empty($invPdf['partyK'])) style="{{ $invPdf['partyK'] }}"@endif>Currency</span>
                                <span class="v"@if(!empty($invPdf['partyV'])) style="{{ $invPdf['partyV'] }}"@endif>{{ $currency }}</span>
                            </div>
                        </div>
                    </div>
            </div>

            @if(!empty($invoice->descriptions))
            <div class="desc"@if(!empty($invPdf['desc'])) style="{{ $invPdf['desc'] }}"@endif>
                <span class="t"@if(!empty($invPdf['descTitle'])) style="{{ $invPdf['descTitle'] }}"@endif>Description</span>
                <p @if(!empty($invPdf['descBody'])) style="{{ $invPdf['descBody'] }}"@endif>{{ $invoice->descriptions }}</p>
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