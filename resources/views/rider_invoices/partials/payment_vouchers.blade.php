{{-- Payment vouchers linked to this rider invoice --}}
@php
    $payment_vouchers = collect($payment_vouchers ?? []);
    $isPdf = $isPdf ?? false;
@endphp
@if($payment_vouchers->isNotEmpty())
<div class="payment-vouchers tbl-wrap" style="margin-top: 12px; margin-bottom: 12px;">
    <table class="items-table" style="margin-bottom: 0; width: 100%;">
        <tr>
            <th colspan="4" class="secondary-header">Payment Voucher{{ $payment_vouchers->count() > 1 ? 's' : '' }}</th>
        </tr>
        <tr class="light-header">
            <th style="width: 28%;">Voucher</th>
            <th style="width: 22%;">Date</th>
            <th style="width: 28%;">Type</th>
            <th style="width: 22%;" class="num">Amount</th>
        </tr>
        @foreach($payment_vouchers as $voucher)
        @php
            $voucherLabel = $voucher->formatted_id
                ?? (($voucher->voucher_type ?: 'V').'-'.str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT));
            $voucherDate = $voucher->trans_date
                ? \Carbon\Carbon::parse($voucher->trans_date)->format('d M Y')
                : '—';
            $voucherTypeLabel = $voucher->voucher_type ?: '—';
            if (class_exists(\App\Helpers\General::class) && method_exists(\App\Helpers\General::class, 'VoucherType')) {
                $voucherTypeLabel = \App\Helpers\General::VoucherType($voucher->voucher_type) ?: $voucherTypeLabel;
            }
        @endphp
        <tr>
            <td>
                @if(empty($isPdf) && Route::has('vouchers.show'))
                <a href="javascript:void(0);"
                   class="show-modal"
                   data-size="xl"
                   data-title="{{ $voucherLabel }}"
                   data-action="{{ route('vouchers.show', $voucher->id) }}"
                   style="font-weight: 700; color: var(--blue, #004aad); text-decoration: none;">
                    {{ $voucherLabel }}
                </a>
                @else
                <strong>{{ $voucherLabel }}</strong>
                @endif
                @if(!empty($voucher->reference_number))
                <div style="font-size: 11px; color: #64748b;">{{ $voucher->reference_number }}</div>
                @endif
            </td>
            <td>{{ $voucherDate }}</td>
            <td>{{ $voucherTypeLabel }}</td>
            <td class="num">{{ number_format((float) ($voucher->amount ?? 0), 2) }}</td>
        </tr>
        @endforeach
    </table>
</div>
@endif
