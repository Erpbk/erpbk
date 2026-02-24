@php
  $voucherTypes = \App\Helpers\General::VoucherType();
@endphp
<div class="voucher-list-sidebar h-100 d-flex flex-column">
  <div class="voucher-list-sidebar-header d-flex align-items-center justify-content-between gap-2 px-3 py-2 border-bottom bg-light flex-shrink-0">
    <div class="d-flex align-items-center gap-1 flex-grow-1 min-width-0">
      <span class="text-truncate fw-semibold small">All Vouchers</span>
      <i class="ti ti-chevron-down ti-xs text-muted"></i>
    </div>
    <div class="d-flex align-items-center gap-1 flex-shrink-0">
      @can('voucher_create')
        @php $firstJv = collect(App\Helpers\General::ActiveVoucherTypesForCreate())->keys()->first(); @endphp
        @if($firstJv)
          <a href="javascript:void(0);" class="btn btn-sm btn-primary py-1 px-2 show-modal" data-size="xl" data-title="Create Voucher" data-action="{{ route('vouchers.create', ['vt' => $firstJv]) }}" title="Add"><i class="ti ti-plus"></i></a>
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
        @endphp
        <a href="javascript:void(0);" class="voucher-list-sidebar-row show-voucher-panel d-flex align-items-stretch gap-2 px-3 py-2 border-bottom text-decoration-none text-body" data-action="{{ route('vouchers.show', $voucher->id) }}" data-title="{{ $typeLabel }} #{{ $voucherId }}" data-collapse-sidebar="1">
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
            <span class="small fw-medium">AED {{ number_format($voucher->amount, 2) }}</span>
            <span class="badge bg-label-success py-0" style="font-size: 0.65rem;">PUBLISHED</span>
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
