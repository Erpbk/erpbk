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
@php
    $approvalVoucher = $payment->voucher
        ?? (!empty($payment->voucher_id) ? \App\Models\Vouchers::query()->with(['approvalEvents', 'approvedByUser', 'rejectedByUser'])->find($payment->voucher_id) : null);
@endphp
<div class="rpv-page">
    <div class="rpv-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom no-print">
        <div class="d-flex align-items-center gap-2">
            @if($approvalVoucher)
                <x-voucher-approval.status-badge :voucher="$approvalVoucher" />
            @endif
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            <a href="javascript:void(0);"
                class="btn btn-sm btn-outline-primary show-modal"
                data-size="xl"
                data-title="Edit Payment"
                data-action="{{ $editUrl }}"
                data-collapse-sidebar="1">
                <i class="ti ti-edit me-1"></i> Edit
            </a>
            @if($approvalVoucher)
                <x-voucher-approval.panel :voucher="$approvalVoucher" />
            @endif
            <a href="javascript:void(0);"
                class="btn btn-sm btn-outline-primary show-modal"
                data-size="xl"
                data-title="Clone Payment"
                data-action="{{ $cloneUrl }}"
                data-collapse-sidebar="1">
                <i class="ti ti-copy me-1"></i> Clone
            </a>
            <a href="{{ $printUrl }}" target="_blank" class="btn btn-sm btn-outline-primary" rel="noopener">
                <i class="ti ti-printer me-1"></i> Print
            </a>
            <a href="{{ $pdfUrl }}" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-file-description me-1"></i> Download PDF
            </a>
        </div>
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