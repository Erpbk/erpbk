@php
$__companySlug = \App\Support\CompanyRouteContext::slug();
$voucherId = $voucher->id ?? null;
$voucherPendingDeletion = !empty($voucher) ? record_is_pending_deletion($voucher) : false;
$moduleActions = !empty($voucher)
    ? \App\Support\VoucherModuleActions::forVoucher($voucher, $__companySlug)
    : null;
$editDeleteFlags = $editDeleteFlags ?? \App\Models\VoucherType::getEditDeleteFlagsByModule('vouchers');
$printUrl = route('vouchers.show', array_filter([
    'voucher' => $voucherId,
    'company_slug' => $__companySlug,
    'print' => 1,
]));
$pdfUrl = $printUrl;
$isPrint = request()->boolean('print') || (bool) request()->get('print');
$moduleBadge = $moduleBadge
    ?? (($riderModuleLabel ?? \App\Models\Settings::getMenuLabel('riders') ?: 'Riders').' - '.($voucher->voucher_type ?? ''));
$voucherCloneParams = static function ($transCode) use ($__companySlug): array {
    $params = ['id' => $transCode];
    if (!empty($__companySlug)) {
        $params['company_slug'] = $__companySlug;
    }
    return $params;
};
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
    <div class="rpv-toolbar d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom no-print">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h5 class="mb-0 fw-bold">{{ $voucherNo ?? '' }}</h5>
            <span class="badge bg-label-primary">{{ $moduleBadge }}</span>
            @if($voucherPendingDeletion)
                @include('delete_requests._pending_badge', ['model' => $voucher])
            @endif
            <x-voucher-approval.status-badge :voucher="$voucher" />
        </div>
        <div class="d-flex flex-wrap align-items-center gap-2">
            @if($voucherPendingDeletion)
                <span class="text-muted small"><i class="ti ti-lock me-1"></i>Locked — awaiting delete approval</span>
            @else
                @canany(['vouchers_edit', 'voucher_edit'])
                @if($moduleActions && !empty($editDeleteFlags[$voucher->voucher_type]['can_edit']))
                <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary show-modal"
                   data-size="{{ $moduleActions['edit_size'] }}"
                   data-title="{{ $moduleActions['edit_title'] }}"
                   data-action="{{ $moduleActions['edit_url'] }}"
                   data-collapse-sidebar="1">
                    <i class="ti ti-edit me-1"></i> Edit
                </a>
                @endif
                @endcanany
                <x-voucher-approval.panel :voucher="$voucher" />
                @canany(['vouchers_create', 'voucher_create'])
                @if(!in_array($voucher->voucher_type, ['VL', 'LV', 'RFV', 'SV', 'RI'], true))
                <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary show-modal"
                   data-size="xl"
                   data-title="Clone Voucher {{ $voucherNo ?? '' }}"
                   data-action="{{ route('vouchers.clone', $voucherCloneParams($voucher->trans_code)) }}"
                   data-collapse-sidebar="1">
                    <i class="ti ti-copy me-1"></i> Clone
                </a>
                @endif
                @endcanany
                @canany(['vouchers_delete', 'voucher_delete'])
                @if($moduleActions && !empty($editDeleteFlags[$voucher->voucher_type]['can_delete']))
                <button type="button" class="btn btn-sm btn-outline-danger" onclick='confirmDelete(@json($moduleActions['delete_url']))'>
                    <i class="fa fa-trash my-1"></i> Delete
                </button>
                @endif
                @endcanany
            @endif
            <a href="{{ $printUrl }}" target="_blank" class="btn btn-sm btn-outline-primary" rel="noopener">
                <i class="ti ti-printer me-1"></i> Print
            </a>
            <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-sm btn-outline-primary" rel="noopener">
                <i class="ti ti-file-description me-1"></i> PDF
            </a>
            @can('email_create')
            <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary" rel="noopener">
                <i class="ti ti-mail me-1"></i> Email
            </a>
            @endcan
        </div>
    </div>
    @endif
    <div class="rpv-page">
        @include($documentBody, $doc ?? [])
    </div>
    <div id="voucher-panel-current"
        data-number="{{ $voucherNo ?? '' }}"
        data-amount="{{ \App\Helpers\Currency::formatPlain((float) ($amount ?? 0), 2) }}"
        style="display:none;"
        aria-hidden="true"></div>
    @include('vouchers.partials.doc_viewer')
@endif
