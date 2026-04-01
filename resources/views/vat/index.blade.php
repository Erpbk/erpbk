@extends('layouts.app')
@section('title', 'VAT Ledger')
@section('content')

<div class="container-fluid card mb-1">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <h5 class="mb-0"><i class="ti ti-receipt-tax ti-lg text-body me-2"></i>VAT Ledger</h5>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      <button type="button" class="btn btn-primary btn-sm ms-2" id="vatReturnFileBtn" data-bs-toggle="modal" data-bs-target="#vatReturnQuarterModal">
        <i class="ti ti-file-export me-1"></i> Return File
      </button>
      <a href="javascript:void(0);" class="btn btn-outline-primary btn-sm ms-2 show-modal" data-size="xl" data-title="New VAT Payment Voucher (VP)" data-action="{{ route('vat.voucher.create') }}">
        <i class="ti ti-file-invoice me-1"></i> New VAT Payment Voucher
      </a>
    </div>
  </div>

  <div class="card-body pt-0 px-0">
    <p class="text-muted small px-3 mb-2">Combined entries of VAT accounts (1023, 1025). When you select a quarter, the ledger shows that quarter <strong>plus any unfiled entries from earlier quarters in the same year</strong>. <strong>Select the entries</strong> you want to include, then click <strong>Return File</strong>, choose the year and quarter — only the selected entries will be included in the return and in the VV voucher.</p>
    <div class="table-responsive" style="max-height: 800px; overflow: auto;">
      <table class="table table-striped table-bordered table-hover mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;">
              <input type="checkbox" class="form-check-input" id="vatSelectAll" title="Select all">
            </th>
            <th>Date</th>
            <th>Account</th>
            <th>Reference</th>
            <th>Month</th>
            <th>Voucher</th>
            <th>Narration</th>
            <th class="text-end">Debit</th>
            <th class="text-end">Credit</th>
            <th class="text-end">Balance</th>
          </tr>
        </thead>
        <tbody>
          @forelse($rows as $r)
          <tr class="{{ $r['is_balance_forward'] ?? false ? 'table-secondary' : '' }} {{ $r['is_total'] ?? false ? 'fw-bold' : '' }}">
            <td>
              @if(isset($r['transaction_id']) && $r['transaction_id'])
              <input type="checkbox" class="form-check-input vat-entry-cb" name="transaction_ids[]" value="{{ $r['transaction_id'] }}" form="vat-file-return-form">
              @else
              &nbsp;
              @endif
            </td>
            <td>{{ $r['date'] }}</td>
            <td>{{ $r['account_name'] }}</td>
            <td>{{ $r['reference_number'] }}</td>
            <td>{{ $r['billing_month'] }}</td>
            <td>{!! $r['voucher'] !!}</td>
            <td>{!! $r['narration'] !!}</td>
            <td class="text-end">{{ $r['debit'] }}</td>
            <td class="text-end">{{ $r['credit'] }}</td>
            <td class="text-end">{{ $r['balance'] }}</td>
          </tr>
          @empty
          <tr>
            <td colspan="10" class="text-center text-muted py-4">No VAT entries found. Ensure accounts 1023 and 1025 are configured in VAT Settings.</td>
          </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </div>
</div>

@if(isset($vpVouchers) && $vpVouchers->isNotEmpty())
<div class="container-fluid card mb-1">
  <div class="card-header">
    <h6 class="mb-0"><i class="ti ti-file-invoice ti-sm me-2"></i>Recent VAT Payment Vouchers (VP)</h6>
  </div>
  <div class="card-body py-2">
    <ul class="list-unstyled mb-0">
      @foreach($vpVouchers as $vp)
      @php $voucherId = $vp->voucher_type . '-' . str_pad($vp->id, 4, '0', STR_PAD_LEFT); @endphp
      <li class="py-1">
        <a href="javascript:void(0);" class="show-modal" data-size="xl" data-title="{{ \App\Helpers\General::VoucherType($vp->voucher_type) ?? 'VP' }} #{{ $voucherId }}" data-action="{{ route('vouchers.show', $vp->id) }}">
          {{ $voucherId }}
        </a>
        <span class="text-muted small ms-2">{{ $vp->trans_date ? \Carbon\Carbon::parse($vp->trans_date)->format('d M Y') : '' }}</span>
        <span class="text-muted small ms-2">{{ number_format($vp->amount ?? 0, 2) }}</span>
      </li>
      @endforeach
    </ul>
  </div>
</div>
@endif

{{-- Modal: Which VAT quarter is this return for? --}}
<div class="modal fade" id="vatReturnQuarterModal" tabindex="-1" aria-labelledby="vatReturnQuarterModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form id="vat-file-return-form" action="{{ route('vat.return.file') }}" method="post">
        @csrf
        <div class="modal-header">
          <h5 class="modal-title" id="vatReturnQuarterModalLabel">File VAT Return – Select Quarter</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p class="text-muted small">Choose the year and quarter for the return. Only the <strong>entries you selected</strong> in the ledger will be included (no entries selected = please select at least one).</p>
          <div class="mb-3">
            <label class="form-label">Year</label>
            <select name="vat_year" class="form-select" required>
              @foreach($years ?? [] as $val => $label)
              @if($val !== '')
              <option value="{{ $val }}" {{ request('vat_year') === (string)$val ? 'selected' : '' }}>{{ $label }}</option>
              @endif
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">VAT Period (Quarter)</label>
            <select name="vat_quarter_slot" class="form-select" required>
              @foreach($quarters ?? [] as $slot => $label)
              <option value="{{ $slot }}" {{ request('vat_quarter_slot') == (string)$slot ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">File Return</button>
        </div>
      </form>
    </div>
  </div>
</div>

@push('third_party_scripts')
<script>
  (function() {
    var btn = document.getElementById('vatReturnFileBtn');
    var selectAll = document.getElementById('vatSelectAll');
    var checkboxes = document.querySelectorAll('.vat-entry-cb');

    if (selectAll) {
      selectAll.addEventListener('change', function() {
        checkboxes.forEach(function(cb) {
          cb.checked = selectAll.checked;
        });
      });
    }

    // Checkboxes use form="vat-file-return-form" so they submit with the modal form
  })();
</script>
@endpush
@endsection