@extends('layouts.app')
@section('title','Riders')
@php
  $hideModuleTopBarSlider = true;
  $topBarSliderCategories = $topBarSliderCategories ?? collect();
  $topBarOptionStats = $topBarOptionStats ?? [];
  $topBarConfig = $topBarConfig ?? \App\Support\ErpModuleRegistry::topBarConfig('riders');
  $topBarStatDefinitions = $topBarStatDefinitions ?? app(\App\Services\Module\TopBarListingService::class)->statDefinitions('riders');
  $optionIdParam = (string) ($topBarConfig['request']['option_id'] ?? 'rider_top_option_id');
  $statusParam = (string) ($topBarConfig['request']['status'] ?? 'rider_status');
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
  $favoriteRiderIds = auth()->user()->favorite_rider_ids ?? [];
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}">
@endpush

@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@can('riders_rider_view')
<div class="riders-list-demo" id="ridersListDemo"
     data-filter-ajax="{{ route('riders.filterAjax') }}"
     data-toggle-favorite="{{ preg_replace('#/0$#', '', route('riders.toggleFavorite', ['id' => 0])) }}"
     data-import-preview="{{ route('riders.import.preview') }}"
     data-import-commit="{{ route('riders.import.commit') }}"
     data-csrf="{{ csrf_token() }}"
     data-option-param="{{ $optionIdParam }}"
     data-status-param="{{ $statusParam }}"
     data-index-url="{{ route('riders.index') }}">

  <div class="rl-wrap">
    @include('flash::message')

    {{-- B1 Summary ticker --}}
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
        <div class="rl-note px-2">No summary cards configured. Add options in Rider Settings → Top Bar.</div>
        @endforelse
        {{-- Duplicate for seamless loop --}}
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

    {{-- B2 Toolbar --}}
    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="actBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Riders Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" id="chev"><path d="m6 9 6 6 6-6"/></svg>
                            </button>
          <div class="rl-menu" id="actMenu" hidden role="menu">
                                @can('riders_rider_create')
            <a role="menuitem" href="{{ route('riders.create') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="8" r="4"/><path d="M3 21c0-4 3-6 7-6M18 14v6M15 17h6"/></svg>
              <span><strong>Create New Rider</strong><em>Add a new rider to the system</em></span>
                                </a>
                                @endcan
            @can('riders_export_data_create')
            <a role="menuitem" href="{{ route('rider.exportRiders') }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 12v6M9 15l3 3 3-3"/></svg>
              <span><strong>Export Riders</strong><em>Export ALL fields to Excel (same format as Copy)</em></span>
                                </a>
                                @endcan
            @can('riders_rider_create')
            <button type="button" role="menuitem" id="importRidersBtn">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 18v-6M9 15l3-3 3 3"/></svg>
              <span><strong>Import Riders</strong><em>Add new / update riders from Excel or CSV</em></span>
            </button>
            @endcan
            <button type="button" role="menuitem" class="openColumnControlSidebar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
              <span><strong>Column Control</strong><em>Open column control modal</em></span>
            </button>
                                    </div>
        </div>
        <a class="rl-btn" id="allBtn" href="{{ route('riders.index') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 20c0-3-2-5-5-5.4"/></svg>
          All Riders
                                </a>
        <span class="rl-chip" id="chip" @if(!$selectedChipName) hidden @endif>
          <span id="chipTxt">{{ $selectedChipName }}</span>
          <button type="button" id="chipX" aria-label="Clear card filter">×</button>
        </span>
                            </div>
      <div class="rl-tb-right">
        <button type="button" class="rl-btn icon" id="copyBtn" title="Copy table (Excel-ready)" aria-label="Copy table">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="copyIco"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
        </button>
        <input class="rl-search" id="q" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
        <button type="button" class="rl-btn" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Riders
        </button>
            </div>
        </div>

    {{-- B3 Table --}}
    <div class="rl-tbox" id="tbox">
      @include('riders.table', [
        'data' => $data,
'tableColumns' => $tableColumns ?? [],
        'favoriteRiderIds' => $favoriteRiderIds,
      ])
            </div>

    {{-- B4 Pager --}}
    <div id="rlPagerWrap">
      @if(method_exists($data, 'links'))
        @include('components.riders-demo-pagination', ['paginator' => $data])
      @endif
    </div>
  </div>
</div>

