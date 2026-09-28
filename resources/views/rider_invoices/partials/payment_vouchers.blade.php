{{-- Payment vouchers linked to this rider invoice --}}
@php
    $paymentVouchers = collect($paymentVouchers ?? []);
    $__companySlug = \App\Support\CompanyRouteContext::slug();
@endphp
@if($paymentVouchers->isNotEmpty())
<div class="tbl-wrap" style="margin-top: 14px;">
    <table class="items-table">
        <tr>
            <th colspan="5" class="secondary-header">Payment Vouchers</th>
        </tr>
        <tr>
            <th class="secondary-header">Date</th>
            <th class="secondary-header">Voucher No</th>
            <th class="secondary-header">Type</th>
            <th class="secondary-header">Reference / Remarks</th>
            <th class="secondary-header">Amount</th>
        </tr>
        @foreach($paymentVouchers as $voucher)
        <tr>
            <td>{{ $voucher->trans_date ? \App\Helpers\Common::DateFormat($voucher->trans_date) : '—' }}</td>
            <td>
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
            <td>{{ $voucher->voucher_type ?: '—' }}</td>
            <td style="text-align: left;">
                {{ $voucher->reference_number ?: ($voucher->remarks ?: '—') }}
            </td>
            <td class="num">{{ number_format((float) $voucher->amount, 2) }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif
