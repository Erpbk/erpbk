@extends('layouts.app')
@section('title', 'VAT Returns')
@php
  $hideModuleTopBarSlider = true;
  $returnStats = $returnStats ?? [
    'unpaid_total' => 0, 'unpaid_count' => 0,
    'paid_total' => 0, 'paid_count' => 0,
    'vp_total' => 0, 'vp_count' => 0,
  ];
  $returns = $returns ?? collect();
  $paymentsById = $paymentsById ?? collect();
  $purchaseAccountId = (int) ($purchaseAccountId ?? 0);
  $salesAccountId = (int) ($salesAccountId ?? 0);
  $quarters = $quarters ?? [];
  $activeFilterCount = (int) ($activeFilterCount ?? 0);
  $money = fn ($n) => strip_tags(\App\Helpers\Currency::format((float) $n));
@endphp
@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}?v=20261030-tbox">
<link rel="stylesheet" href="{{ asset('css/vat-demo.css') }}?v=20261113d">
@include('invoices.partials.status_badge_styles')
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
      <div class="total-card total-amber">
        <div class="label"><i class="ti ti-clock"></i> Unpaid Returns</div>
        <div class="value unpaid-due">{{ $money($returnStats['unpaid_total'] ?? 0) }}</div>
        <div class="meta">{{ number_format((int) ($returnStats['unpaid_count'] ?? 0)) }} return(s) to pay</div>
      </div>
      <div class="total-card total-green">
        <div class="label"><i class="ti ti-circle-check"></i> Paid Returns</div>
        <div class="value unpaid-zero">{{ $money($returnStats['paid_total'] ?? 0) }}</div>
        <div class="meta">{{ number_format((int) ($returnStats['paid_count'] ?? 0)) }} return(s) paid</div>
      </div>
      <div class="total-card total-blue">
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
        <button type="button" class="rl-btn openFilterSidebar" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Returns
          @if($activeFilterCount > 0)
            <span class="cnt">{{ $activeFilterCount }}</span>
          @endif
        </button>
      </div>
    </div>

    @if($returns->isEmpty())
      <div class="vat-rempty">
        @if($activeFilterCount > 0)
          No returns match the selected filters.
        @else
          No returns yet. Tick entries in the VAT Ledger and click Return File.
        @endif
      </div>
    @else
      <div class="rl-tbox vat-returns-tbox">
        <table class="table table-hover mb-0" id="vatReturnsTable">
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
              <th>Voucher</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($returns as $r)
              @php
                $totals = $totalsByReturnId[$r->id] ?? ['output' => 0, 'input' => 0, 'net' => 0];
                $output = (float) ($totals['output'] ?? 0);
                $input = (float) ($totals['input'] ?? 0);
                $net = (float) ($totals['net'] ?? 0);
                $returnNo = 'VTR-' . str_pad($r->id, 4, '0', STR_PAD_LEFT);
                $period = ($r->quarter_label ?? ('Q' . $r->quarter_slot)) . ' ' . $r->year;
                $statusKey = $r->statusBadgeKey();
                $canDeleteReturn = ! $r->hasLivePayments() && (int) $r->status === \App\Models\VatReturn::STATUS_UNPAID;
                $linkedPayments = collect($r->livePaymentIds())
                  ->map(fn ($id) => $paymentsById->get($id))
                  ->filter();
                $vpLabelFor = function ($p) {
                  $v = $p?->voucher;
                  if (! $v) {
                    return 'PAY-' . str_pad((string) $p->id, 4, '0', STR_PAD_LEFT);
                  }

                  return $v->formatted_id ?: (($v->voucher_type ?: 'VP') . '-' . str_pad((string) $v->id, 4, '0', STR_PAD_LEFT));
                };
                $vpSearch = $linkedPayments->map($vpLabelFor)->implode(' ');
                $searchBlob = strtolower($returnNo . ' ' . $period . ' ' . ($r->quarter_label ?? '') . ' ' . $statusKey . ' ' . $vpSearch);
                $colspan = 10;
                $entriesUrl = route('vat.returns.entries', $r);
              @endphp
              <tr class="vat-ret-row" data-search="{{ e($searchBlob) }}" data-return-id="{{ $r->id }}">
                <td>
                  <a href="javascript:void(0);"
                     class="vat-rid vat-toggle-entries"
                     data-target="ent-{{ $r->id }}"
                     data-entries-url="{{ $entriesUrl }}">{{ $returnNo }}</a>
                </td>
                <td>
                  <b>{{ $r->quarter_label ?? ('Q' . $r->quarter_slot) }}</b>
                  <div style="font-size:11px;color:#6b7280">{{ $r->year }}</div>
                </td>
                <td>{{ $r->filed_at ? $r->filed_at->format('d M Y') : '—' }}</td>
                <td>{{ (int) ($r->entries_count ?? 0) }}</td>
                <td class="r" style="color:#15803d">{{ number_format($output, 2) }}</td>
                <td class="r" style="color:#c2410c">{{ number_format($input, 2) }}</td>
                <td class="r" style="font-weight:700;color:{{ $net >= 0 ? '#dc2626' : '#059669' }}">{{ $money($net) }}</td>
                <td>
                  @include('invoices.partials.status_badge', [
                    'status' => $statusKey,
                    'label' => $statusKey === 'partial' ? 'Partially Paid' : ($statusKey === 'paid' ? 'Paid' : 'Unpaid'),
                  ])
                </td>
                <td>
                  @if($linkedPayments->isEmpty())
                    <span style="color:#94a3b8">—</span>
                  @else
                    <div class="vat-vp-links">
                      @foreach($linkedPayments as $payment)
                        @php $voucherId = $vpLabelFor($payment); @endphp
                        <a href="javascript:void(0);"
                           class="vat-rid show-voucher-panel"
                           data-collapse-sidebar="1"
                           data-title="VAT Payment {{ $voucherId }}"
                           data-action="{{ route('payments.show', $payment->id) }}"
                           data-list-url="{{ route('vouchers.list-sidebar') }}">{{ $voucherId }}</a>
                      @endforeach
                    </div>
                  @endif
                </td>
                <td>
                  <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect"
                            type="button"
                            id="vatRetAction_{{ $r->id }}"
                            data-bs-toggle="dropdown"
                            aria-expanded="false"
                            title="Actions">
                      <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="vatRetAction_{{ $r->id }}">
                      <a href="javascript:void(0);"
                         class="dropdown-item waves-effect vat-toggle-entries"
                         data-target="ent-{{ $r->id }}"
                         data-entries-url="{{ $entriesUrl }}">
                        <i class="fa fa-list mx-1"></i> View Entries
                      </a>
                      @if(in_array((int) $r->status, [\App\Models\VatReturn::STATUS_UNPAID, \App\Models\VatReturn::STATUS_PARTIAL], true))
                        @canany(['vat_create', 'vat_edit'])
                        <a href="javascript:void(0);"
                           class="dropdown-item waves-effect show-modal"
                           data-size="xl"
                           data-title="New VAT Payment"
                           data-action="{{ route('payments.create', ['invoice_type' => 'vat_return', 'vat_payment' => 1, 'invoice_id' => $r->id]) }}">
                          <i class="fa fa-money mx-1 text-success"></i>
                          {{ (int) $r->status === \App\Models\VatReturn::STATUS_PARTIAL ? 'Pay Remaining' : 'Pay' }}
                        </a>
                        @endcanany
                      @endif
                      @foreach($linkedPayments as $payment)
                        @php $vpActionLabel = $vpLabelFor($payment); @endphp
                        @canany(['payments_edit', 'vat_edit', 'cash_&_banks_payments_edit'])
                        <a href="javascript:void(0);"
                           class="dropdown-item waves-effect show-modal"
                           data-size="xl"
                           data-title="Edit {{ $vpActionLabel }}"
                           data-action="{{ route('payments.edit', $payment->id) }}">
                          <i class="fa fa-edit mx-1 text-success"></i> Edit {{ $vpActionLabel }}
                        </a>
                        @endcanany
                        @canany(['payments_delete', 'vat_delete', 'cash_&_banks_payments_delete'])
                        <a href="javascript:void(0);"
                           class="dropdown-item waves-effect text-danger"
                           onclick="if(confirm('Delete {{ $vpActionLabel }}? The return status will be recalculated.')){ document.getElementById('vat-del-pay-{{ $payment->id }}').submit(); }">
                          <i class="fa fa-trash mx-1"></i> Delete {{ $vpActionLabel }}
                        </a>
                        <form id="vat-del-pay-{{ $payment->id }}" action="{{ route('payments.destroy', $payment->id) }}" method="post" class="d-none">
                          @csrf
                          @method('DELETE')
                        </form>
                        @endcanany
                      @endforeach
                      @if($canDeleteReturn)
                        @can('vat_delete')
                        <a href="javascript:void(0);"
                           class="dropdown-item waves-effect text-danger"
                           onclick="if(confirm('Delete this return? Its entries will show again in the VAT ledger.')){ document.getElementById('vat-del-ret-{{ $r->id }}').submit(); }">
                          <i class="fa fa-trash mx-1"></i> Delete Return
                        </a>
                        <form id="vat-del-ret-{{ $r->id }}" action="{{ route('vat.returns.destroy', $r) }}" method="post" class="d-none">
                          @csrf
                          @method('DELETE')
                        </form>
                        @endcan
                      @endif
                    </div>
                  </div>
                </td>
              </tr>
              <tr class="vat-ent"
                  id="ent-{{ $r->id }}"
                  style="display:none"
                  data-search="{{ e($searchBlob) }}"
                  data-loaded="0"
                  data-entries-url="{{ $entriesUrl }}">
                <td colspan="{{ $colspan }}">
                  <div class="vat-ent-body text-center text-muted py-3">Loading entries…</div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    @endif
  </div>
