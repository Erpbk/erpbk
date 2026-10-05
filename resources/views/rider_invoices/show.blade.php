<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>RiderID: {{ $riderInvoice->rider?->rider_id ?? $riderInvoice->id }} Month: {{ date('M-Y', strtotime($riderInvoice->billing_month)) }}</title>
    @include('invoices.partials.tax_invoice_styles')
    <style>
        /* Rider template item tables (legacy class names inside items area) */
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
        .invoice-box table.invoice-description-summary,
        .invoice-box table.summary-table,
        .invoice-box .rider-template-items table,
        .invoice-box .tbl-wrap+table,
        .invoice-box .sheet>table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .invoice-box .rider-template-items .tbl-wrap>table {
            margin-bottom: 0;
        }

        .invoice-box table.items-table th,
        .invoice-box table.items-table td,
        .invoice-box table.summary-table td,
        .invoice-box .rider-template-items table th,
        .invoice-box .rider-template-items table td,
        .invoice-box .sheet>table th,
        .invoice-box .sheet>table td {
            border: 1px solid var(--line, #e2e8f0);
            padding: 8px 10px;
            font-size: 12px;
            vertical-align: middle;
        }

        .invoice-box table.items-table tr,
        .invoice-box .rider-template-items table tr {
            vertical-align: middle;
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
            vertical-align: middle;
        }

        .invoice-box .light-header {
            background: var(--blue-soft, #eef4fc);
            color: var(--blue, #004aad);
        }

        .invoice-box td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        .invoice-box .label-cell {
            font-weight: 600;
            background: #f8fafc;
            width: 20%;
        }

        .invoice-box .value-cell {
            width: 30%;
        }

        .invoice-box .red {
            color: #c00;
            font-weight: 600;
        }

        .invoice-box .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.3px;
            line-height: 1.4;
            border: 1px solid transparent;
            animation: status-blink 1.2s ease-in-out infinite;
        }

        .invoice-box .status-badge.status-green {
            color: #15803d;
            background: #dcfce7;
            border-color: #86efac;
        }

        .invoice-box .status-badge.red {
            color: #b91c1c;
            background: #fee2e2;
            border-color: #fca5a5;
        }

        @keyframes status-blink {

            0%,
            100% {
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.45);
            }

            50% {
                opacity: 0.55;
                box-shadow: 0 0 0 4px rgba(34, 197, 94, 0);
            }
        }

        .invoice-box .status-badge.red {
            animation-name: status-blink-red;
        }

        @keyframes status-blink-red {

            0%,
            100% {
                opacity: 1;
                box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.45);
            }

            50% {
                opacity: 0.55;
                box-shadow: 0 0 0 4px rgba(239, 68, 68, 0);
            }
        }

        @media print {
            .invoice-box .status-badge {
                animation: none !important;
                opacity: 1 !important;
                box-shadow: none !important;
            }

            .invoice-box .sheet {
                padding: 8px 10px !important;
            }

            .invoice-box .rider-template-items .tbl-wrap {
                margin-bottom: 6px !important;
            }

            .invoice-box table.items-table,
            .invoice-box .rider-template-items table,
            .invoice-box .sheet>table {
                margin-bottom: 6px !important;
            }

            .invoice-box table.items-table th,
            .invoice-box table.items-table td,
            .invoice-box .rider-template-items table th,
            .invoice-box .rider-template-items table td,
            .invoice-box .sheet>table th,
            .invoice-box .sheet>table td {
                padding: 3px 5px !important;
                font-size: 9px !important;
                line-height: 1.25 !important;
            }

            .invoice-box table.items-table th,
            .invoice-box .secondary-header,
            .invoice-box .accent-total {
                padding: 4px 5px !important;
                font-size: 8.5px !important;
            }

            .invoice-box .totals-area,
            .invoice-box .footnotes,
            .invoice-box .foot {
                page-break-before: avoid !important;
                break-before: avoid-page !important;
            }
        }

        .invoice-box .footer-note,
        .invoice-box .inv-footer-note {
            display: none;
        }

        .invoice-box.invoice-layout-modern table.items-table th,
        .invoice-box.invoice-layout-modern .secondary-header,
        .invoice-box.invoice-layout-modern .accent-total,
        .invoice-box.invoice-layout-modern .light-header,
        .invoice-box.invoice-layout-modern .success-highlight,
        .invoice-box.invoice-layout-modern .amount-highlight,
        .invoice-box.invoice-layout-modern .primary-header {
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
    $hasTemplateItems = View::exists($templateView ?? '')
    || ($riderInvoice->items && $riderInvoice->items->count() > 0);
    @endphp

    @if(empty($isPdf))
    <div class="controls no-print">
        @include('rider_invoices.partials.action_buttons')
    </div>
    @include('delete_requests._confirm_delete_script', [
    'entityName' => 'Rider Invoice',
    'confirmText' => 'This will submit a delete request or move the invoice to the Recycle Bin.',
    'method' => 'GET',
    ])
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
                            <span class="k">Working</span>
                            <span class="v">{{ $riderInvoice->working_days ?? '—' }} | Off: {{ $riderInvoice->off ?? '—' }}</span>
                        </div>
                        <div class="party-line">
                            <span class="k">Zone</span>
                            <span class="v">{{ $riderInvoice->zone ?? '—' }}</span>
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
            'paidAmount' => $paid_amount ?? 0,
            'balanceAmount' => $rider_balance_final ?? 0,
            ])

            @include('rider_invoices.partials.payment_vouchers', [
            'paymentVouchers' => $paymentVouchers ?? collect(),
            'finalAmount' => $finalAmount ?? $totalAmt ?? 0,
            'rider_balance_final' => $rider_balance_final ?? 0,
            'isPdf' => $isPdf ?? null,
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