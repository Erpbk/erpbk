<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rider Invoice {{ $riderInvoice->invoice_number ?? $riderInvoice->id }}</title>
    @include('invoices.partials.tax_invoice_styles')
    <style>
        body {
            background: #fff !important;
            padding: 0 !important;
            font-size: 10px !important;
            line-height: 1.3 !important;
        }

        .invoice-box {
            box-shadow: none !important;
            max-width: 100% !important;
        }

        .invoice-box .sheet {
            padding: 10px 12px 8px !important;
        }

        .invoice-box .hdr {
            margin-bottom: 8px !important;
            padding-bottom: 8px !important;
            gap: 8px !important;
        }

        .invoice-box .brand-logo {
            max-width: 180px !important;
            min-height: 40px !important;
        }

        .invoice-box .brand-logo img {
            max-width: 180px !important;
            max-height: 48px !important;
        }

        .invoice-box .brand-text h1 {
            font-size: 13px !important;
            margin-bottom: 2px !important;
        }

        .invoice-box .parties {
            gap: 8px !important;
            margin-bottom: 8px !important;
        }

        .invoice-box .party {
            padding: 6px 8px !important;
        }

        .invoice-box .party-title {
            margin-bottom: 4px !important;
            font-size: 8.5px !important;
        }

        .invoice-box .party-name {
            font-size: 11px !important;
            margin-bottom: 3px !important;
        }

        .invoice-box .party-line {
            font-size: 9px !important;
        }

        .invoice-box .desc {
            margin-bottom: 6px !important;
            padding: 5px 8px !important;
        }

        .invoice-box .desc p {
            font-size: 9.5px !important;
            margin: 0 !important;
            line-height: 1.3 !important;
        }

        .invoice-box .rider-template-items {
            width: 100%;
        }

        .invoice-box .rider-template-items .tbl-wrap {
            width: 100%;
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 6px;
        }

        .invoice-box table.items-table,
        .invoice-box .rider-template-items table,
        .invoice-box .sheet>table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .invoice-box .rider-template-items .tbl-wrap>table {
            margin-bottom: 0;
        }

        .invoice-box table.items-table th,
        .invoice-box table.items-table td,
        .invoice-box .rider-template-items table th,
        .invoice-box .rider-template-items table td,
        .invoice-box .sheet>table th,
        .invoice-box .sheet>table td {
            border: 1px solid #e2e8f0;
            padding: 3px 5px;
            font-size: 9px;
            vertical-align: middle;
            line-height: 1.25;
        }

        .invoice-box table.items-table tr,
        .invoice-box .rider-template-items table tr {
            vertical-align: middle;
        }

        .invoice-box table.items-table th,
        .invoice-box .secondary-header,
        .invoice-box .accent-total {
            background: #004aad;
            color: #fff;
            font-weight: 700;
            vertical-align: middle;
            font-size: 8.5px;
            padding: 4px 5px;
        }

        .invoice-box td.num {
            text-align: right;
        }

        .invoice-box .totals-area {
            margin: 6px 0 4px !important;
            gap: 10px !important;
        }

        .invoice-box .totals-notes {
            padding: 5px 8px !important;
        }

        .invoice-box .totals-notes h4,
        .invoice-box .note-card h4 {
            font-size: 8px !important;
            margin-bottom: 2px !important;
        }

        .invoice-box .totals-notes .body,
        .invoice-box .note-card .body {
            font-size: 8.5px !important;
            line-height: 1.35 !important;
        }

        .invoice-box .totals {
            width: 230px !important;
        }

        .invoice-box .totals .line {
            padding: 3px 10px !important;
            font-size: 9px !important;
        }

        .invoice-box .totals .grand {
            padding: 6px 10px !important;
            margin-top: 3px !important;
        }

        .invoice-box .totals .grand .k {
            font-size: 8.5px !important;
        }

        .invoice-box .totals .grand .v {
            font-size: 12px !important;
        }

        .invoice-box .footnotes {
            margin-top: 6px !important;
            padding-top: 6px !important;
            gap: 6px !important;
        }

        .invoice-box .note-card {
            padding: 5px 8px !important;
        }

        .invoice-box .foot {
            margin-top: 6px !important;
            padding-top: 4px !important;
            font-size: 8.5px !important;
        }

        .invoice-box.invoice-layout-modern table.items-table th,
        .invoice-box.invoice-layout-modern .secondary-header,
        .invoice-box.invoice-layout-modern .accent-total {
            background: var(--blue-soft, #eef4fc);
            color: var(--ink, #0f172a);
        }

        .invoice-box.invoice-layout-modern th,
        .invoice-box.invoice-layout-modern td {
            border-color: var(--blue-line, #c5d8f0);
        }

        .invoice-box.invoice-layout-modern .band {
            background: var(--blue, #004aad);
        }

        .invoice-box.invoice-layout-modern .hdr {
            border-bottom-color: var(--blue, #004aad);
        }
    </style>
</head>

<body>
    @php
    $isPdf = true;
    $settings = $settings ?? company_table('settings')->pluck('value', 'name')->toArray();
    $currency = \App\Helpers\Currency::code();
    $defaults = \App\Support\InvoiceModuleDefaults::all('rider_invoices');
    $party = $riderInvoice->rider;
    $invoiceTitle = $defaults['title'] ?: 'RIDER INVOICE';
    $partyNote = $riderInvoice->customer_note
    ?: ($party->invoice_note ?? null)
    ?: ($defaults['notes'] ?: null);
    $termsAndConditions = $riderInvoice->terms_and_conditions
    ?: ($party->terms_and_conditions ?? null)
    ?: ($defaults['terms_and_conditions'] ?: null);
    $partyNoteLabel = $party
    ? $party->resolvedInvoiceNoteLabel()
    : 'Invoice Note';
    $termsAndConditionsLabel = $party
    ? $party->resolvedTermsAndConditionsLabel()
    : 'Terms & Conditions';
    $invoiceNumber = $invoiceNumber
    ?? \App\Helpers\General::inv_sch($riderInvoice->id, $riderInvoice->created_at);
    $subtotalAmount = $totalBeforeTax ?? $riderInvoice->subtotal ?? 0;
    $vatAmt = $vatAmount ?? $riderInvoice->vat ?? 0;
    $totalAmt = $finalAmount ?? $riderInvoice->total_amount ?? 0;
    // Note left of totals (like customer invoice); fall back to internal notes when party note empty
    if (!$partyNote && $riderInvoice->notes) {
    $partyNote = $riderInvoice->notes;
    $partyNoteLabel = 'Internal Notes';
    }
    // Terms & Conditions only — full width under balance
    $noteCards = collect([
    $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
    ])->filter()->values();
    $noteGridClass = 'one';
    $serviceFrom = $riderInvoice->service_period_from
    ? $riderInvoice->service_period_from->format('d M Y')
    : date('d M Y', strtotime($riderInvoice->billing_month));
    $serviceTo = $riderInvoice->service_period_to
    ? $riderInvoice->service_period_to->format('d M Y')
    : date('t M Y', strtotime($riderInvoice->billing_month));
    $branch = $party->branch ?? null;
    $branchLabel = $branch
    ? trim($branch->name . ($branch->code ? ' (' . $branch->code . ')' : ''))
    : '—';
    $bikePlate = $party->bikes?->plate ?? $riderInvoice->bike?->plate ?? '—';
    $invoiceDateLabel = optional($riderInvoice->inv_date)->format('d M Y')
    ?? optional($riderInvoice->created_at)->format('d M Y')
    ?? '';
    $billingLabel = date('M Y', strtotime($riderInvoice->billing_month));
    @endphp

    <div class="invoice-box invoice-layout-{{ $activeTemplate?->layout_key ?? 'modern' }}">
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
                    <p class="party-name">{{ $party->name ?? 'N/A' }} <span class="status-badge @if(in_array((int) ($party->status ?? 0), [3, 4, 5], true)) red @else status-green @endif">
                            {{ $riderStatusLabel ?? '—' }}
                        </span></p>
                    <div class="party-grid">
                        <div class="party-line">
                            <span class="k">Rider ID</span>
                            <span class="v">{{ $party->rider_id ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Mobile</span>
                            <span class="v">{{ $party?->sim?->number ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Project</span>
                            <span class="v">{{ $party?->customer?->name ?? '—' }}</span>
                        </div>
                    </div>
                </div>
                <div class="party alt">
                    <h3 class="party-title">Service Period</h3>
                    <div class="party-grid" style="margin-top: 4px;">
                        <div class="party-line">
                            <span class="k">From</span>
                            <span class="v">{{ $serviceFrom }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">To</span>
                            <span class="v">{{ $serviceTo }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Month</span>
                            <span class="v">{{ $billingLabel }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Bike</span>
                            <span class="v">{{ $bikePlate }}</span>
                        </div>
                    </div>
                </div>
            </div>

            @if($riderInvoice->descriptions)
            <div class="desc">
                <span class="t">Description</span>
                <p>{{ $riderInvoice->descriptions }}</p>
            </div>
            @endif

            @if(View::exists($templateView ?? ''))
            @include($templateView)
            @elseif($riderInvoice->items && $riderInvoice->items->count() > 0)
            @include('rider_invoices.partials.invoice_items_and_totals')
            @else
            <div class="empty">No line items on this invoice.</div>
            @endif

            @include('invoices.partials.tax_invoice_totals_notes', [
            'partyNote' => $partyNote,
            'partyNoteLabel' => $partyNoteLabel,
            'subtotalAmount' => $subtotalAmount,
            'vatAmount' => $vatAmt,
            'totalAmount' => $totalAmt,
            'currency' => $currency,
            'paidAmount' => $paid_amount ?? 0,
            'balanceAmount' => $rider_balance_final ?? 0,
            ])

            @include('rider_invoices.partials.payment_vouchers', [
            'paymentVouchers' => $paymentVouchers ?? collect(),
            'finalAmount' => $finalAmount ?? $totalAmt ?? 0,
            'rider_balance_final' => $rider_balance_final ?? 0,
            'isPdf' => true,
            ])

            @include('invoices.partials.tax_invoice_footnotes', [
            'noteCards' => $noteCards,
            'noteGridClass' => $noteGridClass,
            ])

            @include('invoices.partials.tax_invoice_footer', ['settings' => $settings])
        </div>
    </div>
</body>

</html>