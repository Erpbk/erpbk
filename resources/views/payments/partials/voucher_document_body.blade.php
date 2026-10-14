@php
    $isPdf = !empty($isPdf);
    $brand = $brand ?? [];
    $blue = $brand['primary_color'] ?? '#1e4b8f';
    $blueSoft = $brand['primary_soft'] ?? '#eef4fc';
    $blueLine = $brand['border_color'] ?? '#c5d8f0';
    $money = $formatMoney ?? static fn ($n) => ($currencyCode ?? '').' '.number_format((float) $n, 2);
    $showNotes = !empty($settings['show_internal_notes']) && trim((string) ($internalNotes ?? '')) !== '';
    $showSignature = !empty($settings['show_signature']);
@endphp

<style>
.rpv-doc {
    --rpv-blue: {{ $blue }};
    --rpv-soft: {{ $blueSoft }};
    --rpv-line: {{ $blueLine }};
    font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
    color: #0f172a;
    max-width: 920px;
    margin: 0 auto;
    background: #fff;
}
.rpv-doc * { box-sizing: border-box; }
.rpv-sheet { padding: {{ $isPdf ? '12px 14px' : '24px 28px 20px' }}; }
.rpv-hdr {
    display: {{ $isPdf ? 'table' : 'flex' }};
    width: 100%;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 18px;
    border-bottom: 2px solid var(--rpv-line);
    padding-bottom: 14px;
}
.rpv-hdr-left { {{ $isPdf ? 'display:table-cell;width:68%;vertical-align:top;' : '' }} }
.rpv-hdr-right { {{ $isPdf ? 'display:table-cell;width:32%;vertical-align:top;text-align:right;' : 'text-align:right;' }} }
.rpv-company {
    font-size: {{ $isPdf ? '18px' : '22px' }};
    font-weight: 700;
    color: var(--rpv-blue);
    line-height: 1.25;
}
.rpv-title {
    margin-top: 4px;
    font-size: {{ $isPdf ? '11px' : '12px' }};
    letter-spacing: 0.08em;
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
}
.rpv-ref-label {
    font-size: 11px;
    color: #64748b;
    margin-bottom: 4px;
}
.rpv-ref-box {
    display: inline-block;
    min-width: 140px;
    border: 1px solid var(--rpv-line);
    background: var(--rpv-soft);
    padding: 8px 12px;
    font-weight: 700;
    color: var(--rpv-blue);
    text-align: center;
}
.rpv-grid {
    width: 100%;
    margin: 12px 0 16px;
    {{ $isPdf ? 'border-collapse:collapse;' : 'display:grid;grid-template-columns:1fr 1fr;gap:10px 28px;' }}
}
.rpv-grid-table { width: 100%; border-collapse: collapse; }
.rpv-grid-table td { width: 50%; vertical-align: top; padding: 0 12px 0 0; }
.rpv-kv { margin-bottom: 8px; font-size: {{ $isPdf ? '11px' : '13px' }}; }
.rpv-kv .k { color: #64748b; display: inline-block; min-width: 120px; }
.rpv-kv .v { color: #0f172a; font-weight: 600; }
.rpv-kv .v-link { color: var(--rpv-blue); }
.rpv-box {
    border: 1px solid var(--rpv-line);
    border-radius: 6px;
    padding: 10px 12px;
    margin: 12px 0;
    background: #f8fafc;
}
.rpv-box-title {
    font-size: 11px;
    font-weight: 700;
    color: var(--rpv-blue);
    margin-bottom: 4px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}
.rpv-box-body { font-size: {{ $isPdf ? '11px' : '13px' }}; color: #334155; white-space: pre-wrap; }
.rpv-table {
    width: 100%;
    border-collapse: collapse;
    margin: 14px 0 8px;
    font-size: {{ $isPdf ? '10px' : '12px' }};
}
.rpv-table th {
    background: var(--rpv-soft);
    color: var(--rpv-blue);
    border: 1px solid var(--rpv-line);
    padding: 8px 6px;
    text-align: left;
    font-weight: 700;
}
.rpv-table td {
    border: 1px solid #e2e8f0;
    padding: 7px 6px;
    vertical-align: middle;
}
.rpv-table .num { text-align: right; white-space: nowrap; }
.rpv-table tfoot td {
    background: var(--rpv-soft);
    font-weight: 700;
    color: var(--rpv-blue);
}
.rpv-bottom {
    width: 100%;
    margin-top: 10px;
    {{ $isPdf ? '' : 'display:flex;justify-content:flex-end;' }}
}
.rpv-summary {
    width: {{ $isPdf ? '45%' : '280px' }};
    {{ $isPdf ? 'margin-left:auto;' : '' }}
    font-size: {{ $isPdf ? '11px' : '13px' }};
}
.rpv-summary-row {
    display: {{ $isPdf ? 'table' : 'flex' }};
    width: 100%;
    justify-content: space-between;
    padding: 4px 0;
    border-bottom: 1px solid #eef2f7;
}
.rpv-summary-row span:first-child { {{ $isPdf ? 'display:table-cell;width:55%;' : '' }} color: #64748b; }
.rpv-summary-row span:last-child { {{ $isPdf ? 'display:table-cell;width:45%;text-align:right;' : '' }} font-weight: 600; text-align: right; }
.rpv-summary-total {
    margin-top: 6px;
    background: var(--rpv-soft);
    border: 1px solid var(--rpv-line);
    border-radius: 4px;
    padding: 8px 10px;
    font-weight: 700;
    color: var(--rpv-blue);
    display: {{ $isPdf ? 'table' : 'flex' }};
    width: 100%;
    justify-content: space-between;
}
.rpv-summary-total span { {{ $isPdf ? 'display:table-cell;' : '' }} }
.rpv-summary-total span:last-child { {{ $isPdf ? 'text-align:right;' : '' }} }
.rpv-meta-box {
    border: 1px solid var(--rpv-line);
    border-radius: 6px;
    padding: 10px 12px;
    margin-top: 14px;
    font-size: {{ $isPdf ? '11px' : '12px' }};
}
.rpv-meta-box strong { color: var(--rpv-blue); }
.rpv-sigs {
    margin-top: 36px;
    width: 100%;
    {{ $isPdf ? 'border-collapse:collapse;' : 'display:flex;gap:40px;justify-content:space-between;' }}
}
.rpv-sig {
    {{ $isPdf ? 'display:table-cell;width:48%;padding:0 12px;text-align:center;vertical-align:bottom;' : 'flex:1;text-align:center;' }}
}
.rpv-sig-line {
    border-top: 1px solid #94a3b8;
    margin: 48px 12px 8px;
}
.rpv-sig-label {
    font-size: 12px;
    font-weight: 600;
    color: #475569;
}
@media print {
    .rpv-toolbar { display: none !important; }
    .rpv-doc { box-shadow: none !important; max-width: none; }
}
</style>

<div class="rpv-doc">
    <div class="rpv-sheet">
        <div class="rpv-hdr">
            <div class="rpv-hdr-left">
                <div class="rpv-company">{{ $companyName }}</div>
                <div class="rpv-title">{{ $documentTitle }}</div>
            </div>
            <div class="rpv-hdr-right">
                <div class="rpv-ref-label">Reference No.</div>
                <div class="rpv-ref-box">{{ $referenceNo }}</div>
            </div>
        </div>

        @if($isPdf)
            <table class="rpv-grid-table" cellpadding="0" cellspacing="0">
                <tr>
                    <td>
                        <div class="rpv-kv"><span class="k">Payment Date:</span> <span class="v">{{ $paymentDate }}</span></div>
                        <div class="rpv-kv"><span class="k">Payee (Rider):</span> <span class="v v-link">{{ $payeeName }}</span></div>
                        <div class="rpv-kv"><span class="k">Rider Code:</span> <span class="v">{{ $riderCode }}</span></div>
                        <div class="rpv-kv"><span class="k">Billing Month:</span> <span class="v">{{ $billingMonth }}</span></div>
                        <div class="rpv-kv"><span class="k">Paid By:</span> <span class="v">{{ $paidBy }}</span></div>
                    </td>
                    <td>
                        <div class="rpv-kv"><span class="k">Reference:</span> <span class="v">{{ $referenceNo }}</span></div>
                        <div class="rpv-kv"><span class="k">Payment Mode:</span> <span class="v">{{ $paymentMode }}</span></div>
                        <div class="rpv-kv"><span class="k">Payment Type:</span> <span class="v">{{ $paymentType }}</span></div>
                        <div class="rpv-kv"><span class="k">Payment Amount:</span> <span class="v">{{ $money($paymentAmount) }}</span></div>
                        <div class="rpv-kv"><span class="k">Bank Charges:</span> <span class="v">{{ $money($bankCharges) }}</span></div>
                    </td>
                </tr>
            </table>
        @else
            <div class="rpv-grid">
                <div>
                    <div class="rpv-kv"><span class="k">Payment Date:</span> <span class="v">{{ $paymentDate }}</span></div>
                    <div class="rpv-kv"><span class="k">Payee (Rider):</span> <span class="v v-link">{{ $payeeName }}</span></div>
                    <div class="rpv-kv"><span class="k">Rider Code:</span> <span class="v">{{ $riderCode }}</span></div>
                    <div class="rpv-kv"><span class="k">Billing Month:</span> <span class="v">{{ $billingMonth }}</span></div>
                    <div class="rpv-kv"><span class="k">Paid By:</span> <span class="v">{{ $paidBy }}</span></div>
                </div>
                <div>
                    <div class="rpv-kv"><span class="k">Reference:</span> <span class="v">{{ $referenceNo }}</span></div>
                    <div class="rpv-kv"><span class="k">Payment Mode:</span> <span class="v">{{ $paymentMode }}</span></div>
                    <div class="rpv-kv"><span class="k">Payment Type:</span> <span class="v">{{ $paymentType }}</span></div>
                    <div class="rpv-kv"><span class="k">Payment Amount:</span> <span class="v">{{ $money($paymentAmount) }}</span></div>
                    <div class="rpv-kv"><span class="k">Bank Charges:</span> <span class="v">{{ $money($bankCharges) }}</span></div>
                </div>
            </div>
        @endif

        <div class="rpv-box">
            <div class="rpv-box-title">Narration / Description</div>
            <div class="rpv-box-body">{{ $narration }}</div>
        </div>

        <table class="rpv-table">
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Billing Month</th>
                    <th class="num">Total Amount</th>
                    <th class="num">Previously Paid</th>
                    <th class="num">Balance Due</th>
                    <th class="num">Payment</th>
                </tr>
            </thead>
            <tbody>
                @forelse($allocations as $row)
                    <tr>
                        <td>{{ $row['invoice_number'] }}</td>
                        <td>{{ $row['billing_month'] }}</td>
                        <td class="num">{{ $money($row['total']) }}</td>
                        <td class="num">{{ $money($row['previously_paid']) }}</td>
                        <td class="num">{{ $money($row['balance_due']) }}</td>
                        <td class="num">{{ $money($row['payment']) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" style="text-align:center;color:#94a3b8;">No invoice allocation</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" style="text-align:right;">Total Allocated:</td>
                    <td class="num">{{ $money($allocatedTotal) }}</td>
                </tr>
            </tfoot>
        </table>

        <div class="rpv-bottom">
            <div class="rpv-summary">
                <div class="rpv-summary-row"><span>Payment Amount:</span><span>{{ $money($paymentAmount) }}</span></div>
                <div class="rpv-summary-row"><span>Invoice Allocation:</span><span>{{ $money($allocatedTotal) }}</span></div>
                <div class="rpv-summary-row"><span>Difference:</span><span>{{ $money($difference) }}</span></div>
                <div class="rpv-summary-row"><span>Bank Charges:</span><span>{{ $money($bankCharges) }}</span></div>
                <div class="rpv-summary-total"><span>Total Payment:</span><span>{{ $money($totalPayment) }}</span></div>
            </div>
        </div>

        <div class="rpv-meta-box">
            <div><strong>Amount in Words:</strong> {{ $amountInWords }}</div>
        </div>
        <div class="rpv-meta-box">
            <div><strong>Attachments:</strong>
                @if(!empty($attachmentUrl))
                    @if($isPdf)
                        {{ $attachmentName ?? 'Attachment' }}
                    @else
                        <a href="{{ $attachmentUrl }}" target="_blank" rel="noopener">{{ $attachmentName ?? 'View attachment' }}</a>
                    @endif
                @else
                    No attachment
                @endif
            </div>
        </div>

        @if($showNotes)
            <div class="rpv-box">
                <div class="rpv-box-title">{{ $settings['internal_notes_label'] ?? 'Internal Notes' }}</div>
                <div class="rpv-box-body">{{ $internalNotes }}</div>
            </div>
        @endif

        @if($showSignature)
            @if($isPdf)
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
            @else
                <div class="rpv-sigs">
                    <div class="rpv-sig">
                        <div class="rpv-sig-line"></div>
                        <div class="rpv-sig-label">{{ $settings['payer_signature_label'] ?? 'Paid By' }}</div>
                    </div>
                    <div class="rpv-sig">
                        <div class="rpv-sig-line"></div>
                        <div class="rpv-sig-label">{{ $settings['rider_signature_label'] ?? 'Received By' }}</div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
