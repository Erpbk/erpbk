@php
    $isPdf = !empty($isPdf);
    $brand = $brand ?? [];
    $blue = $brand['primary_color'] ?? '#1e4b8f';
    $blueSoft = $brand['primary_soft'] ?? '#eef4fc';
    $blueLine = $brand['border_color'] ?? '#c5d8f0';
    $money = $formatMoney ?? static fn ($n) => ($currencyCode ?? '').' '.number_format((float) $n, 2);
    $showNotes = !empty($settings['show_internal_notes']) && trim((string) ($internalNotes ?? '')) !== '';
    $showSignature = !empty($settings['show_signature']);
    $logoSrc = $logoSrc ?? ($brand['logo_src'] ?? ($brand['logo_url'] ?? null));
    $voucherNo = $voucherNo ?? ($referenceNo ?? '—');
    $companyTrn = trim((string) ($companyTrn ?? (\App\Helpers\Common::getSetting('vat_number') ?? '')));
    $companyPhone = trim((string) ($companyPhone ?? ($brand['phone'] ?? '')));
    $companyEmail = trim((string) ($companyEmail ?? ($brand['email'] ?? '')));
    $companyAddress = trim((string) ($companyAddress ?? ($brand['address'] ?? '')));
    $riderModuleLabel = trim((string) ($riderModuleLabel ?? \App\Models\Settings::getMenuLabel('riders')));
    $riderModuleSingular = \Illuminate\Support\Str::singular($riderModuleLabel !== '' ? $riderModuleLabel : 'Rider');
    $payToLabel = 'Paid To ('.$riderModuleSingular.')';
    $payeeDisplay = trim((string) ($payeeName ?? ''));
    $payeeLen = mb_strlen($payeeDisplay);
    $payeeFontSize = $payeeLen > 40
        ? ($isPdf ? '8.5px' : '10px')
        : ($payeeLen > 32
            ? ($isPdf ? '9.5px' : '11px')
            : ($payeeLen > 26 ? ($isPdf ? '10.5px' : '12px') : null));
    // Widen left card when payee name is long; shrink right card.
    $splitSizeClass = $payeeLen > 34
        ? 'rpv-split--xl-left'
        : ($payeeLen > 24 ? 'rpv-split--wide-left' : '');
    $docModeClass = $isPdf ? 'rpv-doc--pdf' : 'rpv-doc--screen';
@endphp

