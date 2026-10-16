@php
$__companySlug = \App\Support\CompanyRouteContext::slug();
$paymentId = $payment->id;
$editUrl = route('payments.edit', array_filter(['payment' => $paymentId, 'company_slug' => $__companySlug]));
$cloneUrl = route('payments.clone', array_filter(['id' => $paymentId, 'company_slug' => $__companySlug]));
$pdfUrl = route('payments.pdf', array_filter(['id' => $paymentId, 'company_slug' => $__companySlug]));
$printUrl = route('payments.show', array_filter([
'payment' => $paymentId,
'company_slug' => $__companySlug,
'print' => 1,
]));
@endphp

@if(request()->boolean('print'))
@include('payments.partials.voucher_document_body')
<script>
    window.print();
</script>
@else
<div class="rpv-page">
    <div class="rpv-toolbar d-flex flex-wrap gap-2 mb-3 align-items-center justify-content-end">
        <a href="javascript:void(0);"
            class="btn btn-sm btn-outline-secondary show-modal"
            data-size="xl"
            data-title="Edit Payment"
            data-action="{{ $editUrl }}"
            data-collapse-sidebar="1">
            <i class="fa fa-pencil"></i> Edit
        </a>
        <a href="javascript:void(0);"
            class="btn btn-sm btn-outline-secondary show-modal"
            data-size="xl"
            data-title="Clone Payment"
            data-action="{{ $cloneUrl }}"
            data-collapse-sidebar="1">
            <i class="fa fa-copy"></i> Clone
        </a>
        <a href="{{ $printUrl }}" target="_blank" class="btn btn-sm btn-outline-secondary">
            <i class="fa fa-print"></i> Print
        </a>
        <a href="{{ $pdfUrl }}" class="btn btn-sm btn-primary">
            <i class="fa fa-file-pdf"></i> Download PDF
        </a>
    </div>

    @include('payments.partials.voucher_document_body')
</div>
@php
$panelNumber = $voucherNo ?? ($referenceNo ?? ('PV-'.($payment->voucher_id ?: $payment->id)));
$panelAmount = (isset($totalPayment) && isset($formatMoney) && is_callable($formatMoney))
? $formatMoney((float) $totalPayment)
: \App\Helpers\Currency::format((float) ($payment->amount ?? 0));
@endphp
<div id="voucher-panel-current"
    data-number="{{ $panelNumber }}"
    data-amount="{{ $panelAmount }}"
    style="display:none;"
    aria-hidden="true"></div>
@endif