</div>

<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Returns</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ route('vat.returns.index') }}" method="GET">
      <div class="row">
        <div class="form-group col-md-12">
          <label for="year">Year</label>
          @include('vat.partials.year_picker_input', [
            'id' => 'year',
            'name' => 'year',
            'value' => request('year'),
            'placeholder' => 'Select year',
          ])
        </div>
        <div class="form-group col-md-12">
          <label for="quarter_slot">Quarter</label>
          <select class="form-control select2" id="quarter_slot" name="quarter_slot" data-placeholder="All Quarters">
            <option value="">All Quarters</option>
            @foreach($quarters as $slot => $label)
              <option value="{{ $slot }}" {{ (string) request('quarter_slot') === (string) $slot ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="status">Status</label>
          <select class="form-control select2" id="status" name="status" data-placeholder="All Statuses">
            <option value="">All Statuses</option>
            <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            <option value="partial" {{ request('status') === 'partial' ? 'selected' : '' }}>Partially Paid</option>
            <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="filed_from">Filed From</label>
          <input type="date" class="form-control" id="filed_from" name="filed_from" value="{{ request('filed_from') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="filed_to">Filed To</label>
          <input type="date" class="form-control" id="filed_to" name="filed_to" value="{{ request('filed_to') }}">
        </div>
      </div>
      <div class="col-md-12 form-group text-center mt-3 vat-filter-actions">
        <button type="button" class="btn btn-outline-secondary me-2" id="vatFilterCancelBtn">Cancel</button>
        <a href="{{ route('vat.returns.index') }}" class="btn btn-outline-primary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-filter mx-2"></i> Filter Data
        </button>
      </div>
    </form>
  </div>
</div>
@endsection

@push('third_party_scripts')
@include('vat.partials.filter_widgets_script')
<script>
(function () {
  const actBtn = document.getElementById('actBtn');
  const actMenu = document.getElementById('actMenu');
  const rq = document.getElementById('rq');
  const filterBtn = document.getElementById('filterBtn');
  const filterSidebar = document.getElementById('filterSidebar');
  const filterOverlay = document.getElementById('filterOverlay');
  const closeSidebar = document.getElementById('closeSidebar');

  function openFilter() {
    if (filterSidebar) filterSidebar.classList.add('open');
    if (filterOverlay) filterOverlay.classList.add('show');
    document.body.classList.add('filter-open');
  }
  function closeFilter() {
    if (filterSidebar) filterSidebar.classList.remove('open');
    if (filterOverlay) filterOverlay.classList.remove('show');
    document.body.classList.remove('filter-open');
  }
  if (filterBtn) filterBtn.addEventListener('click', openFilter);
  if (closeSidebar) closeSidebar.addEventListener('click', closeFilter);
  if (filterOverlay) filterOverlay.addEventListener('click', closeFilter);
  const vatFilterCancelBtn = document.getElementById('vatFilterCancelBtn');
  if (vatFilterCancelBtn) vatFilterCancelBtn.addEventListener('click', closeFilter);

  function filterRows(q) {
    const needle = (q || '').toLowerCase().trim();
    document.querySelectorAll('#vatReturnsTable tr.vat-ret-row').forEach((tr) => {
      const blob = (tr.dataset.search || '').toLowerCase();
      const match = !needle || blob.indexOf(needle) !== -1;
      tr.style.display = match ? '' : 'none';
      const ent = document.getElementById('ent-' + (tr.dataset.returnId || ''));
      if (ent && !match) {
        ent.style.display = 'none';
      }
    });
  }
  if (rq) rq.addEventListener('input', () => filterRows(rq.value));

  document.addEventListener('click', function (e) {
    const toggle = e.target.closest('.vat-toggle-entries');
    if (!toggle) return;
    e.preventDefault();
    const id = toggle.getAttribute('data-target');
    const row = id ? document.getElementById(id) : null;
    if (!row) return;
    const opening = row.style.display === 'none';
    row.style.display = opening ? 'table-row' : 'none';
    if (!opening) return;
    const parent = document.querySelector('tr.vat-ret-row[data-return-id="' + id.replace('ent-', '') + '"]');
    if (parent) parent.style.display = '';

    if (row.dataset.loaded === '1') return;
    const url = toggle.getAttribute('data-entries-url') || row.getAttribute('data-entries-url');
    const body = row.querySelector('.vat-ent-body');
    if (!url || !body) return;
    body.innerHTML = '<div class="text-center text-muted py-3">Loading entries…</div>';
    fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' },
      credentials: 'same-origin',
    })
      .then(function (res) {
        if (!res.ok) throw new Error('Failed to load entries');
        return res.text();
      })
      .then(function (html) {
        body.innerHTML = html;
        row.dataset.loaded = '1';
      })
      .catch(function () {
        body.innerHTML = '<div class="text-center text-danger py-3">Could not load entries. Try again.</div>';
      });
  });

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