{{-- Filter sidebar outside list wrapper so fixed positioning / z-index work --}}
<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Riders</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ route('riders.index') }}" method="GET" data-rfp-skip-lock="1">
      @if(request()->filled('rider_top_option_id'))
      <input type="hidden" name="rider_top_option_id" value="{{ request('rider_top_option_id') }}">
      @endif
      @foreach((array) request('rider_status', []) as $statusKey)
      @if($statusKey !== null && $statusKey !== '')
      <input type="hidden" name="rider_status[]" value="{{ $statusKey }}">
      @endif
      @endforeach
      @if(request()->filled('quick_search'))
      <input type="hidden" name="quick_search" id="filterQuickSearch" value="{{ request('quick_search') }}">
      @endif
      <div class="row">
        @fieldVisible('rider', 'rider_id')
        <div class="form-group col-md-12">
          <label for="id">Rider Id</label>
          <input type="number" name="rider_id" class="form-control" placeholder="Filter By Rider ID" value="{{ request('rider_id') }}">
        </div>
        @endfieldVisible
        @fieldVisible('rider', 'name')
        <div class="form-group col-md-12">
          <label for="name">Rider Name</label>
          <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="{{ request('name') }}">
        </div>
        @endfieldVisible
        @fieldVisible('rider', 'customer_id')
        <div class="form-group col-md-12">
          <label for="customer_id">Filter by Project</label>
          <select class="form-control" id="customer_id" name="customer_id">
            @php
            $customerIds = company_table('riders')->whereNotNull('customer_id')->where('customer_id', '!=', '')->pluck('customer_id')->unique();
            $customers = company_table('customers')->whereIn('id', $customerIds)->select('id', 'name')->get();
            @endphp
            <option value="" selected>Select</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}" {{ request('customer_id') == $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
            @endforeach
          </select>
        </div>
        @endfieldVisible
        @fieldVisible('rider', 'attendance')
        <div class="form-group col-md-12">
          <label for="attandence">Filter by Attandence</label>
          <select class="form-control" id="attendance" name="attendance">
            @php
            $attandence = company_table('riders')->whereNotNull('attendance')->where('attendance', '!=', '')->select('attendance')->distinct()->pluck('attendance');
            @endphp
            <option value="" selected>Select</option>
            @foreach($attandence as $att)
            <option value="{{ $att }}" {{ request('attendance') == $att ? 'selected' : '' }}>{{ $att }}</option>
            @endforeach
          </select>
        </div>
        @endfieldVisible
        <div class="form-group col-md-12">
          <label for="bike_assignment_status">Filter by Status</label>
          <select class="form-control" id="bike_assignment_status" name="bike_assignment_status">
            <option value="" selected>Select</option>
            @foreach(($riderTopStatusFilterOptions ?? []) as $statusOption)
            <option value="{{ $statusOption['value'] }}" {{ request('bike_assignment_status') == $statusOption['value'] ? 'selected' : '' }}>
              {{ $statusOption['label'] }}
            </option>
            @endforeach
          </select>
        </div>
        @fieldVisible('rider', 'fleet_supervisor')
        <div class="form-group col-md-12">
          <label for="fleet_supervisor">Filter by Supervisor</label>
          <select class="form-control" id="fleet_supervisor" name="fleet_supervisor">
            @php
            $supervisors = company_table('riders')->select('fleet_supervisor')->distinct()->pluck('fleet_supervisor')->toArray();
            @endphp
            <option value="" selected>Select</option>
            @foreach($supervisors as $supervisor)
            <option value="{{ $supervisor }}" {{ request('fleet_supervisor') == $supervisor ? 'selected' : '' }}>{{ $supervisor }}</option>
            @endforeach
          </select>
        </div>
        @endfieldVisible
        <div class="col-md-12 form-group text-center">
          <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
        </div>
      </div>
    </form>
  </div>
</div>

@include('riders.partials.column_control_modal', [
  'tableColumns' => $tableColumns ?? [],
  'exportRoute' => route('rider.exportCustomizableRiders'),
  'tableIdentifier' => 'riders_table',
  'fixedColumnsCount' => 1,
])
@include('riders.partials.import_modal')
<div class="rl-toast" id="rlToast" hidden></div>
@else
<div class="card">
    <div class="card-body">
        <h5>You are not authorized to access this page</h5>
    </div>
</div>
@endcan
@endsection

