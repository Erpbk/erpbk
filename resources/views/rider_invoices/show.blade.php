<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>RiderID: {{ $riderInvoice->rider?->rider_id ?? $riderInvoice->id }} Month: {{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</title>
    @include('invoices.partials.tax_invoice_styles')
    <style>
        /* Rider template item tables (legacy class names inside items area) */
        .invoice-box table.items-table,
        .invoice-box table.invoice-description-summary,
        .invoice-box table.summary-table,
        .invoice-box .tbl-wrap + table,
        .invoice-box .sheet > table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }
        .invoice-box table.items-table th,
        .invoice-box table.items-table td,
        .invoice-box table.summary-table td,
        .invoice-box .sheet > table th,
        .invoice-box .sheet > table td {
            border: 1px solid var(--line, #e2e8f0);
            padding: 8px 10px;
            font-size: 12px;
            vertical-align: top;
        }
        .invoice-box table.items-table th,
        .invoice-box .secondary-header,
        .invoice-box .accent-total,
        .invoice-box .light-header,
        .invoice-box .success-highlight,
        .invoice-box .amount-highlight,
        .invoice-box .primary-header {
            background: var(--blue, #004aad);
            color: #fff;
            font-weight: 700;
            text-align: center;
        }
        .invoice-box .light-header {
            background: var(--blue-soft, #eef4fc);
            color: var(--blue, #004aad);
        }
        .invoice-box td.num { text-align: right; font-variant-numeric: tabular-nums; }
        .invoice-box .label-cell { font-weight: 600; background: #f8fafc; width: 20%; }
        .invoice-box .value-cell { width: 30%; }
        .invoice-box .red { color: #c00; font-weight: 600; }
        .invoice-box .footer-note,
        .invoice-box .inv-footer-note { display: none; }
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
        .invoice-box .balance-lines .k { color: #64748b; font-weight: 500; }
        .invoice-box .balance-lines .v { font-weight: 700; color: #0f172a; }
        .invoice-box.invoice-layout-modern table.items-table th,
        .invoice-box.invoice-layout-modern .secondary-header,
        .invoice-box.invoice-layout-modern .accent-total,
        .invoice-box.invoice-layout-modern .light-header,
        .invoice-box.invoice-layout-modern .success-highlight,
        .invoice-box.invoice-layout-modern .amount-highlight,
        .invoice-box.invoice-layout-modern .primary-header {
            background: #c6d9f1;
            color: #000;
        }
    </style>
</head>

<body>
    @php
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
        $noteCards = collect([
            $termsAndConditions ? ['title' => $termsAndConditionsLabel, 'body' => $termsAndConditions] : null,
            $riderInvoice->notes ? ['title' => 'Internal Notes', 'body' => $riderInvoice->notes] : null,
        ])->filter()->values();
        $noteGridClass = match ($noteCards->count()) {
            1 => 'one',
            3 => 'three',
            default => '',
        };
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
        $hasTemplateItems = View::exists($templateView ?? '')
            || ($riderInvoice->items && $riderInvoice->items->count() > 0);
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @include('rider_invoices.partials.action_buttons')
    </div>
    @endif

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
                            <span class="k">Status</span>
                            <span class="v @if(in_array((int) ($party->status ?? 0), [3, 4, 5], true)) red @endif">{{ $riderStatusLabel ?? '—' }}</span>
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
                            <span class="k">Working</span>
                            <span class="v">{{ $riderInvoice->working_days ?? '—' }} | Off: {{ $riderInvoice->off ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Zone</span>
                            <span class="v">{{ $riderInvoice->zone ?? '—' }}</span>
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

            @if($hasTemplateItems)
            <div class="rider-template-items">
                @if(View::exists($templateView ?? ''))
                    @include($templateView)
                @elseif($riderInvoice->items && $riderInvoice->items->count() > 0)
                    @include('rider_invoices.partials.invoice_items_and_totals')
                @endif
            </div>

            @include('invoices.partials.tax_invoice_totals_notes', [
                'partyNote' => $partyNote,
                'partyNoteLabel' => $partyNoteLabel,
                'subtotalAmount' => $subtotalAmount,
                'vatAmount' => $vatAmt,
                'totalAmount' => $totalAmt,
                'currency' => $currency,
            ])

            <div class="balance-lines">
                <div class="line">
                    <span class="k">Paid Amount</span>
                    <span class="v">{{ number_format($paid_amount ?? 0, 2) }}</span>
                </div>
                <div class="line">
                    <span class="k">Balance</span>
                    <span class="v">{{ number_format($rider_balance_final ?? 0, 2) }}</span>
                </div>
            </div>
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

    @if(empty($isPdf))
    <script>
        (function() {
            document.querySelectorAll('.num').forEach(function(el) {
                var raw = el.innerText.trim();
                var num = parseFloat(raw.replace(/,/g, ''));
                if (!isNaN(num) && raw !== '') {
                    var formatted = num.toLocaleString('en-US', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                    if (el.innerText !== formatted) el.innerText = formatted;
                }
            });

            var templateForm = document.getElementById('riderInvoiceTemplateForm');
            var templateSelect = document.getElementById('template_id');
            if (templateForm && templateSelect) {
                templateSelect.addEventListener('change', function(e) {
                    e.preventDefault();
                    fetch(templateForm.action, {
                            method: 'POST',
                            body: new FormData(templateForm),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json'
                            }
                        }).then(function(res) {
                            return res.json();
                        })
                        .then(function(data) {
                            if (data.reload) window.location.reload();
                        }).catch(function() {
                            templateForm.submit();
                        });
                });
            }
        })();
    </script>
    @endif
</body>

</html>
