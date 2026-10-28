@extends('layouts.app')
@section('title','Visa Expenses')
@php
  $selectedVisaStatusId = (int) request('visa_status_id', 0);
  $selectedPaymentStatus = (string) request('payment_status', '');
  $selectedChipName = null;
  if ($selectedVisaStatusId > 0) {
    $selectedChipName = (string) (($selectedVisaStatus->name ?? null) ?: optional(($visaStatuses ?? collect())->firstWhere('id', $selectedVisaStatusId))->name);
    if ($selectedPaymentStatus !== '') {
      $selectedChipName = trim($selectedChipName . ' · ' . ucfirst($selectedPaymentStatus));
    }
  }
  $personTargets = $personTargets ?? \App\Support\CompanyModuleVisibility::simAssignTargets();
  $allowPersonTypeSelection = $allowPersonTypeSelection ?? (count($personTargets) >= 2);
  $defaultPersonType = $defaultPersonType ?? (count($personTargets) === 1 ? $personTargets[0] : 'rider');
  $riderLabel = \App\Support\CompanyModuleVisibility::customizedMenuLabel('riders') ?? 'Rider';
  $employeeLabel = \App\Support\CompanyModuleVisibility::customizedMenuLabel('employees') ?? 'Employee';
@endphp

@push('third_party_stylesheets')
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
<link rel="stylesheet" href="{{ asset('css/riders-list-demo.css') }}">
<style>
  .riders-list-demo .rl-box.on {
    outline: 2px solid currentColor;
    outline-offset: 1px;
  }
</style>
@endpush

@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
  <div class="spinner-border text-primary" role="status"></div>
</div>