@section('page-script')
@include('delete_requests._confirm_delete_script', [
    'entityName' => 'Rider',
    'confirmText' => 'This will submit a delete request or move the rider to the Recycle Bin.',
    'method' => 'GET',
])
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function () {
  const root = document.getElementById('ridersListDemo');
  if (!root) return;

  const cfg = {
    filterAjax: root.dataset.filterAjax,
    toggleFavoriteBase: root.dataset.toggleFavorite,
    importPreview: root.dataset.importPreview,
    importCommit: root.dataset.importCommit,
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
    setMenu(menu.hidden);
  });
  document.addEventListener('click', function (e) {
    if (!e.target.closest('.rl-dd')) setMenu(false);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      setMenu(false);
      document.querySelectorAll('.rl-ov').forEach(o => { o.hidden = true; });
    }
  });
  document.getElementById('actMenu')?.addEventListener('click', function () {
    setMenu(false);
  });

  document.getElementById('chipX')?.addEventListener('click', function () {
    window.location.href = cfg.indexUrl;
  });

  // Filter sidebar — click only (not hover)
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

  // Select2 for filter fields
  if (window.jQuery) {
    jQuery(function ($) {
      $('#customer_id,#bike_assignment_status,#attendance,#fleet_supervisor').each(function () {
        if ($(this).length) {
          $(this).select2({ dropdownParent: $('#searchTopbody'), allowClear: true, placeholder: $(this).find('option:first').text() || 'Select' });
        }
      });
    });
  }

  // Ticker scroll / drag / click
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
      }
      url.searchParams.delete('page');
                window.location.href = url.toString();
    }, true);
  }

  // Debounced AJAX search + pagination
  let searchTimer = null;
  let ajaxSeq = 0;

  function currentParams(extra) {
    const url = new URL(window.location.href);
    const params = new URLSearchParams(url.search);
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
        // Keep URL in sync without full reload
        const newUrl = new URL(window.location.href);
        params.forEach((v, k) => newUrl.searchParams.set(k, v));
        ['quick_search', 'page', 'per_page'].forEach(k => {
          if (!params.has(k)) newUrl.searchParams.delete(k);
        });
        history.replaceState({}, '', newUrl.toString());
        bindTableEvents();
      })
      .catch(() => toast('Could not refresh riders'))
      .finally(() => { if (overlay) overlay.style.display = 'none'; });
  }

  // Quick search: filter only on Enter (or when the field is cleared)
  const qInput = document.getElementById('q');
  qInput?.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter') return;
    e.preventDefault();
    clearTimeout(searchTimer);
    refreshTable({ page: 1 });
  });
  qInput?.addEventListener('search', function () {
    // Native clear (×) on type=search
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
    document.getElementById('tbox').scrollTop = 0;
  });
  document.getElementById('rlPagerWrap')?.addEventListener('change', function (e) {
    if (e.target.id !== 'rlPerPage') return;
    refreshTable({ per_page: e.target.value, page: 1 });
  });

  function bindTableEvents() {
    // Favorites
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
            this.setAttribute('aria-label', data.favorited ? 'Remove from favorites' : 'Add to favorites');
            toast(data.message);
            refreshTable({});
          })
          .catch(() => toast('Could not update favorite'));
      };
    });

    // Client sort on current page
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

  // Load ExcelJS once (needed for real .xlsx with frozen header — HTML paste cannot freeze rows)
  function loadExcelJs() {
    return new Promise((ok, no) => {
      if (window.ExcelJS) { ok(); return; }
      const existing = document.querySelector('script[data-exceljs]');
      if (existing) {
        existing.addEventListener('load', () => ok());
        existing.addEventListener('error', () => no(new Error('exceljs')));
        return;
      }
      const s = document.createElement('script');
      s.src = 'https://cdnjs.cloudflare.com/ajax/libs/exceljs/4.4.0/exceljs.min.js';
      s.dataset.exceljs = '1';
      s.onload = () => ok();
      s.onerror = () => no(new Error('exceljs'));
      document.head.appendChild(s);
    });
  }

  function getVisibleCopyColumns(table) {
    const checkedKeys = Array.from(document.querySelectorAll('#columnList .column-item'))
      .filter(item => item.querySelector('.column-visibility-checkbox')?.checked)
      .map(item => item.dataset.columnKey)
      .filter(Boolean);
    const visibleCols = [];
    Array.from(table.querySelectorAll('thead th')).forEach((th, i) => {
      if (th.classList.contains('nosort') || th.classList.contains('column-hidden')) return;
      const label = (th.childNodes[0]?.textContent || th.textContent || '').replace(/[⇅↑↓]/g, '').trim();
      if (!label || /^actions$/i.test(label)) return;
      const key = th.getAttribute('data-k') || '';
      if (checkedKeys.length && key && !checkedKeys.includes(key)) return;
      visibleCols.push({ index: i, label, key });
    });
    return visibleCols;
  }

  function cellPlainText(td) {
    if (!td) return '-';
    const nameLink = td.classList.contains('name') ? td.querySelector('a') : null;
    if (nameLink) return nameLink.textContent.trim();
    const clone = td.cloneNode(true);
    clone.querySelectorAll('.rl-star,.dropdown').forEach(n => n.remove());
    return clone.innerText.replace(/\s+/g, ' ').trim() || '-';
  }

  // Copy → download .xlsx with frozen header (Column Control columns only) + TSV on clipboard
  document.getElementById('copyBtn')?.addEventListener('click', async function () {
    const table = document.getElementById('dataTableBuilder');
    if (!table) return;

    const visibleCols = getVisibleCopyColumns(table);
    if (!visibleCols.length) {
      toast('No visible columns to copy – enable columns in Column Control');
      return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.querySelector('.rl-empty'));
    const matrix = [visibleCols.map(c => c.label)];
    rows.forEach(tr => {
      matrix.push(visibleCols.map(col => cellPlainText(tr.children[col.index])));
    });
    const txt = matrix.map(r => r.join('\t')).join('\n');

    // Plain text on clipboard (quick paste without freeze)
    try {
      await navigator.clipboard.writeText(txt);
    } catch (_) {
      try {
        const ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.cssText = 'position:fixed;left:-9999px';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
      } catch (_) {}
    }

    // Real Excel file with frozen first row — paste/HTML cannot freeze panes reliably
    toast('Preparing Excel with frozen header…');
    try {
      await loadExcelJs();
      const wb = new window.ExcelJS.Workbook();
      const ws = wb.addWorksheet('Riders', {
        views: [{ state: 'frozen', ySplit: 1, xSplit: 0 }],
      });
      matrix.forEach((row, ri) => {
        const excelRow = ws.addRow(row);
        excelRow.eachCell((cell, ci) => {
          cell.border = {
            top: { style: 'thin' }, left: { style: 'thin' },
            bottom: { style: 'thin' }, right: { style: 'thin' },
          };
          cell.alignment = {
            horizontal: (visibleCols[ci - 1]?.key === 'name' && ri > 0) ? 'left' : 'center',
            vertical: 'middle',
          };
          if (ri === 0) {
            cell.fill = { type: 'pattern', pattern: 'solid', fgColor: { argb: 'FFDBEAFE' } };
            cell.font = { bold: true, color: { argb: 'FF1E3A8A' } };
          }
        });
      });
      visibleCols.forEach((col, i) => {
        ws.getColumn(i + 1).width = Math.max(12, col.label.length + 4, col.key === 'name' ? 34 : 0);
      });

      const buffer = await wb.xlsx.writeBuffer();
      const blob = new Blob([buffer], { type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' });
      const a = document.createElement('a');
      const stamp = new Date().toISOString().slice(0, 10);
      a.href = URL.createObjectURL(blob);
      a.download = 'Riders-' + stamp + '.xlsx';
      document.body.appendChild(a);
      a.click();
      a.remove();
      setTimeout(() => URL.revokeObjectURL(a.href), 4000);

      const ic = document.getElementById('copyIco');
      if (ic) {
        const o = ic.innerHTML;
        ic.innerHTML = '<path d="M5 12l5 5 9-10"/>';
        setTimeout(() => { ic.innerHTML = o; }, 1500);
      }
      toast('Downloaded Excel with frozen header (' + rows.length + ' rows). Open the file — row 1 stays fixed.');
    } catch (err) {
      console.error(err);
      toast('Excel download failed – table text was still copied to clipboard');
    }
  });

  // Import modal
  const impOv = document.getElementById('impOv');
  document.getElementById('importRidersBtn')?.addEventListener('click', openImport);
  document.querySelectorAll('.rl-ov').forEach(o => {
    o.addEventListener('click', e => {
      if (e.target === o || e.target.closest('[data-close]')) o.hidden = true;
    });
  });

  function openImport() {
    resetImport();
    if (impOv) impOv.hidden = false;
  }

  function resetImport() {
    const title = document.getElementById('impT');
    const drop = document.getElementById('drop');
    const rules = document.getElementById('impRules');
    const note = document.getElementById('impNote');
    const btns = document.getElementById('impBtns');
    const body = document.getElementById('impBody');
    if (title) title.textContent = 'Import Riders';
    if (drop) drop.hidden = false;
    if (rules) rules.hidden = false;
    body?.querySelector('#pv')?.remove();
    if (note) note.textContent = '';
    if (btns) {
      btns.innerHTML = `
        <a class="rl-ghost" href="{{ route('riders.import.template', ['sample' => 1]) }}">Download sample file</a>
        <a class="rl-ghost" href="{{ route('riders.import.template') }}">Blank template</a>
        <button type="button" class="rl-ghost" data-close>Cancel</button>`;
    }
    const file = document.getElementById('impFile');
    if (file) file.value = '';
  }

  const drop = document.getElementById('drop');
  ['dragenter', 'dragover'].forEach(ev => drop?.addEventListener(ev, e => { e.preventDefault(); drop.classList.add('over'); }));
  ['dragleave', 'drop'].forEach(ev => drop?.addEventListener(ev, e => { e.preventDefault(); drop.classList.remove('over'); }));
  drop?.addEventListener('drop', e => {
    const f = e.dataTransfer.files[0];
    if (f) previewImport(f);
  });
  document.getElementById('impFile')?.addEventListener('change', e => {
    const f = e.target.files[0];
    if (f) previewImport(f);
  });

  function previewImport(file) {
    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', cfg.csrf);
    toast('Reading file…');
    fetch(cfg.importPreview, { method: 'POST', headers: { 'X-CSRF-TOKEN': cfg.csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, body: fd })
      .then(r => r.json())
      .then(data => {
        if (!data.success) { toast(data.message || 'Could not read file'); return; }
        showPreview(file.name, data);
      })
      .catch(() => toast('Could not read this file'));
  }

  function showPreview(fname, data) {
    const title = document.getElementById('impT');
    const dropEl = document.getElementById('drop');
    const rules = document.getElementById('impRules');
    const body = document.getElementById('impBody');
    const note = document.getElementById('impNote');
    const btns = document.getElementById('impBtns');
    if (title) title.textContent = 'Import preview – ' + fname;
    if (dropEl) dropEl.hidden = true;
    if (rules) rules.hidden = true;
    body?.querySelector('#pv')?.remove();
    const n = data.counts || { new: 0, upd: 0, err: 0 };
    const okN = (n.new || 0) + (n.upd || 0);
    const rows = data.rows || [];
    const ignored = data.ignored || [];
    const esc = s => String(s ?? '').replace(/[&<>"]/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));
    const div = document.createElement('div');
    div.id = 'pv';
    div.style.cssText = 'display:flex;flex-direction:column;gap:10px';
    div.innerHTML = `
      <div class="counts">
        <span class="tag t-new">${n.new || 0} new</span>
        <span class="tag t-upd">${n.upd || 0} update</span>
        <span class="tag t-err">${n.err || 0} with errors (will be skipped)</span>
        ${ignored.length ? `<span class="rl-note">Ignored columns: ${ignored.map(esc).join(', ')}</span>` : ''}
      </div>
      <div style="overflow-x:auto;border:1px solid #bfdbfe;border-radius:10px">
        <table class="pv"><thead><tr><th>Row</th><th>Rider Id</th><th>Action</th><th>Fields</th><th>Problems</th></tr></thead>
        <tbody>${rows.map(p => {
          const act = p.act === 'new' ? 'New' : (p.act === 'upd' ? 'Update' : 'Error');
          const tag = p.act === 'new' ? 'new' : (p.act === 'upd' ? 'upd' : 'err');
          const fields = p.act === 'err' ? '-' : (Object.entries(p.ch || {}).map(([k, v]) => `<b>${esc(k)}:</b> ${esc(v)}`).join(' · ') || '<span class="rl-note">No changes</span>');
          return `<tr><td>${p.row}</td><td>${esc(p.id)}</td><td><span class="tag t-${tag}">${act}</span></td><td>${fields}</td><td class="prob">${(p.prob || []).map(esc).join('<br>')}</td></tr>`;
        }).join('')}</tbody></table>
      </div>`;
    body?.appendChild(div);
    if (note) note.textContent = 'Only rows without errors will be imported.';
    if (btns) {
      btns.innerHTML = `
        <button type="button" class="rl-ghost" id="impBack">Choose another file</button>
        <button type="button" class="rl-btn" id="impGo" ${okN ? '' : 'disabled'}>Import ${okN} rider${okN === 1 ? '' : 's'}</button>`;
      document.getElementById('impBack')?.addEventListener('click', resetImport);
      document.getElementById('impGo')?.addEventListener('click', () => {
        const payload = { rows: rows.filter(p => p.act !== 'err'), _token: cfg.csrf };
        fetch(cfg.importCommit, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': cfg.csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(payload),
        })
          .then(r => r.json())
          .then(res => {
            if (!res.success) { toast(res.message || 'Import failed'); return; }
            if (impOv) impOv.hidden = true;
            toast(res.message || 'Import finished');
            window.location.reload();
          })
          .catch(() => toast('Import failed'));
      });
    }
  }
})();
</script>
@endsection
