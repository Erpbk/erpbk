@php
    $isPdf = !empty($isPdf);
    $isPrint = $isPdf || request()->boolean('print') || (bool) request()->get('print');
    $brand = $brand ?? [];
    $blue = $brand['primary_color'] ?? '#1e4b8f';
    $blueSoft = $brand['primary_soft'] ?? '#eef4fc';
    $blueLine = $brand['border_color'] ?? '#c5d8f0';
    $money = $formatMoney ?? static fn ($n) => ($currencyCode ?? '').' '.number_format((float) $n, 2);
    $showSignature = !empty($settings['show_signature']);
    $logoSrc = $logoSrc ?? ($brand['logo_src'] ?? ($brand['logo_url'] ?? null));
    $voucherNo = $voucherNo ?? ($referenceNo ?? '—');
    $companyTrn = trim((string) ($companyTrn ?? (\App\Helpers\Common::getSetting('vat_number') ?? '')));
    $companyPhone = trim((string) ($companyPhone ?? ($brand['phone'] ?? '')));
    $companyEmail = trim((string) ($companyEmail ?? ($brand['email'] ?? '')));
    $companyAddress = trim((string) ($companyAddress ?? ($brand['address'] ?? '')));
$riderModuleLabel = trim((string) ($riderModuleLabel ?? \App\Models\Settings::getMenuLabel('riders')));
$riderModuleSingular = \Illuminate\Support\Str::singular($riderModuleLabel !== '' ? $riderModuleLabel : 'Rider');
$payeeDisplay = trim((string) ($payeeName ?? ''));
$payeeLen = mb_strlen($payeeDisplay);
$payeeFontSize = $payeeLen > 36
    ? ($isPdf ? '8.5px' : '10px')
    : ($payeeLen > 28
        ? ($isPdf ? '9.5px' : '11px')
        : ($payeeLen > 22 ? ($isPdf ? '10.5px' : '12px') : null));
@endphp

