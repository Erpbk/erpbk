{{-- Payment vouchers linked to this rider invoice --}}
@php
$paymentVouchers = collect($paymentVouchers ?? []);
$__companySlug = \App\Support\CompanyRouteContext::slug();
$invoiceDue = (float) ($finalAmount ?? $totalAmt ?? 0);
// Oldest first so running balance after each payment is correct
$vouchersOrdered = $paymentVouchers
->sortBy(function ($voucher) {
return ($voucher->trans_date ?? '1970-01-01').'-'.str_pad((string) $voucher->id, 10, '0', STR_PAD_LEFT);
})
->values();
$runningPaid = 0.0;
$balancesById = [];
foreach ($vouchersOrdered as $voucher) {
$runningPaid = round($runningPaid + (float) $voucher->amount, 2);
$balancesById[$voucher->id] = round($invoiceDue - $runningPaid, 2);
}
@endphp
@if($paymentVouchers->isNotEmpty())
<div class="tbl-wrap" style="margin-top: 14px;">
    <table class="items-table">
        <tr>
            <th class="secondary-header">Date</th>
            <th class="secondary-header">Voucher No</th>
            <th class="secondary-header">Type</th>
            <th class="secondary-header">Reference / Remarks</th>
            <th class="secondary-header">Paid Amount</th>
            <th class="secondary-header">Balance</th>
        </tr>
        @foreach($paymentVouchers as $voucher)
        <tr class="">
            <td style="text-align: center;">{{ $voucher->trans_date ? \App\Helpers\Common::DateFormat($voucher->trans_date) : '—' }}</td>
            <td style="text-align: center;">
                @if(empty($isPdf))
                <a href="javascript:void(0);"
                    class="show-modal"
                    data-size="xl"
                    data-title="Voucher {{ $voucher->formatted_id }}"
                    data-action="{{ route('vouchers.show', array_filter([
                        'voucher' => $voucher->id,
                        'company_slug' => $__companySlug,
                    ])) }}">
                    {{ $voucher->formatted_id }}
                </a>
                @else
                {{ $voucher->formatted_id }}
                @endif
            </td>
            <td style="text-align: center;">{{ $voucher->voucher_type ?: '—' }}</td>
            <td style="text-align: center;">
                {{ $voucher->reference_number ?: ($voucher->remarks ?: '—') }}
            </td>
            <td style="text-align: center;">{{ number_format((float) $voucher->amount, 2) }}</td>
            <td style="text-align: center;">{{ number_format((float) ($balancesById[$voucher->id] ?? ($rider_balance_final ?? 0)), 2) }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif