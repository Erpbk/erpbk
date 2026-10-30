@extends('layouts.app')
@section('title','Rider Invoices')
@php
  $hideModuleTopBarSlider = true;
  $stats = $stats ?? [
    'invoice_count' => 0,
    'paid_count' => 0,
    'partial_count' => 0,
    'unpaid_count' => 0,
    'total_invoice_amount' => 0,
    'project_label' => 'All',
    'billing_month_label' => 'All',
    'total_payable_salary' => 0,
    'paid_amount' => 0,
    'balance' => 0,
    'money_available' => true,
  ];
@endphp
@push('third_party_stylesheets')
@include('invoices.partials.status_badge_styles')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}">
<style>
  /* Page-only: one-row stats + combined status list. Shared .total-card lives in custom.css. */
  .riders-list-demo .totals-cards {
    margin: 0 0 12px 0;
    display: flex;
    flex-wrap: nowrap;
    gap: 8px;
  }
  .riders-list-demo .totals-cards .total-card {
    flex: 1 1 0;
    min-width: 0;
    width: auto;
    box-sizing: border-box;
  }
  .riders-list-demo .ri-status-card .ri-status-rows {
    display: flex;
    flex-direction: column;
    gap: 4px;
    margin-top: 2px;
  }
  .riders-list-demo .ri-status-card .ri-status-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    font-size: 12px;
    font-weight: 600;
    line-height: 1.25;
  }
  .riders-list-demo .ri-status-card .ri-status-row b {
    font-variant-numeric: tabular-nums;
    font-weight: 700;
  }
  .riders-list-demo .ri-status-card .ri-status-row.paid { color: #059669; }
  .riders-list-demo .ri-status-card .ri-status-row.partial { color: #d97706; }
  .riders-list-demo .ri-status-card .ri-status-row.unpaid { color: #dc2626; }
  @media (max-width: 992px) {
    .riders-list-demo .totals-cards { flex-wrap: wrap; }
    .riders-list-demo .totals-cards .total-card {
      flex: 1 1 calc(33.333% - 6px);
      min-width: calc(33.333% - 6px);
    }
  }
  @media (max-width: 576px) {
    .riders-list-demo .totals-cards .total-card {
      flex: 1 1 calc(50% - 4px);
      min-width: calc(50% - 4px);
    }
  }
  .invoice-checkbox, #selectAllCheckbox { transform: scale(1.2); cursor: pointer; }
  .filter-sidebar .select2-container { width: 100% !important; }
  body > .select2-container { z-index: 12000 !important; }
  body > .select2-container .select2-dropdown { z-index: 12001 !important; }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@can('riders_invoices_view')
<div class="riders-list-demo" id="riderInvoicesListDemo"
     data-index-url="{{ route('riderInvoices.index') }}"
     data-export-url="{{ route('riderInvoices.export') }}"
     data-search-riders="{{ route('riderInvoices.searchRiders') }}"
     data-csrf="{{ csrf_token() }}">

  <div class="rl-wrap">
    @include('flash::message')

    <div class="totals-cards" id="riderInvoiceStats" aria-label="Rider invoice stats">
      <div class="total-card total-blue">
        <div class="label"><i class="ti ti-building"></i>Project</div>
        <div class="value" id="stat_project_label">{{ $stats['project_label'] }}</div>
        <div class="meta"><span id="stat_invoice_count">{{ number_format((int) $stats['invoice_count']) }}</span> invoices</div>
      </div>
      <div class="total-card total-black">
        <div class="label"><i class="ti ti-calendar"></i>Billing Month</div>
        <div class="value" id="stat_billing_month_label">{{ $stats['billing_month_label'] }}</div>
      </div>
      <div class="total-card total-2">
        <div class="label"><i class="ti ti-receipt"></i>Invoice Amount</div>
        <div class="value" id="stat_total_invoice_amount">{!! \App\Helpers\Currency::format($stats['total_invoice_amount'] ?? 0) !!}</div>
      </div>
      <div class="total-card total-1" id="stat_payable_card">
        <div class="label"><i class="ti ti-cash"></i>Payable Salary</div>
        <div class="value" id="stat_total_payable_salary">{!! \App\Helpers\Currency::format($stats['total_payable_salary'] ?? 0) !!}</div>
      </div>
      <div class="total-card total-green" id="stat_paid_card">
        <div class="label"><i class="ti ti-coin"></i>Paid Amount</div>
        <div class="value" id="stat_paid_amount">{!! \App\Helpers\Currency::format($stats['paid_amount'] ?? 0) !!}</div>
      </div>
      <div class="total-card total-red" id="stat_balance_card">
        <div class="label"><i class="ti ti-scale"></i>Balance</div>
        <div id="stat_balance" class="value {{ abs((float) ($stats['balance'] ?? 0)) < 0.005 ? 'unpaid-zero' : 'unpaid-due' }}">
          {!! \App\Helpers\Currency::format($stats['balance'] ?? 0) !!}
        </div>
      </div>
      <div class="total-card total-amber ri-status-card">
        <div class="label"><i class="ti ti-list-check"></i>Status</div>
        <div class="ri-status-rows">
          <div class="ri-status-row paid"><span>Paid</span><b id="stat_paid_count">{{ number_format((int) $stats['paid_count']) }}</b></div>
          <div class="ri-status-row partial"><span>Partially Paid</span><b id="stat_partial_count">{{ number_format((int) $stats['partial_count']) }}</b></div>
          <div class="ri-status-row unpaid"><span>Unpaid</span><b id="stat_unpaid_count">{{ number_format((int) $stats['unpaid_count']) }}</b></div>
        </div>
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
            @can('riders_invoices_create')
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="xl" data-title="Create Rider Invoice" data-action="{{ route('riderInvoices.create') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
              <span><strong>Create New Invoice</strong><em>Add a new rider invoice</em></span>
            </a>
            <a role="menuitem" class="show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Invoices" data-action="{{ route('rider.invoice_import') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 18v-6M9 15l3-3 3 3"/></svg>
              <span><strong>Import Invoices</strong><em>Upload invoices from a file</em></span>
            </a>
            <a role="menuitem" href="{{ route('riderInvoices.importPaid') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
              <span><strong>Salary Payments</strong><em>Import rider salary payments</em></span>
            </a>
            @endcan
            <a role="menuitem" id="exportRiderInvoicesBtn" href="{{ route('riderInvoices.export', request()->except(['page', '_token'])) }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 12v6M9 15l3 3 3-3"/></svg>
              <span><strong>Export</strong><em>Excel with frozen header row</em></span>
            </a>
          </div>
        </div>
        <a class="rl-btn" id="allBtn" href="{{ route('riderInvoices.index') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 20c0-3-2-5-5-5.4"/></svg>
          All Invoices
        </a>
        <button type="button" class="rl-btn" id="deleteSelectedBtn" style="display:none;" onclick="deleteSelectedInvoices()">
          <i class="fa fa-trash"></i> Delete Selected
        </button>
      </div>
      <div class="rl-tb-right">
        <button type="button" class="rl-btn icon" id="copyBtn" title="Copy table to clipboard" aria-label="Copy table">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="copyIco"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
        </button>
        <button type="button" class="rl-btn icon" id="resetFiltersBtn" title="Reset filters" aria-label="Reset filters">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v5h5"/></svg>
        </button>
        <input class="rl-search" id="q" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
        <button type="button" class="rl-btn openFilterSidebar" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Invoices
        </button>
      </div>
    </div>

    <div class="rl-tbox" id="tbox">
      @include('rider_invoices.table', ['data' => $data])
    </div>

    <div id="rlPagerWrap">
      @if(method_exists($data, 'links'))
        @include('components.riders-demo-pagination', ['paginator' => $data, 'entityLabel' => 'invoices'])
      @endif
    </div>
  </div>
</div>

<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Invoices</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ route('riderInvoices.index') }}" method="GET">
      @csrf
      @if(request()->filled('quick_search'))
      <input type="hidden" name="quick_search" value="{{ request('quick_search') }}">
      @endif
      <div class="row">
        <div class="form-group col-md-12 col-sm-12">
          <label for="id">Invoice ID</label>
          <input type="text" name="id" id="id" class="form-control" placeholder="e.g. 9515 or RINV-9515" value="{{ request('id') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="rider_id">Filter by Rider</label>
          @php
            $selectedFilterRider = request()->filled('rider_id')
              ? \App\Models\Riders::query()->select('id', 'rider_id', 'name')->find(request('rider_id'))
              : null;
          @endphp
          <select class="form-control" id="rider_id" name="rider_id" data-placeholder="Type rider ID or name…">
            <option value=""></option>
            @if($selectedFilterRider)
              <option value="{{ $selectedFilterRider->id }}" selected>{{ trim(($selectedFilterRider->rider_id ?? '').'-'.($selectedFilterRider->name ?? ''), '-') }}</option>
            @endif
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="customer_id">Filter by Project</label>
          <select class="form-control" id="customer_id" name="customer_id">
            @php
              $customerIds = company_table('riders')
                ->whereNotNull('customer_id')
                ->where('customer_id', '!=', '')
                ->pluck('customer_id')
                ->unique();
              $projects = company_table('customers')
                ->whereIn('id', $customerIds)
                ->select('id', 'name')
                ->orderBy('name')
                ->get();
            @endphp
            <option value="">Select</option>
            @foreach($projects as $project)
              <option value="{{ $project->id }}" {{ (string) request('customer_id') === (string) $project->id ? 'selected' : '' }}>{{ $project->name }}</option>
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
    'entityName' => 'Rider Invoice',
    'confirmText' => 'This will submit a delete request or move the invoice to the Recycle Bin.',
    'method' => 'GET',
])
<script type="text/javascript">
(function () {
  const root = document.getElementById('riderInvoicesListDemo');
  if (!root) return;

  const indexUrl = root.dataset.indexUrl;
  const exportUrl = root.dataset.exportUrl;
  const searchRidersUrl = root.dataset.searchRiders;
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
      const $rider = $('#rider_id');
      if ($rider.length) {
        if ($rider.hasClass('select2-hidden-accessible')) {
          $rider.select2('destroy');
        }
        $rider.select2({
          dropdownParent: $body,
          allowClear: true,
          width: '100%',
          placeholder: $rider.data('placeholder') || 'Type rider ID or name…',
          minimumInputLength: 1,
          ajax: {
            url: searchRidersUrl,
            dataType: 'json',
            delay: 250,
            data: function (params) {
              return { q: params.term || '' };
            },
            processResults: function (data) {
              return { results: data.results || [] };
            },
            cache: true,
          },
        });
      }

      [
        { sel: '#customer_id', placeholder: 'Filter By Project' },
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
    const link = document.getElementById('exportRiderInvoicesBtn');
    if (link) link.setAttribute('href', exportUrl + (query ? '?' + query : ''));
  }

  function applyStats(stats) {
    if (!stats) return;
    const setText = (id, text) => {
      const el = document.getElementById(id);
      if (el) el.textContent = text;
    };
    setText('stat_project_label', stats.project_label ?? 'All');
    setText('stat_billing_month_label', stats.billing_month_label ?? 'All');
    setText('stat_invoice_count', Number(stats.invoice_count || 0).toLocaleString());
    setText('stat_total_invoice_amount', formatAmount(stats.total_invoice_amount));
    setText('stat_total_payable_salary', formatAmount(stats.total_payable_salary));
    setText('stat_paid_amount', formatAmount(stats.paid_amount));
    setText('stat_balance', formatAmount(stats.balance));
    setText('stat_paid_count', Number(stats.paid_count || 0).toLocaleString());
    setText('stat_partial_count', Number(stats.partial_count || 0).toLocaleString());
    setText('stat_unpaid_count', Number(stats.unpaid_count || 0).toLocaleString());

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

  function loadRiderInvoices(params) {
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
    loadRiderInvoices(buildFilterParams({ page: 1 }));
  });

  document.getElementById('q')?.addEventListener('keydown', function (e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      loadRiderInvoices(buildFilterParams({ page: 1 }));
    }
  });
  document.getElementById('q')?.addEventListener('search', function () {
    if (!this.value) loadRiderInvoices(buildFilterParams({ page: 1 }));
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
    if (col.key === 'rider' || col.key === 'invoice' || td.classList.contains('name')) {
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
    loadRiderInvoices(buildFilterParams({ page: page }));
  });

  document.getElementById('rlPagerWrap')?.addEventListener('change', function (e) {
    if (e.target && e.target.id === 'rlPerPage') {
      const params = buildFilterParams({ per_page: e.target.value });
      delete params.page;
      loadRiderInvoices(params);
    }
  });

  updateExportLink(currentQueryParams());
  bindClientSort();
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
      url: @json(route('riderInvoices.bulkDelete')),
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
