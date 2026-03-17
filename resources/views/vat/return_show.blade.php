@extends('layouts.app')
@section('title', 'VAT Return – ' . ($vat_return->quarter_label ?? 'Q' . $vat_return->quarter_slot) . ' ' . $vat_return->year)
@section('content')

@include('flash::message')

<div class="container-fluid card mb-1">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
    <div class="d-flex align-items-center gap-2">
      <a href="{{ route('vat.returns.index') }}" class="btn btn-outline-secondary btn-sm"><i class="ti ti-arrow-left me-1"></i> Back to returns</a>
      <h5 class="mb-0">VAT Return: {{ $vat_return->quarter_label ?? 'Q' . $vat_return->quarter_slot }} {{ $vat_return->year }}</h5>
      <span class="badge {{ $vat_return->status === 'paid' ? 'bg-success' : 'bg-warning text-dark' }}">{{ ucfirst($vat_return->status) }}</span>
    </div>
  </div>
  <div class="card-body">
    <div class="row mb-4">
      <div class="col-md-6">
        <p class="mb-1"><strong>Quarter:</strong> {{ $vat_return->quarter_label ?? 'Q' . $vat_return->quarter_slot }}</p>
        <p class="mb-1"><strong>Year:</strong> {{ $vat_return->year }}</p>
        <p class="mb-1"><strong>Filed at:</strong> {{ $vat_return->filed_at ? $vat_return->filed_at->format('d M Y H:i') : '—' }}</p>
      </div>
      <div class="col-md-6">
        <div class="border rounded p-3 bg-light">
          <p class="mb-1 small text-muted">Total Debit</p>
          <p class="mb-0 fw-bold">{{ number_format($totalDebit ?? 0, 2) }}</p>
          <p class="mb-1 small text-muted mt-2">Total Credit</p>
          <p class="mb-0 fw-bold">{{ number_format($totalCredit ?? 0, 2) }}</p>
          <hr class="my-2">
          <p class="mb-1 small text-muted">Payable amount</p>
          <p class="mb-0 fs-5 fw-bold {{ ($payableAmount ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
            {{ ($payableAmount ?? 0) >= 0 ? '+' : '' }}{{ number_format($payableAmount ?? 0, 2) }}
          </p>
          <hr class="my-2">
          <p class="mb-2 small text-muted">Payment</p>
          @if($vat_return->status === 'unpaid')
          <a href="javascript:void(0);" class="btn btn-success btn-sm me-1 show-modal" data-size="xl" data-title="New VAT Payment Voucher (VP)" data-action="{{ route('vat.voucher.create', ['vat_return_id' => $vat_return->id]) }}">
            <i class="ti ti-file-invoice me-1"></i> Make payment
          </a>
          @else
          <p class="text-success small mb-1"><i class="ti ti-circle-check me-1"></i> Paid</p>
          @endif
        </div>
      </div>
    </div>

    <div class="d-flex align-items-center justify-content-between mb-2">
      <h6 class="mb-0">Entries in this return</h6>
      <form id="vat-delete-entries-form" action="{{ route('vat.returns.delete-entries', $vat_return) }}" method="post" class="d-none">
        @csrf
        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Remove selected entries from this return? They will appear again in the VAT ledger.');">
          <i class="ti ti-trash me-1"></i> Delete Entries
        </button>
      </form>
    </div>
    <div class="table-responsive mb-4">
      <table class="table table-sm table-bordered">
        <thead class="table-light">
          <tr>
            <th style="width: 40px;">
              <input type="checkbox" class="form-check-input" id="vatReturnSelectAll" title="Select all">
            </th>
            <th>Date</th>
            <th>Account</th>
            <th>Reference</th>
            <th>Month</th>
            <th class="text-end">Debit</th>
            <th class="text-end">Credit</th>
          </tr>
        </thead>
        <tbody>
          @foreach($entries as $entry)
          @if($entry->transaction)
          @php $t = $entry->transaction; @endphp
          <tr>
            <td>
              <input type="checkbox" class="form-check-input vat-return-entry-cb" name="entry_ids[]" value="{{ $entry->id }}" form="vat-delete-entries-form">
            </td>
            <td>{{ \App\Helpers\Common::DateFormat($t->trans_date) }}</td>
            <td>{{ $t->account ? $t->account->account_code . ' – ' . $t->account->name : 'N/A' }}</td>
            <td>{{ $t->voucher ? ($t->voucher->reference_number ?? '—') : '—' }}</td>
            <td>{{ $t->billing_month ? date('M Y', strtotime($t->billing_month)) : '—' }}</td>
            <td class="text-end">{{ number_format($t->debit, 2) }}</td>
            <td class="text-end">{{ number_format($t->credit, 2) }}</td>
          </tr>
          @endif
          @endforeach
        </tbody>
        <tfoot class="table-light">
          <tr class="fw-bold">
            <td colspan="5" class="text-end">Total</td>
            <td class="text-end">{{ number_format($totalDebit ?? 0, 2) }}</td>
            <td class="text-end">{{ number_format($totalCredit ?? 0, 2) }}</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </div>
</div>

@push('third_party_scripts')
<script>
  (function() {
    var form = document.getElementById('vat-delete-entries-form');
    var selectAll = document.getElementById('vatReturnSelectAll');
    var checkboxes = document.querySelectorAll('.vat-return-entry-cb');

    function toggleDeleteBtn() {
      var anyChecked = Array.prototype.slice.call(checkboxes).some(function(cb) {
        return cb.checked;
      });
      form.classList.toggle('d-none', !anyChecked);
    }

    if (selectAll) {
      selectAll.addEventListener('change', function() {
        checkboxes.forEach(function(cb) {
          cb.checked = selectAll.checked;
        });
        toggleDeleteBtn();
      });
    }
    checkboxes.forEach(function(cb) {
      cb.addEventListener('change', toggleDeleteBtn);
    });
    toggleDeleteBtn();
  })();
</script>
@endpush
@endsection