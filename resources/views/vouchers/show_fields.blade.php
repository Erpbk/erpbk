<!doctype html>
<html lang="en">
<head>
@php
    $voucher_type = \App\Helpers\General::VoucherType($voucher->voucher_type ?? '');
    $voucherNo = ($voucher->voucher_type ?? '') . '-' . str_pad((string) ($voucher->id ?? 0), 4, '0', STR_PAD_LEFT);
    $settings = company_table('settings')->pluck('value', 'name')->toArray();
    $brand = $brand ?? ($companyBrand ?? []);
    $blue = $brand['primary_color'] ?? '#1e4b8f';
    $blueSoft = '#eef4fc';
    $blueLine = $brand['border_color'] ?? '#c5d8f0';
    $companyName = $settings['company_name'] ?? ($brand['name'] ?? config('app.name'));
    $companyAddress = trim((string) ($settings['company_address'] ?? ($brand['address'] ?? '')));
    $companyTrn = trim((string) ($settings['vat_number'] ?? ''));
    $companyPhone = trim((string) ($settings['company_phone'] ?? ($brand['phone'] ?? '')));
    $logoSrc = $companyLogoUrl ?? ($brand['logo_src'] ?? ($brand['logo_url'] ?? URL::asset('assets/img/logo-full.png')));
    $vf = static fn (string $f): bool => field_visible('voucher', $f);
    $createdByName = 'N/A';
    if (!empty($voucher->Created_By)) {
        $createdByName = \App\Models\User::query()->where('id', $voucher->Created_By)->value('name') ?? 'N/A';
    }
    $voucherDate = $vf('trans_date') && !empty($voucher->trans_date)
        ? $voucher->trans_date
        : '—';
    $billingMonth = ($vf('billing_month') && !empty($voucher->billing_month))
        ? date('M-Y', strtotime($voucher->billing_month))
        : '—';
    $creationDate = !empty($voucher->created_at)
        ? \Illuminate\Support\Carbon::parse($voucher->created_at)->format('d-M-Y')
        : '—';
    $amount = (float) ($voucher->amount ?? 0);
    if ($amount < 0.00001 && $voucher->relationLoaded('transactions')) {
        $amount = (float) $voucher->transactions->sum('debit');
    }
    $amountInWords = '—';
    if ($amount > 0.00001) {
        $words = \App\Helpers\Account::convertNumberToWord(number_format($amount, 2, '.', ''));
        $amountInWords = is_string($words) && $words !== ''
            ? ucwords(strtolower($words)) . ' Only'
            : '—';
    }
    $remarks = trim((string) ($voucher->remarks ?? ''));
    $isPrint = request()->boolean('print') || (bool) request()->get('print');
@endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $voucher_type }} # {{ $voucherNo }}</title>
    <style>
        :root {
            --cv-blue: {{ $blue }};
            --cv-soft: {{ $blueSoft }};
            --cv-line: {{ $blueLine }};
        }
        * { box-sizing: border-box; }
        html, body {
            margin: 0;
            padding: 0;
            background: #fff;
            color: #0f172a;
            font-family: 'Segoe UI', DejaVu Sans, Arial, Helvetica, sans-serif;
            line-height: 1.45;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            color-adjust: exact !important;
        }
        body { padding: 16px; }
        .cv-doc {
            max-width: 920px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid #e6edf5;
            border-radius: 12px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 16px 40px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .cv-accent { height: 5px; background: var(--cv-blue); }
        .cv-sheet { padding: 26px 30px 24px; }
        .cv-hdr {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 18px;
            table-layout: fixed;
        }
        .cv-hdr td {
            border: none;
            vertical-align: middle;
            padding: 0 0 16px;
            border-bottom: 2px solid var(--cv-blue);
        }
        .cv-hdr-logo { width: 28%; text-align: left; }
        .cv-hdr-brand { width: 44%; text-align: center; padding-left: 8px; padding-right: 8px; }
        .cv-hdr-right { width: 28%; text-align: right; vertical-align: top; }
        .cv-logo {
            display: block;
            max-width: 170px;
            max-height: 58px;
            width: auto;
            height: auto;
            object-fit: contain;
            object-position: left center;
        }
        .cv-company {
            margin: 0;
            font-size: 17px;
            font-weight: 700;
            color: var(--cv-blue);
            line-height: 1.25;
        }
        .cv-meta {
            display: block;
            margin-top: 4px;
            font-size: 11px;
            color: #64748b;
            line-height: 1.4;
        }
        .cv-badge {
            display: inline-block;
            background: var(--cv-soft);
            color: #0f172a;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 7px 14px;
            margin: 0 0 10px;
            border-radius: 6px;
        }
        .cv-stamp-kv {
            border-collapse: collapse;
            margin-left: auto;
            font-size: 12px;
        }
        .cv-stamp-kv td {
            padding: 3px 0 3px 14px;
            border: none;
            vertical-align: middle;
        }
        .cv-stamp-kv .k { color: #64748b; font-weight: 500; text-align: left; white-space: nowrap; }
        .cv-stamp-kv .v { color: #0f172a; font-weight: 700; text-align: right; white-space: nowrap; }
        .cv-split {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin: 0 0 14px;
            table-layout: fixed;
        }
        .cv-split-cell {
            width: 50%;
            vertical-align: top;
            border: none;
            padding: 0;
        }
        .cv-split-cell:first-child { padding-right: 8px; }
        .cv-split-cell:last-child { padding-left: 8px; }
        .cv-card {
            background: #fff;
            border: 1px solid var(--cv-line);
            border-top: 3px solid var(--cv-blue);
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
        }
        .cv-kv {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }
        .cv-kv td {
            border: none;
            border-bottom: 1px solid rgba(197, 216, 240, 0.85);
            padding: 8px 14px;
            vertical-align: middle;
            font-size: 13px;
            background: #fff;
        }
        .cv-kv tr:last-child td { border-bottom: none; }
        .cv-kv tr.cv-kv-head td {
            background: var(--cv-soft);
            border-bottom: 1px solid var(--cv-line);
            padding-top: 8px;
            padding-bottom: 8px;
        }
        .cv-kv-head-title {
            color: var(--cv-blue);
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .cv-kv .k {
            color: #64748b;
            font-weight: 500;
            width: 42%;
            text-align: left;
            white-space: nowrap;
        }
        .cv-kv .v {
            color: #0f172a;
            font-weight: 700;
            width: 58%;
            text-align: right;
            word-break: break-word;
        }
        .cv-box {
            border: 1px solid var(--cv-line);
            border-top: 3px solid var(--cv-blue);
            border-radius: 8px;
            padding: 12px 14px;
            margin: 0 0 14px;
            background: #f8fafc;
        }
        .cv-box-title {
            font-size: 10px;
            font-weight: 700;
            color: var(--cv-blue);
            margin-bottom: 4px;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .cv-box-body {
            font-size: 13px;
            color: #334155;
            white-space: pre-wrap;
        }
        .cv-words {
            border: 1px solid #d5d8ec;
            border-radius: 8px;
            padding: 10px 14px;
            margin-top: 12px;
            font-size: 12.5px;
            color: #334155;
            background: var(--cv-soft);
        }
        .cv-words strong {
            color: var(--cv-blue);
            margin-right: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            font-size: 11px;
        }
        .cv-sigs {
            margin-top: 36px;
            width: 100%;
            border-collapse: collapse;
        }
        .cv-sig {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 14px;
            border: none;
        }
        .cv-sig-line {
            border-top: 1px solid #94a3b8;
            margin: 48px 8px 8px;
        }
        .cv-sig-label {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            color: #475569;
        }
        .cv-toolbar {
            max-width: 920px;
            margin: 0 auto 14px;
            text-align: right;
        }
        .cv-toolbar .btn {
            border-radius: 8px;
            font-weight: 600;
            font-size: 12.5px;
            padding: 6px 14px;
            cursor: pointer;
            border: 1px solid #cbd5e1;
            background: #fff;
            color: #0f172a;
        }
        .cv-toolbar .btn-primary {
            background: var(--cv-blue);
            border-color: var(--cv-blue);
            color: #fff;
        }
        .cv-print-footer {
            display: none;
        }
        /* Posted transactions — print-safe contrast */
        .voucher-posted-transactions {
            --vpt-blue: var(--cv-blue);
            --vpt-line: var(--cv-line);
            margin-top: 4px;
            margin-bottom: 0;
        }
        .voucher-posted-transactions .vpt-header {
            color: var(--cv-blue);
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            margin-bottom: 8px;
            padding-bottom: 6px;
            border-bottom: 2px solid var(--cv-blue);
        }
        .voucher-posted-transactions .vpt-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 12.5px;
            color: #0f172a;
            border: 1px solid var(--cv-line);
            border-top: 3px solid var(--cv-blue);
            border-radius: 8px;
            overflow: hidden;
        }
        .voucher-posted-transactions .vpt-table col.col-account { width: 34%; }
        .voucher-posted-transactions .vpt-table col.col-narration { width: 40%; }
        .voucher-posted-transactions .vpt-table col.col-debit,
        .voucher-posted-transactions .vpt-table col.col-credit { width: 13%; }
        .voucher-posted-transactions .vpt-table thead th {
            background: var(--cv-blue) !important;
            color: #fff !important;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            padding: 10px 12px;
            border: 1px solid var(--cv-blue);
            white-space: nowrap;
            text-align: left;
        }
        .voucher-posted-transactions .vpt-table thead th.text-end {
            text-align: right;
        }
        .voucher-posted-transactions .vpt-table tbody td,
        .voucher-posted-transactions .vpt-table tfoot td {
            padding: 9px 12px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: top;
            background: #fff;
        }
        .voucher-posted-transactions .vpt-table tbody tr:nth-child(even) td {
            background: #f8fafc;
        }
        .voucher-posted-transactions .vpt-narration {
            color: #334155;
            word-break: break-word;
        }
        .voucher-posted-transactions .vpt-table .text-end {
            text-align: right;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }
        .voucher-posted-transactions .vpt-table tfoot td {
            border-top: 2px solid var(--cv-blue);
            border-bottom: none;
            background: var(--cv-soft) !important;
            padding-top: 10px;
            padding-bottom: 10px;
        }
        .voucher-posted-transactions .vpt-total-label,
        .voucher-posted-transactions .vpt-total-val {
            font-weight: 800;
            color: #0f172a;
        }
        @page {
            size: A4;
            margin: 10mm;
        }
        @media print {
            body { padding: 0; }
            .cv-toolbar, .no-print { display: none !important; }
            .cv-doc {
                max-width: none;
                border: none;
                border-radius: 0;
                box-shadow: none;
            }
            .cv-sheet { padding: 8px 4px 40px; }
            .cv-accent { height: 4px; background: var(--cv-blue) !important; }
            .cv-badge {
                background: var(--cv-soft) !important;
                color: #0f172a !important;
                border-radius: 0;
            }
            .cv-card {
                border: 1px solid var(--cv-line) !important;
                border-top: 3px solid var(--cv-blue) !important;
                border-radius: 0;
            }
            .cv-kv tr.cv-kv-head td { background: var(--cv-soft) !important; }
            .cv-box {
                background: #f8fafc !important;
                border-radius: 0;
            }
            .cv-words {
                background: var(--cv-soft) !important;
                border-radius: 0;
            }
            .voucher-posted-transactions .vpt-table {
                border-radius: 0;
            }
            .voucher-posted-transactions .vpt-table thead th {
                background: var(--cv-blue) !important;
                color: #fff !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .voucher-posted-transactions .vpt-table tbody tr:nth-child(even) td {
                background: #f8fafc !important;
            }
            .voucher-posted-transactions .vpt-table tfoot td {
                background: var(--cv-soft) !important;
            }
            .cv-print-footer {
                display: flex !important;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                font-size: 11px;
                color: #475569;
                border-top: 1px solid #94a3b8;
                padding: 6px 10mm 0;
                justify-content: space-between;
                background: #fff;
            }
            .cv-doc, .cv-doc * {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
                color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
@isset($voucher)
    <div class="cv-toolbar no-print">
        <button type="button" class="btn btn-primary" onclick="window.print()">Print / PDF</button>
    </div>

    <div class="cv-doc">
        <div class="cv-accent"></div>
        <div class="cv-sheet">
            <table class="cv-hdr" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="cv-hdr-logo">
                        @if(!empty($logoSrc))
                        <img src="{{ $logoSrc }}" alt="{{ $companyName }}" class="cv-logo">
                        @endif
                    </td>
                    <td class="cv-hdr-brand">
                        <div class="cv-company">{{ $companyName }}</div>
                        @if($companyAddress !== '')
                        <div class="cv-meta">{{ $companyAddress }}</div>
                        @endif
                        @if($companyTrn !== '' || $companyPhone !== '')
                        <div class="cv-meta">
                            @if($companyTrn !== '')TRN {{ $companyTrn }}@endif
                            @if($companyTrn !== '' && $companyPhone !== '') · @endif
                            @if($companyPhone !== '')Tel: {{ $companyPhone }}@endif
                        </div>
                        @endif
                    </td>
                    <td class="cv-hdr-right">
                        <div class="cv-badge">{{ $voucher_type }}</div>
                        <table class="cv-stamp-kv" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="k">Voucher No</td>
                                <td class="v">{{ $voucherNo }}</td>
                            </tr>
                            <tr>
                                <td class="k">Date</td>
                                <td class="v">{{ $voucherDate }}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <table class="cv-split" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="cv-split-cell">
                        <div class="cv-card">
                            <table class="cv-kv" cellpadding="0" cellspacing="0">
                                <tr class="cv-kv-head">
                                    <td colspan="2" class="cv-kv-head-title">Voucher Details</td>
                                </tr>
                                <tr>
                                    <td class="k">Voucher No</td>
                                    <td class="v">{{ $voucherNo }}</td>
                                </tr>
                                <tr>
                                    <td class="k">Voucher Type</td>
                                    <td class="v">{{ $voucher_type }}</td>
                                </tr>
                                <tr>
                                    <td class="k">Created By</td>
                                    <td class="v">{{ $createdByName }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td class="cv-split-cell">
                        <div class="cv-card">
                            <table class="cv-kv" cellpadding="0" cellspacing="0">
                                <tr class="cv-kv-head">
                                    <td colspan="2" class="cv-kv-head-title">Dates & Period</td>
                                </tr>
                                <tr>
                                    <td class="k">Voucher Date</td>
                                    <td class="v">{{ $voucherDate }}</td>
                                </tr>
                                <tr>
                                    <td class="k">Billing Month</td>
                                    <td class="v">{{ $billingMonth }}</td>
                                </tr>
                                <tr>
                                    <td class="k">Creation Date</td>
                                    <td class="v">{{ $creationDate }}</td>
                                </tr>
                            </table>
                        </div>
                    </td>
                </tr>
            </table>

            @if($vf('remarks') && $remarks !== '')
            <div class="cv-box">
                <div class="cv-box-title">Narration / Notes</div>
                <div class="cv-box-body">{{ $remarks }}</div>
            </div>
            @endif

            @php
                $fin_detail = ($voucher->voucher_type ?? '') === 'RFV'
                    ? company_table('rta_fines')->where('id', $voucher->ref_id)->first()
                    : null;
            @endphp
            @include('vouchers.partials.posted_transactions', [
                'transactions' => $voucher->transactions,
                'voucherType' => $voucher->voucher_type,
                'finDetail' => $fin_detail,
                'showPostedTransactions' => true,
                'includeInPrint' => true,
            ])

            @if($amountInWords !== '—')
            <div class="cv-words">
                <strong>Amount in Words</strong> {{ $amountInWords }}
            </div>
            @endif

            <table class="cv-sigs" width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td class="cv-sig">
                        <div class="cv-sig-line"></div>
                        <div class="cv-sig-label">Prepared By</div>
                    </td>
                    <td class="cv-sig">
                        <div class="cv-sig-line"></div>
                        <div class="cv-sig-label">Checked By</div>
                    </td>
                    <td class="cv-sig">
                        <div class="cv-sig-line"></div>
                        <div class="cv-sig-label">Authorized By</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="cv-print-footer">
        <span>Printed date: {{ now()->format('d-M-Y') }}</span>
        <span>Printed by: {{ auth()->user()->name ?? 'System' }}</span>
    </div>
@else
    <div style="color:#b91c1c;padding:24px;">No Voucher found</div>
@endisset

@if($isPrint ?? false)
<script>window.onload = function () { window.print(); };</script>
@endif
</body>
</html>
