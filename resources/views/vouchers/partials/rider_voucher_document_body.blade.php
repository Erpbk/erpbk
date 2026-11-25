@php
    $isPdf = !empty($isPdf);
    $isPrint = $isPdf || request()->boolean('print') || (bool) request()->get('print');
    $brand = $brand ?? [];
    if (empty($brand['primary_color']) || empty($brand['primary_soft']) || empty($brand['border_color'])) {
        $brand = array_merge(
            app(\App\Services\Agreements\AgreementPdfBranding::class)
                ->colorPalette(\App\Support\CompanyContext::id()),
            $brand
        );
    }
    $blue = (string) ($brand['primary_color'] ?? '');
    $blueSoft = (string) ($brand['primary_soft'] ?? ($brand['primary_light'] ?? ''));
    $blueLine = (string) ($brand['border_color'] ?? '');
    $money = $formatMoney ?? static fn ($n) => \App\Helpers\Currency::format((float) $n);
    $showSignature = !empty($settings['show_signature']);
    $logoSrc = $logoSrc ?? ($brand['logo_src'] ?? ($brand['logo_url'] ?? null));
    $voucherNo = $voucherNo ?? ($referenceNo ?? '—');
    $companyTrn = trim((string) ($companyTrn ?? (\App\Helpers\Common::getSetting('vat_number') ?? '')));
    $companyPhone = trim((string) ($companyPhone ?? ($brand['phone'] ?? '')));
    $companyEmail = trim((string) ($companyEmail ?? ($brand['email'] ?? '')));
    $companyAddress = trim((string) ($companyAddress ?? ($brand['address'] ?? '')));
    $payeeDisplay = trim((string) ($payeeName ?? ''));
    $payeeLen = mb_strlen($payeeDisplay);
    $payeeFontSize = $payeeLen > 36
        ? ($isPdf ? '8.5px' : '10px')
        : ($payeeLen > 28
            ? ($isPdf ? '9.5px' : '11px')
            : ($payeeLen > 22 ? ($isPdf ? '10.5px' : '12px') : null));
    $splitSizeClass = $payeeLen > 34 ? 'rpv-split--xl-left' : ($payeeLen > 24 ? 'rpv-split--wide-left' : '');
    $docModeClass = ($isPdf || $isPrint) ? 'rpv-doc--pdf' : 'rpv-doc--screen';
    $lines = $lines ?? [];
    $lineTotal = isset($lineTotal) ? (float) $lineTotal : (float) ($amount ?? 0);
    $paidBy = $paidBy ?? ($counterAccount ?? '—');
    $paymentType = $paymentType ?? ($voucherTypeLabel ?? '—');
    $paymentMode = $paymentMode ?? '—';
@endphp