{{-- CSS has no Blade {{ }} inside rules — a formatter was splitting those and breaking layout. --}}
<style>
.rpv-page { max-width: 920px; margin: 4px auto 28px; }
.rpv-toolbar .btn { border-radius: 8px; font-weight: 600; font-size: 12.5px; padding: 6px 12px; }
.rpv-toolbar .btn-primary { box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12); }
.rpv-doc {
    font-family: 'Segoe UI', DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #0f172a;
    width: 100%;
    margin: 0 auto;
    background: #fff;
    overflow: hidden;
    line-height: 1.45;
}
.rpv-doc--screen {
    max-width: 920px;
    border: 1px solid #e6edf5;
    border-radius: 12px;
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 16px 40px rgba(15, 23, 42, 0.06);
}
.rpv-doc--pdf {
    max-width: none;
    border: none;
    border-radius: 0;
    box-shadow: none;
}
.rpv-page .rpv-doc { max-width: none; }
.rpv-doc * { box-sizing: border-box; }
.rpv-accent { height: 5px; background: var(--rpv-blue); }
.rpv-doc--pdf .rpv-accent { height: 4px; }
.rpv-sheet { padding: 26px 30px 24px; }
.rpv-doc--pdf .rpv-sheet { padding: 14px 16px 16px; }
.rpv-hdr {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 18px;
    table-layout: fixed;
}
.rpv-doc--pdf .rpv-hdr { margin: 0 0 14px; }
.rpv-hdr td {
    border: none;
    vertical-align: middle;
    padding: 0 0 16px;
    border-bottom: 2px solid var(--rpv-blue);
}
.rpv-doc--pdf .rpv-hdr td { padding: 0 0 12px; }
.rpv-hdr-logo { width: 28%; text-align: left; }
.rpv-hdr-brand { width: 44%; text-align: center; padding-left: 8px; padding-right: 8px; }
.rpv-hdr-right { width: 28%; text-align: right; vertical-align: top; }
.rpv-logo {
    display: block;
    max-width: 180px;
    max-height: 60px;
    width: auto;
    height: auto;
    object-fit: contain;
    object-position: left center;
    margin: 0;
}
.rpv-doc--pdf .rpv-logo { max-width: 150px; max-height: 52px; }
.rpv-company {
    font-size: 18px;
    font-weight: 700;
    color: var(--rpv-blue);
    line-height: 1.25;
    text-align: center;
    margin: 0;
}
.rpv-doc--pdf .rpv-company { font-size: 16px; }
.rpv-meta {
    display: block;
    margin-top: 5px;
    font-size: 11px;
    font-weight: 400;
    color: #666666;
    line-height: 1.4;
    text-align: center;
}
.rpv-doc--pdf .rpv-meta { margin-top: 4px; font-size: 9.5px; }
.rpv-meta + .rpv-meta { margin-top: 2px; }
.rpv-badge {
    display: inline-block;
    background: var(--rpv-blue);
    color: #fff;
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 1px;
    text-transform: uppercase;
    padding: 7px 14px;
    margin: 0 0 10px;
    border-radius: 6px;
}
.rpv-doc--pdf .rpv-badge {
    font-size: 10px;
    padding: 6px 12px;
    border-radius: 0;
}
.rpv-stamp-kv {
    border-collapse: collapse;
    margin-left: auto;
    font-size: 12px;
}
.rpv-doc--pdf .rpv-stamp-kv { font-size: 9.5px; }
.rpv-stamp-kv td {
    padding: 3px 0 3px 16px;
    border: none;
    background: transparent;
    vertical-align: middle;
}
.rpv-doc--pdf .rpv-stamp-kv td { padding: 2px 0 2px 12px; }
.rpv-stamp-kv .k {
    color: #64748b;
    font-weight: 500;
    text-align: left;
    white-space: nowrap;
}
.rpv-stamp-kv .v {
    color: #0f172a;
    font-weight: 700;
    text-align: right;
    white-space: nowrap;
}
.rpv-split {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0 0 14px;
    table-layout: fixed;
}
.rpv-doc--pdf .rpv-split { margin: 0 0 12px; }
.rpv-split-cell {
    width: 50%;
    vertical-align: top;
    border: none;
    padding: 0;
}
.rpv-split-cell:first-child { padding-right: 8px; }
.rpv-split-cell:last-child { padding-left: 8px; }
.rpv-doc--pdf .rpv-split-cell:first-child { padding-right: 6px; }
.rpv-doc--pdf .rpv-split-cell:last-child { padding-left: 6px; }
.rpv-split--wide-left .rpv-split-cell:first-child { width: 62%; }
.rpv-split--wide-left .rpv-split-cell:last-child { width: 38%; }
.rpv-split--xl-left .rpv-split-cell:first-child { width: 70%; }
.rpv-split--xl-left .rpv-split-cell:last-child { width: 30%; }
.rpv-split--wide-left .rpv-kv .k,
.rpv-split--xl-left .rpv-kv .k { width: 34%; }
.rpv-split--wide-left .rpv-kv .v,
.rpv-split--xl-left .rpv-kv .v { width: 66%; }
.rpv-split--xl-left .rpv-split-cell:last-child .rpv-kv .k { width: 48%; }
.rpv-split--xl-left .rpv-split-cell:last-child .rpv-kv .v { width: 52%; }
.rpv-card {
    background: var(--rpv-soft);
    border: 1px solid var(--rpv-line);
    border-top: 3px solid var(--rpv-blue);
    border-radius: 8px;
    padding: 4px 14px 6px;
    height: 100%;
}
.rpv-doc--pdf .rpv-card {
    border-radius: 0;
    padding: 2px 12px 4px;
}
.rpv-kv {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.rpv-kv td {
    border: none;
    border-bottom: 1px solid rgba(197, 216, 240, 0.85);
    padding: 7px 0;
    vertical-align: middle;
    font-size: 13px;
}
.rpv-doc--pdf .rpv-kv td { padding: 5px 0; font-size: 11px; }
.rpv-kv tr:last-child td { border-bottom: none; }
.rpv-kv .k {
    color: #64748b;
    font-weight: 500;
    width: 40%;
    text-align: left;
    padding-right: 10px;
    white-space: nowrap;
}
.rpv-kv .v {
    color: #0f172a;
    font-weight: 700;
    width: 60%;
    text-align: right;
    word-break: normal;
    overflow-wrap: normal;
}
.rpv-kv .v-link { color: var(--rpv-blue); }
.rpv-kv .v-nowrap {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 100%;
}
.rpv-box {
    border: 1px solid var(--rpv-line);
    border-top: 3px solid var(--rpv-blue);
    border-radius: 8px;
    padding: 12px 14px;
    margin: 0 0 14px;
    background: #f8fafc;
}
.rpv-doc--pdf .rpv-box {
    border-radius: 0;
    padding: 8px 12px;
    margin: 0 0 12px;
}
.rpv-box-title {
    font-size: 10px;
    font-weight: 700;
    color: var(--rpv-blue);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.rpv-doc--pdf .rpv-box-title { font-size: 9px; }
.rpv-box-body {
    font-size: 13px;
    color: #334155;
    white-space: pre-wrap;
}
.rpv-doc--pdf .rpv-box-body { font-size: 11px; }
.rpv-table-wrap {
    border: 1px solid var(--rpv-line);
    border-top: 3px solid var(--rpv-blue);
    border-radius: 8px;
    overflow: hidden;
    margin: 0 0 4px;
}
.rpv-doc--pdf .rpv-table-wrap {
    border-radius: 0;
    margin: 0 0 10px;
}
.rpv-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    font-size: 12.5px;
}
.rpv-doc--pdf .rpv-table { font-size: 10px; }
.rpv-table th {
    background: var(--rpv-soft);
    color: #0f172a;
    border: none;
    border-bottom: 1px solid var(--rpv-line);
    padding: 10px;
    text-align: center;
    font-weight: 700;
    font-size: 10.5px;
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.rpv-doc--pdf .rpv-table th { padding: 7px 6px; font-size: 9px; }
.rpv-table td {
    border: none;
    border-bottom: 1px solid #e8eef5;
    padding: 9px 10px;
    vertical-align: middle;
    text-align: center;
    color: #0f172a;
}
.rpv-doc--pdf .rpv-table td { padding: 6px; }
.rpv-table tbody tr:last-child td { border-bottom: none; }
.rpv-table tbody tr:nth-child(even) td { background: #fafbfc; }
.rpv-table .num {
    text-align: center;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.rpv-table .pay { font-weight: 700; }
.rpv-table tfoot td {
    background: var(--rpv-soft);
    font-weight: 700;
    color: var(--rpv-blue);
    border-top: 1px solid var(--rpv-line);
    border-bottom: none;
    padding: 10px;
}
.rpv-doc--pdf .rpv-table tfoot td { padding: 7px 6px; }
.rpv-table tfoot td.rpv-allocated {
    padding: 10px;
}
.rpv-table tfoot td.rpv-allocated-spacer {
    text-align: left;
}
.rpv-table tfoot td.rpv-allocated-label {
    text-align: left !important;
    white-space: nowrap;
}
.rpv-table tfoot td.rpv-allocated-val {
    text-align: center !important;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.rpv-table .empty { text-align: center; color: #94a3b8; }
.rpv-bottom {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14px;
}
.rpv-doc--pdf .rpv-bottom { margin-top: 10px; }
.rpv-bottom td { border: none; vertical-align: top; padding: 0; }
.rpv-bottom-sum { width: 320px; }
.rpv-doc--pdf .rpv-bottom-sum { width: 48%; }
.rpv-summary {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--rpv-line);
    border-radius: 8px;
    overflow: hidden;
    font-size: 13px;
}
.rpv-doc--pdf .rpv-summary { border-radius: 0; font-size: 11px; }
.rpv-summary td {
    padding: 8px 14px;
    border: none;
    border-bottom: 1px solid #e8eef5;
}
.rpv-doc--pdf .rpv-summary td { padding: 5px 10px; }
.rpv-summary .k { color: #64748b; font-weight: 500; text-align: left; }
.rpv-summary .v {
    font-weight: 700;
    text-align: right;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}
.rpv-summary tr:last-child td { border-bottom: none; }
.rpv-summary-total td {
    background: var(--rpv-blue);
    color: #fff !important;
    font-weight: 700;
    border-bottom: none;
    padding: 11px 14px;
}
.rpv-doc--pdf .rpv-summary-total td { padding: 8px 10px; }
.rpv-meta-box {
    border: 1px solid var(--rpv-line);
    border-radius: 8px;
    padding: 10px 14px;
    margin-top: 12px;
    font-size: 12.5px;
    color: #334155;
}
.rpv-doc--pdf .rpv-meta-box {
    border-radius: 0;
    padding: 8px 12px;
    margin-top: 10px;
    font-size: 11px;
}
.rpv-meta-box strong {
    color: var(--rpv-blue);
    margin-right: 6px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-size: 11px;
}
.rpv-doc--pdf .rpv-meta-box strong { font-size: 10px; }
.rpv-meta-box a { color: var(--rpv-blue); font-weight: 600; text-decoration: none; }
.rpv-sigs {
    margin-top: 36px;
    width: 100%;
    border-collapse: collapse;
}
.rpv-doc--pdf .rpv-sigs { margin-top: 28px; }
.rpv-sig {
    width: 50%;
    text-align: center;
    vertical-align: bottom;
    padding: 0 18px;
    border: none;
}
.rpv-sig-line {
    border-top: 1px solid #94a3b8;
    margin: 48px 8px 8px;
}
.rpv-doc--pdf .rpv-sig-line { margin: 36px 8px 8px; }
.rpv-sig-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #475569;
}
/*
 * Keep desktop-like 2-column voucher layout on tablets + foldables.
 * Only stack on narrow phone portrait widths.
 */
@media (max-width: 576px) {
    .rpv-doc--screen .rpv-sheet { padding: 14px 12px 16px; }
    .rpv-doc--screen .rpv-hdr,
    .rpv-doc--screen .rpv-hdr tbody,
    .rpv-doc--screen .rpv-hdr tr,
    .rpv-doc--screen .rpv-hdr-logo,
    .rpv-doc--screen .rpv-hdr-brand,
    .rpv-doc--screen .rpv-hdr-right {
        display: block;
        width: 100% !important;
        text-align: center;
        padding-left: 0 !important;
        padding-right: 0 !important;
    }
    .rpv-doc--screen .rpv-hdr-logo { margin-bottom: 10px; }
    .rpv-doc--screen .rpv-logo { margin: 0 auto; object-position: center; }
    .rpv-doc--screen .rpv-hdr-right { margin-top: 10px; }
    .rpv-doc--screen .rpv-stamp-kv { margin: 0 auto; }
    .rpv-doc--screen .rpv-split,
    .rpv-doc--screen .rpv-split tbody,
    .rpv-doc--screen .rpv-split tr,
    .rpv-doc--screen .rpv-split-cell {
        display: block;
        width: 100% !important;
    }
    .rpv-doc--screen .rpv-split-cell {
        padding-left: 0 !important;
        padding-right: 0 !important;
        margin-bottom: 10px;
    }
    .rpv-doc--screen .rpv-split-cell:last-child { margin-bottom: 0; }
    .rpv-doc--screen .rpv-bottom-sum { width: 100%; }
    .rpv-doc--screen .rpv-kv .k { width: 42%; }
    .rpv-doc--screen .rpv-kv .v { width: 58%; }
    .rpv-doc--screen .rpv-company { font-size: 15px; }
    .rpv-doc--screen .rpv-meta { font-size: 10px; }
    .rpv-doc--screen .rpv-kv td { font-size: 12px; }
    .rpv-doc--screen .rpv-badge { font-size: 10px; padding: 6px 10px; }
}
@media print {
    .rpv-toolbar { display: none !important; }
    .rpv-page { max-width: none; margin: 0; }
    .rpv-doc,
    .rpv-doc * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    .rpv-doc {
        box-shadow: none !important;
        border: none !important;
        max-width: none;
        border-radius: 0;
        background: #fff !important;
    }
    .rpv-accent {
        background: var(--rpv-blue) !important;
        print-color-adjust: exact !important;
    }
    .rpv-badge {
        background: var(--rpv-blue) !important;
        color: #fff !important;
        border-radius: 6px !important;
    }
    .rpv-card {
        background: var(--rpv-soft) !important;
        border: 1px solid var(--rpv-line) !important;
        border-top: 3px solid var(--rpv-blue) !important;
    }
    .rpv-box,
    .rpv-meta-box {
        background: #f8fafc !important;
        border: 1px solid var(--rpv-line) !important;
    }
    .rpv-box { border-top: 3px solid var(--rpv-blue) !important; }
    .rpv-table-wrap {
        border: 1px solid var(--rpv-line) !important;
        border-top: 3px solid var(--rpv-blue) !important;
    }
    .rpv-table th,
    .rpv-table tfoot td {
        background: var(--rpv-soft) !important;
        color: var(--rpv-blue) !important;
    }
    .rpv-table th {
        color: #0f172a !important;
        text-align: center !important;
    }
    .rpv-table tbody td {
        text-align: center !important;
    }
    .rpv-table tfoot td.rpv-allocated-label {
        text-align: left !important;
        color: var(--rpv-blue) !important;
    }
    .rpv-table tfoot td.rpv-allocated-val {
        text-align: center !important;
        color: var(--rpv-blue) !important;
    }
    .rpv-summary {
        border: 1px solid var(--rpv-line) !important;
    }
    .rpv-summary-total td {
        background: var(--rpv-blue) !important;
        color: #fff !important;
    }
    .voucher-posted-transactions,
    .rpv-attachments,
    .no-print,
    .rpv-diff-row { display: none !important; }
}
</style>

<div class="rpv-doc {{ $docModeClass }}"
     style="--rpv-blue: {{ $blue }}; --rpv-soft: {{ $blueSoft }}; --rpv-line: {{ $blueLine }};">
    <div class="rpv-accent"></div>
    <div class="rpv-sheet">
        <table class="rpv-hdr" cellpadding="0" cellspacing="0">
            <tr>
                <td class="rpv-hdr-logo">
                    @if(!empty($logoSrc))
                    <img src="{{ $logoSrc }}" alt="{{ $companyName }}" class="rpv-logo">
                    @endif
                </td>
                <td class="rpv-hdr-brand">
                    <div class="rpv-company">{{ $companyName }}</div>
                    @if($companyTrn !== '' || $companyPhone !== '' || $companyEmail !== '')
                    <div class="rpv-meta">
                        @if($companyTrn !== '')TRN: {{ $companyTrn }}@endif
                        @if($companyTrn !== '' && $companyPhone !== '') · @endif
                        @if($companyPhone !== '')Tel: {{ $companyPhone }}@endif
                        @if($companyEmail !== ''){{ ($companyTrn !== '' || $companyPhone !== '') ? ' ' : '' }}{{ $companyEmail }}@endif
                    </div>
                    @endif
                    @if($companyAddress !== '')
                    <div class="rpv-meta">{{ $companyAddress }}</div>
                    @endif
                </td>
                <td class="rpv-hdr-right">
                    <div class="rpv-badge">{{ $documentTitle }}</div>
                    <table class="rpv-stamp-kv" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="k">Voucher No</td>
                            <td class="v">{{ $voucherNo }}</td>
                        </tr>
                        <tr>
                            <td class="k">Date</td>
                            <td class="v">{{ $paymentDate }}</td>
                        </tr>
                        <tr>
                            <td class="k">Billing</td>
                            <td class="v">{{ $billingMonth }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <table class="rpv-split {{ $splitSizeClass }}" cellpadding="0" cellspacing="0">
            <tr>
                <td class="rpv-split-cell">
                    <div class="rpv-card">
                        <table class="rpv-kv" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="k">{{ $payToLabel }}:</td>
                                <td class="v v-link v-nowrap"@if($payeeFontSize) style="font-size: {{ $payeeFontSize }};"@endif title="{{ $payeeDisplay }}">{{ $payeeDisplay !== '' ? $payeeDisplay : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="k">{{ $riderModuleSingular }} Code:</td>
                                <td class="v">{{ $riderCode }}</td>
                            </tr>
                            <tr>
                                <td class="k">Paid By:</td>
                                <td class="v">{{ $paidBy }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="rpv-split-cell">
                    <div class="rpv-card">
                        <table class="rpv-kv" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="k">Payment Mode:</td>
                                <td class="v">{{ $paymentMode }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payment Type:</td>
                                <td class="v">{{ $paymentType }}</td>
                            </tr>
                            <tr>
                                <td class="k">Created By:</td>
                                <td class="v">{{ $createdBy ?? '—' }}</td>
                            </tr>
                            @if((float) ($bankCharges ?? 0) > 0)
                            <tr>
                                <td class="k">Bank Charges:</td>
                                <td class="v">{{ $money($bankCharges) }}</td>
                            </tr>
                            @endif
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="rpv-box">
            <div class="rpv-box-title">Narration / Description</div>
            <div class="rpv-box-body">{{ $narration }}</div>
        </div>

        <div class="rpv-table-wrap">
            <table class="rpv-table">
                <thead>
                    <tr>
                        <th>Invoice #</th>
                        <th>Billing Month</th>
                        <th class="num">Total Amount</th>
                        <th class="num">Payment</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($allocations as $row)
                    <tr>
                        <td>{{ $row['invoice_number'] }}</td>
                        <td>{{ $row['billing_month'] }}</td>
                        <td class="num">{{ number_format((float) $row['total'], 2) }}</td>
                        <td class="num pay">{{ number_format((float) $row['payment'], 2) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty">No invoice allocation</td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" class="rpv-allocated rpv-allocated-spacer"></td>
                        <td class="rpv-allocated rpv-allocated-label">Total Amount</td>
                        <td class="rpv-allocated rpv-allocated-val">{{ number_format((float) $allocatedTotal, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <table class="rpv-bottom" cellpadding="0" cellspacing="0">
            <tr>
                <td></td>
                <td class="rpv-bottom-sum">
                    <table class="rpv-summary" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="k">Payment Amount:</td>
                            <td class="v">{{ $money($paymentAmount) }}</td>
                        </tr>
                        <tr>
                            <td class="k">Invoice Allocation:</td>
                            <td class="v">{{ $money($allocatedTotal) }}</td>
                        </tr>
                        <tr class="rpv-diff-row no-print">
                            <td class="k">Difference:</td>
                            <td class="v">{{ $money($difference) }}</td>
                        </tr>
                        @if((float) ($bankCharges ?? 0) > 0)
                        <tr>
                            <td class="k">Bank Charges:</td>
                            <td class="v">{{ $money($bankCharges) }}</td>
                        </tr>
                        @endif
                        <tr class="rpv-summary-total">
                            <td>Total Payment:</td>
                            <td class="v">{{ $money($totalPayment) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="rpv-meta-box">
            <strong>Amount in Words:</strong> {{ $amountInWords }}
        </div>
        @if(!$isPdf && !request()->boolean('print'))
        <div class="rpv-meta-box rpv-attachments no-print">
            <strong>Attachments:</strong>
            @if(!empty($attachmentUrl))
            <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener">{{ $attachmentName ?? 'View attachment' }}</a>
            @else
            No attachment
            @endif
        </div>
        @endif

        @if($showNotes)
        <div class="rpv-box" style="margin-top:14px;">
            <div class="rpv-box-title">{{ $settings['internal_notes_label'] ?? 'Internal Notes' }}</div>
            <div class="rpv-box-body">{{ $internalNotes }}</div>
        </div>
        @endif

        @if($showSignature)
        <table class="rpv-sigs" width="100%" cellpadding="0" cellspacing="0">
            <tr>
                <td class="rpv-sig">
                    <div class="rpv-sig-line"></div>
                    <div class="rpv-sig-label">{{ $settings['payer_signature_label'] ?? 'Paid By' }}</div>
                </td>
                <td class="rpv-sig">
                    <div class="rpv-sig-line"></div>
                    <div class="rpv-sig-label">{{ $settings['rider_signature_label'] ?? 'Received By' }}</div>
                </td>
            </tr>
        </table>
        @endif

        @if(!$isPdf && !request()->boolean('print'))
        @include('vouchers.partials.posted_transactions', [
            'transactions' => $postedTransactions ?? collect(),
            'voucherType' => $payment->voucher->voucher_type ?? 'PV',
            'showPostedTransactions' => true,
        ])
        @endif
    </div>
</div>
