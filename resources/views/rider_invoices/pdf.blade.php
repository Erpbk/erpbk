<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Rider Invoice {{ $riderInvoice->invoice_number ?? $riderInvoice->id }}</title>
    @include('invoices.partials.tax_invoice_styles')
    <style>
        body { background: #fff !important; padding: 0 !important; }
        .invoice-box { box-shadow: none !important; max-width: 100% !important; }
        .invoice-box .rider-template-items {
            width: 100%;
        }
        .invoice-box .rider-template-items .tbl-wrap {
            width: 100%;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 12px;
        }
        .invoice-box table.items-table,
        .invoice-box .rider-template-items table,
        .invoice-box .sheet > table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .invoice-box .rider-template-items .tbl-wrap > table {
            margin-bottom: 0;
        }
        .invoice-box table.items-table th,
        .invoice-box table.items-table td,
        .invoice-box .rider-template-items table th,
        .invoice-box .rider-template-items table td,
        .invoice-box .sheet > table th,
        .invoice-box .sheet > table td {
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            font-size: 11px;
            vertical-align: middle;
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
        }
        .invoice-box td.num { text-align: right; }
        .invoice-box.invoice-layout-modern table.items-table th,
        .invoice-box.invoice-layout-modern .secondary-header,
        .invoice-box.invoice-layout-modern .accent-total {
            background: #c6d9f1;
            color: #000;
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
                    <p class="party-name">{{ $party->name ?? 'N/A' }}</p>
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
                            <span class="k">Client</span>
                            <span class="v">{{ $party?->vendor?->name ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Branch</span>
                            <span class="v">{{ $branchLabel }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Bike</span>
                            <span class="v">{{ $bikePlate }}</span>
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
                            <span class="k">Currency</span>
                            <span class="v">{{ $currency }}</span>
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
                'showPaidBalance' => true,
                'paidAmount' => $paid_amount ?? 0,
                'balanceAmount' => $rider_balance_final ?? 0,
            ])

            @include('rider_invoices.partials.payment_vouchers', [
                'payment_vouchers' => $payment_vouchers ?? collect(),
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
