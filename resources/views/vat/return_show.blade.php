{{-- Loaded in #rightSideModal (AJAX) or as a standalone page --}}
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}?v=20261030-tbox">
<link rel="stylesheet" href="{{ asset('css/vat-demo.css') }}?v=20261107d">
@php
  $money = fn ($n) => strip_tags(\App\Helpers\Currency::format((float) $n));
  $returnNo = 'VTR-' . str_pad($vat_return->id, 4, '0', STR_PAD_LEFT);
  $period = ($vat_return->quarter_label ?? ('Q' . $vat_return->quarter_slot)) . ' ' . $vat_return->year;
@endphp
<div class="riders-list-demo vat-demo vat-return-modal p-3">
  <div class="vat-head" style="margin-bottom:12px">
    <i class="ti ti-receipt-tax" style="font-size:18px;color:#3b3fd8"></i>
    <h5 style="margin:0">{{ $returnNo }} · {{ $period }}</h5>
    <span class="vat-rb {{ $vat_return->status === 'paid' ? 'paid' : 'unpaid' }}">{{ strtoupper($vat_return->status) }}</span>
  </div>

  <div class="totals-cards" aria-label="VAT return detail" style="margin-bottom:14px">
    <div class="total-card total-blue">
      <div class="label"><i class="ti ti-calendar"></i> Quarter</div>
      <div class="value" style="font-size:15px">{{ $vat_return->quarter_label ?? ('Q' . $vat_return->quarter_slot) }}</div>
      <div class="meta">Year {{ $vat_return->year }}</div>
    </div>
    <div class="total-card total-black">
      <div class="label"><i class="ti ti-clock"></i> Filed At</div>
      <div class="value" style="font-size:13px">{{ $vat_return->filed_at ? $vat_return->filed_at->format('d M Y H:i') : '—' }}</div>
    </div>
    <div class="total-card total-amber">
      <div class="label"><i class="ti ti-arrow-down-circle"></i> Total Debit</div>
      <div class="value">{{ $money($totalDebit ?? 0) }}</div>
    </div>
    <div class="total-card total-green">
      <div class="label"><i class="ti ti-arrow-up-circle"></i> Total Credit</div>
      <div class="value">{{ $money($totalCredit ?? 0) }}</div>
    </div>
    <div class="total-card total-red">
      <div class="label"><i class="ti ti-scale"></i> Payable</div>
      <div class="value {{ ((float)($payableAmount ?? 0)) >= 0 ? 'unpaid-due' : 'unpaid-zero' }}">
        {{ $money($payableAmount ?? 0) }}
      </div>
      <div class="meta">
        @if($vat_return->status === 'unpaid')
          @canany(['vat_create', 'vat_edit'])
            <a href="javascript:void(0);" class="show-modal" data-size="xl" data-title="New VAT Payment Voucher (VP)" data-action="{{ route('vat.voucher.create', ['vat_return_id' => $vat_return->id]) }}">Pay</a>
          @endcanany
        @else
          Paid
        @endif
      </div>
    </div>
  </div>

  <div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="mb-0" style="font-weight:700;color:#1e293b">Entries in this return</h6>
    <form id="vat-delete-entries-form" action="{{ route('vat.returns.delete-entries', $vat_return) }}" method="post" class="d-none">
      @csrf
      @can('vat_delete')
      <button type="submit" class="vat-sb rd" onclick="return confirm('Remove selected entries from this return? They will appear again in the VAT ledger.');">
        Delete Entries
      </button>
      @endcan
    </form>
  </div>

  <div class="rl-tbox">
    <table class="table table-hover mb-0">
      <thead>
        <tr>
          <th style="width:40px">
            <input type="checkbox" class="form-check-input" id="vatReturnSelectAll" title="Select all">
          </th>
          <th>Date</th>
          <th>Account</th>
          <th>Reference</th>
          <th>Month</th>
          <th class="r">Debit</th>
          <th class="r">Credit</th>
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
              <td class="r">{{ number_format($t->debit, 2) }}</td>
              <td class="r">{{ number_format($t->credit, 2) }}</td>
            </tr>
          @endif
        @endforeach
      </tbody>
      <tfoot>
        <tr>
          <td colspan="5" class="r">Total</td>
          <td class="r">{{ number_format($totalDebit ?? 0, 2) }}</td>
          <td class="r">{{ number_format($totalCredit ?? 0, 2) }}</td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>
<script>
(function() {
  var root = document.querySelector('.vat-return-modal') || document;
  var form = root.querySelector('#vat-delete-entries-form');
  var selectAll = root.querySelector('#vatReturnSelectAll');
  var checkboxes = root.querySelectorAll('.vat-return-entry-cb');

  function toggleDeleteBtn() {
    if (!form) return;
    var anyChecked = Array.prototype.slice.call(checkboxes).some(function(cb) { return cb.checked; });
    form.classList.toggle('d-none', !anyChecked);
  }

  if (selectAll) {
    selectAll.addEventListener('change', function() {
      checkboxes.forEach(function(cb) { cb.checked = selectAll.checked; });
      toggleDeleteBtn();
    });
  }
  checkboxes.forEach(function(cb) {
    cb.addEventListener('change', toggleDeleteBtn);
  });
  toggleDeleteBtn();
})();
</script>
