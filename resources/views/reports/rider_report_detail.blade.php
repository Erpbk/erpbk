<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Rider Report: {{ $rider->rider_id }} · {{ $periodLabel }}</title>
</head>
<body>
@php
    $fmt = fn ($n) => number_format((float) $n, 2);
    $fmtMonth = function ($value) {
        if (empty($value)) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('M-Y');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $fmtDate = function ($value) {
        if (empty($value)) {
            return '—';
        }
        try {
            return \Carbon\Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return (string) $value;
        }
    };
    $currency = \App\Helpers\Currency::code();

    $voucherSections = [
        'VC' => ['title' => 'Vendor Charges', 'rows' => $vouchersByType['VC'], 'total' => $totals['vc']],
        'COD' => ['title' => 'COD', 'rows' => $vouchersByType['COD'], 'total' => $totals['cod']],
        'AL' => ['title' => 'Advance', 'rows' => $vouchersByType['AL'], 'total' => $totals['advance']],
        'PN' => ['title' => 'Penalty', 'rows' => $vouchersByType['PN'], 'total' => $totals['penalty']],
        'INC' => ['title' => 'Incentive', 'rows' => $vouchersByType['INC'], 'total' => $totals['incentive']],
    ];
@endphp
<style>
    @include('rider_invoices.partials.invoice_brand_styles')

    * { margin: 0; padding: 0; box-sizing: border-box; }

    html, body {
        height: 100%;
        margin: 0;
        padding: 0;
    }

    body {
        overflow: hidden;
        display: flex;
        flex-direction: column;
        font-family: Calibri, Arial, sans-serif;
        font-size: 12px;
        color: #334155;
        background: #f4f5f7;
    }

    .report-layout {
        display: flex;
        flex-direction: column;
        height: 100vh;
        height: 100dvh;
        min-height: 0;
        background: #f4f5f7;
    }

    .report-toolbar {
        flex-shrink: 0;
        background: #fff;
        border-bottom: 1px solid #e2e8f0;
    }

    .report-toolbar-inner {
        max-width: 900px;
        margin: 0 auto;
        padding: 10px 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
        justify-content: space-between;
    }

    .report-toolbar-left,
    .report-toolbar-right {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    .toolbar-btn {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        background: #fff;
        border: 1px solid #d1d5db;
        border-radius: 5px;
        color: #475569;
        font-size: 13px;
        text-decoration: none;
        cursor: pointer;
        line-height: 1.2;
    }

    .toolbar-btn:hover {
        background: #f8fafc;
        border-color: #94a3b8;
        color: #1e293b;
    }

    .toolbar-btn-primary {
        background: #1e293b;
        border-color: #1e293b;
        color: #fff;
    }

    .toolbar-btn-primary:hover {
        background: #0f172a;
        border-color: #0f172a;
        color: #fff;
    }

    .report-scroll {
        flex: 1;
        overflow-y: auto;
        -webkit-overflow-scrolling: touch;
        padding: 20px;
    }

    /* Soften shared invoice brand fills for this report */
    .invoice-box {
        border-color: #e2e8f0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
        padding: 22px 24px;
    }

    .invoice-box th {
        background: #f1f5f9 !important;
        color: #334155 !important;
        font-weight: 600;
        text-align: left;
        border-color: #e2e8f0 !important;
    }

    .invoice-box td {
        border-color: #e8edf2 !important;
        color: #334155;
        vertical-align: top;
    }

    .invoice-box .inv-doc-title {
        font-size: 18px;
        font-weight: 700;
        color: #0f172a;
        letter-spacing: 0.04em;
    }

    .invoice-box .inv-section-header {
        background: transparent;
        color: #64748b;
        padding: 0 0 6px;
        margin-bottom: 6px;
        border-bottom: 1px solid #e2e8f0;
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        font-weight: 700;
    }

    .invoice-box .inv-panel {
        background: #fafbfc;
        border-color: #e2e8f0 !important;
        padding: 12px 14px;
    }

    .invoice-box .inv-label {
        color: #64748b;
        font-weight: 600;
    }

    .invoice-box .section-title {
        margin: 22px 0 8px;
        padding: 0 0 6px;
        font-size: 12px;
        font-weight: 700;
        color: #0f172a;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        border-bottom: 2px solid #e2e8f0;
    }

    .invoice-box .section-title span.count {
        float: right;
        font-weight: 600;
        color: #94a3b8;
        text-transform: none;
        letter-spacing: 0;
    }

    .invoice-box table.data-table {
        margin-bottom: 0;
    }

    .invoice-box table.data-table th {
        font-size: 11px;
        padding: 7px 8px;
        white-space: nowrap;
    }

    .invoice-box table.data-table td {
        padding: 6px 8px;
        font-size: 12px;
    }

    .invoice-box table.data-table tbody tr:nth-child(even) {
        background: #fafbfc;
    }

    .invoice-box .row-total td {
        background: #f8fafc !important;
        color: #0f172a !important;
        font-weight: 700;
        border-top: 1px solid #cbd5e1 !important;
    }

    .invoice-box .empty-cell {
        text-align: center;
        color: #94a3b8;
        padding: 12px 8px !important;
    }

    .kpi-row {
        width: 100%;
        border-collapse: separate;
        border-spacing: 8px 0;
        margin: 14px 0 6px;
    }

    .kpi-row td {
        border: 1px solid #e2e8f0 !important;
        background: #fafbfc;
        padding: 10px 12px !important;
        width: 25%;
        vertical-align: top;
    }

    .kpi-label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #94a3b8;
        margin-bottom: 4px;
    }

    .kpi-value {
        display: block;
        font-size: 15px;
        font-weight: 700;
        color: #0f172a;
        text-align: right;
        font-variant-numeric: tabular-nums;
    }

    .kpi-row td.is-balance .kpi-value {
        color: #0f172a;
    }

    .summary-table td:first-child {
        width: 70%;
        color: #475569;
    }

    .summary-table .row-subtotal td {
        background: #f8fafc !important;
        font-weight: 600;
        color: #0f172a !important;
    }

    .summary-table .row-payable td {
        background: #f1f5f9 !important;
        font-weight: 700;
        color: #0f172a !important;
        border-top: 2px solid #cbd5e1 !important;
    }

    .summary-table .row-balance td {
        background: #0f172a !important;
        color: #fff !important;
        font-weight: 700;
        font-size: 13px;
    }

    .summary-table .row-muted td {
        color: #64748b;
        background: #fafbfc !important;
    }

    .header-divider {
        border: none;
        border-top: 1px solid #e2e8f0;
        margin: 12px 0 16px;
    }

    .footer-note {
        font-size: 11px;
        margin-top: 14px;
        color: #94a3b8;
        text-align: center;
        line-height: 1.5;
    }

    .sign-box {
        margin-top: 28px;
        text-align: right;
        font-weight: 600;
        color: #475569;
        font-size: 12px;
    }

    .sign-box span {
        display: block;
        margin-top: 28px;
        color: #cbd5e1;
        letter-spacing: 1px;
    }

    .status-text {
        color: #64748b;
        font-size: 11px;
    }

    .company-name {
        margin: 0 0 4px;
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    @media print {
        html, body {
            height: auto;
            overflow: visible;
            background: #fff;
            display: block;
        }

        .report-layout {
            height: auto;
            max-height: none;
            background: #fff;
        }

        .report-toolbar,
        .no-print {
            display: none !important;
        }

        .report-scroll {
            overflow: visible;
            padding: 0;
        }

        .invoice-box {
            width: 100%;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .kpi-row td,
        .summary-table .row-payable td,
        .summary-table .row-balance td,
        .invoice-box th,
        .invoice-box .row-total td {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .invoice-box .section-title {
            break-after: avoid;
        }

        .invoice-box table.data-table {
            break-inside: avoid;
        }
    }

    @media (max-width: 700px) {
        .report-scroll { padding: 10px; }
        .invoice-box { padding: 14px; }
        .kpi-row { border-spacing: 0; }
        .kpi-row td {
            display: block;
            width: 100%;
            margin-bottom: 6px;
        }
    }
</style>

<div class="report-layout">
    <div class="report-toolbar no-print">
        <div class="report-toolbar-inner">
            <div class="report-toolbar-left">
                <a href="{{ route('reports.rider_report', ['from_month' => $fromMonth, 'to_month' => $toMonth]) }}" class="toolbar-btn">
                    ← Back
                </a>
                <a href="{{ route('riders.show', $rider->id) }}" target="_blank" class="toolbar-btn">
                    Rider Profile
                </a>
            </div>
            <div class="report-toolbar-right">
                <button type="button" class="toolbar-btn toolbar-btn-primary" onclick="window.print()">
                    Print
                </button>
            </div>
        </div>
    </div>

    <div class="report-scroll">
        <div class="invoice-box">
            {{-- Header --}}
            <table class="no-border" width="100%">
                <tr>
                    <td width="28%" class="no-border" style="vertical-align: middle;">
                        @if(!empty($settings['company_logo']) && Storage::disk('public')->exists($settings['company_logo']))
                            <img src="{{ storage_url($settings['company_logo']) }}" width="120" alt="logo" />
                        @elseif(!empty($brand['logo_src']))
                            <img src="{{ $brand['logo_src'] }}" width="120" alt="logo" />
                        @else
                            <img src="{{ URL::asset('assets/img/logo-full.png') }}" width="120" alt="logo" />
                        @endif
                    </td>
                    <td width="44%" class="no-border" style="text-align: center; vertical-align: middle;">
                        <h4 class="company-name">{{ ucwords($settings['company_name'] ?? '') }}</h4>
                        <p class="inv-meta inv-meta-muted" style="margin: 2px 0;">{{ ucwords($settings['company_address'] ?? '') }}</p>
                        <p class="inv-meta inv-meta-muted" style="margin: 2px 0;">
                            @if(!empty($settings['company_phone'])) TEL: {{ $settings['company_phone'] }} @endif
                            @if(!empty($settings['vat_number'])) · TRN: {{ $settings['vat_number'] }} @endif
                        </p>
                    </td>
                    <td width="28%" class="no-border" style="text-align: right; vertical-align: top;">
                        <h2 class="inv-doc-title">RIDER REPORT</h2>
                        <p class="inv-meta inv-meta-muted" style="margin: 2px 0;">{{ $periodLabel }}</p>
                        <p class="inv-meta" style="margin: 6px 0 0;">
                            <span class="inv-label">Balance</span><br>
                            <strong style="font-size: 16px; color: #0f172a;">{{ \App\Helpers\Currency::format($totals['balance'], 2) }}</strong>
                        </p>
                    </td>
                </tr>
            </table>

            <hr class="header-divider">

            {{-- Rider / Period --}}
            <table>
                <tr>
                    <td width="55%" class="inv-panel" style="vertical-align: top;">
                        <span class="inv-section-header">Rider</span>
                        <p style="font-size: 14px; margin-bottom: 6px;"><strong style="color:#0f172a;">{{ $rider->name }}</strong></p>
                        <p><span class="inv-label">ID</span> {{ $rider->rider_id }}</p>
                        <p><span class="inv-label">Designation</span> {{ $rider->designation ?: '—' }}</p>
                        <p><span class="inv-label">Emirates</span> {{ $rider->emirate_hub ?: '—' }}</p>
                        <p><span class="inv-label">Project</span> {{ optional($rider->customer)->name ?: '—' }}</p>
                        <p><span class="inv-label">Mobile</span> {{ optional($rider->sim)->number ?: '—' }}</p>
                    </td>
                    <td width="45%" class="inv-panel" style="vertical-align: top;">
                        <span class="inv-section-header">Period</span>
                        <table class="no-border" style="width: 100%; margin: 0;">
                            <tr class="no-border">
                                <td class="no-border inv-label" style="text-align: right; padding-right: 12px; width: 45%;">From</td>
                                <td class="no-border">{{ \Carbon\Carbon::parse($fromMonth . '-01')->format('M Y') }}</td>
                            </tr>
                            <tr class="no-border">
                                <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">To</td>
                                <td class="no-border">{{ \Carbon\Carbon::parse($toMonth . '-01')->format('M Y') }}</td>
                            </tr>
                            <tr class="no-border">
                                <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">Generated</td>
                                <td class="no-border">{{ now()->format('Y-m-d') }}</td>
                            </tr>
                            <tr class="no-border">
                                <td class="no-border inv-label" style="text-align: right; padding-right: 12px;">Pending</td>
                                <td class="no-border">{{ $fmt($totals['pending_pct']) }}%</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            {{-- Key figures --}}
            <table class="kpi-row no-border">
                <tr class="no-border">
                    <td class="no-border">
                        <span class="kpi-label">Invoices</span>
                        <span class="kpi-value">{{ $fmt($totals['invoices']) }}</span>
                    </td>
                    <td class="no-border">
                        <span class="kpi-label">Deductions</span>
                        <span class="kpi-value">{{ $fmt($totals['deductions']) }}</span>
                    </td>
                    <td class="no-border">
                        <span class="kpi-label">Payable</span>
                        <span class="kpi-value">{{ $fmt($totals['payable']) }}</span>
                    </td>
                    <td class="no-border is-balance">
                        <span class="kpi-label">Balance</span>
                        <span class="kpi-value">{{ $fmt($totals['balance']) }}</span>
                    </td>
                </tr>
            </table>

            {{-- Invoices --}}
            @if($invoices->isNotEmpty())
            <div class="section-title">Invoices <span class="count">{{ $invoices->count() }}</span></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Month</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th style="text-align:right;">Total ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $i => $invoice)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>RINV-{{ str_pad($invoice->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $fmtDate($invoice->inv_date) }}</td>
                        <td>{{ $fmtMonth($invoice->billing_month) }}</td>
                        <td>{{ $invoice->descriptions ?: '—' }}</td>
                        <td class="status-text">
                            @if(($invoice->status ?? 0) == 1) Paid
                            @elseif(($invoice->status ?? 0) == 3) Partially Paid
                            @else Unpaid
                            @endif
                        </td>
                        <td class="num">{{ $fmt($invoice->total_amount) }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="6" style="text-align:right;">Total</td>
                        <td class="num">{{ $fmt($totals['invoices']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- Voucher sections (only if data) --}}
            @foreach($voucherSections as $type => $section)
                @if($section['rows']->isNotEmpty())
                <div class="section-title">{{ $section['title'] }} <span class="count">{{ $section['rows']->count() }}</span></div>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Date</th>
                            <th>Month</th>
                            <th>Remarks / Code</th>
                            <th style="text-align:right;">Amount ({{ $currency }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($section['rows'] as $i => $voucher)
                        <tr>
                            <td>{{ $i + 1 }}</td>
                            <td>{{ $fmtDate($voucher->trans_date) }}</td>
                            <td>{{ $fmtMonth($voucher->billing_month) }}</td>
                            <td>{{ trim(($voucher->remarks ?: '') . ' ' . ($voucher->trans_code ? '(' . $voucher->trans_code . ')' : '')) ?: '—' }}</td>
                            <td class="num">{{ $fmt($voucher->amount) }}</td>
                        </tr>
                        @endforeach
                        <tr class="row-total">
                            <td colspan="4" style="text-align:right;">Total</td>
                            <td class="num">{{ $fmt($section['total']) }}</td>
                        </tr>
                    </tbody>
                </table>
                @endif
            @endforeach

            {{-- RTA --}}
            @if($rtaFines->isNotEmpty())
            <div class="section-title">RTA Fines <span class="count">{{ $rtaFines->count() }}</span></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Ticket No</th>
                        <th>Month</th>
                        <th style="text-align:right;">Amount</th>
                        <th style="text-align:right;">Total ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($rtaFines as $i => $fine)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $fine->ticket_no ?: '—' }}</td>
                        <td>{{ $fmtMonth($fine->billing_month) }}</td>
                        <td class="num">{{ $fmt($fine->amount ?? 0) }}</td>
                        <td class="num">{{ $fmt($fine->total_amount) }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="4" style="text-align:right;">Total</td>
                        <td class="num">{{ $fmt($totals['rta']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- Salik --}}
            @if($saliks->isNotEmpty())
            <div class="section-title">Salik FEE <span class="count">{{ $saliks->count() }}</span></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Trip Date</th>
                        <th>Month</th>
                        <th>Toll Gate</th>
                        <th>Plate</th>
                        <th style="text-align:right;">Total ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($saliks as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $fmtDate($row->trip_date) }}</td>
                        <td>{{ $fmtMonth($row->billing_month) }}</td>
                        <td>{{ $row->toll_gate ?: '—' }}</td>
                        <td>{{ $row->plate ?: '—' }}</td>
                        <td class="num">{{ $fmt($row->total_amount) }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="5" style="text-align:right;">Total</td>
                        <td class="num">{{ $fmt($totals['salik']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- Fuel invoices --}}
            @if($fuelInvoices->isNotEmpty())
            <div class="section-title">Fuel Invoices <span class="count">{{ $fuelInvoices->count() }}</span></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Invoice</th>
                        <th>Date</th>
                        <th>Month</th>
                        <th style="text-align:right;">Lines</th>
                        <th style="text-align:right;">Qty</th>
                        <th style="text-align:right;">Total ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($fuelInvoices as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $row->inv_id ?: '—' }}</td>
                        <td>{{ $fmtDate($row->trans_date) }}</td>
                        <td>{{ $fmtMonth($row->billing_month) }}</td>
                        <td class="num">{{ (int) $row->line_count }}</td>
                        <td class="num">{{ $fmt($row->total_qty) }}</td>
                        <td class="num">{{ $fmt($row->total_amount) }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="6" style="text-align:right;">Total</td>
                        <td class="num">{{ $fmt($totals['fuel']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- Visa installments --}}
            @if($visaInstallments->isNotEmpty())
            <div class="section-title">Visa Installments <span class="count">{{ $visaInstallments->count() }}</span></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Date</th>
                        <th>Month</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th style="text-align:right;">Amount ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($visaInstallments as $i => $row)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $fmtDate($row->date) }}</td>
                        <td>{{ $fmtMonth($row->billing_month) }}</td>
                        <td>{{ $row->reference_number ?: '—' }}</td>
                        <td class="status-text">{{ ucfirst((string) ($row->status ?: '—')) }}</td>
                        <td class="num">{{ $fmt($row->amount) }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="5" style="text-align:right;">Total</td>
                        <td class="num">{{ $fmt($totals['visa']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- JV against rider account --}}
            @if($jvEntries->isNotEmpty())
            <div class="section-title">Journal Vouchers (JV) <span class="count">{{ $jvEntries->count() }}</span></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Date</th>
                        <th>Month</th>
                        <th>Narration / Code</th>
                        <th style="text-align:right;">Debit</th>
                        <th style="text-align:right;">Credit</th>
                        <th style="text-align:right;">Net ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($jvEntries as $i => $row)
                    @php $net = (float) $row->debit - (float) $row->credit; @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $fmtDate($row->trans_date) }}</td>
                        <td>{{ $fmtMonth($row->billing_month) }}</td>
                        <td>{{ trim(($row->narration ?: '') . ' ' . ($row->trans_code ? '(' . $row->trans_code . ')' : '')) ?: '—' }}</td>
                        <td class="num">{{ $fmt($row->debit) }}</td>
                        <td class="num">{{ $fmt($row->credit) }}</td>
                        <td class="num">{{ $fmt($net) }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="6" style="text-align:right;">Total</td>
                        <td class="num">{{ $fmt($totals['jv']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- Payments --}}
            @if($payments->isNotEmpty())
            <div class="section-title">Payments <span class="count">{{ $payments->count() }}</span></div>
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width:40px;">#</th>
                        <th>Payment Date</th>
                        <th>Month</th>
                        <th>Reference / Description</th>
                        <th style="text-align:right;">Amount ({{ $currency }})</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $i => $payment)
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>{{ $fmtDate($payment->date_of_payment) }}</td>
                        <td>{{ $fmtMonth($payment->billing_month) }}</td>
                        <td>{{ trim(($payment->reference ?: '') . ' ' . ($payment->description ?: '')) ?: '—' }}</td>
                        <td class="num">{{ $fmt($payment->amount) }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-total">
                        <td colspan="4" style="text-align:right;">Total</td>
                        <td class="num">{{ $fmt($totals['paid']) }}</td>
                    </tr>
                </tbody>
            </table>
            @endif

            {{-- Summary --}}
            <div class="section-title">Summary</div>
            <table class="data-table summary-table">
                <tbody>
                    <tr>
                        <td>Total Amount (Invoices)</td>
                        <td class="num">{{ $fmt($totals['invoices']) }}</td>
                    </tr>
                    @if($totals['vc'] != 0)
                    <tr>
                        <td>Less: Vendor Charges</td>
                        <td class="num">-{{ $fmt($totals['vc']) }}</td>
                    </tr>
                    @endif
                    @if($totals['cod'] != 0)
                    <tr>
                        <td>Less: COD</td>
                        <td class="num">-{{ $fmt($totals['cod']) }}</td>
                    </tr>
                    @endif
                    @if($totals['rta'] != 0)
                    <tr>
                        <td>Less: RTA Fine</td>
                        <td class="num">-{{ $fmt($totals['rta']) }}</td>
                    </tr>
                    @endif
                    @if($totals['salik'] != 0)
                    <tr>
                        <td>Less: Salik FEE</td>
                        <td class="num">-{{ $fmt($totals['salik']) }}</td>
                    </tr>
                    @endif
                    @if($totals['fuel'] != 0)
                    <tr>
                        <td>Less: Fuel</td>
                        <td class="num">-{{ $fmt($totals['fuel']) }}</td>
                    </tr>
                    @endif
                    @if(($totals['visa'] ?? 0) != 0)
                    <tr>
                        <td>Less: Visa Installment</td>
                        <td class="num">-{{ $fmt($totals['visa']) }}</td>
                    </tr>
                    @endif
                    @if(($totals['jv_deduction'] ?? 0) != 0)
                    <tr>
                        <td>Less: Journal Voucher (JV)</td>
                        <td class="num">-{{ $fmt($totals['jv_deduction']) }}</td>
                    </tr>
                    @endif
                    @if($totals['advance'] != 0)
                    <tr>
                        <td>Less: Advance</td>
                        <td class="num">-{{ $fmt($totals['advance']) }}</td>
                    </tr>
                    @endif
                    @if($totals['penalty'] != 0)
                    <tr>
                        <td>Less: Penalty</td>
                        <td class="num">-{{ $fmt($totals['penalty']) }}</td>
                    </tr>
                    @endif
                    <tr class="row-subtotal">
                        <td>Total Deductions</td>
                        <td class="num">-{{ $fmt($totals['deductions']) }}</td>
                    </tr>
                    @if($totals['incentive'] != 0)
                    <tr>
                        <td>Add: Incentive</td>
                        <td class="num">+{{ $fmt($totals['incentive']) }}</td>
                    </tr>
                    @endif
                    @if($totals['previous_balance'] != 0)
                    <tr>
                        <td>Add: Previous Balance</td>
                        <td class="num">+{{ $fmt($totals['previous_balance']) }}</td>
                    </tr>
                    @endif
                    @if(($totals['jv_addition'] ?? 0) != 0)
                    <tr>
                        <td>Add: Journal Voucher (JV)</td>
                        <td class="num">+{{ $fmt($totals['jv_addition']) }}</td>
                    </tr>
                    @endif
                    <tr class="row-subtotal">
                        <td>Total Additions</td>
                        <td class="num">+{{ $fmt($totals['additions']) }}</td>
                    </tr>
                    <tr class="row-payable">
                        <td>Payable</td>
                        <td class="num">{{ $fmt($totals['payable']) }}</td>
                    </tr>
                    <tr>
                        <td>Less: Paid Amount</td>
                        <td class="num">-{{ $fmt($totals['paid']) }}</td>
                    </tr>
                    <tr class="row-balance">
                        <td>Rider Balance</td>
                        <td class="num">{{ $fmt($totals['balance']) }}</td>
                    </tr>
                    <tr class="row-muted">
                        <td>Pending %</td>
                        <td class="num">{{ $fmt($totals['pending_pct']) }}%</td>
                    </tr>
                </tbody>
            </table>

            <div class="sign-box">
                <div>Authorized Signature</div>
                <span>________________________</span>
            </div>
        </div>
    </div>
</div>
</body>
</html>