{{-- CSS has no Blade {{ }} inside rules — keep styles static like payments doc. --}}
<style>
.rpv-page { max-width: 920px; margin: 4px auto 28px; }
.rpv-toolbar .btn { border-radius: 8px; font-weight: 600; font-size: 12.5px; padding: 6px 12px; }
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
.rpv-doc--pdf { max-width: none; border: none; border-radius: 0; box-shadow: none; overflow: visible; }
.rpv-page .rpv-doc { max-width: none; }
.rpv-doc * { box-sizing: border-box; }
.rpv-sheet { padding: 26px 30px 24px; }
.rpv-doc--pdf .rpv-sheet { padding: 14px 16px 16px; }
/* Title bar under company header — soft box with thick left accent */
.rpv-titlebar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin: 0 0 16px;
    padding: 4px 6px 4px 7px;
    background: var(--rpv-soft);
    border: 1px solid var(--rpv-line);
    border-left: 4px solid var(--rpv-blue);
    border-radius: 8px;
    box-sizing: border-box;
}
.rpv-doc--pdf .rpv-titlebar {
    margin: 0 0 12px;
    padding: 8px 12px 8px 14px;
    /* Keep the same soft box + left accent in print/PDF */
    border-radius: 8px !important;
    border-left: 4px solid var(--rpv-blue) !important;
    display: flex !important;
    width: 100%;
}
.rpv-titlebar-l { min-width: 0; flex: 1 1 auto; overflow: visible; }
.rpv-titlebar-r {
    display: flex;
    align-items: center;
    gap: 14px;
    flex: 0 0 auto;
    white-space: nowrap;
}
.rpv-doc--pdf .rpv-titlebar-l,
.rpv-doc--pdf .rpv-titlebar-r { display: block; }
.rpv-doc--pdf .rpv-titlebar-r {
    display: flex !important;
    text-align: right;
}
.rpv-title {
    margin: 0;
    font-size: 15px;
    font-weight: 800;
    color: var(--rpv-blue);
    letter-spacing: 0.04em;
    text-transform: uppercase;
    line-height: 1.2;
    white-space: nowrap;
}
.rpv-doc--pdf .rpv-title { font-size: 15px; white-space: nowrap; }
.rpv-subtitle {
    margin: 3px 0 0;
    font-size: 12px;
    font-weight: 600;
    color: #64748b;
    line-height: 1.3;
}
.rpv-doc--pdf .rpv-subtitle { font-size: 10px; }
.rpv-title-kv { display: inline-flex; align-items: baseline; gap: 6px; font-size: 12.5px; }
.rpv-doc--pdf .rpv-title-kv { font-size: 10.5px; }
.rpv-title-kv .k { color: #64748b; font-weight: 500; }
.rpv-title-kv .v { color: #0f172a; font-weight: 700; }
.rpv-title-sep {
    width: 1px;
    height: 22px;
    background: #cbd5e1;
    flex: none;
}
.rpv-doc--pdf .rpv-title-sep {
    display: block;
    width: 1px;
    height: 16px;
    background: #cbd5e1;
}
.rpv-split { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0 0 14px; table-layout: fixed; }
.rpv-split--wide-left .rpv-split-cell:first-child { width: 56%; }
.rpv-split--wide-left .rpv-split-cell:last-child { width: 44%; }
.rpv-split--xl-left .rpv-split-cell:first-child { width: 60%; }
.rpv-split--xl-left .rpv-split-cell:last-child { width: 40%; }
.rpv-split-cell { width: 50%; vertical-align: top; border: none; padding: 0; }
.rpv-split-cell:first-child { padding-right: 8px; }
.rpv-split-cell:last-child { padding-left: 8px; }
.rpv-doc--pdf .rpv-split-cell:first-child { padding-right: 6px; }
.rpv-doc--pdf .rpv-split-cell:last-child { padding-left: 6px; }
.rpv-card {
    background: #fff; border: 1px solid var(--rpv-line); border-top: 3px solid var(--rpv-blue);
    border-radius: 8px; overflow: hidden; height: 100%;
}
.rpv-doc--pdf .rpv-card { border-radius: 8px; }
.rpv-kv { width: 100%; border-collapse: collapse; table-layout: fixed; }
.rpv-kv td {
    border: none; border-bottom: 1px solid rgba(197, 216, 240, 0.85); padding: 7px 14px;
    vertical-align: middle; font-size: 13px; background: #fff;
}
.rpv-doc--pdf .rpv-kv td { padding: 5px 12px; font-size: 11px; }
.rpv-kv tr:last-child td { border-bottom: none; }
.rpv-kv tr.rpv-kv-head td {
    background: var(--rpv-soft); padding-top: 8px; padding-bottom: 8px;
    border-bottom: 1px solid var(--rpv-line); font-weight: 700;
}
.rpv-kv .rpv-kv-head-title {
    color: var(--rpv-blue); font-weight: 700 !important; font-size: 11px;
    text-transform: uppercase; letter-spacing: 0.4px; text-align: left;
}
.rpv-kv .k { color: #64748b; font-weight: 500; width: 40%; text-align: left; padding-right: 10px; white-space: nowrap; }
.rpv-kv .v { color: #0f172a; font-weight: 700; width: 60%; text-align: right; }
.rpv-kv .v-link { color: var(--rpv-blue); }
.rpv-kv .v-nowrap { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 100%; }
.rpv-box {
    border: 1px solid var(--rpv-line); border-top: 3px solid var(--rpv-blue); border-radius: 8px;
    padding: 12px 14px; margin: 0 0 14px; background: #f8fafc;
}
.rpv-doc--pdf .rpv-box { border-radius: 8px; padding: 8px 12px; margin: 0 0 12px; }
.rpv-box-title { font-size: 10px; font-weight: 700; color: var(--rpv-blue); margin-bottom: 4px; text-transform: uppercase; letter-spacing: 0.08em; }
.rpv-box-body { font-size: 13px; color: #334155; white-space: pre-wrap; }
.rpv-doc--pdf .rpv-box-body { font-size: 11px; }
.rpv-table-wrap {
    border: 1px solid var(--rpv-line); border-top: 3px solid var(--rpv-blue); border-radius: 8px;
    overflow: hidden; margin: 0 0 14px;
}
.rpv-doc--pdf .rpv-table-wrap { border-radius: 8px; overflow: hidden; margin: 0 0 12px; }
.rpv-table { width: 100%; border-collapse: collapse; margin: 0; font-size: 12.5px; }
.rpv-doc--pdf .rpv-table { font-size: 10px; }
.rpv-table th {
    background: var(--rpv-soft); color: #0f172a; border: none; padding: 11px 12px;
    text-align: center; font-weight: 700; font-size: 11px; letter-spacing: 0.04em; text-transform: uppercase;
}
.rpv-table td {
    border: none; border-bottom: 1px solid #e8eef5; padding: 11px 12px; vertical-align: middle;
    text-align: center; color: #0f172a; background: #fff;
}
.rpv-table tbody tr:last-child td { border-bottom: none; }
.rpv-table .num { text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
.rpv-table .acc { text-align: left; }
.rpv-table .nar { text-align: left; color: #334155; }
.rpv-table .sno { width: 64px; text-align: center; }
.rpv-table .pay { font-weight: 600; }
.rpv-table .empty { text-align: center; color: #94a3b8; }
.rpv-total-paid {
    width: 360px; max-width: 100%; margin: 0 0 14px auto; background: var(--rpv-soft); color: #0f172a;
    border-radius: 8px; padding: 12px 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px;
}
.rpv-doc--pdf .rpv-total-paid {
    width: 360px; max-width: 100%; margin: 0 0 12px auto; border-radius: 8px;
    padding: 10px 14px; display: flex; align-items: center; justify-content: space-between;
}
.rpv-total-paid-label { font-size: 13px; font-weight: 500; white-space: nowrap; }
.rpv-total-paid-val { font-size: 16px; font-weight: 700; white-space: nowrap; font-variant-numeric: tabular-nums; text-align: right; }
.rpv-meta-box {
    border: 1px solid var(--rpv-line); border-radius: 8px; padding: 12px 14px; margin-bottom: 12px;
    font-size: 12.5px; color: #334155; background: var(--rpv-soft);
}
.rpv-doc--pdf .rpv-meta-box { border-radius: 8px; padding: 8px 12px; margin-bottom: 10px; font-size: 11px; }
.rpv-meta-box.rpv-attachments { background: #fff; }
.rpv-meta-box strong {
    color: var(--rpv-blue); margin-right: 6px; text-transform: uppercase; letter-spacing: 0.04em;
    font-size: 11px; font-weight: 700;
}
.rpv-meta-box a { color: var(--rpv-blue); font-weight: 600; text-decoration: none; }
.rpv-sigs { margin-top: 36px; width: 100%; border-collapse: collapse; }
.rpv-doc--pdf .rpv-sigs { margin-top: 28px; }
.rpv-sig { width: 50%; text-align: center; vertical-align: bottom; padding: 0 18px; border: none; }
.rpv-sig-line { border-top: 1px solid #94a3b8; margin: 48px 8px 8px; }
.rpv-doc--pdf .rpv-sig-line { margin: 36px 8px 8px; }
.rpv-sig-label { font-size: 11px; font-weight: 700; letter-spacing: 0.08em; text-transform: uppercase; color: #475569; }
.rpv-doc .app-currency-icon { display: inline-block; height: 1em; width: auto; max-width: 1.75em; object-fit: contain; vertical-align: -0.15em; }
@media print {
    .voucher-posted-transactions, .rpv-attachments, .rpv-toolbar, .no-print { display: none !important; }
    .rpv-doc, .rpv-doc * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    .rpv-doc { overflow: visible !important; }
    .rpv-titlebar,
    .rpv-card,
    .rpv-box,
    .rpv-table-wrap,
    .rpv-total-paid,
    .rpv-meta-box {
        border-radius: 8px !important;
    }
    .rpv-titlebar {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        border-left: 4px solid var(--rpv-blue) !important;
        background: var(--rpv-soft) !important;
    }
    .rpv-title { white-space: nowrap !important; }
    .rpv-titlebar-r { display: flex !important; }
    .rpv-total-paid {
        display: flex !important;
        width: 360px !important;
        max-width: 100% !important;
        margin-left: auto !important;
    }
}
</style>
@php
    [$printLayout, $plHeader, $plPos] = app(\App\Services\Voucher\VoucherSignatureResolver::class)
        ->layoutPartsFor((string) ($voucherTypeCode ?? ($voucher->voucher_type ?? '')));
@endphp
@include('vouchers.partials.print_layout_styles', [
    'printLayout' => $printLayout,
    'plPos' => $plPos,
    'isPdf' => !empty($isPdf) || !empty($isPrint),
])

<div class="rpv-doc {{ $docModeClass }}"
     style="--rpv-blue: {{ $blue }}; --rpv-soft: {{ $blueSoft }}; --rpv-line: {{ $blueLine }};">
    <div class="rpv-sheet rpv-sheet--sig-{{ $plPos }}">
        @include('vouchers.partials.print_company_header', [
            'plHeader' => $plHeader,
            'companyName' => $companyName,
            'logoSrc' => $logoSrc,
            'companyPhone' => $companyPhone,
            'companyEmail' => $companyEmail,
            'companyAddress' => $companyAddress,
            'companyTrn' => $companyTrn,
        ])

        <div class="rpv-titlebar">
            <div class="rpv-titlebar-l">
                <div class="rpv-title">{{ $documentTitle }}</div>
                @if(!empty($moduleBadge))
                <div class="rpv-subtitle">{{ $moduleBadge }}</div>
                @endif
            </div>
            <div class="rpv-titlebar-r">
                <div class="rpv-title-kv"><span class="k">Voucher No</span> <span class="v">{{ $voucherNo }}</span></div>
                <span class="rpv-title-sep" aria-hidden="true"></span>
                <div class="rpv-title-kv"><span class="k">Date</span> <span class="v">{{ $voucherDate }}</span></div>
            </div>
        </div>

        <table class="rpv-split {{ $splitSizeClass }}" cellpadding="0" cellspacing="0">
            <tr>
                <td class="rpv-split-cell">
                    <div class="rpv-card">
                        <table class="rpv-kv" cellpadding="0" cellspacing="0">
                            <tr class="rpv-kv-head">
                                <td colspan="2" class="rpv-kv-head-title"><strong>Paid To</strong></td>
                            </tr>
                            <tr>
                                <td class="k">Name</td>
                                <td class="v v-link v-nowrap"@if($payeeFontSize) style="font-size: {{ $payeeFontSize }};"@endif title="{{ $payeeDisplay }}">{{ $payeeDisplay !== '' ? $payeeDisplay : '—' }}</td>
                            </tr>
                            <tr>
                                <td class="k">ID</td>
                                <td class="v">{{ $riderCode ?? '—' }}</td>
                            </tr>
                            <tr>
                                <td class="k">Created By</td>
                                <td class="v">{{ $createdBy ?? '—' }}</td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td class="rpv-split-cell">
                    <div class="rpv-card">
                        <table class="rpv-kv" cellpadding="0" cellspacing="0">
                            <tr class="rpv-kv-head">
                                <td colspan="2" class="rpv-kv-head-title"><strong>Payment Detail</strong></td>
                            </tr>
                            <tr>
                                <td class="k">Payment Mode</td>
                                <td class="v">{{ $paymentMode }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payment Type</td>
                                <td class="v">{{ $paymentType }}</td>
                            </tr>
                            <tr>
                                <td class="k">Billing Month</td>
                                <td class="v">{{ $billingMonth }}</td>
                            </tr>
                            <tr>
                                <td class="k">Paid By</td>
                                <td class="v">{{ $paidBy }}</td>
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

        <div class="rpv-table-wrap">
            <table class="rpv-table">
                <thead>
                    <tr>
                        <th class="sno">S.No</th>
                        <th>Account</th>
                        <th>Narration</th>
                        <th class="num">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lines as $row)
                    <tr>
                        <td class="sno">{{ $loop->iteration }}</td>
                        <td class="acc">{{ $row['account'] ?? '—' }}</td>
                        <td class="nar">{{ $row['narration'] ?? '—' }}</td>
                        <td class="num pay">{{ $money((float) ($row['amount'] ?? 0)) }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="4" class="empty">No line items</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="rpv-total-paid">
            <span class="rpv-total-paid-label">Total Paid Amount</span>
            <span class="rpv-total-paid-val">{{ $money($lineTotal) }}</span>
        </div>

        <div class="rpv-meta-box">
            <strong>Amount in Words</strong> {{ $amountInWords }}
        </div>
        @unless($isPrint)
        <div class="rpv-meta-box rpv-attachments no-print">
            <strong>Attachments:</strong>
            @if(!empty($attachmentUrl))
            <a href="{{ $attachmentUrl }}"
               class="js-vc-doc-view"
               rel="noopener"
               data-file-url="{{ $attachmentUrl }}"
               data-file-name="{{ $attachmentName ?? 'attachment' }}"
               data-file-meta="{{ trim(($voucherTypeLabel ?? '').' · '.($voucherNo ?? '')) }}"
               data-file-context="{{ $voucherTypeLabel ?? 'this voucher' }}">{{ $attachmentName ?? 'View attachment' }}</a>
            @else
            No attachment
            @endif
        </div>
        @endunless

        @unless($isPrint)
        @include('vouchers.partials.posted_transactions', [
            'transactions' => $postedTransactions ?? collect(),
            'voucherType' => $voucher->voucher_type ?? null,
            'finDetail' => $finDetail ?? null,
            'showPostedTransactions' => true,
        ])
        @endunless

        @php
            $configuredSignatureSlots = collect($signatureSlots ?? []);
            $hasSignatureBlock = $configuredSignatureSlots->isNotEmpty() || !empty($showSignature);
        @endphp
        @if($hasSignatureBlock && $plPos === 'bottom')
        <div class="rpv-sig-push" aria-hidden="true"></div>
        @endif
        @if($configuredSignatureSlots->isNotEmpty())
            @include('vouchers.partials.signature_block', [
                'signatureSlots' => $configuredSignatureSlots,
                'signatureVariant' => 'rpv',
                'signaturePerRow' => 4,
            ])
        @elseif($showSignature)
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
    </div>
</div>
