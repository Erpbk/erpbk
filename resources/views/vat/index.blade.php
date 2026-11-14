@extends('layouts.app')
@section('title', 'VAT Ledger')
@php
  $hideModuleTopBarSlider = true;
  $stats = $stats ?? [
    'output' => 0, 'input' => 0, 'net' => 0,
    'output_count' => 0, 'input_count' => 0,
    'shown_count' => 0, 'total_count' => 0,
    'total_entries' => 0, 'filed_entries' => 0, 'unfiled_entries' => 0,
    'total_debit' => 0, 'total_credit' => 0,
  ];
  $year = $year ?? (string) date('Y');
  $money = fn ($n) => strip_tags(\App\Helpers\Currency::format((float) $n));
@endphp
@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}?v=20261030-tbox">
<link rel="stylesheet" href="{{ asset('css/vat-demo.css') }}?v=20261107d">
@endpush

@section('content')
<div class="riders-list-demo vat-demo" id="vatLedgerDemo"
     data-index-url="{{ route('vat.index') }}"
     data-csrf="{{ csrf_token() }}">

  <div class="rl-wrap">
    @include('flash::message')

    <div class="vat-head">
      <i class="ti ti-receipt-tax" style="font-size:20px;color:#3b3fd8"></i>
      <h5>VAT Ledger</h5>
    </div>

    <div class="totals-cards" aria-label="VAT ledger stats">
      <div class="total-card total-green">
        <div class="label"><i class="ti ti-arrow-up-circle"></i> Output VAT (Sales)</div>
        <div class="value">{{ $money($stats['output'] ?? 0) }}</div>
        <div class="meta">{{ number_format((int) ($stats['output_count'] ?? 0)) }} entries</div>
      </div>
      <div class="total-card total-amber">
        <div class="label"><i class="ti ti-arrow-down-circle"></i> Input VAT (Purchase)</div>
        <div class="value">{{ $money($stats['input'] ?? 0) }}</div>
        <div class="meta">{{ number_format((int) ($stats['input_count'] ?? 0)) }} entries</div>
      </div>
      <div class="total-card total-red">
        <div class="label"><i class="ti ti-alert-triangle"></i> Net VAT Payable</div>
        <div class="value {{ ((float)($stats['net'] ?? 0)) >= 0 ? 'unpaid-due' : 'unpaid-zero' }}">
          {{ $money($stats['net'] ?? 0) }}
        </div>
        <div class="meta">{{ number_format((int) ($stats['shown_count'] ?? 0)) }} of {{ number_format((int) ($stats['total_count'] ?? 0)) }} unfiled shown</div>
      </div>
      <div class="total-card total-blue">
        <div class="label"><i class="ti ti-list-numbers"></i> Total Entries</div>
        <div class="value">{{ number_format((int) ($stats['total_entries'] ?? 0)) }}</div>
        @php
          $scopeMeta = ($year !== '' && $year !== null) ? (string) $year : 'All years';
          if (($year !== '' && $year !== null) && request()->filled('vat_quarter_slot')) {
            $qLabel = $quarters[request('vat_quarter_slot')] ?? ('Q' . request('vat_quarter_slot'));
            $scopeMeta .= ' · ' . $qLabel;
          }
        @endphp
        <div class="meta">{{ $scopeMeta }} · VAT accounts</div>
      </div>
      <div class="total-card total-black">
        <div class="label"><i class="ti ti-file-check"></i> Filed Entries</div>
        <div class="value">{{ number_format((int) ($stats['filed_entries'] ?? 0)) }}</div>
        <div class="meta">{{ number_format((int) ($stats['unfiled_entries'] ?? 0)) }} still unfiled</div>
      </div>
    </div>

    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="actBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            VAT Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" id="chev"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="actMenu" hidden role="menu">
            @canany(['vat_create', 'vat_edit'])
            <a role="menuitem" href="javascript:void(0);" id="vatReturnFileMenuBtn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 12v6"/></svg>
              <span><strong>File Return</strong><em>File selected ledger entries for a quarter</em></span>
            </a>
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="xl" data-title="New VAT Payment" data-action="{{ route('payments.create', ['invoice_type' => 'vat_return', 'vat_payment' => 1]) }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6"/></svg>
              <span><strong>New VAT Payment</strong><em>Allocate payment to unpaid returns</em></span>
            </a>
            @endcanany
            <a role="menuitem" href="{{ route('vat.returns.index') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 7h16M4 12h16M4 17h10"/></svg>
              <span><strong>VAT Returns</strong><em>View unpaid / paid returns &amp; VP vouchers</em></span>
            </a>
          </div>
        </div>
        <a class="rl-btn" id="allBtn" href="{{ route('vat.index') }}?vat_year=">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg>
          All Entries
        </a>
      </div>
      <div class="rl-tb-right">
        <button type="button" class="rl-btn icon" id="copyBtn" title="Copy table to clipboard" aria-label="Copy table">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
        </button>
        <form method="GET" action="{{ route('vat.index') }}" id="vatQuickSearchForm" class="d-inline">
          @foreach(request()->except(['quick_search', 'page']) as $k => $v)
            @if(is_array($v))
              @foreach($v as $vv)
                <input type="hidden" name="{{ $k }}[]" value="{{ $vv }}">
              @endforeach
            @else
              <input type="hidden" name="{{ $k }}" value="{{ $v }}">
            @endif
          @endforeach
          <input class="rl-search" id="q" name="quick_search" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
        </form>
        <button type="button" class="rl-btn openFilterSidebar" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Entries
          @if(($activeFilterCount ?? 0) > 0)
            <span class="cnt">{{ $activeFilterCount }}</span>
          @endif
        </button>
      </div>
    </div>

    <div class="rl-tbox" id="tbox">
      @include('vat.table', ['rows' => $rows])
    </div>
  </div>
