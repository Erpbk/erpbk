@php
$__companySlug = \App\Support\CompanyRouteContext::slug();
$voucherId = $voucher->id ?? null;
$printUrl = route('vouchers.show', array_filter([
    'voucher' => $voucherId,
    'company_slug' => $__companySlug,
    'print' => 1,
]));
$isPrint = request()->boolean('print') || (bool) request()->get('print');
@endphp

@if($isPrint)
    @include('vouchers.partials.rider_voucher_document_body', array_merge($doc ?? [], ['isPdf' => false]))
    <script>window.onload = function () { window.print(); };</script>
@else
    <div class="rpv-page">
        @include('vouchers.partials.rider_voucher_document_body', $doc ?? [])
    </div>
    <div id="voucher-panel-current"
        data-number="{{ $voucherNo ?? '' }}"
        data-amount="{{ isset($formatMoney) && is_callable($formatMoney) ? $formatMoney((float) ($amount ?? 0)) : \App\Helpers\Currency::format((float) ($amount ?? 0)) }}"
        style="display:none;"
        aria-hidden="true"></div>
@endif
