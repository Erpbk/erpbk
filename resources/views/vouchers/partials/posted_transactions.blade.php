@php
/**
 * Posted Transactions (screen view only — hidden from print/PDF).
 *
 * @var \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $transactions
 * @var object|null $finDetail Optional RTA fine detail for RFV narration enrichment
 * @var string|null $voucherType
 * @var bool $showPostedTransactions Force hide when false
 */
$showPostedTransactions = $showPostedTransactions ?? true;
$transactions = collect($transactions ?? []);
$voucherType = $voucherType ?? null;
$finDetail = $finDetail ?? ($fin_detail ?? null);

$totalDebit = 0.0;
$totalCredit = 0.0;
@endphp

@if($showPostedTransactions && $transactions->isNotEmpty())
<div class="voucher-posted-transactions no-print">
    <div class="vpt-header">Posted Transactions</div>
    <div class="table-responsive">
        <table class="vpt-table">
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
                    <td colspan="2" class="text-end vpt-total-label">Total:</td>
                    <td class="text-end vpt-total-val">{{ number_format($totalDebit, 2) }}</td>
                    <td class="text-end vpt-total-val">{{ number_format($totalCredit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<style>
.voucher-posted-transactions {
    --vpt-blue: #1e4b8f;
    --vpt-line: #c5d4eb;
    margin-top: 1.25rem;
    margin-bottom: 0.5rem;
}
.voucher-posted-transactions .vpt-header {
    color: var(--vpt-blue);
    font-weight: 700;
    font-size: 1rem;
    margin-bottom: 0.45rem;
    padding-bottom: 0.35rem;
    border-bottom: 2px solid var(--vpt-blue);
}
.voucher-posted-transactions .vpt-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.88rem;
    color: #0f172a;
    margin-bottom: 0;
}
.voucher-posted-transactions .vpt-table thead th {
    background: var(--vpt-blue);
    color: #fff;
    font-weight: 700;
    font-size: 0.78rem;
    letter-spacing: 0.02em;
    padding: 0.55rem 0.75rem;
    border: 1px solid var(--vpt-blue);
    white-space: nowrap;
}
.voucher-posted-transactions .vpt-table tbody td,
.voucher-posted-transactions .vpt-table tfoot td {
    padding: 0.55rem 0.75rem;
    border-bottom: 1px solid #e2e8f0;
    vertical-align: top;
}
.voucher-posted-transactions .vpt-table tbody tr:last-child td {
    border-bottom: 1px solid #cbd5e1;
}
.voucher-posted-transactions .vpt-narration {
    color: #334155;
    word-break: break-word;
}
.voucher-posted-transactions .vpt-table tfoot td {
    border-top: 1px solid #94a3b8;
    border-bottom: 1px solid #94a3b8;
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
.voucher-posted-transactions .vpt-total-label,
.voucher-posted-transactions .vpt-total-val {
    font-weight: 700;
    color: #0f172a;
}
@media print {
    .voucher-posted-transactions,
    .no-print.voucher-posted-transactions {
        display: none !important;
    }
}
</style>
@endif
