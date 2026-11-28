@extends('layouts.app')
@section('title', 'Cheques')
@php
  $hideModuleTopBarSlider = true;
  $filter = $filter ?? request('filter', 'all');
  $totals = $totals ?? [];
  $nextDueId = $nextDueId ?? null;
  $money = static fn ($n) => \App\Helpers\Currency::format((float) $n, 2);
  $cardOn = static fn (string $key) => $filter === $key;
@endphp
@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/module-list.css') }}?v=20261128-ml">
<link rel="stylesheet" href="{{ asset('css/cheques-list.css') }}?v=20261128g">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
@endpush

@section('content')
@can('cash_&_banks_cheques_view')
<div class="module-list cheques-list" id="chequesList"
     data-index-url="{{ route('cheques.index') }}"
     data-filter-ajax="{{ route('cheques.filterAjax') }}"
     data-csrf="{{ csrf_token() }}"
     data-filter="{{ $filter }}">
  <div class="rl-wrap">
    @include('flash::message')
    <div class="chq-track" aria-label="Cheque summary cards" id="chqTrack">
      <button type="button" class="chq-card blue{{ $cardOn('all') ? ' is-on' : '' }}" data-filter="all" title="Payable + Receivable">
        <div class="l"><i class="fas fa-money-check"></i>Total Cheques</div>
        <div class="v">{{ number_format((int) ($totals['count'] ?? 0)) }} cheques</div>
        <div class="s">{{ $totals['count_meta'] ?? 'Payable + Receivable' }}</div>
      </button>
      <button type="button" class="chq-card pur{{ $cardOn('security') ? ' is-on' : '' }}" data-filter="security" title="Held as security deposit">
        <div class="l"><i class="fas fa-shield-halved"></i>Security Cheques</div>
        <div class="v">{{ number_format((int) ($totals['security_count'] ?? 0)) }} cheques</div>
        <div class="s">{{ $totals['security_meta'] ?? 'Held as security deposit' }}</div>
      </button>
      <button type="button" class="chq-card red{{ $cardOn('payable') ? ' is-on' : '' }}" data-filter="payable" title="Payable (excl. security)">
        <div class="l"><i class="fas fa-arrow-up"></i>Payable Amount</div>
        <div class="v">{!! $money($totals['payable_amount'] ?? 0) !!}</div>
        <div class="s">{{ $totals['payable_meta'] ?? '' }}</div>
      </button>
      <button type="button" class="chq-card grn{{ $cardOn('receivable') ? ' is-on' : '' }}" data-filter="receivable" title="Receivable (excl. security)">
        <div class="l"><i class="fas fa-arrow-down"></i>Receivable Amount</div>
        <div class="v">{!! $money($totals['receivable_amount'] ?? 0) !!}</div>
        <div class="s">{{ $totals['receivable_meta'] ?? '' }}</div>
      </button>
      <button type="button" class="chq-card org{{ $cardOn('due7') ? ' is-on' : '' }}" data-filter="due7" title="Payable due in 7 days">
        <div class="l"><i class="fas fa-calendar-day"></i>Payable · 7 Days</div>
        <div class="v">{!! $money($totals['due7_amount'] ?? 0) !!}</div>
        <div class="s">{{ $totals['due7_meta'] ?? '' }}</div>
      </button>
      <button type="button" class="chq-card amb{{ $cardOn('due30') ? ' is-on' : '' }}" data-filter="due30" title="Payable due in 30 days">
        <div class="l"><i class="fas fa-calendar-days"></i>Payable · 30 Days</div>
        <div class="v">{!! $money($totals['due30_amount'] ?? 0) !!}</div>
        <div class="s">{{ $totals['due30_meta'] ?? '' }}</div>
      </button>
      <button type="button" class="chq-card red2{{ $cardOn('overdue') ? ' is-on' : '' }}" data-filter="overdue" title="Overdue (excl. security)">
        <div class="l"><i class="fas fa-triangle-exclamation"></i>Overdue</div>
        <div class="v">{!! $money($totals['overdue_amount'] ?? 0) !!}</div>
        <div class="s">{{ $totals['overdue_meta'] ?? '' }}</div>
      </button>
      <button type="button" class="chq-card rtn{{ $cardOn('returned') ? ' is-on' : '' }}" data-filter="returned" title="Returned / bounced">
        <div class="l"><i class="fas fa-rotate-left"></i>Returned</div>
        <div class="v">{!! $money($totals['returned_amount'] ?? 0) !!}</div>
        <div class="s">{{ $totals['returned_meta'] ?? '' }}</div>
      </button>
    </div>

    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="actBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" id="chev"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="actMenu" hidden role="menu">
            @can('cash_&_banks_cheques_create')
            <a role="menuitem" href="javascript:void(0);" class="show-modal" data-size="xl" data-title="Add New Cheque" data-action="{{ route('cheques.create') }}">
              <i class="fas fa-money-check" style="width:20px;text-align:center;color:#64748b;font-size:16px;margin-top:3px"></i>
              <span><strong>New Cheque</strong><em>Add a new cheque</em></span>
            </a>
            @endcan
            <div class="mi-sep" aria-hidden="true"></div>
            <button type="button" role="menuitem" id="chqExportBtn">
              <i class="fas fa-file-excel" style="width:20px;text-align:center;color:#64748b;font-size:16px;margin-top:3px"></i>
              <span><strong>Export Cheques</strong><em>Download the current page as CSV</em></span>
            </button>
            <button type="button" role="menuitem" id="chqPrintBtn">
              <i class="fas fa-print" style="width:20px;text-align:center;color:#64748b;font-size:16px;margin-top:3px"></i>
              <span><strong>Print List</strong><em>Print the current cheque list</em></span>
            </button>
            <button type="button" role="menuitem" class="openColumnControlSidebar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
              <span><strong>Column Control</strong><em>Show, hide &amp; reorder list columns</em></span>
            </button>
          </div>
        </div>
        <span class="rl-tabs" id="chqTabs">
          @php
            $tabs = [
              ['all', 'All', ''],
              ['payable', 'Payable', ''],
              ['receivable', 'Receivable', ''],
              ['security', 'Security', ''],
              ['cleared', 'Cleared', ''],
              ['overdue', 'Overdue', 'od'],
              ['returned', 'Returned', 'rt'],
            ];
          @endphp
          @foreach($tabs as [$key, $label, $extra])
            <button type="button" class="rl-btn chq-tab {{ $extra }}{{ $filter === $key ? ' on' : '' }}" data-filter="{{ $key }}">{{ $label }}</button>
          @endforeach
        </span>
      </div>
      <div class="rl-tb-right">
        <input class="rl-search" id="q" name="quick_search" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
      </div>
    </div>

    <div class="chq-results" id="chqResults">
      <div class="chq-table-loading" id="chqTableLoading" hidden aria-hidden="true">
        <div class="spinner-border text-primary" role="status"></div>
      </div>
      <div class="rl-tbox" id="tbox">
        @include('cheques.table', [
          'data' => $data,
          'nextDueId' => $nextDueId,
          'listTable' => true,
          'tableColumns' => $tableColumns ?? [],
        ])
      </div>

      <div id="rlPagerWrap">
        @if(method_exists($data, 'links'))
          @include('components.list-pagination', [
            'paginator' => $data,
            'entityLabel' => 'cheques',
          ])
        @endif
      </div>
    </div>
  </div>
