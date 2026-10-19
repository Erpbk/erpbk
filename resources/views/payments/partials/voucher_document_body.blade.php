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
@endphp

<style>
.rpv-page {
    max-width: 920px;
    margin: 4px auto 28px;
}
.rpv-toolbar .btn {
    border-radius: 8px;
    font-weight: 600;
    font-size: 12.5px;
    padding: 6px 12px;
}
.rpv-toolbar .btn-primary {
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.12);
}
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
.rpv-page .rpv-doc { max-width: none; }
.rpv-doc * { box-sizing: border-box; }
.rpv-accent {
    height: {{ $isPdf ? '4px' : '5px' }};
    background: var(--rpv-blue);
}
.rpv-sheet { padding: {{ $isPdf ? '14px 16px 16px' : '26px 30px 24px' }}; }
.rpv-hdr { width: 100%; border-collapse: collapse; margin: 0 0 {{ $isPdf ? '14px' : '18px' }}; }
.rpv-hdr td {
    border: none;
    vertical-align: middle;
    padding: 0 0 {{ $isPdf ? '12px' : '16px' }};
    border-bottom: 2px solid var(--rpv-blue);
}
.rpv-hdr-logo { width: 28%; text-align: left; }
.rpv-hdr-brand { width: 44%; text-align: center; padding-left: 8px; padding-right: 8px; }
.rpv-hdr-right { width: 28%; text-align: right; }
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
    font-size: {{ $isPdf ? '17px' : '20px' }};
    font-weight: 700;
    color: var(--rpv-blue);
    line-height: 1.2;
    letter-spacing: -0.01em;
    text-align: center;
}
.rpv-title {
    display: block;
    margin-top: 6px;
    font-size: {{ $isPdf ? '10px' : '11px' }};
    letter-spacing: 0.16em;
    color: var(--rpv-blue);
    font-weight: 700;
    text-transform: uppercase;
    text-align: center;
}
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
.rpv-split { width: 100%; border-collapse: separate; border-spacing: 0; margin: 0 0 {{ $isPdf ? '12px' : '14px' }}; table-layout: fixed; }
.rpv-split-cell { width: 50%; vertical-align: top; border: none; padding: 0; }
.rpv-split-cell:first-child { padding-right: {{ $isPdf ? '6px' : '8px' }}; }
.rpv-split-cell:last-child { padding-left: {{ $isPdf ? '6px' : '8px' }}; }
.rpv-card {
    background: var(--rpv-soft);
    border: 1px solid var(--rpv-line);
    border-top: 3px solid var(--rpv-blue);
    border-radius: {{ $isPdf ? '0' : '8px' }};
    padding: {{ $isPdf ? '2px 12px 4px' : '4px 14px 6px' }};
}
.rpv-kv { width: 100%; border-collapse: collapse; table-layout: fixed; }
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
    word-break: break-word;
}
.rpv-kv .v-link { color: var(--rpv-blue); }
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
.rpv-box-body { font-size: {{ $isPdf ? '11px' : '13px' }}; color: #334155; white-space: pre-wrap; }
.rpv-table-wrap {
    border: 1px solid var(--rpv-line);
    border-top: 3px solid var(--rpv-blue);
    border-radius: {{ $isPdf ? '0' : '8px' }};
    overflow: hidden;
    margin: 0 0 {{ $isPdf ? '10px' : '4px' }};
}
.rpv-table {
    width: 100%;
    border-collapse: collapse;
    margin: 0;
    font-size: {{ $isPdf ? '10px' : '12.5px' }};
}
.rpv-table th {
    background: var(--rpv-soft);
    color: #0f172a;
    border: none;
    border-bottom: 1px solid var(--rpv-line);
    padding: {{ $isPdf ? '7px 6px' : '10px 10px' }};
    text-align: center;
    font-weight: 700;
    font-size: {{ $isPdf ? '9px' : '10.5px' }};
    letter-spacing: 0.04em;
    text-transform: uppercase;
}
.rpv-table td {
    border: none;
    border-bottom: 1px solid #e8eef5;
    padding: {{ $isPdf ? '6px' : '9px 10px' }};
    vertical-align: middle;
    color: #0f172a;
}
.rpv-table tbody tr:last-child td { border-bottom: none; }
.rpv-table tbody tr:nth-child(even) td { background: #fafbfc; }
.rpv-table .num { text-align: center; white-space: nowrap; font-variant-numeric: tabular-nums; }
.rpv-table .pay { font-weight: 700; }
.rpv-table tfoot td {
    background: var(--rpv-soft);
    font-weight: 700;
    color: var(--rpv-blue);
    border-top: 1px solid var(--rpv-line);
    border-bottom: none;
    padding: {{ $isPdf ? '7px 6px' : '10px' }};
}
.rpv-table tfoot td.rpv-allocated {
    text-align: right !important;
}
.rpv-table .empty { text-align: center; color: #94a3b8; }
.rpv-bottom { width: 100%; border-collapse: collapse; margin-top: {{ $isPdf ? '10px' : '14px' }}; }
.rpv-bottom td { border: none; vertical-align: top; padding: 0; }
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
.rpv-summary .k { color: #64748b; font-weight: 500; text-align: left; }
.rpv-summary .v { font-weight: 700; text-align: right; white-space: nowrap; font-variant-numeric: tabular-nums; }
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
.rpv-meta-box a { color: var(--rpv-blue); font-weight: 600; text-decoration: none; }
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
    .rpv-toolbar { display: none !important; }
    .rpv-page { max-width: none; margin: 0; }
    .rpv-doc { box-shadow: none !important; border: none !important; max-width: none; border-radius: 0; }
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
                                <td class="k">Payment Date:</td>
                                <td class="v">{{ $paymentDate }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payee (Rider):</td>
                                <td class="v v-link">{{ $payeeName }}</td>
                            </tr>
                            <tr>
                                <td class="k">Rider Code:</td>
                                <td class="v">{{ $riderCode }}</td>
                            </tr>
                            <tr>
                                <td class="k">Billing Month:</td>
                                <td class="v">{{ $billingMonth }}</td>
                            </tr>
                            <tr>
                                <td class="k">Paid By (Account):</td>
                                <td class="v">{{ $paidBy }}</td>
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
                                <td class="k">Voucher No.:</td>
                                <td class="v">{{ $voucherNo }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payment Mode:</td>
                                <td class="v">{{ $paymentMode }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payment Type:</td>
                                <td class="v">{{ $paymentType }}</td>
                            </tr>
                            <tr>
                                <td class="k">Payment Amount:</td>
                                <td class="v">{{ $money($paymentAmount) }}</td>
                            </tr>
                            <tr>
                                <td class="k">Bank Charges:</td>
                                <td class="v">{{ $money($bankCharges) }}</td>
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
                        <td colspan="4" class="rpv-allocated">
                            Total Allocated: {{ number_format((float) $allocatedTotal, 2) }}
                        </td>
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
                        <tr>
                            <td class="k">Difference:</td>
                            <td class="v">{{ $money($difference) }}</td>
                        </tr>
                        <tr>
                            <td class="k">Bank Charges:</td>
                            <td class="v">{{ $money($bankCharges) }}</td>
                        </tr>
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

        {{-- Screen-only: posted ledger lines (hidden for print + PDF) --}}
        @if(!$isPdf && !request()->boolean('print'))
            @include('vouchers.partials.posted_transactions', [
                'transactions' => $postedTransactions ?? collect(),
                'voucherType' => $payment->voucher->voucher_type ?? 'PV',
                'showPostedTransactions' => true,
            ])
        @endif
    </div>
</div>
