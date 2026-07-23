@php
  $__companySlug = \App\Support\CompanyRouteContext::slug();
  $voucherTypes = \App\Helpers\General::VoucherType();
  $voucherTypesForCreate = $voucherTypesForCreate ?? \App\Models\VoucherType::activeCodeLabelMapForModule('vouchers');
@endphp
<div class="voucher-list-sidebar h-100 d-flex flex-column">
  <div class="voucher-list-sidebar-header d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom bg-light flex-shrink-0">
    <div class="d-flex align-items-center gap-1 flex-grow-1 min-width-0">
      <span class="text-truncate fw-semibold small">All Vouchers</span>
      <i class="ti ti-chevron-down ti-xs text-muted"></i>
    </div>
    <div class="d-flex align-items-center gap-1 flex-shrink-0">
        @can('vouchers_create')
        @php $firstVt = collect($voucherTypesForCreate)->keys()->first(); @endphp
        @if($firstVt)
          <a href="javascript:void(0);" class="btn btn-sm btn-primary py-1 px-2 show-modal" data-size="xl" data-title="Create Voucher" data-action="{{ route('vouchers.create', ['company_slug' => $__companySlug, 'vt' => $firstVt]) }}" title="Add"><i class="ti ti-plus"></i></a>
        @endif
      @endcan
      <span class="badge bg-label-secondary">{{ $data->total() }}</span>
    </div>
  </div>
  <div class="px-3 py-2 border-bottom small text-muted flex-shrink-0">
    <span>Period: All</span>
  </div>
  <div class="voucher-list-sidebar-body overflow-auto flex-grow-1 min-height-0">
    @if(isset($data) && $data->count() > 0)
      @foreach($data as $voucher)
        @php
          $voucherId = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
          $typeLabel = $voucherTypes[$voucher->voucher_type] ?? $voucher->voucher_type;
          $voucherPendingDeletion = record_is_pending_deletion($voucher);
        @endphp
        <a href="javascript:void(0);" class="voucher-list-sidebar-row show-voucher-panel d-flex align-items-stretch gap-2 px-3 py-2 border-bottom text-decoration-none text-body {{ $voucherPendingDeletion ? 'bg-warning-subtle' : '' }}" data-action="{{ route('vouchers.show', ['company_slug' => $__companySlug, 'voucher' => $voucher->id]) }}" data-title="{{ $typeLabel }} #{{ $voucherId }}" data-collapse-sidebar="1" data-list-url="{{ route('vouchers.list-sidebar', ['company_slug' => $__companySlug]) }}">
          <div class="d-flex align-items-start pt-1">
            <input type="checkbox" class="form-check-input mt-0" onclick="event.preventDefault(); event.stopPropagation();" aria-label="Select">
          </div>
          <div class="d-flex flex-column flex-grow-1 min-width-0">
            <span class="fw-medium small">{{ \App\Helpers\Common::DateFormat($voucher->trans_date) }}</span>
            <span class="text-muted" style="font-size: 0.75rem;">{{ $voucher->id }}</span>
          </div>
          @if($voucher->attach_file)
            <div class="d-flex align-items-center flex-shrink-0">
              <i class="ti ti-paperclip ti-xs text-muted" title="Has attachment"></i>
            </div>
          @endif
          <div class="d-flex flex-column align-items-end flex-shrink-0 text-end">
            <span class="small fw-medium">{{ \App\Helpers\Currency::format($voucher->amount, 2) }}</span>
            @if($voucherPendingDeletion)
              <span class="badge bg-warning text-dark py-0" style="font-size: 0.65rem;"><i class="ti ti-lock me-1"></i>PENDING DELETION</span>
            @else
              <span class="badge bg-label-success py-0" style="font-size: 0.65rem;">PUBLISHED</span>
            @endif
          </div>
        </a>
      @endforeach
      @if($data->hasPages())
        <div class="px-3 py-2 border-top small text-center">
          {!! $data->appends(request()->query())->links('pagination') !!}
        </div>
      @endif
    @else
      <div class="px-3 py-4 text-center text-muted small">No vouchers found</div>
    @endif
  </div>
</div>
