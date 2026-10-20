@php
$__companySlug = \App\Support\CompanyRouteContext::slug();
$voucherPendingDeletion = record_is_pending_deletion($voucher);
$voucherRouteParams = static function ($voucherKey) use ($__companySlug): array {
  $params = ['voucher' => $voucherKey];
  if (!empty($__companySlug)) {
    $params['company_slug'] = $__companySlug;
  }
  return $params;
};
$voucherCloneParams = static function ($transCode) use ($__companySlug): array {
  $params = ['id' => $transCode];
  if (!empty($__companySlug)) {
    $params['company_slug'] = $__companySlug;
  }
  return $params;
};
$moduleActions = \App\Support\VoucherModuleActions::forVoucher($voucher, $__companySlug);
$editDeleteFlags = $editDeleteFlags ?? \App\Models\VoucherType::getEditDeleteFlagsByModule('vouchers');

$riderDoc = app(\App\Services\Voucher\RiderModuleVoucherViewData::class);
$useRiderDocument = empty($visaCreditEditEmbed) && $riderDoc->supports($voucher);
$doc = $useRiderDocument ? $riderDoc->build($voucher) : null;
$voucher_number = $doc['voucherNo']
  ?? ($voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT));
@endphp
<div class="voucher-modal-content">
  @if(empty($visaCreditEditEmbed))
  <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3 pb-2 border-bottom no-print">
    <div class="d-flex align-items-center gap-2">
      @if($voucherPendingDeletion)
        @include('delete_requests._pending_badge', ['model' => $voucher])
      @else
        <span class="badge bg-label-success">Published</span>
      @endif
    </div>
    <div class="d-flex flex-wrap align-items-center gap-2">
      @if($voucherPendingDeletion)
        <span class="text-muted small"><i class="ti ti-lock me-1"></i>Locked — awaiting delete approval</span>
      @else
      @canany(['vouchers_edit', 'voucher_edit'])
      @if(!empty($editDeleteFlags[$voucher->voucher_type]['can_edit']))
      <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary show-modal" data-size="{{ $moduleActions['edit_size'] }}" data-title="{{ $moduleActions['edit_title'] }}" data-action="{{ $moduleActions['edit_url'] }}" data-collapse-sidebar="1"><i class="ti ti-edit me-1"></i> Edit</a>
      @endif
      @endcanany
      @canany(['vouchers_create', 'voucher_create'])
        @if(!in_array($voucher->voucher_type, ['VL', 'LV', 'RFV', 'SV', 'RI']))
          <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary show-modal" data-size="xl" data-title="Clone Voucher {{ $voucher_number }}" data-action="{{ route('vouchers.clone', $voucherCloneParams($voucher->trans_code)) }}" data-collapse-sidebar="1"><i class="ti ti-copy me-1"></i> Clone</a>
        @endif
      @endcanany
      @canany(['vouchers_delete', 'voucher_delete'])
      @if(!empty($editDeleteFlags[$voucher->voucher_type]['can_delete']))
        <button type="button" class="btn btn-sm btn-outline-danger" onclick='confirmDelete(@json($moduleActions['delete_url']))'>
          <i class="fa fa-trash my-1"></i> Delete
        </button>
      @endif
      @endcanany
      @endif
      <a href="{{ route('vouchers.show', $voucherRouteParams($voucher->id)) }}?print=1" target="_blank" class="btn btn-sm btn-outline-primary" rel="noopener"><i class="ti ti-file-description me-1"></i> PDF/Print</a>
      @can('email_create')
        <a href="javascript:void(0);" class="btn btn-sm btn-outline-primary" rel="noopener"><i class="ti ti-arrow-right  me-1"></i> Email</a>
      @endcan
    </div>
  </div>
  @endif

  @if($useRiderDocument)
    @include('vouchers.partials.rider_voucher_document_body', $doc)
  @else
    @php
      $voucher_type_label = \App\Helpers\General::VoucherType($voucher->voucher_type);
      $fin_detail = $voucher->voucher_type === 'RFV' ? company_table('rta_fines')->where('id', $voucher->ref_id)->first() : null;
      $settings = company_table('settings')->pluck('value', 'name')->toArray();
      $vf = static fn (string $f): bool => field_visible('voucher', $f);
    @endphp
    <div class="mb-3">
      <div class="d-flex align-items-center gap-3 flex-wrap">
        <img src="{{ $companyLogoUrl ?? asset('assets/img/logo-full.png') }}" alt="" width="120" class="img-fluid" onerror="this.style.display='none'">
        <div>
          <h6 class="mb-0 fw-semibold">{{ $settings['company_name'] ?? config('app.name') }}</h6>
          <p class="text-muted small mb-0">{{ $settings['company_address'] ?? '' }}</p>
          @if(!empty($settings['vat_number'])) <p class="text-muted small mb-0">TRN {{ $settings['vat_number'] }}</p> @endif
        </div>
      </div>
    </div>
    <div class="mb-3">
      <h5 class="mb-2">{{ $voucher_type_label }}</h5>
      <div class="row g-2">
        <div class="col-auto">
          <span class="text-muted">#</span><strong class="ms-1">{{ $voucher_number }}</strong>
        </div>
        @if($vf('trans_date'))<div class="col-auto text-muted">Date: {{ \Carbon\Carbon::parse($voucher->trans_date)->format('d M Y') }}</div>@endif
        @if($vf('billing_month') && $voucher->billing_month)<div class="col-auto text-muted">Billing Month: {{ \Carbon\Carbon::parse($voucher->billing_month)->format('M Y') }}</div>@endif
        @if($vf('amount'))<div class="col-auto fw-semibold">Amount: {{ \App\Helpers\Currency::format($voucher->amount, 2) }}</div>@endif
        @if($vf('reference_number') && $voucher->reference_number)
        <div class="col-12 text-muted small">Reference Number: {{ $voucher->reference_number }}</div>
        @endif
      </div>
    </div>
    @if($vf('remarks') && $voucher->remarks)
    <div class="mb-3">
      <label class="form-label small text-uppercase text-muted mb-1">Notes</label>
      <p class="mb-0">{!! $voucher->remarks !!}</p>
    </div>
    @endif
    @include('vouchers.partials.posted_transactions', [
      'transactions' => $voucher->transactions,
      'voucherType' => $voucher->voucher_type,
      'finDetail' => $fin_detail,
      'showPostedTransactions' => true,
      'includeInPrint' => true,
    ])
    <div class="row g-3 mb-3 no-print">
      <div class="col-md-4"><hr class="my-0"><span class="small text-muted">Authorized Signature</span></div>
      <div class="col-md-4"><hr class="my-0"><span class="small text-muted">Accountant Signature</span></div>
      <div class="col-md-4"><hr class="my-0"><span class="small text-muted">Customer Signature</span></div>
    </div>
    <div class="text-center py-2 border-top">
      @if($voucher->attach_file)
      @php
      $attach_url = in_array($voucher->voucher_type, ['RFV', 'LV']) ? url('storage/' . $voucher->attach_file) : url('storage/vouchers/' . $voucher->attach_file);
      @endphp
      <a href="{{ $attach_url }}" target="_blank" class="text-decoration-none text-body d-inline-flex align-items-center gap-1">
        <i class="ti ti-paperclip ti-sm"></i>
        <span>1 Attachment(s) added</span>
      </a>
      @else
      <span class="text-muted d-inline-flex align-items-center gap-1">
        <i class="ti ti-paperclip ti-sm"></i>
        0 Attachment(s) added
      </span>
      @endif
    </div>
  @endif

  <div id="voucher-panel-current" data-number="{{ $voucher_number }}" data-amount="{{ \App\Helpers\Currency::format($voucher->amount, 2) }}" style="display: none;" aria-hidden="true"></div>
</div>
