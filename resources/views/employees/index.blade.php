@extends('layouts.app')
@section('title', 'Employees')
@php
  $hideModuleTopBarSlider = true;
  $topBarSliderCategories = $topBarSliderCategories ?? collect();
  $topBarOptionStats = $topBarOptionStats ?? [];
  $topBarConfig = $topBarConfig ?? \App\Support\ErpModuleRegistry::topBarConfig('employees');
  $topBarStatDefinitions = $topBarStatDefinitions ?? app(\App\Services\Module\TopBarListingService::class)->statDefinitions('employees');
  $optionIdParam = (string) ($topBarConfig['request']['option_id'] ?? 'top_option_id');
  $statusParam = (string) ($topBarConfig['request']['status'] ?? 'employee_status');
  $selectedOptionId = (int) request($optionIdParam, 0);
  $selectedChipName = null;
  if ($selectedOptionId > 0) {
    foreach ($topBarSliderCategories as $category) {
      foreach (($category->options ?? []) as $option) {
        if ((int) $option->id === $selectedOptionId) {
          $selectedChipName = (string) $option->name;
          break 2;
        }
      }
    }
  }
  $favoriteEmployeeIds = auth()->user()->favorite_employee_ids ?? [];
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}?v=20261030-tbox">
<link rel="stylesheet" href="{{ asset('css/employees-demo.css') }}?v=20261102">
@endpush

@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
  <div class="spinner-border text-primary" role="status"></div>
</div>
@can('employees_employee_view')
@php
  $companySlug = request()->route('company_slug');
  $empFilterAjaxUrl = safe_route('employees.filterAjax', [], 'employees/filter-ajax');
  $empToggleFavoriteUrl = \Illuminate\Support\Facades\Route::has('employees.toggleFavorite')
    ? preg_replace('#/0$#', '', route('employees.toggleFavorite', ['id' => 0]))
    : company_app_url('employees/toggle-favorite');
  $empIndexUrl = safe_route('employees.index', [], 'employees');
  $empCreateUrl = safe_route('employees.create', [], 'employees/create');