</div>

{{-- Floating selection bar --}}
<div class="vat-float" id="vatSelectionBar" aria-live="polite">
  <div><span class="k">Selected</span><span class="n" id="vatSelCount">0</span></div>
  <div><span class="k">Output</span><span class="n" id="vatSelOutput" style="color:#15803d">0.00</span></div>
  <div><span class="k">Input</span><span class="n" id="vatSelInput" style="color:#c2410c">0.00</span></div>
  <div><span class="k">Net Payable</span><span class="n" id="vatSelNet" style="color:#dc2626">0.00</span></div>
  <button type="button" class="rl-btn" id="vatSelClear" style="background:#fff;color:#3b3fd8;border:2px solid #bfdbfe;box-shadow:none">Clear</button>
  @canany(['vat_create', 'vat_edit'])
  <button type="button" class="rl-btn" id="vatSelReturnBtn">⇪ Return File</button>
  @endcanany
</div>

{{-- Filter sidebar --}}
<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Entries</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ route('vat.index') }}" method="GET">
      @if(request()->filled('quick_search'))
        <input type="hidden" name="quick_search" value="{{ request('quick_search') }}">
      @endif
      <div class="row">
        <div class="form-group col-md-12">
          <label for="vat_type">Filter by Input / Output</label>
          <select class="form-control" id="vat_type" name="vat_type">
            <option value="">All</option>
            <option value="s" {{ request('vat_type') === 's' ? 'selected' : '' }}>Output (Sales)</option>
            <option value="p" {{ request('vat_type') === 'p' ? 'selected' : '' }}>Input (Purchase)</option>
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="vat_year">Year</label>
          <select class="form-control" id="vat_year" name="vat_year">
            @foreach($years ?? [] as $val => $label)
              <option value="{{ $val }}" {{ (string) $year === (string) $val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="vat_quarter_slot">Filter by Quarter</label>
          <select class="form-control" id="vat_quarter_slot" name="vat_quarter_slot">
            <option value="">All Quarters</option>
            @foreach($quarters ?? [] as $slot => $label)
              <option value="{{ $slot }}" {{ (string) request('vat_quarter_slot') === (string) $slot ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="date_from">Date From</label>
          <input type="date" class="form-control" id="date_from" name="date_from" value="{{ request('date_from') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="date_to">Date To</label>
          <input type="date" class="form-control" id="date_to" name="date_to" value="{{ request('date_to') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="billing_month">Filter by Billing Month</label>
          <select class="form-control" id="billing_month" name="billing_month">
            <option value="">All Months</option>
            @foreach($filterMonths ?? [] as $m)
              <option value="{{ $m }}" {{ request('billing_month') === $m ? 'selected' : '' }}>
                {{ date('M Y', strtotime($m . '-01')) }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="module">Filter by Module</label>
          <select class="form-control" id="module" name="module">
            <option value="">All Modules</option>
            @foreach($filterModules ?? [] as $mod)
              <option value="{{ $mod }}" {{ request('module') === $mod ? 'selected' : '' }}>{{ $mod }}</option>
            @endforeach
          </select>
        </div>
      </div>
      <div class="col-md-12 form-group text-center mt-3 vat-filter-actions">
        <button type="button" class="btn btn-outline-secondary me-2" id="vatFilterCancelBtn">Cancel</button>
        <a href="{{ route('vat.index') }}" class="btn btn-outline-primary me-2">Reset</a>
        <button type="submit" class="btn btn-primary">
          <i class="fa fa-filter mx-2"></i> Filter Data
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Modal: File VAT Return --}}
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
          <p class="text-muted small">Choose the year and quarter for the return. Only the <strong>entries you selected</strong> in the ledger will be included.</p>
          <div class="mb-3">
            <label class="form-label">Year</label>
            <select name="vat_year" class="form-select" required>
              @foreach($years ?? [] as $val => $label)
                @if($val !== '')
                  <option value="{{ $val }}" {{ (string) $year === (string) $val ? 'selected' : '' }}>{{ $label }}</option>
                @endif
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">VAT Period (Quarter)</label>
            <select name="vat_quarter_slot" class="form-select" required>
              @foreach($quarters ?? [] as $slot => $label)
                <option value="{{ $slot }}" {{ request('vat_quarter_slot') == (string) $slot ? 'selected' : '' }}>{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div id="vatReturnInfo" class="small rounded border p-2" style="background:#f8faff;border-color:#e0ecff!important;color:#334155"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">File Return</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('third_party_scripts')
<script>
(function () {
  const fmt = (n) => Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const selectAll = document.getElementById('vatSelectAll');
  const bar = document.getElementById('vatSelectionBar');
  const actBtn = document.getElementById('actBtn');
  const actMenu = document.getElementById('actMenu');
  const filterBtn = document.getElementById('filterBtn');
  const filterSidebar = document.getElementById('filterSidebar');
  const filterOverlay = document.getElementById('filterOverlay');
  const closeSidebar = document.getElementById('closeSidebar');
  const modalEl = document.getElementById('vatReturnQuarterModal');

  function openReturnModal() {
    const checked = document.querySelectorAll('.vat-entry-cb:checked');
    if (!checked.length) {
      if (window.toastr) toastr.warning('Please select at least one entry in the ledger.');
      else alert('Please select at least one entry in the ledger.');
      return;
    }
    updateReturnInfo();
    if (window.bootstrap && modalEl) {
      bootstrap.Modal.getOrCreateInstance(modalEl).show();
    } else if (window.$ && modalEl) {
      $(modalEl).modal('show');
    }
  }

  function updateSelection() {
    const checked = Array.from(document.querySelectorAll('.vat-entry-cb:checked'));
    let output = 0, input = 0;
    checked.forEach((cb) => {
      const debit = parseFloat(cb.dataset.debit || 0);
      const credit = parseFloat(cb.dataset.credit || 0);
      if (cb.dataset.output === '1') output += credit - debit;
      else input += debit - credit;
    });
    document.getElementById('vatSelCount').textContent = checked.length;
    document.getElementById('vatSelOutput').textContent = fmt(output);
    document.getElementById('vatSelInput').textContent = fmt(input);
    document.getElementById('vatSelNet').textContent = fmt(output - input);
    if (bar) bar.classList.toggle('show', checked.length > 0);

    const all = document.querySelectorAll('.vat-entry-cb');
    if (selectAll) {
      selectAll.checked = all.length > 0 && checked.length === all.length;
      selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
    }
    document.querySelectorAll('.vat-row').forEach((tr) => {
      const cb = tr.querySelector('.vat-entry-cb');
      tr.classList.toggle('vat-chk', !!(cb && cb.checked));
    });
    updateReturnInfo();
  }

  function updateReturnInfo() {
    const info = document.getElementById('vatReturnInfo');
    if (!info) return;
    const checked = Array.from(document.querySelectorAll('.vat-entry-cb:checked'));
    let output = 0, input = 0;
    checked.forEach((cb) => {
      const debit = parseFloat(cb.dataset.debit || 0);
      const credit = parseFloat(cb.dataset.credit || 0);
      if (cb.dataset.output === '1') output += credit - debit;
      else input += debit - credit;
    });
    info.innerHTML = '<b>' + checked.length + '</b> entries selected · Output <b style="color:#15803d">' + fmt(output) +
      '</b> · Input <b style="color:#c2410c">' + fmt(input) +
      '</b> · Net Payable <b style="color:#dc2626">' + fmt(output - input) + '</b>' +
      (checked.length ? '' : '<div style="color:#dc2626;margin-top:4px">Please select at least one entry in the ledger.</div>');
  }

  if (selectAll) {
    selectAll.addEventListener('change', function () {
      document.querySelectorAll('.vat-entry-cb').forEach((cb) => { cb.checked = selectAll.checked; });
      updateSelection();
    });
  }
  document.addEventListener('change', function (e) {
    if (e.target && e.target.classList.contains('vat-entry-cb')) updateSelection();
  });
  const clearBtn = document.getElementById('vatSelClear');
  if (clearBtn) {
    clearBtn.addEventListener('click', function () {
      document.querySelectorAll('.vat-entry-cb').forEach((cb) => { cb.checked = false; });
      updateSelection();
    });
  }
  const selReturn = document.getElementById('vatSelReturnBtn');
  const menuReturn = document.getElementById('vatReturnFileMenuBtn');
  if (selReturn) selReturn.addEventListener('click', openReturnModal);
  if (menuReturn) menuReturn.addEventListener('click', openReturnModal);

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

  const q = document.getElementById('q');
  const qForm = document.getElementById('vatQuickSearchForm');
  let qTimer = null;
  if (q && qForm) {
    q.addEventListener('keydown', function (e) {
      if (e.key === 'Enter') { e.preventDefault(); qForm.submit(); }
    });
  }

  const copyBtn = document.getElementById('copyBtn');
  if (copyBtn) {
    copyBtn.addEventListener('click', async function () {
      const rows = Array.from(document.querySelectorAll('#vatLedgerTable tbody tr.vat-row'));
      const header = 'Date\tAccount\tReference\tMonth\tVoucher\tAttachment\tModule\tNarration\tDebit\tCredit';
      const lines = rows.map((tr) => {
        const tds = tr.querySelectorAll('td');
        return [
          (tds[1] || {}).innerText || '',
          (tds[2] || {}).innerText || '',
          (tds[3] || {}).innerText || '',
          (tds[4] || {}).innerText || '',
          (tds[5] || {}).innerText || '',
          (tds[6] || {}).innerText || '',
          (tds[7] || {}).innerText || '',
          ((tds[8] || {}).innerText || '').replace(/\s+/g, ' ').trim(),
          (tds[9] || {}).innerText || '',
          (tds[10] || {}).innerText || '',
        ].join('\t');
      });
      const text = [header, ...lines].join('\n');
      try {
        await navigator.clipboard.writeText(text);
        if (window.toastr) toastr.success('Copied ' + rows.length + ' entries');
      } catch (err) {
        const a = document.createElement('textarea');
        a.value = text; document.body.appendChild(a); a.select();
        document.execCommand('copy'); a.remove();
      }
    });
  }

  updateSelection();

  @if(session('success'))
    if (window.toastr) toastr.success(@json(session('success')));
  @endif
  @if(session('error'))
    if (window.toastr) toastr.error(@json(session('error')));
  @endif
})();
</script>
@endpush