</div>

@include('riders.partials.column_control_modal', [
  'tableColumns' => $tableColumns ?? [],
  'exportRoute' => null,
  'showExportOptions' => false,
  'tableIdentifier' => 'cheques_index_table',
  'fixedColumnsCount' => 1,
  'moduleKey' => null,
])
<div class="rl-toast" id="rlToast" hidden></div>
@else
  <div class="text-center mt-5">
    <h3>You do not have permission to view Cheques.</h3>
  </div>
@endcan
@endsection

@section('page-script')
@include('delete_requests._confirm_delete_script', [
  'entityName' => 'Cheque',
  'confirmText' => 'This will submit a delete request or move the cheque to the Recycle Bin.',
])
<script>
(function () {
  const root = document.getElementById('chequesList');
  if (!root) return;

  const cfg = {
    filterAjax: root.dataset.filterAjax,
    csrf: root.dataset.csrf,
    indexUrl: root.dataset.indexUrl,
  };

  let searchTimer = null;
  let ajaxSeq = 0;
  let activeFilter = root.dataset.filter || 'all';
  let toastTimer;

  function toast(msg) {
    const el = document.getElementById('rlToast');
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(function () { el.hidden = true; }, 2400);
  }
  window.rlToast = toast;

  function setMenu(open) {
    const menu = document.getElementById('actMenu');
    const btn = document.getElementById('actBtn');
    const chev = document.getElementById('chev');
    if (!menu || !btn) return;
    menu.hidden = !open;
    btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (chev) chev.innerHTML = open ? '<path d="m6 15 6-6 6 6"/>' : '<path d="m6 9 6 6 6-6"/>';
  }

  document.getElementById('actBtn')?.addEventListener('click', function (e) {
    e.stopPropagation();
    const menu = document.getElementById('actMenu');
    setMenu(!!menu?.hidden);
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.rl-dd')) setMenu(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setMenu(false);
  });
  document.getElementById('actMenu')?.addEventListener('click', function () {
    setMenu(false);
  });

  function chqSkipColumnIndexes(table) {
    const skip = new Set();
    const headers = table.querySelectorAll('thead th');
    headers.forEach(function (th, idx) {
      const key = th.getAttribute('data-k');
      if (!key || key === 'attachment') skip.add(idx);
    });
    if (headers.length) skip.add(headers.length - 1); // Actions
    return skip;
  }

  function chqRemoveSkippedColumns(table, skip) {
    const indexes = Array.from(skip).sort(function (a, b) { return b - a; });
    table.querySelectorAll('tr').forEach(function (tr) {
      if (tr.querySelector('td[colspan]')) return;
      indexes.forEach(function (idx) {
        if (tr.children[idx]) tr.children[idx].remove();
      });
    });
  }

  document.getElementById('chqPrintBtn')?.addEventListener('click', function () {
    const source = document.querySelector('#tbox table');
    if (!source) {
      toast('Nothing to print');
      return;
    }

    const table = source.cloneNode(true);
    table.querySelectorAll('.column-hidden').forEach(function (el) { el.remove(); });
    chqRemoveSkippedColumns(table, chqSkipColumnIndexes(table));
    table.querySelectorAll('.ar, .dropdown, .pending-deletion-lock').forEach(function (el) { el.remove(); });
    // Currency icons are <img> marks; without app CSS they render full-size in the print window.
    table.querySelectorAll('img.app-currency-icon').forEach(function (img) {
      const mark = document.createTextNode((img.getAttribute('alt') || img.getAttribute('title') || '').trim() || 'AED');
      img.replaceWith(mark);
    });
    table.querySelectorAll('a').forEach(function (a) {
      const span = document.createElement('span');
      span.textContent = (a.textContent || '').replace(/\s+/g, ' ').trim();
      a.replaceWith(span);
    });
    table.querySelectorAll('th, td').forEach(function (cell) {
      cell.textContent = (cell.textContent || '').replace(/\s+/g, ' ').trim();
      cell.removeAttribute('style');
      cell.removeAttribute('class');
      cell.removeAttribute('tabindex');
      cell.removeAttribute('title');
    });

    const filterLabel = (
      document.querySelector('#chqTabs .chq-tab.on')?.textContent
      || document.querySelector('#chqTrack .chq-card.is-on .l')?.textContent
      || activeFilter
      || 'All'
    ).replace(/\s+/g, ' ').trim();
    const rowCount = table.querySelectorAll('tbody tr').length;
    const printedAt = new Date().toLocaleString();
    const search = (document.getElementById('q')?.value || '').trim();

    const html = '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Cheques</title><style>'
      + '@page{margin:12mm}'
      + 'body{font-family:Arial,sans-serif;font-size:11px;color:#111;margin:0;padding:16px}'
      + '.hd{margin-bottom:14px;padding-bottom:8px;border-bottom:2px solid #111}'
      + '.hd h1{margin:0 0 4px;font-size:18px}'
      + '.hd .meta{color:#555;font-size:11px}'
      + 'table{width:100%;border-collapse:collapse;table-layout:auto}'
      + 'th,td{border:1px solid #333;padding:5px 6px;vertical-align:top;text-align:left;word-break:break-word}'
      + 'th{background:#f0f0f0;font-weight:700;text-align:center}'
      + 'td:empty::after{content:"-"}'
      + '.ft{margin-top:12px;font-size:10px;color:#666;text-align:center}'
      + '@media print{body{padding:0}.hd{page-break-after:avoid}tr{page-break-inside:avoid}thead{display:table-header-group}}'
      + '</style></head><body>'
      + '<div class="hd"><h1>Cheques</h1><div class="meta">'
      + '<strong>Filter:</strong> ' + filterLabel
      + (search ? ' | <strong>Search:</strong> ' + search.replace(/</g, '&lt;') : '')
      + ' | <strong>Rows:</strong> ' + rowCount
      + ' | <strong>Printed:</strong> ' + printedAt
      + '</div></div>'
      + table.outerHTML
      + '<div class="ft">Generated from Cheques list</div>'
      + '<script>window.onload=function(){window.focus();window.print();};<\/script>'
      + '</body></html>';

    const win = window.open('', '_blank', 'width=1200,height=800');
    if (!win) {
      toast('Allow pop-ups to print');
      return;
    }
    win.document.open();
    win.document.write(html);
    win.document.close();
  });

  document.getElementById('chqExportBtn')?.addEventListener('click', function () {
    const table = document.querySelector('#tbox table');
    if (!table) return;
    const skip = chqSkipColumnIndexes(table);
    const rows = [];
    table.querySelectorAll('tr').forEach(function (tr) {
      if (tr.querySelector('td[colspan]')) return;
      const cells = [...tr.querySelectorAll('th,td')].filter(function (td, idx) {
        if (td.classList.contains('column-hidden')) return false;
        if (skip.has(idx)) return false;
        return true;
      }).map(function (td) {
        return '"' + String(td.innerText || '').replace(/\s+/g, ' ').trim().replace(/"/g, '""') + '"';
      });
      if (cells.length) rows.push(cells.join(','));
    });
    const blob = new Blob(['\ufeff' + rows.join('\n')], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'cheques.csv';
    a.click();
    URL.revokeObjectURL(a.href);
  });

  function setActiveFilter(filter) {
    activeFilter = filter || 'all';
    root.dataset.filter = activeFilter;
    document.querySelectorAll('#chqTabs .chq-tab').forEach(function (tab) {
      tab.classList.toggle('on', tab.dataset.filter === activeFilter);
    });
    document.querySelectorAll('#chqTrack .chq-card').forEach(function (card) {
      card.classList.toggle('is-on', card.dataset.filter === activeFilter);
    });
  }

  function currentParams(extra) {
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);
    params.set('filter', activeFilter);
    if (extra) {
      Object.keys(extra).forEach(function (k) {
        if (extra[k] === null || extra[k] === undefined || extra[k] === '') params.delete(k);
        else params.set(k, extra[k]);
      });
    }
    return params;
  }

  function refreshTable(extra) {
    if (!cfg.filterAjax) return;
    const seq = ++ajaxSeq;
    const params = currentParams(extra);
    const qEl = document.getElementById('q');
    if (qEl) {
      if (qEl.value) params.set('quick_search', qEl.value);
      else params.delete('quick_search');
    }
    const loading = document.getElementById('chqTableLoading');
    const results = document.getElementById('chqResults');
    if (loading) {
      loading.hidden = false;
      loading.setAttribute('aria-hidden', 'false');
    }
    if (results) results.classList.add('is-loading');

    fetch(cfg.filterAjax, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
        'X-CSRF-TOKEN': cfg.csrf,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
      },
      body: params.toString() + '&_token=' + encodeURIComponent(cfg.csrf),
    })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        if (seq !== ajaxSeq) return;
        if (!data.success) return;
        const tbox = document.getElementById('tbox');
        const pagerWrap = document.getElementById('rlPagerWrap');
        if (tbox && data.html !== undefined) tbox.innerHTML = data.html;
        if (pagerWrap && data.pagination !== undefined) pagerWrap.innerHTML = data.pagination;
        if (data.filter) setActiveFilter(data.filter);
        if (window.ColumnController && typeof window.ColumnController.reapplySettings === 'function') {
          window.ColumnController.reapplySettings();
        }

        const newUrl = new URL(window.location.href);
        params.forEach(function (v, k) { newUrl.searchParams.set(k, v); });
        ['quick_search', 'page', 'per_page', 'filter'].forEach(function (k) {
          if (!params.has(k)) newUrl.searchParams.delete(k);
        });
        history.replaceState({}, '', newUrl.toString());
      })
      .catch(function () { /* keep current table */ })
      .finally(function () {
        if (seq !== ajaxSeq) return;
        if (loading) {
          loading.hidden = true;
          loading.setAttribute('aria-hidden', 'true');
        }
        if (results) results.classList.remove('is-loading');
      });
  }

  document.getElementById('chqTrack')?.addEventListener('click', function (e) {
    const card = e.target.closest('.chq-card[data-filter]');
    if (!card) return;
    e.preventDefault();
    setActiveFilter(card.dataset.filter);
    refreshTable({ page: 1 });
  });

  document.getElementById('chqTabs')?.addEventListener('click', function (e) {
    const tab = e.target.closest('.chq-tab[data-filter]');
    if (!tab) return;
    e.preventDefault();
    setActiveFilter(tab.dataset.filter);
    refreshTable({ page: 1 });
  });

  const qInput = document.getElementById('q');
  qInput?.addEventListener('input', function () {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(function () {
      refreshTable({ page: 1 });
    }, 400);
  });
  qInput?.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    clearTimeout(searchTimer);
    refreshTable({ page: 1 });
  });
  qInput?.addEventListener('search', function () {
    if (!this.value) {
      clearTimeout(searchTimer);
      refreshTable({ page: 1 });
    }
  });

  document.getElementById('rlPagerWrap')?.addEventListener('click', function (e) {
    const a = e.target.closest('a.rl-pg[data-page]');
    if (!a) return;
    e.preventDefault();
    refreshTable({ page: a.dataset.page });
    const tbox = document.getElementById('tbox');
    if (tbox) tbox.scrollTop = 0;
  });
  document.getElementById('rlPagerWrap')?.addEventListener('change', function (e) {
    if (e.target.id !== 'rlPerPage') return;
    refreshTable({ per_page: e.target.value, page: 1 });
  });

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.delete-cheque');
    if (!btn) return;
    e.preventDefault();
    if (typeof confirmDelete === 'function') confirmDelete(btn.getAttribute('data-url'));
  });
})();
</script>
@endsection