@endphp
<div class="riders-list-demo" id="employeesListDemo"
     data-filter-ajax="{{ $empFilterAjaxUrl }}"
     data-toggle-favorite="{{ $empToggleFavoriteUrl }}"
     data-csrf="{{ csrf_token() }}"
     data-option-param="{{ $optionIdParam }}"
     data-status-param="{{ $statusParam }}"
     data-index-url="{{ $empIndexUrl }}">

  <div class="rl-wrap">
    @include('flash::message')

    <div class="rl-ticker" id="rlTicker" aria-label="Summary cards">
      <div class="rl-track" id="rlTrack">
        @php
          $cardIco = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 4-6 8-6s8 2 8 6"/></svg>';
          $flatCards = [];
          foreach ($topBarSliderCategories as $category) {
            foreach (($category->options ?? []) as $option) {
              $stats = $topBarOptionStats[$option->id] ?? [];
              $flatCards[] = [
                'id' => (int) $option->id,
                'name' => (string) $option->name,
                'category' => (string) $category->name,
                'active' => (int) ($stats['active'] ?? 0),
                'inactive' => (int) ($stats['inactive'] ?? 0),
                'sel' => $selectedOptionId === (int) $option->id,
              ];
            }
          }
        @endphp
        @forelse($flatCards as $c)
        <div class="rl-card{{ $c['sel'] ? ' sel' : '' }}" data-option-id="{{ $c['id'] }}" title="{{ $c['name'] }}">
          <h4>{{ $c['name'] }}</h4>
          <small>{{ $c['category'] }}</small>
          <div class="rl-boxes">
            <div class="rl-box a">{!! $cardIco !!}<span>ACTIVE</span><b>{{ $c['active'] }}</b></div>
            <div class="rl-box i">{!! $cardIco !!}<span>INACTIVE</span><b>{{ $c['inactive'] }}</b></div>
          </div>
        </div>
        @empty
        <div class="rl-note px-2">No summary cards configured. Add options in Employee Settings → Top Bar.</div>
        @endforelse
        @foreach($flatCards as $c)
        <div class="rl-card{{ $c['sel'] ? ' sel' : '' }}" data-option-id="{{ $c['id'] }}" title="{{ $c['name'] }}" aria-hidden="true">
          <h4>{{ $c['name'] }}</h4>
          <small>{{ $c['category'] }}</small>
          <div class="rl-boxes">
            <div class="rl-box a">{!! $cardIco !!}<span>ACTIVE</span><b>{{ $c['active'] }}</b></div>
            <div class="rl-box i">{!! $cardIco !!}<span>INACTIVE</span><b>{{ $c['inactive'] }}</b></div>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="actBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Add Employee
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" id="chev"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="actMenu" hidden role="menu">
            @can('employees_employee_create')
            <a role="menuitem" href="{{ $empCreateUrl }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="8" r="4"/><path d="M3 21c0-4 3-6 7-6M18 14v6M15 17h6"/></svg>
              <span><strong>Create New Employee</strong><em>Add a new employee to the system</em></span>
            </a>
            @endcan
            <button type="button" role="menuitem" class="openColumnControlSidebar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
              <span><strong>Column Control</strong><em>Show or hide table columns</em></span>
            </button>
          </div>
        </div>
        <a class="rl-btn" id="allBtn" href="{{ $empIndexUrl }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 20c0-3-2-5-5-5.4"/></svg>
          All Employees
        </a>
        <span class="rl-chip" id="chip" @if(!$selectedChipName) hidden @endif>
          <span id="chipTxt">{{ $selectedChipName }}</span>
          <button type="button" id="chipX" aria-label="Clear card filter">×</button>
        </span>
      </div>
      <div class="rl-tb-right">
        <button type="button" class="rl-btn icon" id="copyBtn" title="Copy table to clipboard" aria-label="Copy table">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="copyIco"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
        </button>
        <input class="rl-search" id="q" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
        <button type="button" class="rl-btn" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Employees
        </button>
      </div>
    </div>

    <div class="rl-tbox" id="tbox">
      @include('employees.table', [
        'data' => $data,
        'tableColumns' => $tableColumns ?? [],
        'favoriteEmployeeIds' => $favoriteEmployeeIds,
      ])
    </div>

    <div id="rlPagerWrap">
      @if(method_exists($data, 'links'))
        @include('components.riders-demo-pagination', ['paginator' => $data])
      @endif
    </div>
  </div>
</div>

