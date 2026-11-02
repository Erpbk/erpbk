@extends('layouts.app')
@section('title','Employee Invoices')
@php
  $hideModuleTopBarSlider = true;
  $stats = $stats ?? [
    'invoice_count' => 0,
    'paid_count' => 0,
    'partial_count' => 0,
    'unpaid_count' => 0,
    'total_invoice_amount' => 0,
    'project_label' => 'All',
    'billing_month_label' => 'All Months',
    'total_payable_salary' => 0,
    'paid_amount' => 0,
    'balance' => 0,
    'partial_due' => 0,
    'money_available' => true,
  ];
@endphp
@push('third_party_stylesheets')
@include('invoices.partials.status_badge_styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}?v=20261030-tbox">
<link rel="stylesheet" href="{{ asset('css/employees-demo.css') }}?v=20261102inv2">
<style>
  .invoice-checkbox, #selectAllCheckbox { transform: scale(1.2); cursor: pointer; }
  .filter-sidebar .select2-container { width: 100% !important; }
  body > .select2-container { z-index: 12000 !important; }
  body > .select2-container .select2-dropdown { z-index: 12001 !important; }
  #deleteSelectedBtn {
    background: #dc2626 !important;
    border-color: #dc2626 !important;
    color: #fff !important;
  }
  #deleteSelectedBtn:hover { background: #b91c1c !important; border-color: #b91c1c !important; }
  .emp-demo .ei-emp { display: flex; flex-direction: column; align-items: flex-start; gap: 1px; line-height: 1.25; text-align: left; }
  .emp-demo .ei-emp .ei-name,
  .emp-demo .ei-inv-link { color: #1d4ed8; font-weight: 600; text-decoration: none; }
  .emp-demo .ei-emp .ei-name:hover,
  .emp-demo .ei-inv-link:hover { text-decoration: underline; }
  .emp-demo .ei-emp .ei-code { font-size: 11px; color: #64748b; font-weight: 500; }
  .emp-demo .ei-branch-pill {
    display: inline-block; max-width: 100%;
    padding: 3px 10px; border-radius: 999px;
    background: #dbeafe; color: #1d4ed8;
    font-size: 11.5px; font-weight: 600; line-height: 1.35;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
  }
  .emp-demo .ei-dash { color: #94a3b8; }
  .emp-demo .ei-desc { font-size: 12.5px; color: #334155; }
  .emp-demo .ei-status-cell { display: flex; flex-direction: column; align-items: center; gap: 2px; }
  .emp-demo .ei-status-due { font-size: 11px; font-weight: 700; color: #dc2626; font-variant-numeric: tabular-nums; }
  .emp-demo .ei-money { font-variant-numeric: tabular-nums; white-space: nowrap; }
  .emp-demo .ei-total { font-weight: 700; color: #0f172a; }
  /* Compact circular hover card for invoice list (matches screenshot) */
  .emp-demo .emp-hv { width: 210px; }
  .emp-demo .emp-hv .ava {
    width: 96px; height: 96px; border-radius: 50%;
  }
  .emp-demo .emp-hv .ava.ph { font-size: 22px; }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@can('employees_invoice_view')
@php
  $eiIndexUrl = safe_route('employeeInvoices.index', [], 'employeeInvoices');
  $eiExportUrl = safe_route('employeeInvoices.export', request()->except(['page', '_token']), 'employeeInvoices/export');
  $eiImportFormUrl = safe_route('employeeInvoices.import.form', [], 'employeeInvoices/import');
  $eiBulkDeleteUrl = safe_route('employeeInvoices.bulkDelete', [], 'employeeInvoices/bulk-delete');
  $eiCreateUrl = safe_route('employeeInvoices.create', [], 'employeeInvoices/create');
  $eiPayCreateUrl = safe_route('payments.create', ['employee_payment' => 1], 'payments/create');
@endphp
<div class="riders-list-demo emp-demo" id="employeeInvoicesListDemo"
     data-index-url="{{ $eiIndexUrl }}"
     data-export-url="{{ $eiExportUrl }}"
     data-csrf="{{ csrf_token() }}">

  <div class="rl-wrap">
    @include('flash::message')

    @php
      $money = fn ($n) => strip_tags(\App\Helpers\Currency::format((float) $n));
    @endphp
    <div class="iv-cards" id="employeeInvoiceStats" aria-label="Employee invoice stats">
      <div class="iv-card" style="--c:#6d5bd0">
        <div class="lb">Billing Month</div>
        <div class="vl" id="stat_billing_month_label">{{ $stats['billing_month_label'] ?? 'All Months' }}</div>
        <div class="st"><span id="stat_invoice_count">{{ number_format((int) ($stats['invoice_count'] ?? 0)) }}</span> invoices</div>
      </div>
      <div class="iv-card" style="--c:#3b3fd8">
        <div class="lb">Total Invoiced</div>
        <div class="vl" id="stat_total_invoice_amount">{{ $money($stats['total_invoice_amount'] ?? 0) }}</div>
        <div class="st">Subtotal + VAT</div>
      </div>
      <div class="iv-card" style="--c:#16a34a">
        <div class="lb">Paid Invoices</div>
        <div class="vl" id="stat_paid_amount">{{ $money($stats['paid_amount'] ?? 0) }}</div>
        <div class="st"><span id="stat_paid_count">{{ number_format((int) ($stats['paid_count'] ?? 0)) }}</span> invoices</div>
      </div>
      <div class="iv-card" style="--c:#dc2626">
        <div class="lb">Unpaid Invoices</div>
        <div class="vl" id="stat_balance">{{ $money($stats['balance'] ?? 0) }}</div>
        <div class="st"><span id="stat_unpaid_count">{{ number_format((int) ($stats['unpaid_count'] ?? 0)) }}</span> invoices</div>
      </div>
      <div class="iv-card" style="--c:#d97706">
        <div class="lb">Partially Paid Due</div>
        <div class="vl" id="stat_partial_due">{{ $money($stats['partial_due'] ?? 0) }}</div>
        <div class="st"><span id="stat_partial_invoice_count">{{ number_format((int) ($stats['partial_count'] ?? 0)) }}</span> invoices</div>
      </div>
      <div class="iv-card" style="--c:#f59e0b">
        <div class="lb">Status</div>
        <a class="sr" href="{{ safe_route('employeeInvoices.index', array_merge(request()->except('page','status'), ['status' => '1']), 'employeeInvoices') }}" style="color:#15803d"><span>Paid</span><span id="stat_paid_count_row">{{ number_format((int) ($stats['paid_count'] ?? 0)) }}</span></a>
        <a class="sr" href="{{ safe_route('employeeInvoices.index', array_merge(request()->except('page','status'), ['status' => '3']), 'employeeInvoices') }}" style="color:#d97706"><span>Partially Paid</span><span id="stat_partial_count">{{ number_format((int) ($stats['partial_count'] ?? 0)) }}</span></a>
        <a class="sr" href="{{ safe_route('employeeInvoices.index', array_merge(request()->except('page','status'), ['status' => '0']), 'employeeInvoices') }}" style="color:#dc2626"><span>Unpaid</span><span id="stat_unpaid_count_row">{{ number_format((int) ($stats['unpaid_count'] ?? 0)) }}</span></a>
      </div>
    </div>

    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="actBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Invoice Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" id="chev"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="actMenu" hidden role="menu">
            @can('employees_invoice_create')
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="xl" data-title="Create Employee Invoice" data-action="{{ $eiCreateUrl }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span><strong>Create New Invoice</strong><em>Add a new employee invoice</em></span>
            </a>
            <a role="menuitem" href="{{ $eiImportFormUrl }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 18v-6M9 15l3-3 3 3"/></svg>
              <span><strong>Import Invoices</strong><em>Upload invoices from a file</em></span>
            </a>
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="xl" data-title="Salary Payment" data-action="{{ $eiPayCreateUrl }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              <span><strong>Salary Payment</strong><em>Record employee salary payment</em></span>
            </a>
            @endcan
            <a role="menuitem" id="exportEmployeeInvoicesBtn" href="{{ $eiExportUrl }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 12v6M9 15l3 3 3-3"/></svg>
              <span><strong>Export</strong><em>Excel with frozen header row</em></span>
            </a>
          </div>
        </div>
        <a class="rl-btn" id="allBtn" href="{{ $eiIndexUrl }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 20c0-3-2-5-5-5.4"/></svg>
          All Invoices
        </a>
      </div>
      <div class="rl-tb-right">
        <button type="button" class="rl-btn icon" id="copyBtn" title="Copy table to clipboard" aria-label="Copy table">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="copyIco"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
        </button>
        <button type="button" class="rl-btn icon" id="resetFiltersBtn" title="Reset filters" aria-label="Reset filters">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
        </button>
        <input class="rl-search" id="q" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
        <button type="button" class="rl-btn" id="deleteSelectedBtn" style="display:none;" onclick="deleteSelectedInvoices()">
          <i class="fa fa-trash"></i> Delete Selected
        </button>
        <button type="button" class="rl-btn openFilterSidebar" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Invoices
        </button>
      </div>
    </div>

    <div class="rl-tbox" id="tbox">
      @include('employee_invoices.table', ['data' => $data])
    </div>

    <div id="rlPagerWrap">
      @if(method_exists($data, 'links'))
        @include('components.riders-demo-pagination', ['paginator' => $data, 'entityLabel' => 'invoices'])
      @endif
    </div>
  </div>
</div>
<div class="emp-hv" id="empHv" role="tooltip" aria-hidden="true"></div>

<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Invoices</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ $eiIndexUrl }}" method="GET">
      @if(request()->filled('quick_search'))
      <input type="hidden" name="quick_search" value="{{ request('quick_search') }}">
      @endif
      <div class="row">
        <div class="form-group col-md-12 col-sm-12">
          <label for="id">Invoice ID</label>
          <input type="text" name="id" id="id" class="form-control" placeholder="e.g. 12 or EMP_INV00012" value="{{ request('id') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="employee_id">Filter by Employee</label>
          <select class="form-control" id="employee_id" name="employee_id">
            @php
              $employees = \App\Models\Employee::query()
                ->select('id', 'employee_id', 'name')
                ->orderBy('name')
                ->get();
            @endphp
            <option value="">Select</option>
            @foreach($employees as $employee)
              <option value="{{ $employee->id }}" {{ (string) request('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                {{ $employee->employee_id }} - {{ $employee->name }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="zone">Filter by Zone</label>
          <select class="form-control" id="zone" name="zone">
            @php
              $zones = company_table('employee_invoices')
                ->whereNotNull('zone')
                ->where('zone', '!=', '')
                ->pluck('zone')
                ->unique()
                ->sort()
                ->values();
            @endphp
            <option value="">Select</option>
            @foreach($zones as $zone)
              <option value="{{ $zone }}" {{ request('zone') == $zone ? 'selected' : '' }}>{{ $zone }}</option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="billing_month">Billing Month</label>
          <input type="month" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="status">Status</label>
          <select class="form-control" id="status" name="status">
            <option value="">Select</option>
            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Unpaid</option>
            <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Partially Paid</option>
            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Paid</option>
          </select>
        </div>
        <div class="col-md-12 form-group text-center">
          <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
        </div>
      </div>
    </form>
  </div>
</div>
<div class="rl-toast" id="rlToast" hidden></div>
@else
<div class="card">
  <div class="card-body">
    <h5 class="card-title">You are not authorized to access this page</h5>
  </div>
</div>
@endcan
@endsection

@section('page-script')
@include('delete_requests._confirm_delete_script', [
    'entityName' => 'Employee Invoice',
    'confirmText' => 'This will submit a delete request or move the invoice to the Recycle Bin.',
    'method' => 'GET',
])
<script type="text/javascript">
(function () {
  const root = document.getElementById('employeeInvoicesListDemo');
  if (!root) return;

  const indexUrl = root.dataset.indexUrl;
  const exportUrl = root.dataset.exportUrl;
  const currencySymbol = @json(\App\Helpers\Currency::symbol());
  let ajaxSeq = 0;

  function openFilter(e) {
    if (e) {
      e.preventDefault();
      e.stopPropagation();
    }
    document.getElementById('filterSidebar')?.classList.add('open');
    document.getElementById('filterOverlay')?.classList.add('show');
  }
  function closeFilter() {
    document.getElementById('filterSidebar')?.classList.remove('open');
    document.getElementById('filterOverlay')?.classList.remove('show');
  }

  document.getElementById('actBtn')?.addEventListener('click', function (e) {
    e.stopPropagation();
    const menu = document.getElementById('actMenu');
    const open = menu && !menu.hidden;
    if (menu) menu.hidden = open;
    this.setAttribute('aria-expanded', open ? 'false' : 'true');
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.rl-dd')) {
      const menu = document.getElementById('actMenu');
      if (menu) menu.hidden = true;
      document.getElementById('actBtn')?.setAttribute('aria-expanded', 'false');
    }
  });

  document.getElementById('filterBtn')?.addEventListener('click', openFilter);
  document.getElementById('closeSidebar')?.addEventListener('click', closeFilter);
  document.getElementById('filterOverlay')?.addEventListener('click', closeFilter);

  if (window.jQuery && window.jQuery.fn && window.jQuery.fn.select2) {
    window.jQuery(function ($) {
      const $body = $(document.body);

      [
        { sel: '#employee_id', placeholder: 'Filter By Employee' },
        { sel: '#zone', placeholder: 'Filter By Zone' },
        { sel: '#status', placeholder: 'Filter By Status' },
      ].forEach(function (field) {
        const $el = $(field.sel);
        if (!$el.length) return;
        if ($el.hasClass('select2-hidden-accessible')) {
          $el.select2('destroy');
        }
        $el.select2({
          dropdownParent: $body,
          allowClear: true,
          width: '100%',
          placeholder: field.placeholder,
        });
      });
    });
  }

  function formatAmount(value) {
    const num = Number(value || 0);
    return currencySymbol + num.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  }

  function currentQueryParams() {
    return Object.fromEntries(new URL(window.location.href).searchParams.entries());
  }

  function buildFilterParams(extra) {
    const params = {};
    const form = document.getElementById('filterForm');
    if (form) {
      new FormData(form).forEach(function (value, key) {
        if (key === '_token') return;
        const v = String(value || '').trim();
        if (v !== '') params[key] = v;
      });
    }
    const q = (document.getElementById('q')?.value || '').trim();
    if (q) params.quick_search = q;
    else delete params.quick_search;

    const perPage = currentQueryParams().per_page;
    if (perPage) params.per_page = perPage;

    return Object.assign(params, extra || {});
  }

  function updateExportLink(params) {
    const query = new URLSearchParams(params || {}).toString();
    const link = document.getElementById('exportEmployeeInvoicesBtn');
    if (link) link.setAttribute('href', exportUrl + (query ? '?' + query : ''));
  }

  function applyStats(stats) {
    if (!stats) return;
    const setText = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };
    setText('stat_billing_month_label', stats.billing_month_label ?? 'All Months');
    setText('stat_invoice_count', Number(stats.invoice_count || 0).toLocaleString());
    setText('stat_total_invoice_amount', formatAmount(stats.total_invoice_amount));
    setText('stat_paid_amount', formatAmount(stats.paid_amount));
    setText('stat_balance', formatAmount(stats.balance));
    setText('stat_partial_due', formatAmount(stats.partial_due));
    setText('stat_paid_count', Number(stats.paid_count || 0).toLocaleString());
    setText('stat_partial_count', Number(stats.partial_count || 0).toLocaleString());
    setText('stat_partial_invoice_count', Number(stats.partial_count || 0).toLocaleString());
    setText('stat_unpaid_count', Number(stats.unpaid_count || 0).toLocaleString());
    setText('stat_paid_count_row', Number(stats.paid_count || 0).toLocaleString());
    setText('stat_unpaid_count_row', Number(stats.unpaid_count || 0).toLocaleString());

    const balanceEl = document.getElementById('stat_balance');
    if (balanceEl) {
      balanceEl.classList.remove('unpaid-zero', 'unpaid-due');
      balanceEl.classList.add(Math.abs(Number(stats.balance || 0)) < 0.005 ? 'unpaid-zero' : 'unpaid-due');
    }
  }

  function bindClientSort() {
    const table = document.querySelector('#tbox #dataTableBuilder');
    if (!table) return;
    const headers = table.querySelectorAll('th.sorting');
    const tbody = table.querySelector('tbody');
    headers.forEach(function (header, colIndex) {
      header.onclick = function () {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = header.classList.contains('sorted-asc');
        headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
        header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');
        rows.sort(function (a, b) {
          let aText = (a.children[colIndex]?.textContent || '').trim().toLowerCase();
          let bText = (b.children[colIndex]?.textContent || '').trim().toLowerCase();
          const aVal = isNaN(aText.replace(/,/g, '')) ? aText : parseFloat(aText.replace(/,/g, ''));
          const bVal = isNaN(bText.replace(/,/g, '')) ? bText : parseFloat(bText.replace(/,/g, ''));
          if (aVal < bVal) return isAsc ? 1 : -1;
          if (aVal > bVal) return isAsc ? -1 : 1;
          return 0;
        });
        rows.forEach(row => tbody.appendChild(row));
      };
    });
  }

  function loadEmployeeInvoices(params) {
    const seq = ++ajaxSeq;
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'flex';
    const loaderStart = Date.now();
    const requestParams = Object.assign({}, params || {});
    delete requestParams._token;

    const query = new URLSearchParams(requestParams).toString();
    fetch(indexUrl + (query ? '?' + query : ''), {
      method: 'GET',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
    })
      .then(r => r.json())
      .then(function (data) {
        if (seq !== ajaxSeq) return;
        const tbox = document.getElementById('tbox');
        const pagerWrap = document.getElementById('rlPagerWrap');
        if (tbox) tbox.innerHTML = data.html || data.tableData || '';
        if (pagerWrap) pagerWrap.innerHTML = data.pagination || data.paginationLinks || '';
        applyStats(data.stats);
        history.pushState(null, '', indexUrl + (query ? '?' + query : ''));
        updateExportLink(requestParams);
        bindClientSort();
        updateDeleteButton();
      })
      .catch(function (err) { console.error(err); })
      .finally(function () {
        const elapsed = Date.now() - loaderStart;
        const remaining = 1000 - elapsed;
        setTimeout(function () {
          if (overlay) overlay.style.display = 'none';
        }, remaining > 0 ? remaining : 0);
      });
  }

  document.getElementById('filterForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    closeFilter();
    loadEmployeeInvoices(buildFilterParams({ page: 1 }));
  });

  document.getElementById('q')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      loadEmployeeInvoices(buildFilterParams({ page: 1 }));
    }
  });
  document.getElementById('q')?.addEventListener('search', function () {
    if (!this.value) loadEmployeeInvoices(buildFilterParams({ page: 1 }));
  });

  document.getElementById('resetFiltersBtn')?.addEventListener('click', function () {
    window.location.href = indexUrl;
  });

  let toastTimer;
  function toast(msg) {
    const el = document.getElementById('rlToast');
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.hidden = true; }, 2400);
  }

  function getVisibleCopyColumns(table) {
    const visibleCols = [];
    Array.from(table.querySelectorAll('thead th')).forEach(function (th, i) {
      if (th.classList.contains('nosort') || th.classList.contains('column-hidden')) return;
      if (window.getComputedStyle(th).display === 'none') return;
      const label = (th.childNodes[0]?.textContent || th.textContent || '').replace(/[⇅↑↓]/g, '').trim();
      if (!label || /^actions$/i.test(label)) return;
      visibleCols.push({ index: i, label: label, key: th.getAttribute('data-k') || '' });
    });
    return visibleCols;
  }

  function cellPlainText(td) {
    if (!td) return '-';
    const clone = td.cloneNode(true);
    clone.querySelectorAll('.dropdown').forEach(function (n) { n.remove(); });
    return clone.innerText.replace(/\s+/g, ' ').trim() || '-';
  }

  function cellExportValue(td, col) {
    if (!td) return { text: '-', href: null };
    if (col.key === 'rider' || col.key === 'employee' || col.key === 'invoice' || td.classList.contains('name')) {
      const a = td.querySelector('a[href]');
      if (a) return { text: (a.textContent || '').trim() || '-', href: a.href };
    }
    if (col.key === 'status' || col.key === 'rider_status') {
      const badge = td.querySelector('.badge');
      if (badge) return { text: (badge.textContent || '').trim() || '-', href: null };
    }
    return { text: cellPlainText(td), href: null };
  }

  function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
  }

  document.getElementById('copyBtn')?.addEventListener('click', async function () {
    const table = document.querySelector('#tbox #dataTableBuilder') || document.getElementById('dataTableBuilder');
    if (!table) return;

    const visibleCols = getVisibleCopyColumns(table);
    if (!visibleCols.length) {
      toast('No visible columns to copy');
      return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr')).filter(function (tr) {
      return !tr.querySelector('.rl-empty');
    });
    const matrix = [visibleCols.map(function (c) { return { text: c.label, href: null }; })];
    rows.forEach(function (tr) {
      matrix.push(visibleCols.map(function (col) {
        return cellExportValue(tr.children[col.index], col);
      }));
    });

    const txt = matrix.map(function (r) {
      return r.map(function (c) { return c.text; }).join('\t');
    }).join('\n');
    const htmlTable = '<table><thead><tr>' +
      visibleCols.map(function (c) { return '<th>' + escHtml(c.label) + '</th>'; }).join('') +
      '</tr></thead><tbody>' +
      matrix.slice(1).map(function (r) {
        return '<tr>' + r.map(function (c) {
          if (c.href) {
            return '<td><a href="' + escHtml(c.href) + '">' + escHtml(c.text) + '</a></td>';
          }
          return '<td>' + escHtml(c.text) + '</td>';
        }).join('') + '</tr>';
      }).join('') +
      '</tbody></table>';

    let copied = false;
    try {
      if (window.ClipboardItem && navigator.clipboard && navigator.clipboard.write) {
        await navigator.clipboard.write([
          new ClipboardItem({
            'text/plain': new Blob([txt], { type: 'text/plain' }),
            'text/html': new Blob([htmlTable], { type: 'text/html' }),
          }),
        ]);
        copied = true;
      } else {
        await navigator.clipboard.writeText(txt);
        copied = true;
      }
    } catch (_) {
      try {
        const ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.cssText = 'position:fixed;left:-9999px';
        document.body.appendChild(ta);
        ta.select();
        copied = document.execCommand('copy');
        ta.remove();
      } catch (_) {}
    }

    if (!copied) {
      toast('Could not copy to clipboard');
      return;
    }

    const ic = document.getElementById('copyIco');
    if (ic) {
      const o = ic.innerHTML;
      ic.innerHTML = '<path d="M5 12l5 5 9-10"/>';
      setTimeout(function () { ic.innerHTML = o; }, 1500);
    }
    toast('Copied ' + rows.length + ' row' + (rows.length === 1 ? '' : 's') + ' to clipboard');
  });

  document.getElementById('rlPagerWrap')?.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-page]');
    if (!link) return;
    e.preventDefault();
    const page = link.getAttribute('data-page');
    if (!page) return;
    loadEmployeeInvoices(buildFilterParams({ page: page }));
  });

  document.getElementById('rlPagerWrap')?.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'rlPerPage') {
      const params = buildFilterParams({ per_page: e.target.value });
      delete params.page;
      loadEmployeeInvoices(params);
    }
  });

  updateExportLink(currentQueryParams());
  bindClientSort();

  // Employee hover card (photo → ID → name) — same pattern as employees list
  (function () {
    const tip = document.getElementById('empHv');
    if (!tip) return;
    let hideT;
    const show = (a) => {
      clearTimeout(hideT);
      const photo = a.dataset.hvPhoto || '';
      const initials = a.dataset.hvInitials || '';
      const av = photo
        ? `<img class="ava" src="${photo}" alt="">`
        : `<div class="ava ph">${initials}</div>`;
      tip.innerHTML = `<div class="emp-hv-top">${av}<div class="idpill">${a.dataset.hvId || ''}</div><div class="nm">${a.dataset.hvName || ''}</div></div>`;
      tip.classList.add('show');
      tip.setAttribute('aria-hidden', 'false');
      const r = a.getBoundingClientRect();
      const tw = tip.offsetWidth || 210;
      const th = tip.offsetHeight || 200;
      let left = r.left;
      let top = r.bottom + 8;
      if (left + tw > window.innerWidth - 12) left = window.innerWidth - tw - 12;
      if (top + th > window.innerHeight - 12) top = r.top - th - 8;
      tip.style.left = Math.max(8, left) + 'px';
      tip.style.top = Math.max(8, top) + 'px';
    };
    const hide = () => {
      hideT = setTimeout(() => {
        tip.classList.remove('show');
        tip.setAttribute('aria-hidden', 'true');
      }, 120);
    };
    document.addEventListener('mouseover', (e) => {
      const a = e.target.closest('#employeeInvoicesListDemo .emp-name-link');
      if (a) show(a);
    });
    document.addEventListener('mouseout', (e) => {
      const a = e.target.closest('#employeeInvoicesListDemo .emp-name-link');
      if (a && !e.relatedTarget?.closest?.('.emp-hv')) hide();
    });
    tip.addEventListener('mouseenter', () => clearTimeout(hideT));
    tip.addEventListener('mouseleave', hide);
  })();
})();

