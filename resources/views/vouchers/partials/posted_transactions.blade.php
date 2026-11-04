@php
/**
 * Posted Transactions table (Account / Narration / Debit / Credit).
 *
 * By default hidden from print/PDF (payment & rider documents already show
 * formatted amounts). Pass includeInPrint=true for classic voucher print
 * (JV, EXP, etc.) where this table is the document body.
 *
 * @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $transactions
 * @var object|null $finDetail Optional RTA fine detail for RFV narration enrichment
 * @var string|null $voucherType
 * @var bool $showPostedTransactions Force hide when false
 * @var bool $includeInPrint When true, render on print/PDF as well
 */
$showPostedTransactions = $showPostedTransactions ?? true;
$includeInPrint = !empty($includeInPrint);
$transactions = collect($transactions ?? []);
$voucherType = $voucherType ?? null;
$finDetail = $finDetail ?? ($fin_detail ?? null);

$totalDebit = 0.0;
$totalCredit = 0.0;
$currencyMark = \App\Helpers\Currency::mark();
@endphp

@if($showPostedTransactions && $transactions->isNotEmpty())
<div class="voucher-posted-transactions{{ $includeInPrint ? ' vpt-print-ready' : ' no-print' }}">
    <div class="vpt-header">Posted Transactions</div>
    <div class="table-responsive">
        <table class="vpt-table">
            <colgroup>
                <col class="col-account">
                <col class="col-narration">
                <col class="col-debit">
                <col class="col-credit">
            </colgroup>
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Narration</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $item)
                @php
                    $debit = (float) ($item->debit ?? 0);
                    $credit = (float) ($item->credit ?? 0);
                    $totalDebit += $debit;
                    $totalCredit += $credit;
                    $accountLabel = $item->account
                        ? trim(($item->account->account_code ? $item->account->account_code . ' - ' : '') . ($item->account->name ?? ''))
                        : '—';
                @endphp
                <tr>
                    <td>{{ $accountLabel !== '' ? $accountLabel : '—' }}</td>
                    <td class="vpt-narration">
                        @if($voucherType === 'RFV' && $finDetail)
                            {!! $item->narration !!}
                            <strong>Ticket No:</strong> {{ $finDetail->ticket_no ?? '' }},
                            <strong>Bike:</strong> {{ $finDetail->plate_no ?? '' }}
                            @if(!empty($finDetail->trip_date))
                                {{ \Carbon\Carbon::parse($finDetail->trip_date)->format('d M Y') }}
                            @endif
                        @else
                            {!! $item->narration ?: '—' !!}
                        @endif
                    </td>
                    <td class="text-end">{{ $debit > 0.00001 ? number_format($debit, 2) : '' }}</td>
                    <td class="text-end">{{ $credit > 0.00001 ? number_format($credit, 2) : '' }}</td>
                </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2" class="text-end vpt-total-label">Total</td>
                    <td class="text-end vpt-total-val"><span class="vpt-currency">{!! $currencyMark !!}</span>{{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end vpt-total-val"><span class="vpt-currency">{!! $currencyMark !!}</span>{{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
.voucher-posted-transactions {
    --vpt-blue: #1e4b8f;
    --vpt-soft: #eef4fc;
    --vpt-line: #c5d4eb;
    --vpt-radius: 10px;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
    -webkit-print-color-adjust: exact;
    print-color-adjust: exact;
}
.voucher-posted-transactions .vpt-header {
    color: var(--vpt-blue);
    font-weight: 700;
    font-size: 0.82rem;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    margin-bottom: 0.55rem;
    padding-bottom: 0;
    border-bottom: none;
}
.voucher-posted-transactions .table-responsive {
    border: 1px solid var(--vpt-line);
    border-radius: var(--vpt-radius);
    overflow: hidden;
    background: #fff;
}
.voucher-posted-transactions .vpt-table {
    width: 100%;
    border-collapse: collapse;
    table-layout: fixed;
    font-size: 0.88rem;
    color: #0f172a;
    margin-bottom: 0;
    border: none;
}
.voucher-posted-transactions .vpt-table col.col-account { width: 34%; }
.voucher-posted-transactions .vpt-table col.col-narration { width: 40%; }
.voucher-posted-transactions .vpt-table col.col-debit,
.voucher-posted-transactions .vpt-table col.col-credit { width: 13%; }
.voucher-posted-transactions .vpt-table thead th {
    background: var(--vpt-blue) !important;
    color: #ffffff !important;
    font-weight: 700;
    font-size: 0.72rem;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    padding: 0.65rem 0.75rem;
    border: none;
    white-space: nowrap;
    text-align: left;
    -webkit-print-color-adjust: exact !important;
    print-color-adjust: exact !important;
}
.voucher-posted-transactions .vpt-table thead th.text-end {
    text-align: right;
}
.voucher-posted-transactions .vpt-table tbody td,
.voucher-posted-transactions .vpt-table tfoot td {
    padding: 0.55rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
    background: #fff;
}
.voucher-posted-transactions .vpt-table tbody tr:nth-child(even) td {
    background: #f8fafc;
}
.voucher-posted-transactions .vpt-table tbody tr:last-child td {
    border-bottom: 1px solid #cbd5e1;
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
    border-top: 2px solid var(--vpt-blue);
    border-bottom: none;
    background: var(--vpt-soft) !important;
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
.voucher-posted-transactions .vpt-total-label,
.voucher-posted-transactions .vpt-total-val {
    font-weight: 800;
    color: #0f172a;
}
.voucher-posted-transactions .vpt-currency {
    display: inline-flex;
    align-items: center;
    margin-right: 0.28em;
    font-size: 0.9em;
    font-weight: 700;
    color: inherit;
    vertical-align: baseline;
}
.voucher-posted-transactions .vpt-currency .app-currency-icon {
    display: inline-block;
    height: 0.9em;
    width: auto;
    max-width: 1.4em;
    object-fit: contain;
    vertical-align: -0.12em;
}
@if($includeInPrint)
@media print {
    .voucher-posted-transactions.vpt-print-ready,
    .voucher-posted-transactions.vpt-print-ready * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
    }
    .voucher-posted-transactions.vpt-print-ready .table-responsive {
        border-radius: var(--vpt-radius) !important;
        overflow: hidden;
    }
    .voucher-posted-transactions.vpt-print-ready .vpt-table thead th {
        background: var(--vpt-blue) !important;
        color: #ffffff !important;
    }
    .voucher-posted-transactions.vpt-print-ready .vpt-table tbody tr:nth-child(even) td {
        background: #f8fafc !important;
    }
    .voucher-posted-transactions.vpt-print-ready .vpt-table tfoot td {
        background: var(--vpt-soft) !important;
    }
}
@else
@media print {
    .voucher-posted-transactions,
    .no-print.voucher-posted-transactions {
        display: none !important;
    }
}
@endif
</style>
@endif