{{-- Reuse Payment Voucher chrome (same classes / look) --}}
<style>
.rpv-doc {
    --rpv-blue: {{ $blue }};
    --rpv-soft: {{ $blueSoft }};
    --rpv-line: {{ $blueLine }};
    font-family: 'Segoe UI', DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #0f172a;
    max-width: {{ $isPdf ? 'none' : '920px' }};
    width: 100%;
    margin: 0 auto;
    background: #fff;
    border: {{ $isPdf ? 'none' : '1px solid #e6edf5' }};
    border-radius: {{ $isPdf ? '0' : '12px' }};
    box-shadow: {{ $isPdf ? 'none' : '0 1px 2px rgba(15, 23, 42, 0.04), 0 16px 40px rgba(15, 23, 42, 0.06)' }};
    overflow: hidden;
    line-height: 1.45;
}
.rpv-doc * { box-sizing: border-box; }
.rpv-accent {
    height: {{ $isPdf ? '4px' : '5px' }};
    background: var(--rpv-blue);
}
.rpv-sheet { padding: {{ $isPdf ? '14px 16px 16px' : '26px 30px 24px' }}; }
.rpv-hdr {
    width: 100%;
    border-collapse: collapse;
    margin: 0 0 {{ $isPdf ? '14px' : '18px' }};
}
.rpv-hdr td {
    border: none;
    vertical-align: middle;
    padding: 0 0 {{ $isPdf ? '12px' : '16px' }};
    border-bottom: 2px solid var(--rpv-blue);
}
.rpv-hdr-logo {
    width: 28%;
    text-align: left;
}
.rpv-hdr-brand {
    width: 44%;
    text-align: center;
    padding-left: 8px;
    padding-right: 8px;
}
.rpv-hdr-right {
    width: 28%;
    text-align: right;
}
.rpv-logo {
    display: block;
    max-width: {{ $isPdf ? '150px' : '180px' }};
    max-height: {{ $isPdf ? '52px' : '60px' }};
    width: auto;
    height: auto;
    object-fit: contain;
    object-position: left center;
    margin: 0;
}
.rpv-company {
    font-size: {{ $isPdf ? '16px' : '18px' }};
    font-weight: 700;
    color: var(--rpv-blue);
    line-height: 1.25;
    letter-spacing: 0;
    text-align: center;
    margin: 0;
}
.rpv-title {
    display: block;
    margin-top: {{ $isPdf ? '4px' : '5px' }};
    font-size: {{ $isPdf ? '10px' : '11px' }};
    letter-spacing: 0.16em;
    color: var(--rpv-blue);
    font-weight: 600;
    text-transform: uppercase;
    text-align: center;
}
.rpv-meta {
    display: block;
    margin-top: {{ $isPdf ? '4px' : '5px' }};
    font-size: {{ $isPdf ? '9.5px' : '11px' }};
    font-weight: 400;
    color: #666666;
    line-height: 1.4;
    text-align: center;
}
.rpv-meta + .rpv-meta { margin-top: 2px; }
.rpv-ref-label {
    font-size: 10px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    color: #64748b;
    margin-bottom: 6px;
    font-weight: 600;
}
.rpv-ref-box {
    display: inline-block;
    min-width: 148px;
    border: 1px solid var(--rpv-line);
    background: var(--rpv-soft);
    padding: 9px 14px;
    font-weight: 700;
    font-size: {{ $isPdf ? '13px' : '15px' }};
    letter-spacing: 0.03em;
    color: var(--rpv-blue);
    text-align: center;
    border-radius: {{ $isPdf ? '0' : '8px' }};
}
.rpv-split {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    margin: 0 0 {{ $isPdf ? '12px' : '14px' }};
    table-layout: fixed;
}
.rpv-split-cell {
    width: 50%;
    vertical-align: top;
    border: none;
    padding: 0;
}
.rpv-split-cell:first-child { padding-right: {{ $isPdf ? '6px' : '8px' }}; }
.rpv-split-cell:last-child { padding-left: {{ $isPdf ? '6px' : '8px' }}; }
.rpv-card {
    background: var(--rpv-soft);
    border: 1px solid var(--rpv-line);
    border-top: 3px solid var(--rpv-blue);
    border-radius: {{ $isPdf ? '0' : '8px' }};
    padding: {{ $isPdf ? '2px 12px 4px' : '4px 14px 6px' }};
    height: 100%;
}
.rpv-kv {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
}
.rpv-kv td {
    border: none;
    border-bottom: 1px solid rgba(197, 216, 240, 0.85);
    padding: {{ $isPdf ? '5px 0' : '7px 0' }};
    vertical-align: middle;
    font-size: {{ $isPdf ? '11px' : '13px' }};
}
.rpv-kv tr:last-child td { border-bottom: none; }
.rpv-kv .k {
    color: #64748b;
    font-weight: 500;
    width: 42%;
    text-align: left;
    padding-right: 8px;
    white-space: nowrap;
}
.rpv-kv .v {
    color: #0f172a;
    font-weight: 700;
    width: 58%;
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
    border-radius: {{ $isPdf ? '0' : '8px' }};
    padding: {{ $isPdf ? '8px 12px' : '12px 14px' }};
    margin: 0 0 {{ $isPdf ? '12px' : '14px' }};
    background: #f8fafc;
}
.rpv-box-title {
    font-size: {{ $isPdf ? '9px' : '10px' }};
    font-weight: 700;
    color: var(--rpv-blue);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
}
.rpv-box-body {
    font-size: {{ $isPdf ? '11px' : '13px' }};
    color: #334155;
    white-space: pre-wrap;
}
.rpv-bottom {
    width: 100%;
    border-collapse: collapse;
    margin-top: {{ $isPdf ? '10px' : '14px' }};
}
.rpv-bottom td {
    border: none;
    vertical-align: top;
    padding: 0;
}
.rpv-bottom-sum { width: {{ $isPdf ? '48%' : '320px' }}; }
.rpv-summary {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--rpv-line);
    border-radius: {{ $isPdf ? '0' : '8px' }};
    overflow: hidden;
    font-size: {{ $isPdf ? '11px' : '13px' }};
}
.rpv-summary td {
    padding: {{ $isPdf ? '5px 10px' : '8px 14px' }};
    border: none;
    border-bottom: 1px solid #e8eef5;
}
.rpv-summary .k {
    color: #64748b;
    font-weight: 500;
    text-align: left;
}
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
    padding: {{ $isPdf ? '8px 10px' : '11px 14px' }};
}
.rpv-meta-box {
    border: 1px solid var(--rpv-line);
    border-radius: {{ $isPdf ? '0' : '8px' }};
    padding: {{ $isPdf ? '8px 12px' : '10px 14px' }};
    margin-top: {{ $isPdf ? '10px' : '12px' }};
    font-size: {{ $isPdf ? '11px' : '12.5px' }};
    color: #334155;
}
.rpv-meta-box strong {
    color: var(--rpv-blue);
    margin-right: 6px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    font-size: {{ $isPdf ? '10px' : '11px' }};
}
.rpv-meta-box a {
    color: var(--rpv-blue);
    font-weight: 600;
    text-decoration: none;
}
.rpv-sigs {
    margin-top: {{ $isPdf ? '28px' : '36px' }};
    width: 100%;
    border-collapse: collapse;
}
.rpv-sig {
    width: 50%;
    text-align: center;
    vertical-align: bottom;
    padding: 0 18px;
    border: none;
}
.rpv-sig-line {
    border-top: 1px solid #94a3b8;
    margin: {{ $isPdf ? '36px' : '48px' }} 8px 8px;
}
.rpv-sig-label {
    font-size: 11px;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: #475569;
}
@media print {
    .voucher-posted-transactions,
    .rpv-attachments { display: none !important; }
}
</style>

