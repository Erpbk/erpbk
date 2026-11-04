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

@php
    $documentBody = !empty($isVisaExpenseDocument)
        ? 'vouchers.partials.visa_expense_voucher_document_body'
        : 'vouchers.partials.rider_voucher_document_body';
@endphp
@if($isPrint)
    @include($documentBody, array_merge($doc ?? [], ['isPdf' => false]))
    <script>window.onload = function () { window.print(); };</script>
@else
    @if(!empty($voucher))
        <div class="d-flex flex-wrap align-items-center gap-2 mb-3 no-print">
            <x-voucher-approval.status-badge :voucher="$voucher" />
            <x-voucher-approval.panel :voucher="$voucher" />
        </div>
    @endif
    <div class="rpv-page">
        @include($documentBody, $doc ?? [])
    </div>
    <div id="voucher-panel-current"
        data-number="{{ $voucherNo ?? '' }}"
        data-amount="{{ isset($formatMoney) && is_callable($formatMoney) ? $formatMoney((float) ($amount ?? 0)) : \App\Helpers\Currency::format((float) ($amount ?? 0)) }}"
        style="display:none;"
        aria-hidden="true"></div>
@endif