@canany(['visa_expense_account_creation_view', 'visa_expense_entries_view'])
<div class="riders-list-demo" id="visaAccountsListDemo"
     data-filter-ajax="{{ route('VisaExpense.filterAjax') }}"
     data-search-persons="{{ route('VisaExpense.searchPersons') }}"
     data-csrf="{{ csrf_token() }}"
     data-index-url="{{ route('VisaExpense.index') }}">

  <div class="rl-wrap">
    @include('flash::message')

    @if(isset($visaHeadAccountConfigured) && ! $visaHeadAccountConfigured)
    <div class="alert alert-warning mb-0">
      Visa Expense chart account is not configured in Global Accounts. You can view accounts, but creating expenses or payments may fail until an administrator sets up <strong>VISA_EXPENSE_ACCOUNT</strong>.
    </div>
    @endif

    {{-- Summary ticker --}}
    <div class="rl-ticker" id="rlTicker" aria-label="Visa status cards">
      <div class="rl-track" id="rlTrack">
        @php
          $cardIcoPaid = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="9"/><path d="m8.5 12.5 2.5 2.5 4.5-5"/></svg>';
          $cardIcoUnpaid = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16h.01"/></svg>';
          $flatCards = [];
          foreach (($visaStatuses ?? collect()) as $vs) {
            $counts = $visaStatusSliderCounts[$vs->id] ?? ['paid' => 0, 'unpaid' => 0];
            $catLabel = ($vs->relationLoaded('renewalCategories') && $vs->renewalCategories->isNotEmpty())
              ? $vs->renewalCategories->pluck('name')->join(', ')
              : 'Visa Status';
            $flatCards[] = [
              'id' => (int) $vs->id,
              'name' => (string) $vs->name,
              'category' => $catLabel,
              'paid' => (int) ($counts['paid'] ?? 0),
              'unpaid' => (int) ($counts['unpaid'] ?? 0),
              'sel' => $selectedVisaStatusId === (int) $vs->id,
            ];
          }
        @endphp
        @forelse($flatCards as $c)
        <div class="rl-card{{ $c['sel'] ? ' sel' : '' }}" data-status-id="{{ $c['id'] }}" title="{{ $c['name'] }}">
          <h4>{{ $c['name'] }}</h4>
          <small>{{ $c['category'] }}</small>
          <div class="rl-boxes">
            <div class="rl-box a{{ $c['sel'] && $selectedPaymentStatus === 'paid' ? ' on' : '' }}" data-payment="paid">{!! $cardIcoPaid !!}<span>PAID</span><b>{{ $c['paid'] }}</b></div>
            <div class="rl-box i{{ $c['sel'] && $selectedPaymentStatus === 'unpaid' ? ' on' : '' }}" data-payment="unpaid">{!! $cardIcoUnpaid !!}<span>UNPAID</span><b>{{ $c['unpaid'] }}</b></div>
          </div>
        </div>
        @empty
        <div class="rl-note px-2">No summary cards configured. Add options in Visa Expense Settings → Visa Expense Top.</div>
        @endforelse
        @foreach($flatCards as $c)
        <div class="rl-card{{ $c['sel'] ? ' sel' : '' }}" data-status-id="{{ $c['id'] }}" title="{{ $c['name'] }}" aria-hidden="true">
          <h4>{{ $c['name'] }}</h4>
          <small>{{ $c['category'] }}</small>
          <div class="rl-boxes">
            <div class="rl-box a{{ $c['sel'] && $selectedPaymentStatus === 'paid' ? ' on' : '' }}" data-payment="paid">{!! $cardIcoPaid !!}<span>PAID</span><b>{{ $c['paid'] }}</b></div>
            <div class="rl-box i{{ $c['sel'] && $selectedPaymentStatus === 'unpaid' ? ' on' : '' }}" data-payment="unpaid">{!! $cardIcoUnpaid !!}<span>UNPAID</span><b>{{ $c['unpaid'] }}</b></div>
          </div>
        </div>
        @endforeach
      </div>
    </div>

    {{-- Toolbar --}}
    <div class="rl-toolbar">
      <div class="rl-tb-left">
        <div class="rl-dd">
          <button type="button" class="rl-btn" id="actBtn" aria-haspopup="true" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
            Visa Actions
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" id="chev"><path d="m6 9 6 6 6-6"/></svg>
          </button>
          <div class="rl-menu" id="actMenu" hidden role="menu">
            @can('visa_expense_account_creation_create')
            <button type="button" role="menuitem" data-bs-toggle="modal" data-bs-target="#createaccount">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="10" cy="8" r="4"/><path d="M3 21c0-4 3-6 7-6M18 14v6M15 17h6"/></svg>
              <span><strong>Create Expense Account</strong><em>Open a visa expense account for a person</em></span>
            </button>
            @endcan
          </div>
        </div>
        <a class="rl-btn" id="allBtn" href="{{ route('VisaExpense.index') }}">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.5"/><path d="M2 20c0-3.5 3-5.5 7-5.5s7 2 7 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7M22 20c0-3-2-5-5-5.4"/></svg>
          All Accounts
        </a>
        <span class="rl-chip" id="chip" @if(!$selectedChipName) hidden @endif>
          <span id="chipTxt">{{ $selectedChipName }}</span>
          <button type="button" id="chipX" aria-label="Clear status filter">×</button>
        </span>
      </div>
      <div class="rl-tb-right">
        <input class="rl-search" id="q" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
        <button type="button" class="rl-btn" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Accounts
        </button>
      </div>
    </div>

    <div class="rl-tbox" id="tbox">
      @include('visa_expenses.account_table', [
        'data' => $data,
        'nextUnpaidVisaByAccountId' => $nextUnpaidVisaByAccountId ?? [],
        'urgentVisaExpiryByAccountId' => $urgentVisaExpiryByAccountId ?? [],
        'riders' => collect(),
        'employees' => collect(),
        'personTargets' => $personTargets,
        'allowPersonTypeSelection' => $allowPersonTypeSelection,
        'defaultPersonType' => $defaultPersonType,
        'ajaxFragment' => true,
      ])
    </div>

    <div id="rlPagerWrap">
      @if(method_exists($data, 'links'))
        @include('components.riders-demo-pagination', ['paginator' => $data, 'entityLabel' => 'accounts'])
      @endif
    </div>
  </div>
</div>

