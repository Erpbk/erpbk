@extends('layouts.app')
@section('title', 'VAT Returns')
@php
  $hideModuleTopBarSlider = true;
  $returnStats = $returnStats ?? [
    'unpaid_total' => 0, 'unpaid_count' => 0,
    'paid_total' => 0, 'paid_count' => 0,
    'vp_total' => 0, 'vp_count' => 0,
  ];
  $pane = $pane ?? 'unpaid';
  $money = fn ($n) => strip_tags(\App\Helpers\Currency::format((float) $n));
@endphp
@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}?v=20261030-tbox">
<link rel="stylesheet" href="{{ asset('css/vat-demo.css') }}?v=20261107d">
@endpush

@section('content')
<div class="riders-list-demo vat-demo" id="vatReturnsDemo">
  <div class="rl-wrap">
    @include('flash::message')

    <div class="vat-head">
      <i class="ti ti-file-export" style="font-size:20px;color:#3b3fd8"></i>
      <h5>VAT Returns</h5>
    </div>

    <div class="totals-cards" aria-label="VAT return stats">
      <div class="total-card total-amber vat-pane-tile {{ $pane === 'unpaid' ? 'is-on' : '' }}" data-pane="unpaid" role="button" tabindex="0">
        <div class="label"><i class="ti ti-clock"></i> Unpaid Returns</div>
        <div class="value unpaid-due">{{ $money($returnStats['unpaid_total'] ?? 0) }}</div>
        <div class="meta">{{ number_format((int) ($returnStats['unpaid_count'] ?? 0)) }} return(s) to pay</div>
      </div>
      <div class="total-card total-green vat-pane-tile {{ $pane === 'paid' ? 'is-on' : '' }}" data-pane="paid" role="button" tabindex="0">
        <div class="label"><i class="ti ti-circle-check"></i> Paid Returns</div>
        <div class="value unpaid-zero">{{ $money($returnStats['paid_total'] ?? 0) }}</div>
        <div class="meta">{{ number_format((int) ($returnStats['paid_count'] ?? 0)) }} return(s) paid</div>
      </div>
      <div class="total-card total-blue vat-pane-tile {{ $pane === 'vp' ? 'is-on' : '' }}" data-pane="vp" role="button" tabindex="0">
        <div class="label"><i class="ti ti-file-invoice"></i> VAT Payment Vouchers</div>
        <div class="value">{{ number_format((int) ($returnStats['vp_count'] ?? 0)) }} voucher(s)</div>
        <div class="meta">Total {{ $money($returnStats['vp_total'] ?? 0) }}</div>
      </div>
    </div>

    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="actBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            VAT Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="actMenu" hidden role="menu">
            @canany(['vat_create', 'vat_edit'])
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="xl" data-title="New VAT Payment" data-action="{{ route('payments.create', ['invoice_type' => 'vat_return', 'vat_payment' => 1]) }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
              <span><strong>New VAT Payment</strong><em>Allocate payment to unpaid returns</em></span>
            </a>
            @endcanany
            <a role="menuitem" href="{{ route('vat.index') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
              <span><strong>VAT Ledger</strong><em>Select entries and file a return</em></span>
            </a>
          </div>
        </div>
        <a class="rl-btn" href="{{ route('vat.index') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
          VAT Ledger
        </a>
      </div>
      <div class="rl-tb-right">
        <input class="rl-search" id="rq" type="search" placeholder="Quick Search..." aria-label="Quick search">
      </div>
    </div>

    {{-- Unpaid --}}
    <div class="vat-pane {{ $pane === 'unpaid' ? 'is-active' : '' }}" id="pane-unpaid" data-pane="unpaid">
      @if($unpaidReturns->isEmpty())
        <div class="vat-rempty">No unpaid returns. Tick entries in the VAT Ledger and click Return File.</div>
      @else
        <div class="rl-tbox">
          <table class="table table-hover mb-0" id="vatUnpaidTable">
            <thead>
              <tr>
                <th>Return #</th>
                <th>Quarter</th>
                <th>Filed On</th>
                <th>Entries</th>
                <th class="r">Output VAT</th>
                <th class="r">Input VAT</th>
                <th class="r">Net Payable</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($unpaidReturns as $r)
                @php
                  $hasEntries = $r->relationLoaded('entries');
                  $output = 0; $input = 0; $debit = 0; $credit = 0;
                  if ($hasEntries) {
                    foreach ($r->entries as $entry) {
                      if (!$entry->transaction) continue;
                      $t = $entry->transaction;
                      $debit += (float) $t->debit;
                      $credit += (float) $t->credit;
                      if ((float) $t->credit > 0) $output += (float) $t->credit - (float) $t->debit;
                      else $input += (float) $t->debit - (float) $t->credit;
                    }
                  }
                  $net = $credit - $debit;
                  $returnNo = 'VTR-' . str_pad($r->id, 4, '0', STR_PAD_LEFT);
                  $period = ($r->quarter_label ?? ('Q' . $r->quarter_slot)) . ' ' . $r->year;
                  $searchBlob = strtolower($returnNo . ' ' . $period . ' ' . ($r->quarter_label ?? ''));
                  $showTitle = 'VAT Return ' . $returnNo . ' · ' . $period;
                  $statusKey = $r->statusBadgeKey();
                  $statusLabel = strtoupper(str_replace('_', ' ', $r->statusLabel()));
                  $canDeleteReturn = ! $r->hasLivePayments() && (int) $r->status === \App\Models\VatReturn::STATUS_UNPAID;
                @endphp
                <tr class="vat-ret-row" data-search="{{ e($searchBlob) }}">
                  <td>
                    <a href="javascript:void(0);"
                       class="vat-rid show-modal-right"
                       data-size="xl"
                       data-title="{{ $showTitle }}"
                       data-action="{{ route('vat.returns.show', $r) }}">{{ $returnNo }}</a>
                  </td>
                  <td>
                    <b>{{ $r->quarter_label ?? ('Q' . $r->quarter_slot) }}</b>
                    <div style="font-size:11px;color:#6b7280">{{ $r->year }}</div>
                  </td>
                  <td>{{ $r->filed_at ? $r->filed_at->format('d M Y') : '—' }}</td>
                  <td>{{ $hasEntries ? $r->entries->count() : ($r->entries_count ?? 0) }}</td>
                  <td class="r" style="color:#15803d">{{ $hasEntries ? number_format($output, 2) : '—' }}</td>
                  <td class="r" style="color:#c2410c">{{ $hasEntries ? number_format($input, 2) : '—' }}</td>
                  <td class="r" style="font-weight:700;color:{{ $hasEntries ? ($net >= 0 ? '#dc2626' : '#059669') : '#64748b' }}">{{ $hasEntries ? $money($net) : '—' }}</td>
                  <td><span class="vat-rb unpaid">{{ $statusLabel }}</span></td>
                  <td>
                    <div class="vat-actions">
                      @canany(['vat_create', 'vat_edit'])
                      <a href="javascript:void(0);"
                         class="vat-sb pay show-modal"
                         data-size="xl"
                         data-title="New VAT Payment"
                         data-action="{{ route('payments.create', ['invoice_type' => 'vat_return', 'vat_payment' => 1, 'invoice_id' => $r->id]) }}">Pay</a>
                      @endcanany
                      @if($canDeleteReturn)
                      <form action="{{ route('vat.returns.destroy', $r) }}" method="post" class="d-inline" onsubmit="return confirm('Delete this return? Its entries will show again in the VAT ledger.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="vat-sb rd" title="Delete" aria-label="Delete">
                          <i class="ti ti-trash"></i>
                        </button>
                      </form>
                      @else
                      <a href="javascript:void(0);"
                         class="vat-sb ol show-modal-right"
                         data-size="xl"
                         data-title="{{ $showTitle }}"
                         data-action="{{ route('vat.returns.show', $r) }}"
                         title="Manage linked payments">Payments</a>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- Paid --}}
    <div class="vat-pane {{ $pane === 'paid' ? 'is-active' : '' }}" id="pane-paid" data-pane="paid">
      @if($paidReturns->isEmpty())
        <div class="vat-rempty">No paid returns yet.</div>
      @else
        <div class="rl-tbox">
          <table class="table table-hover mb-0" id="vatPaidTable">
            <thead>
              <tr>
                <th>Return #</th>
                <th>Quarter</th>
                <th>Filed On</th>
                <th>Entries</th>
                <th class="r">Output VAT</th>
                <th class="r">Input VAT</th>
                <th class="r">Net Payable</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($paidReturns as $r)
                @php
                  $hasEntries = $r->relationLoaded('entries');
                  $output = 0; $input = 0; $debit = 0; $credit = 0;
                  if ($hasEntries) {
                    foreach ($r->entries as $entry) {
                      if (!$entry->transaction) continue;
                      $t = $entry->transaction;
                      $debit += (float) $t->debit;
                      $credit += (float) $t->credit;
                      if ((float) $t->credit > 0) $output += (float) $t->credit - (float) $t->debit;
                      else $input += (float) $t->debit - (float) $t->credit;
                    }
                  }
                  $net = $credit - $debit;
                  $returnNo = 'VTR-' . str_pad($r->id, 4, '0', STR_PAD_LEFT);
                  $period = ($r->quarter_label ?? ('Q' . $r->quarter_slot)) . ' ' . $r->year;
                  $searchBlob = strtolower($returnNo . ' ' . $period . ' ' . ($r->quarter_label ?? ''));
                  $showTitle = 'VAT Return ' . $returnNo . ' · ' . $period;
                @endphp
                <tr class="vat-ret-row" data-search="{{ e($searchBlob) }}">
                  <td>
                    <a href="javascript:void(0);"
                       class="vat-rid show-modal-right"
                       data-size="xl"
                       data-title="{{ $showTitle }}"
                       data-action="{{ route('vat.returns.show', $r) }}">{{ $returnNo }}</a>
                  </td>
                  <td>
                    <b>{{ $r->quarter_label ?? ('Q' . $r->quarter_slot) }}</b>
                    <div style="font-size:11px;color:#6b7280">{{ $r->year }}</div>
                  </td>
                  <td>{{ $r->filed_at ? $r->filed_at->format('d M Y') : '—' }}</td>
                  <td>{{ $hasEntries ? $r->entries->count() : ($r->entries_count ?? 0) }}</td>
                  <td class="r" style="color:#15803d">{{ $hasEntries ? number_format($output, 2) : '—' }}</td>
                  <td class="r" style="color:#c2410c">{{ $hasEntries ? number_format($input, 2) : '—' }}</td>
                  <td class="r" style="font-weight:700;color:{{ $hasEntries ? ($net >= 0 ? '#dc2626' : '#059669') : '#64748b' }}">{{ $hasEntries ? $money($net) : '—' }}</td>
                  <td><span class="vat-rb paid">PAID</span></td>
                  <td>
                    <div class="vat-actions">
                      <a href="javascript:void(0);"
                         class="vat-sb ol show-modal-right"
                         data-size="xl"
                         data-title="{{ $showTitle }}"
                         data-action="{{ route('vat.returns.show', $r) }}">View</a>
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      @endif
    </div>

    {{-- VP vouchers --}}
    <div class="vat-pane {{ $pane === 'vp' ? 'is-active' : '' }}" id="pane-vp" data-pane="vp">
      <div class="rl-tbox">
        <table class="table table-hover mb-0" id="vatVpTable">
          <thead>
            <tr>
              <th>Voucher ID</th>
              <th>Date</th>
              <th>Billing Month</th>
              <th>Reference</th>
              <th class="r">Amount</th>
              <th>Created By</th>
              <th style="width:90px">Action</th>
            </tr>
          </thead>
          <tbody>
            @forelse($vpVouchers as $vp)
              @php
                $voucherId = $vp->voucher_type . '-' . str_pad($vp->id, 4, '0', STR_PAD_LEFT);
                $searchBlob = strtolower($voucherId . ' ' . ($vp->reference_number ?? '') . ' ' . ($vp->amount ?? ''));
              @endphp
              <tr class="vat-ret-row" data-search="{{ e($searchBlob) }}">
                <td>
                  <a href="javascript:void(0);" class="vat-rid show-modal" data-size="xl" data-title="{{ \App\Helpers\General::VoucherType($vp->voucher_type) ?? 'VP' }} #{{ $voucherId }}" data-action="{{ route('vouchers.show', $vp->id) }}">{{ $voucherId }}</a>
                </td>
                <td>{{ $vp->trans_date ? \App\Helpers\Common::DateFormat($vp->trans_date) : '—' }}</td>
                <td>{{ $vp->billing_month ? \App\Helpers\Common::MonthFormat($vp->billing_month) : '—' }}</td>
                <td>{{ $vp->reference_number ?? '—' }}</td>
                <td class="r"><b>{{ $money($vp->amount ?? 0) }}</b></td>
                <td>{{ \App\Helpers\Common::UserName($vp->Created_By) }}</td>
                <td>
                  <a href="javascript:void(0);" class="vat-sb ol show-modal" data-size="xl" data-title="{{ \App\Helpers\General::VoucherType($vp->voucher_type) ?? 'VP' }} #{{ $voucherId }}" data-action="{{ route('vouchers.show', $vp->id) }}">View</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="7" class="vat-rempty">No VAT Payment vouchers yet. Create one from an unpaid return or from VAT Actions.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      @if(method_exists($vpVouchers, 'hasPages') && $vpVouchers->hasPages())
        <div class="d-flex justify-content-center mt-2" id="vpPager">
          {{ $vpVouchers->links('pagination') }}
        </div>
      @endif
    </div>
  </div>