function toggleSelectAll(selectAllCheckbox) {
  document.querySelectorAll('.invoice-checkbox').forEach(function (checkbox) {
    checkbox.checked = selectAllCheckbox.checked;
  });
  updateDeleteButton();
}

function updateDeleteButton() {
  const selectedCheckboxes = document.querySelectorAll('.invoice-checkbox:checked');
  const deleteBtn = document.getElementById('deleteSelectedBtn');
  const selectAllCheckbox = document.getElementById('selectAllCheckbox');
  if (!deleteBtn) return;

  if (selectedCheckboxes.length > 0) {
    deleteBtn.style.display = 'inline-flex';
    deleteBtn.innerHTML = '<i class="fa fa-trash"></i> Delete Selected (' + selectedCheckboxes.length + ')';
  } else {
    deleteBtn.style.display = 'none';
  }

  if (!selectAllCheckbox) return;
  const allCheckboxes = document.querySelectorAll('.invoice-checkbox');
  if (selectedCheckboxes.length === 0) {
    selectAllCheckbox.indeterminate = false;
    selectAllCheckbox.checked = false;
  } else if (selectedCheckboxes.length === allCheckboxes.length) {
    selectAllCheckbox.indeterminate = false;
    selectAllCheckbox.checked = true;
  } else {
    selectAllCheckbox.indeterminate = true;
  }
}

function deleteSelectedInvoices() {
  const selectedIds = Array.from(document.querySelectorAll('.invoice-checkbox:checked')).map(cb => cb.value);
  if (selectedIds.length === 0) {
    Swal.fire('Error', 'Please select invoices to delete', 'error');
    return;
  }

  Swal.fire({
    title: 'Are you sure?',
    text: 'You are about to delete ' + selectedIds.length + ' invoice(s). This action cannot be undone!',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonColor: '#d33',
    cancelButtonColor: '#3085d6',
    confirmButtonText: 'Yes, delete them!'
  }).then(function (result) {
    if (!result.isConfirmed) return;
    Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: () => Swal.showLoading() });
    $.ajax({
      url: @json($eiBulkDeleteUrl),
      type: 'POST',
      data: { _token: @json(csrf_token()), invoice_ids: selectedIds },
      success: function (response) {
        Swal.fire({ title: 'Success!', text: response.message, icon: 'success' }).then(() => location.reload());
      },
      error: function (xhr) {
        Swal.fire({
          title: 'Error!',
          text: (xhr.responseJSON && xhr.responseJSON.message) || 'An error occurred while deleting invoices.',
          icon: 'error'
        });
      }
    });
  });
}
</script>
@endsection