<div class="rpv-doc">
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
                    <div class="rpv-title">{{ $documentTitle }}</div>
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
                    <div class="rpv-ref-label">Voucher No.</div>
                    <div class="rpv-ref-box">{{ $voucherNo }}</div>
                </td>
            </tr>
        </table>

        <table class="rpv-split" cellpadding="0" cellspacing="0">
            <tr>
                <td class="rpv-split-cell">
                    <div class="rpv-card">
                        <table class="rpv-kv" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="k">Date:</td>
                                <td class="v">{{ $voucherDate }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payee:</td>
                                <td class="v v-link v-nowrap"@if($payeeFontSize) style="font-size: {{ $payeeFontSize }};"@endif title="{{ $payeeDisplay }}">{{ $payeeDisplay !== '' ? $payeeDisplay : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="k">{{ $riderModuleSingular }} Code:</td>
                                <td class="v">{{ $riderCode }}</td>
                            </tr>
                            <tr>
                                <td class="k">Billing Month:</td>
                                <td class="v">{{ $billingMonth }}</td>
                            </tr>
                            <tr>
                                <td class="k">Counter Account:</td>
                                <td class="v">{{ $counterAccount }}</td>
                            </tr>
                            <tr>
                                <td class="k">Created By:</td>
                                <td class="v">{{ $createdBy ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="rpv-split-cell">
                    <div class="rpv-card">
                        <table class="rpv-kv" cellpadding="0" cellspacing="0">
                            <tr>
                                <td class="k">Voucher Type:</td>
                                <td class="v">{{ $voucherTypeLabel }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payment Mode:</td>
                                <td class="v">{{ $paymentMode }}</td>
                            </tr>
                            <tr>
                                <td class="k">Reference:</td>
                                <td class="v">{{ $referenceNo }}</td>
                            </tr>
                            <tr>
                                <td class="k">Amount:</td>
                                <td class="v">{{ $money($amount) }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
            </tr>
        </table>

        <div class="rpv-box">
            <div class="rpv-box-title">Narration / Description</div>
            <div class="rpv-box-body">{{ $narration }}</div>
        </div>

        <table class="rpv-bottom" cellpadding="0" cellspacing="0">
            <tr>
                <td></td>
                <td class="rpv-bottom-sum">
                    <table class="rpv-summary" cellpadding="0" cellspacing="0">
                        <tr>
                            <td class="k">Amount:</td>
                            <td class="v">{{ $money($amount) }}</td>
                        </tr>
                        <tr class="rpv-summary-total">
                            <td>Total Voucher:</td>
                            <td class="v">{{ $money($amount) }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="rpv-meta-box">
            <strong>Amount in Words:</strong> {{ $amountInWords }}
        </div>
        @unless($isPrint)
        <div class="rpv-meta-box rpv-attachments no-print">
            <strong>Attachments:</strong>
            @if(!empty($attachmentUrl))
            <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener">{{ $attachmentName ?? 'View attachment' }}</a>
            @else
            No attachment
            @endif
        </div>
        @endunless

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

        @unless($isPrint)
        @include('vouchers.partials.posted_transactions', [
            'transactions' => $postedTransactions ?? collect(),
            'voucherType' => $voucher->voucher_type ?? null,
            'finDetail' => $finDetail ?? null,
            'showPostedTransactions' => true,
        ])
        @endunless
    </div>
</div>