</div>
@endsection

@push('third_party_scripts')
<script>
(function () {
  const tiles = document.querySelectorAll('.vat-pane-tile');
  const panes = document.querySelectorAll('.vat-pane');
  const actBtn = document.getElementById('actBtn');
  const actMenu = document.getElementById('actMenu');
  const rq = document.getElementById('rq');

  function setPane(name) {
    const url = new URL(window.location.href);
    if ((url.searchParams.get('pane') || 'unpaid') === name) {
      tiles.forEach((t) => t.classList.toggle('is-on', t.dataset.pane === name));
      panes.forEach((p) => p.classList.toggle('is-active', p.dataset.pane === name));
      if (rq) filterRows(rq.value);
      return;
    }
    // Reload so the controller can eager-load only the active pane's entry trees.
    url.searchParams.set('pane', name);
    window.location.href = url.toString();
  }

  tiles.forEach((t) => {
    t.addEventListener('click', () => setPane(t.dataset.pane));
    t.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); setPane(t.dataset.pane); }
    });
  });

  function filterRows(q) {
    const needle = (q || '').toLowerCase().trim();
    const active = document.querySelector('.vat-pane.is-active');
    if (!active) return;
    active.querySelectorAll('tr.vat-ret-row').forEach((tr) => {
      const blob = (tr.dataset.search || '').toLowerCase();
      tr.style.display = (!needle || blob.indexOf(needle) !== -1) ? '' : 'none';
    });
  }
  if (rq) rq.addEventListener('input', () => filterRows(rq.value));

  if (actBtn && actMenu) {
    actBtn.addEventListener('click', function (e) {
      e.stopPropagation();
      const open = actMenu.hasAttribute('hidden');
      if (open) actMenu.removeAttribute('hidden'); else actMenu.setAttribute('hidden', '');
      actBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', function () {
      actMenu.setAttribute('hidden', '');
      actBtn.setAttribute('aria-expanded', 'false');
    });
  }

  @if(session('success'))
    if (window.toastr) toastr.success(@json(session('success')));
  @endif
  @if(session('error'))
    if (window.toastr) toastr.error(@json(session('error')));
  @endif
})();
</script>
@endpush