<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Employees</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ $empIndexUrl }}" method="GET" data-rfp-skip-lock="1">
      @if(request()->filled($optionIdParam))
      <input type="hidden" name="{{ $optionIdParam }}" value="{{ request($optionIdParam) }}">
      @endif
      @foreach((array) request($statusParam, []) as $statusKey)
      @if($statusKey !== null && $statusKey !== '')
      <input type="hidden" name="{{ $statusParam }}[]" value="{{ $statusKey }}">
      @endif
      @endforeach
      @if(request()->filled('quick_search'))
      <input type="hidden" name="quick_search" id="filterQuickSearch" value="{{ request('quick_search') }}">
      @endif
      <div class="row">
        @fieldVisible('employees', 'employee_id')
        <div class="form-group col-md-12">
          <label for="employee_id">Employee ID</label>
          <input type="text" name="employee_id" class="form-control" placeholder="Filter by Employee ID" value="{{ request('employee_id') }}">
        </div>
        @endfieldVisible
        @fieldVisible('employees', 'name')
        <div class="form-group col-md-12">
          <label for="name">Employee Name</label>
          <input type="text" name="name" class="form-control" placeholder="Filter by Name" value="{{ request('name') }}">
        </div>
        @endfieldVisible
        @fieldVisible('employees', 'branch_id')
        <div class="form-group col-md-12">
          <label for="branch_id">Branch</label>
          <select class="form-control" id="branch_id" name="branch_id">
            <option value="">Select</option>
            @foreach(\App\Models\Branch::active()->orderBy('name')->get() as $branch)
            <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
            @endforeach
          </select>
        </div>
        @endfieldVisible
        @fieldVisible('employees', 'designation')
        <div class="form-group col-md-12">
          <label for="designation">Designation</label>
          <select class="form-control" id="designation" name="designation">
            <option value="">Select</option>
            @php
              $designations = company_table('employees')->whereNotNull('designation')->where('designation', '!=', '')->select('designation')->distinct()->orderBy('designation')->pluck('designation');
            @endphp
            @foreach($designations as $designation)
            <option value="{{ $designation }}" {{ request('designation') == $designation ? 'selected' : '' }}>{{ $designation }}</option>
            @endforeach
          </select>
        </div>
        @endfieldVisible
        <div class="form-group col-md-12">
          <label for="status">Status</label>
          <select class="form-control" id="status" name="status">
            <option value="">Select</option>
            @foreach(($employeeStatusFilterOptions ?? []) as $statusOption)
            <option value="{{ $statusOption['value'] }}" {{ request('status') == $statusOption['value'] ? 'selected' : '' }}>{{ $statusOption['label'] }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-12 form-group text-center">
          <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
        </div>
      </div>
    </form>
  </div>
</div>

@include('components.column-control-panel', [
  'tableColumns' => $tableColumns ?? [],
  'tableIdentifier' => 'employees_index_table',
])
<div class="rl-toast" id="rlToast" hidden></div>
<div class="emp-hv" id="empHv" role="tooltip" aria-hidden="true"></div>
@else
<div class="alert alert-danger mt-4" role="alert">
  You do not have permission to view employees.
</div>
@endcan
@endsection

@section('page-script')
@include('delete_requests._confirm_delete_script', [
  'entityName' => 'Employee',
  'confirmText' => 'This will submit a delete request or move the employee to the Recycle Bin.',
  'functionName' => 'confirmDeleteEmployee',
])
<script>
(function () {
  const root = document.getElementById('employeesListDemo');
  if (!root) return;

  const cfg = {
    filterAjax: root.dataset.filterAjax,
    toggleFavoriteBase: root.dataset.toggleFavorite,
    csrf: root.dataset.csrf,
    optionParam: root.dataset.optionParam,
    statusParam: root.dataset.statusParam,
    indexUrl: root.dataset.indexUrl,
  };

  let toastTimer;
  function toast(msg) {
    const el = document.getElementById('rlToast');
    if (!el) return;
    el.textContent = msg;
    el.hidden = false;
    clearTimeout(toastTimer);
    toastTimer = setTimeout(() => { el.hidden = true; }, 2400);
  }

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
    setMenu(menu.hidden);
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.rl-dd')) setMenu(false);
  });
  document.getElementById('actMenu')?.addEventListener('click', function () { setMenu(false); });

  document.getElementById('chipX')?.addEventListener('click', function () {
    window.location.href = cfg.indexUrl;
  });

  function openFilter(e) {
    if (e) { e.preventDefault(); e.stopPropagation(); }
    document.getElementById('filterSidebar')?.classList.add('open');
    document.getElementById('filterOverlay')?.classList.add('show');
  }
  function closeFilter() {
    document.getElementById('filterSidebar')?.classList.remove('open');
    document.getElementById('filterOverlay')?.classList.remove('show');
  }
  document.getElementById('filterBtn')?.addEventListener('click', openFilter);
  document.getElementById('closeSidebar')?.addEventListener('click', closeFilter);
  document.getElementById('filterOverlay')?.addEventListener('click', closeFilter);
  document.getElementById('filterForm')?.addEventListener('submit', function () {
    const q = document.getElementById('q');
    let hidden = document.getElementById('filterQuickSearch');
    if (q && q.value) {
      if (!hidden) {
        hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'quick_search';
        hidden.id = 'filterQuickSearch';
        this.appendChild(hidden);
      }
      hidden.value = q.value;
    }
    closeFilter();
  });

  if (window.jQuery) {
    jQuery(function ($) {
      $('#branch_id,#designation,#status').each(function () {
        if ($(this).length) {
          $(this).select2({ dropdownParent: $('#searchTopbody'), allowClear: true, placeholder: 'Select' });
        }
      });
    });
  }

  const tk = document.getElementById('rlTicker');
  const track = document.getElementById('rlTrack');
  if (tk && track && track.querySelector('.rl-card')) {
    let pos = 0, hover = false, down = false, moved = false, sx = 0, sp = 0;
    const reduce = matchMedia('(prefers-reduced-motion: reduce)').matches;
    const norm = () => {
      const h = track.scrollWidth / 2;
      if (h <= 0) return;
      if (pos >= h) pos -= h;
      if (pos < 0) pos += h;
      tk.scrollLeft = pos;
    };
    (function loop() {
      if (!hover && !down && !reduce) { pos += 0.4; norm(); }
      requestAnimationFrame(loop);
    })();
    tk.addEventListener('mouseenter', () => { hover = true; });
    tk.addEventListener('mouseleave', () => { hover = false; down = false; tk.classList.remove('dragging'); });
    tk.addEventListener('pointerdown', e => { down = true; moved = false; sx = e.clientX; sp = pos; tk.classList.add('dragging'); });
    window.addEventListener('pointermove', e => {
      if (!down) return;
      const dx = e.clientX - sx;
      if (Math.abs(dx) > 4) moved = true;
      pos = sp - dx;
      norm();
    });
    window.addEventListener('pointerup', () => { down = false; tk.classList.remove('dragging'); });
    tk.addEventListener('wheel', e => {
      e.preventDefault();
      pos += (Math.abs(e.deltaX) > Math.abs(e.deltaY) ? e.deltaX : e.deltaY);
      norm();
    }, { passive: false });
    tk.addEventListener('click', e => {
      if (moved) { e.stopPropagation(); e.preventDefault(); moved = false; return; }
      const c = e.target.closest('.rl-card');
      if (!c) return;
      const optionId = c.dataset.optionId;
      if (!optionId) return;
      const url = new URL(window.location.href);
      if (url.searchParams.get(cfg.optionParam) === String(optionId)) {
        url.searchParams.delete(cfg.optionParam);
        Array.from(url.searchParams.keys()).forEach(k => {
          if (k === cfg.statusParam || k.indexOf(cfg.statusParam + '[') === 0) url.searchParams.delete(k);
        });
      } else {
        url.searchParams.set(cfg.optionParam, String(optionId));
        Array.from(url.searchParams.keys()).forEach(k => {
          if (k === cfg.statusParam || k.indexOf(cfg.statusParam + '[') === 0) url.searchParams.delete(k);
        });
        url.searchParams.append(cfg.statusParam + '[]', 'active');
        url.searchParams.append(cfg.statusParam + '[]', 'inactive');
        url.searchParams.append(cfg.statusParam + '[]', 'on_leave');
      }
      url.searchParams.delete('page');
      window.location.href = url.toString();
    }, true);
  }

  let ajaxSeq = 0;
  function currentParams(extra) {
    const params = new URLSearchParams(new URL(window.location.href).search);
    if (extra) {
      Object.keys(extra).forEach(k => {
        if (extra[k] === null || extra[k] === undefined || extra[k] === '') params.delete(k);
        else params.set(k, extra[k]);
      });
    }
    return params;
  }

  function refreshTable(extra) {
    const seq = ++ajaxSeq;
    const params = currentParams(extra);
    const qEl = document.getElementById('q');
    if (qEl) {
      if (qEl.value) params.set('quick_search', qEl.value);
      else params.delete('quick_search');
    }
    const overlay = document.getElementById('loading-overlay');
    if (overlay) overlay.style.display = 'flex';

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
      .then(r => r.json())
      .then(data => {
        if (seq !== ajaxSeq) return;
        if (!data.success) { toast(data.message || 'Search failed'); return; }
        const tbox = document.getElementById('tbox');
        const pagerWrap = document.getElementById('rlPagerWrap');
        if (tbox && data.html) tbox.innerHTML = data.html;
        if (pagerWrap && data.pagination !== undefined) pagerWrap.innerHTML = data.pagination;
        const newUrl = new URL(window.location.href);
        params.forEach((v, k) => newUrl.searchParams.set(k, v));
        ['quick_search', 'page', 'per_page'].forEach(k => {
          if (!params.has(k)) newUrl.searchParams.delete(k);
        });
        history.replaceState({}, '', newUrl.toString());
        bindTableEvents();
      })
      .catch(() => toast('Could not refresh employees'))
      .finally(() => { if (overlay) overlay.style.display = 'none'; });
  }

  const qInput = document.getElementById('q');
  qInput?.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    refreshTable({ page: 1 });
  });
  qInput?.addEventListener('search', function () {
    if (!this.value) refreshTable({ page: 1 });
  });

  document.getElementById('rlPagerWrap')?.addEventListener('click', function (e) {
    const a = e.target.closest('a.rl-pg[data-page]');
    if (!a) return;
    e.preventDefault();
    refreshTable({ page: a.dataset.page });
    document.getElementById('tbox').scrollTop = 0;
  });
  document.getElementById('rlPagerWrap')?.addEventListener('change', function (e) {
    if (e.target.id !== 'rlPerPage') return;
    refreshTable({ per_page: e.target.value, page: 1 });
  });

  function bindTableEvents() {
    document.querySelectorAll('#rlBody [data-fav]').forEach(btn => {
      btn.onclick = function (e) {
        e.preventDefault();
        e.stopPropagation();
        const id = this.dataset.fav;
        fetch(cfg.toggleFavoriteBase + '/' + id, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': cfg.csrf,
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
          body: new URLSearchParams({ _token: cfg.csrf }),
        })
          .then(r => r.json())
          .then(data => {
            if (!data.success) { toast(data.message || 'Failed'); return; }
            this.classList.toggle('on', !!data.favorited);
            toast(data.message);
            refreshTable({});
          })
          .catch(() => toast('Could not update favorite'));
      };
    });

    const head = document.getElementById('rlHead');
    const body = document.getElementById('rlBody');
    if (head && body) {
      head.querySelectorAll('th.sorting').forEach((th, colIndex) => {
        th.onclick = () => {
          const rows = Array.from(body.querySelectorAll('tr'));
          const isAsc = th.classList.contains('sorted-asc');
          head.querySelectorAll('th').forEach(h => {
            h.classList.remove('sorted-asc', 'sorted-desc', 'on');
            const ar = h.querySelector('.ar');
            if (ar) ar.textContent = '⇅';
          });
          th.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc', 'on');
          const ar = th.querySelector('.ar');
          if (ar) ar.textContent = isAsc ? '↓' : '↑';
          rows.sort((a, b) => {
            let aText = (a.children[colIndex]?.textContent || '').trim().toLowerCase();
            let bText = (b.children[colIndex]?.textContent || '').trim().toLowerCase();
            const aVal = isNaN(aText) ? aText : parseFloat(aText);
            const bVal = isNaN(bText) ? bText : parseFloat(bText);
            if (aVal < bVal) return isAsc ? 1 : -1;
            if (aVal > bVal) return isAsc ? -1 : 1;
            return 0;
          });
          rows.forEach(row => body.appendChild(row));
        };
      });
    }
  }
  bindTableEvents();

  // HTML-demo hover card (photo → ID → name)
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
      const tw = tip.offsetWidth || 230;
      const th = tip.offsetHeight || 220;
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
      const a = e.target.closest('.emp-name-link');
      if (a) show(a);
    });
    document.addEventListener('mouseout', (e) => {
      const a = e.target.closest('.emp-name-link');
      if (a && !e.relatedTarget?.closest?.('.emp-hv')) hide();
    });
    tip.addEventListener('mouseenter', () => clearTimeout(hideT));
    tip.addEventListener('mouseleave', hide);
  })();

  document.getElementById('copyBtn')?.addEventListener('click', async function () {
    const table = document.getElementById('dataTableBuilder');
    if (!table) return;
    const rows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.querySelector('.rl-empty'));
    const headers = Array.from(table.querySelectorAll('thead th')).filter(th => !th.classList.contains('nosort'));
    const txt = [headers.map(h => (h.textContent || '').replace(/[⇅↑↓]/g, '').trim()).join('\t')]
      .concat(rows.map(tr => Array.from(tr.children).slice(0, headers.length).map(td => {
        const clone = td.cloneNode(true);
        clone.querySelectorAll('.rl-star,.dropdown').forEach(n => n.remove());
        return clone.innerText.replace(/\s+/g, ' ').trim() || '-';
      }).join('\t'))).join('\n');
    try {
      await navigator.clipboard.writeText(txt);
      toast('Copied ' + rows.length + ' row' + (rows.length === 1 ? '' : 's'));
    } catch (_) {
      toast('Could not copy to clipboard');
    }
  });
})();
</script>
@endsection