{{-- Filter sidebar --}}
<div id="filterOverlay" class="filter-overlay"></div>
<div id="filterSidebar" class="filter-sidebar riders-filter-sidebar">
  <div class="filter-header">
    <h5>Filter Accounts</h5>
    <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
  </div>
  <div class="filter-body" id="searchTopbody">
    <form id="filterForm" action="{{ route('VisaExpense.index') }}" method="GET" data-rfp-skip-lock="1">
      @if(request()->filled('visa_status_id'))
      <input type="hidden" name="visa_status_id" value="{{ request('visa_status_id') }}">
      @endif
      @if(request()->filled('quick_search'))
      <input type="hidden" name="quick_search" id="filterQuickSearch" value="{{ request('quick_search') }}">
      @endif
      <div class="row">
        <div class="form-group col-md-12">
          <label for="name">Account Name</label>
          <input type="text" name="name" id="name" class="form-control" placeholder="Filter By Account Name" value="{{ request('name') }}">
        </div>
        <div class="form-group col-md-12">
          <label for="payment_status_filter">Payment Status</label>
          <select class="form-control" name="payment_status" id="payment_status_filter">
            <option value="">All</option>
            <option value="paid" {{ request('payment_status') === 'paid' ? 'selected' : '' }}>Paid</option>
            <option value="unpaid" {{ request('payment_status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
          </select>
        </div>
        <div class="col-md-12 form-group text-center">
          <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
        </div>
      </div>
    </form>
  </div>
</div>

{{-- Create account modal --}}
<div class="modal fade" id="createaccount" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create Visa Expense Account</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form action="{{ route('VisaExpense.accountcreate') }}" method="POST" id="visaCreateAccountForm">
          @csrf
          <div class="row g-3">
            @if(empty($personTargets))
            <div class="col-12">
              <div class="alert alert-warning mb-0">No person modules are enabled for this company. Enable Riders and/or Employees in company settings.</div>
            </div>
            @else
            @if($allowPersonTypeSelection)
            <div class="col-12">
              <label class="form-label d-block mb-2">Type</label>
              <div class="btn-group w-100" role="group" aria-label="Person type">
                @if(in_array('rider', $personTargets, true))
                <input type="radio" class="btn-check" name="person_type" id="visa_person_type_rider" value="rider"
                  {{ $defaultPersonType === 'rider' ? 'checked' : '' }} autocomplete="off">
                <label class="btn btn-outline-primary" for="visa_person_type_rider">{{ $riderLabel }}</label>
                @endif
                @if(in_array('employee', $personTargets, true))
                <input type="radio" class="btn-check" name="person_type" id="visa_person_type_employee" value="employee"
                  {{ $defaultPersonType === 'employee' ? 'checked' : '' }} autocomplete="off">
                <label class="btn btn-outline-primary" for="visa_person_type_employee">{{ $employeeLabel }}</label>
                @endif
              </div>
            </div>
            @elseif($defaultPersonType !== '')
            <input type="hidden" name="person_type" id="visa_person_type_hidden" value="{{ $defaultPersonType }}">
            @endif

            @if(in_array('rider', $personTargets, true))
            <div class="col-12 visa-person-field visa-person-field-rider{{ $defaultPersonType === 'employee' ? ' d-none' : '' }}">
              <label for="visa_rider_id" class="form-label">Select {{ $riderLabel }}</label>
              <select class="form-select visa-person-select" id="visa_rider_id" data-person-type="rider"
                {{ $defaultPersonType === 'rider' ? 'required' : 'disabled' }}>
                <option value="">Type to search…</option>
              </select>
            </div>
            @endif

            @if(in_array('employee', $personTargets, true))
            <div class="col-12 visa-person-field visa-person-field-employee{{ $defaultPersonType === 'rider' ? ' d-none' : '' }}">
              <label for="visa_employee_id" class="form-label">Select {{ $employeeLabel }}</label>
              <select class="form-select visa-person-select" id="visa_employee_id" data-person-type="employee"
                {{ $defaultPersonType === 'employee' ? 'required' : 'disabled' }}>
                <option value="">Type to search…</option>
              </select>
            </div>
            @endif

            <input type="hidden" name="person_key" id="person_key" value="">

            <div class="col-12">
              <label for="renewal_category_id" class="form-label">Visa Category</label>
              <select class="form-select" id="renewal_category_id" name="renewal_category_id" required>
                <option value="">Select person first</option>
                @foreach($renewalCategories ?? [] as $cat)
                <option value="{{ $cat->id }}" disabled>{{ $cat->name }}</option>
                @endforeach
              </select>
              <div class="form-text" id="renewal_category_help">
                Tickets are generated only from statuses in the selected visa category.
              </div>
            </div>
            @endif
            <div class="col-12 text-end">
              <button type="submit" class="btn btn-primary" @if(empty($personTargets)) disabled @endif>Create</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="rl-toast" id="rlToast" hidden></div>
@else
<div class="card">
  <div class="card-body">
    <h5>You are not authorized to access this page</h5>
  </div>
</div>
@endcanany
@endsection

@section('page-script')
@include('delete_requests._confirm_delete_script', [
  'entityName' => 'Visa Expense Account',
  'confirmText' => 'This will submit a delete request or move the account to the Recycle Bin.',
  'method' => 'GET',
])
<script>
(function () {
  const root = document.getElementById('visaAccountsListDemo');
  if (!root) return;

  const cfg = {
    filterAjax: root.dataset.filterAjax,
    searchPersons: root.dataset.searchPersons,
    csrf: root.dataset.csrf,
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
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') setMenu(false);
  });
  document.getElementById('actMenu')?.addEventListener('click', function () {
    setMenu(false);
  });

  document.getElementById('chipX')?.addEventListener('click', function () {
    window.location.href = cfg.indexUrl;
  });

  // Match riders list: sidebar uses .open, overlay uses .show
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
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') closeFilter();
  });

  // Ticker scroll / drag / click — scroll the overflow container (#rlTicker), not #rlTrack
  const tk = document.getElementById('rlTicker');
  const track = document.getElementById('rlTrack');
  if (tk && track && track.querySelector('.rl-card[data-status-id]')) {
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
      const payBox = e.target.closest('.rl-box[data-payment]');
      const card = e.target.closest('.rl-card[data-status-id]');
      if (!card) return;
      const statusId = card.dataset.statusId;
      if (!statusId) return;
      const url = new URL(window.location.href);
      if (payBox) {
        const payment = payBox.dataset.payment;
        const same = url.searchParams.get('visa_status_id') === String(statusId)
          && url.searchParams.get('payment_status') === payment;
        if (same) {
          url.searchParams.delete('payment_status');
        } else {
          url.searchParams.set('visa_status_id', String(statusId));
          url.searchParams.set('payment_status', payment);
        }
      } else {
        if (url.searchParams.get('visa_status_id') === String(statusId) && !url.searchParams.get('payment_status')) {
          url.searchParams.delete('visa_status_id');
          url.searchParams.delete('payment_status');
        } else {
          url.searchParams.set('visa_status_id', String(statusId));
          url.searchParams.delete('payment_status');
        }
      }
      url.searchParams.delete('page');
      window.location.href = url.toString();
    }, true);
  }

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
        const newUrl = new URL(window.location.href);
        ['quick_search', 'page', 'per_page', 'name', 'payment_status', 'visa_status_id'].forEach(k => {
          if (params.has(k)) newUrl.searchParams.set(k, params.get(k));
          else if (['quick_search', 'page', 'per_page', 'name'].includes(k)) newUrl.searchParams.delete(k);
        });
        history.replaceState({}, '', newUrl.toString());
        bindTableEvents();
      })
      .catch(() => toast('Could not refresh accounts'))
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

  document.getElementById('filterForm')?.addEventListener('submit', function (e) {
    e.preventDefault();
    const fd = new FormData(this);
    const extra = { page: 1 };
    ['name', 'payment_status', 'visa_status_id', 'quick_search'].forEach(k => {
      const v = fd.get(k);
      extra[k] = v ? String(v) : '';
    });
    closeFilter();
    refreshTable(extra);
  });

  function bindTableEvents() {
    document.querySelectorAll('.js-delete-expense-account').forEach(el => {
      el.onclick = function () {
        const url = this.dataset.deleteUrl;
        if (url && typeof confirmDelete === 'function') confirmDelete(url);
      };
    });
  }
  bindTableEvents();

  // Create modal + Select2 AJAX person search
  const $ = window.jQuery;
  if ($) {
    const $createModal = $('#createaccount');
    const $categorySelect = $('#renewal_category_id');
    const $categoryHelp = $('#renewal_category_help');
    const $personKey = $('#person_key');
    const $form = $('#visaCreateAccountForm');
    const eligibleCategoriesUrlTemplate = @json(route('VisaExpense.eligibleRenewalCategories', ['personType' => '__TYPE__', 'personId' => '__ID__']));
    const defaultPersonType = @json($defaultPersonType ?? 'rider');

    function currentPersonType() {
      const checked = $createModal.find('input[name="person_type"]:checked').val();
      if (checked) return checked;
      return $createModal.find('input[name="person_type"]').filter(':hidden').val() || defaultPersonType;
    }
    function activePersonSelect() {
      return $createModal.find('.visa-person-select[data-person-type="' + currentPersonType() + '"]');
    }
    function syncPersonKey() {
      const $select = activePersonSelect();
      const value = $select.length && !$select.prop('disabled') ? ($select.val() || '') : '';
      $personKey.val(value);
      return value;
    }
    function initPersonSelect2($select) {
      if (!$select.length) return;
      if ($select.hasClass('select2-hidden-accessible')) $select.select2('destroy');
      const personType = $select.data('person-type');
      $select.select2({
        dropdownParent: $createModal,
        placeholder: 'Type to search…',
        allowClear: true,
        minimumInputLength: 1,
        ajax: {
          url: cfg.searchPersons,
          dataType: 'json',
          delay: 250,
          data: function (params) {
            return { q: params.term || '', type: personType };
          },
          processResults: function (data) {
            return { results: data.results || [] };
          },
        },
      });
    }
    function initCategorySelect2() {
      if ($categorySelect.hasClass('select2-hidden-accessible')) $categorySelect.select2('destroy');
      $categorySelect.select2({
        dropdownParent: $createModal,
        placeholder: 'Visa category',
        allowClear: true,
      });
    }
    function applyPersonType(type) {
      $createModal.find('.visa-person-field').addClass('d-none');
      $createModal.find('.visa-person-select').each(function () {
        const $sel = $(this);
        const isActive = $sel.data('person-type') === type;
        $sel.prop('disabled', !isActive);
        $sel.prop('required', isActive);
        if (!isActive) $sel.val(null).trigger('change');
      });
      $createModal.find('.visa-person-field-' + type).removeClass('d-none');
      initPersonSelect2(activePersonSelect());
      syncPersonKey();
      loadEligibleCategories(syncPersonKey());
    }
    function resetCategorySelect() {
      $categorySelect.find('option').each(function () {
        const $opt = $(this);
        if ($opt.val() === '') $opt.prop('disabled', false).text('Select person first');
        else $opt.prop('disabled', true);
      });
      $categorySelect.val('').trigger('change');
      initCategorySelect2();
      $categoryHelp.text('Select a person first to see available visa categories.');
    }
    function applyEligibleCategories(categories) {
      $categorySelect.find('option').each(function () {
        const $opt = $(this);
        if ($opt.val() === '') {
          $opt.prop('disabled', false).text(categories.length ? 'Select' : 'No category available');
          return;
        }
        $opt.prop('disabled', true);
      });
      categories.forEach(function (cat) {
        $categorySelect.find('option[value="' + cat.id + '"]').prop('disabled', false);
      });
      if (categories.length === 0) {
        $categorySelect.val('').trigger('change');
        initCategorySelect2();
        $categoryHelp.text('All visa categories already have accounts for this person.');
        return;
      }
      if (categories.length === 1) {
        $categorySelect.val(String(categories[0].id));
        $categoryHelp.text('Only one category remaining: ' + categories[0].name + '.');
      } else {
        $categorySelect.val('');
        $categoryHelp.text('Select any visa category to create an account for.');
      }
      initCategorySelect2();
      $categorySelect.trigger('change');
    }
    function loadEligibleCategories(personKey) {
      if (!personKey || personKey.indexOf(':') === -1) {
        resetCategorySelect();
        return;
      }
      const parts = personKey.split(':');
      $categoryHelp.text('Loading allowed categories…');
      $.ajax({
        url: eligibleCategoriesUrlTemplate
          .replace('__TYPE__', encodeURIComponent(parts[0]))
          .replace('__ID__', encodeURIComponent(parts[1])),
        method: 'GET',
        dataType: 'json',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
      }).done(function (response) {
        applyEligibleCategories(response.categories || []);
      }).fail(function () {
        resetCategorySelect();
        $categoryHelp.text('Unable to load renewal categories. Please refresh and try again.');
      });
    }

    $createModal.on('change', 'input[name="person_type"]', function () {
      applyPersonType($(this).val());
    });
    $createModal.on('change select2:select select2:clear', '.visa-person-select', function () {
      loadEligibleCategories(syncPersonKey());
    });
    $form.on('submit', function (e) {
      const personKey = syncPersonKey();
      if (!personKey) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'warning', title: 'Select a person', text: 'Please select a rider or employee first.' });
        }
        return false;
      }
    });
    $createModal.on('shown.bs.modal', function () {
      applyPersonType(currentPersonType());
    });
    $createModal.on('hidden.bs.modal', function () {
      $createModal.find('.visa-person-select').val(null).trigger('change');
      $personKey.val('');
      resetCategorySelect();
      if ($createModal.find('input[name="person_type"][type="radio"]').length) {
        $createModal.find('input[name="person_type"][value="' + defaultPersonType + '"]').prop('checked', true);
      }
    });

  }
})();
</script>
@endsection
