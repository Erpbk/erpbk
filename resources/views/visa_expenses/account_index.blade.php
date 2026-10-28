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
  if (!isset($defaultPersonType)) {
    $defaultPersonType = match (true) {
      $personTargets === [] => '',
      count($personTargets) === 1 => $personTargets[0],
      in_array('rider', $personTargets, true) => 'rider',
      default => $personTargets[0],
    };
  }
  $createAccountFormLocked = $personTargets === [];
  $riderLabel = \App\Support\CompanyModuleVisibility::customizedMenuLabel('riders') ?? 'Rider';
  $employeeLabel = \App\Support\CompanyModuleVisibility::customizedMenuLabel('employees') ?? 'Employee';
  $singlePersonTypeLabel = match ($defaultPersonType) {
    'rider' => $riderLabel,
    'employee' => $employeeLabel,
    default => '',
  };
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
  .visa-totals-cards {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin: 0 0 12px 0;
  }
  .visa-totals-cards .total-card {
    flex: 1 1 calc(12% - 8px);
    min-width: 118px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-left-width: 4px;
    border-radius: 8px;
    padding: 8px 10px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    text-decoration: none;
    color: inherit;
    transition: box-shadow .15s ease, transform .15s ease;
    cursor: pointer;
  }
  .visa-totals-cards .total-card:hover {
    box-shadow: 0 2px 8px rgba(15, 23, 42, 0.08);
    transform: translateY(-1px);
  }
  .visa-totals-cards .total-card.is-on {
    outline: 2px solid currentColor;
    outline-offset: 1px;
  }
  .visa-totals-cards .total-card .label {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: .3px;
    color: #6b7280;
    margin-bottom: 2px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .visa-totals-cards .total-card .label i {
    font-size: 10px;
    flex-shrink: 0;
  }
  .visa-totals-cards .total-card .value {
    font-size: 16px;
    font-weight: 700;
    color: #111827;
  }
  .visa-totals-cards .total-card .meta {
    margin-top: 2px;
    font-size: 11px;
    color: #6b7280;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }
  .visa-totals-cards .total-card .meta .unpaid-zero { color: #059669; font-weight: 600; }
  .visa-totals-cards .total-card .meta .unpaid-due { color: #6b7280; }
  .visa-totals-cards .total-card .value.unpaid-zero { color: #059669; }
  .visa-totals-cards .total-card .value.unpaid-due { color: #dc2626; }
  .visa-totals-cards .total-category {
    border-left-color: #7c3aed;
    background: linear-gradient(180deg, rgba(124, 58, 237, 0.06), rgba(124, 58, 237, 0.02));
  }
  .visa-totals-cards .total-category .label { color: #7c3aed; }
  .visa-totals-cards .total-next-unpaid {
    border-left-color: #dc2626;
    background: linear-gradient(180deg, rgba(220, 38, 38, 0.06), rgba(220, 38, 38, 0.02));
  }
  .visa-totals-cards .total-next-unpaid .label { color: #dc2626; }
  .visa-totals-cards .total-wps {
    border-left-color: #059669;
    background: linear-gradient(180deg, rgba(5, 150, 105, 0.06), rgba(5, 150, 105, 0.02));
  }
  .visa-totals-cards .total-wps .label { color: #059669; }
  @media (max-width: 768px) {
    .visa-totals-cards .total-card {
      flex: 1 1 calc(50% - 8px);
      min-width: 140px;
    }
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
          $visaStatusesByName = collect($visaStatuses ?? [])->keyBy(fn ($vs) => trim((string) $vs->name));
          $flatCards = [];
          // Same pattern as riders: option name as heading, top-bar category (driving column group) underneath
          foreach (($topBarSliderCategories ?? collect()) as $topCategory) {
            foreach (($topCategory->options ?? []) as $option) {
              $optionName = trim((string) ($option->name ?? ''));
              if ($optionName === '') {
                continue;
              }
              $vs = $visaStatusesByName->get($optionName);
              if (! $vs) {
                continue;
              }
              $counts = $visaStatusSliderCounts[$vs->id] ?? ['paid' => 0, 'unpaid' => 0];
              $flatCards[] = [
                'id' => (int) $vs->id,
                'name' => (string) $vs->name,
                'category' => (string) ($topCategory->name ?? ''),
                'paid' => (int) ($counts['paid'] ?? 0),
                'unpaid' => (int) ($counts['unpaid'] ?? 0),
                'sel' => $selectedVisaStatusId === (int) $vs->id,
              ];
            }
          }
          // Fallback if top-bar categories are empty but statuses were resolved
          if ($flatCards === [] && ($visaStatuses ?? collect())->isNotEmpty()) {
            foreach ($visaStatuses as $vs) {
              $counts = $visaStatusSliderCounts[$vs->id] ?? ['paid' => 0, 'unpaid' => 0];
              $flatCards[] = [
                'id' => (int) $vs->id,
                'name' => (string) $vs->name,
                'category' => '',
                'paid' => (int) ($counts['paid'] ?? 0),
                'unpaid' => (int) ($counts['unpaid'] ?? 0),
                'sel' => $selectedVisaStatusId === (int) $vs->id,
              ];
            }
          }
        @endphp
        @forelse($flatCards as $c)
        <div class="rl-card{{ $c['sel'] ? ' sel' : '' }}" data-status-id="{{ $c['id'] }}" title="{{ $c['name'] }}">
          <h4>{{ $c['name'] }}</h4>
          @if($c['category'] !== '')
          <small>{{ $c['category'] }}</small>
          @endif
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
          @if($c['category'] !== '')
          <small>{{ $c['category'] }}</small>
          @endif
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
            @canany(['visa_expense_account_creation_view', 'visa_expense_entries_view'])
            <a role="menuitem" href="{{ route('VisaExpense.exportCustomizableAccounts', request()->only(['name', 'payment_status', 'visa_status_id', 'quick_search', 'person_status', 'renewal_category_id', 'wps'])) }}">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><path d="M14 3v6h6M12 12v6M9 15l3 3 3-3"/></svg>
              <span><strong>Export Accounts</strong><em>Export visible columns to Excel (latest category per person)</em></span>
            </a>
            @endcanany
            <button type="button" role="menuitem" class="openColumnControlSidebar">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
              <span><strong>Column Control</strong><em>Show, hide, and reorder table columns</em></span>
            </button>
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
        <button type="button" class="rl-btn icon" id="copyBtn" title="Copy table to clipboard" aria-label="Copy table">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" id="copyIco"><rect x="8" y="8" width="12" height="12" rx="2"/><path d="M16 8V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h2"/></svg>
        </button>
        <input class="rl-search" id="q" type="search" placeholder="Quick Search..." aria-label="Quick search" value="{{ request('quick_search') }}">
        <button type="button" class="rl-btn" id="filterBtn">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
          Filter Accounts
        </button>
      </div>
    </div>

    @php
      $wpsOptions = $wpsOptions ?? [];
      $accountStats = $accountStats ?? [
        'next_unpaid' => 0,
        'next_unpaid_amount' => 0,
        'next_unpaid_documents' => 0,
        'by_wps' => [],
        'by_category' => [],
        'by_category_unpaid_amount' => [],
      ];
      $statsBaseParams = request()->except(['renewal_category_id', 'wps', 'payment_status', 'visa_status_id', 'page']);
      $statsNextUnpaidOn = request('payment_status') === 'unpaid' && !request()->filled('visa_status_id') && !request()->filled('renewal_category_id') && !request()->filled('wps');
      $nextUnpaidAmount = (float) ($accountStats['next_unpaid_amount'] ?? 0);
    @endphp
    <div class="visa-totals-cards" aria-label="Visa account stats">
      @foreach($wpsOptions as $wpsValue => $wpsLabel)
      @php
        $wpsStat = $accountStats['by_wps'][$wpsValue] ?? ['count' => 0, 'unpaid_amount' => 0, 'label' => $wpsLabel];
        $wpsOn = (string) request('wps') === (string) $wpsValue
          && !request()->filled('renewal_category_id')
          && !request()->filled('payment_status')
          && !request()->filled('visa_status_id');
        $wpsParams = $wpsOn ? $statsBaseParams : array_merge($statsBaseParams, ['wps' => $wpsValue]);
        $wpsUnpaidAmount = (float) ($wpsStat['unpaid_amount'] ?? 0);
      @endphp
      <a class="total-card total-wps{{ $wpsOn ? ' is-on' : '' }}"
         href="{{ route('VisaExpense.index', $wpsParams) }}"
         title="Accounts for {{ $wpsLabel }}">
        <div class="label"><i class="ti ti-badge"></i>{{ $wpsLabel }}</div>
        <div class="value">{{ number_format((int) ($wpsStat['count'] ?? 0)) }} accounts</div>
        <div class="meta">
          Next unpaid:
          <span class="{{ $wpsUnpaidAmount <= 0 ? 'unpaid-zero' : 'unpaid-due' }}">{!! \App\Helpers\Currency::format($wpsUnpaidAmount, 2) !!}</span>
        </div>
      </a>
      @endforeach
      <a class="total-card total-next-unpaid{{ $statsNextUnpaidOn ? ' is-on' : '' }}"
         href="{{ route('VisaExpense.index', $statsNextUnpaidOn ? $statsBaseParams : array_merge($statsBaseParams, ['payment_status' => 'unpaid'])) }}"
         title="Accounts with unpaid visa documents">
        <div class="label"><i class="ti ti-alert-circle"></i>Next Unpaid</div>
        <div class="value {{ $nextUnpaidAmount <= 0 ? 'unpaid-zero' : 'unpaid-due' }}">{!! \App\Helpers\Currency::format($nextUnpaidAmount, 2) !!}</div>
        <div class="meta">{{ number_format((int) ($accountStats['next_unpaid_documents'] ?? 0)) }} unpaid documents</div>
      </a>
      @foreach(($renewalCategories ?? []) as $statCat)
      @php
        $catId = (int) $statCat->id;
        $catOn = (string) request('renewal_category_id') === (string) $catId
          && !request()->filled('wps')
          && !request()->filled('payment_status')
          && !request()->filled('visa_status_id');
        $catParams = $catOn ? $statsBaseParams : array_merge($statsBaseParams, ['renewal_category_id' => $catId]);
        $catUnpaidAmount = (float) ($accountStats['by_category_unpaid_amount'][$catId] ?? 0);
      @endphp
      <a class="total-card total-category{{ $catOn ? ' is-on' : '' }}"
         href="{{ route('VisaExpense.index', $catParams) }}"
         title="{{ $statCat->name }} accounts">
        <div class="label"><i class="ti ti-passport"></i>{{ $statCat->name }}</div>
        <div class="value">{{ number_format((int) ($accountStats['by_category'][$catId] ?? 0)) }} accounts</div>
        <div class="meta">
          Next unpaid:
          <span class="{{ $catUnpaidAmount <= 0 ? 'unpaid-zero' : 'unpaid-due' }}">{!! \App\Helpers\Currency::format($catUnpaidAmount, 2) !!}</span>
        </div>
      </a>
      @endforeach
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
        'wpsOptions' => $wpsOptions ?? [],
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
      @if(request()->filled('payment_status'))
      <input type="hidden" name="payment_status" value="{{ request('payment_status') }}">
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
          <label for="person_status_filter">Person Status</label>
          <select class="form-control" name="person_status" id="person_status_filter">
            <option value="">All</option>
            <option value="active" {{ request('person_status') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ request('person_status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            <option value="employee" {{ request('person_status') === 'employee' ? 'selected' : '' }}>Employee</option>
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="renewal_category_id_filter">Visa Category</label>
          <select class="form-control" name="renewal_category_id" id="renewal_category_id_filter">
            <option value="">All</option>
            @foreach(($renewalCategories ?? []) as $cat)
            <option value="{{ $cat->id }}" {{ (string) request('renewal_category_id') === (string) $cat->id ? 'selected' : '' }}>
              {{ $cat->name }}
            </option>
            @endforeach
          </select>
        </div>
        <div class="form-group col-md-12">
          <label for="wps_filter">WPS</label>
          <select class="form-control" name="wps" id="wps_filter">
            <option value="">All</option>
            @foreach(($wpsOptions ?? []) as $wpsValue => $wpsLabel)
            <option value="{{ $wpsValue }}" {{ request('wps') === (string) $wpsValue ? 'selected' : '' }}>{{ $wpsLabel }}</option>
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
            @if($createAccountFormLocked)
            <div class="col-12">
              <div class="alert alert-warning mb-0" role="alert">
                <i class="ti ti-lock me-1"></i>
                No person modules are enabled for this company. Enable <strong>{{ $riderLabel }}</strong> and/or <strong>{{ $employeeLabel }}</strong> in company module settings to create visa expense accounts.
              </div>
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
            <div class="col-12">
              <p class="text-muted small mb-0">
                <i class="ti ti-user-check me-1"></i>Person type is set to <strong>{{ $singlePersonTypeLabel }}</strong> (only enabled person module).
              </p>
            </div>
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
              <button type="submit" class="btn btn-primary" @if($createAccountFormLocked) disabled @endif>Create</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@include('riders.partials.column_control_modal', [
  'tableColumns' => $tableColumns ?? [],
  'exportRoute' => route('VisaExpense.exportCustomizableAccounts'),
  'tableIdentifier' => 'visa_accounts_table',
  'fixedColumnsCount' => 1,
  'exportFilterKeys' => ['name', 'payment_status', 'visa_status_id', 'quick_search', 'person_status', 'renewal_category_id', 'wps'],
  'exportNote' => 'Only ticked columns are exported, in the current order, for all accounts (one row per person using the latest visa category). Current list filters are applied.',
])

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
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
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

  // Select2 for filter fields
  if (window.jQuery) {
    jQuery(function ($) {
      $('#person_status_filter,#renewal_category_id_filter,#wps_filter').each(function () {
        if ($(this).length) {
          $(this).select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: $(this).find('option:first').text() || 'Select',
          });
        }
      });
    });
  }

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
        ['quick_search', 'page', 'per_page', 'name', 'payment_status', 'visa_status_id', 'person_status', 'renewal_category_id', 'wps'].forEach(k => {
          if (params.has(k) && params.get(k) !== '') newUrl.searchParams.set(k, params.get(k));
          else if (['quick_search', 'page', 'per_page', 'name', 'person_status', 'renewal_category_id', 'wps'].includes(k)) newUrl.searchParams.delete(k);
        });
        history.replaceState({}, '', newUrl.toString());
        bindTableEvents();
        if (window.ColumnController && typeof window.ColumnController.reapplySettings === 'function') {
          window.ColumnController.reapplySettings();
        }
      })
      .catch(() => toast('Could not refresh accounts'))
      .finally(() => { if (overlay) overlay.style.display = 'none'; });
  }

  window.rlToast = toast;

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
    ['name', 'payment_status', 'visa_status_id', 'quick_search', 'person_status', 'renewal_category_id', 'wps'].forEach(k => {
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

  function getVisibleCopyColumns(table) {
    const checkedKeys = Array.from(document.querySelectorAll('#columnList .column-item'))
      .filter(item => item.querySelector('.column-visibility-checkbox')?.checked)
      .map(item => item.dataset.columnKey)
      .filter(Boolean);
    const visibleCols = [];
    Array.from(table.querySelectorAll('thead th')).forEach((th, i) => {
      if (th.classList.contains('nosort') || th.classList.contains('column-hidden')) return;
      if (window.getComputedStyle(th).display === 'none') return;
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
    const clone = td.cloneNode(true);
    clone.querySelectorAll('.dropdown').forEach(n => n.remove());
    return clone.innerText.replace(/\s+/g, ' ').trim() || '-';
  }

  function cellExportValue(td, col) {
    if (!td) return { text: '-', href: null };
    if (col.key === 'name') {
      const a = td.querySelector('a[href]');
      if (a) return { text: (a.textContent || '').trim() || '-', href: a.href };
    }
    if (col.key === 'status' || col.key === 'wps' || col.key === 'category') {
      const badge = td.querySelector('.badge');
      if (badge) return { text: (badge.textContent || '').trim() || '-', href: null };
    }
    return { text: cellPlainText(td), href: null };
  }

  function escHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({
      '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;',
    }[c]));
  }

  document.getElementById('copyBtn')?.addEventListener('click', async function () {
    const table = document.getElementById('dataTableBuilder');
    if (!table) return;

    const visibleCols = getVisibleCopyColumns(table);
    if (!visibleCols.length) {
      toast('No visible columns to copy – enable columns in Column Control');
      return;
    }

    const rows = Array.from(table.querySelectorAll('tbody tr')).filter(tr => !tr.querySelector('.rl-empty'));
    const matrix = [visibleCols.map(c => ({ text: c.label, href: null }))];
    rows.forEach(tr => {
      matrix.push(visibleCols.map(col => cellExportValue(tr.children[col.index], col)));
    });

    const txt = matrix.map(r => r.map(c => c.text).join('\t')).join('\n');
    const htmlTable = '<table><thead><tr>' +
      visibleCols.map(c => '<th>' + escHtml(c.label) + '</th>').join('') +
      '</tr></thead><tbody>' +
      matrix.slice(1).map(r => '<tr>' + r.map(c => {
        if (c.href) {
          return '<td><a href="' + escHtml(c.href) + '">' + escHtml(c.text) + '</a></td>';
        }
        return '<td>' + escHtml(c.text) + '</td>';
      }).join('') + '</tr>').join('') +
      '</tbody></table>';

    let copied = false;
    try {
      if (window.ClipboardItem && navigator.clipboard?.write) {
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
      setTimeout(() => { ic.innerHTML = o; }, 1500);
    }
    toast('Copied ' + rows.length + ' row' + (rows.length === 1 ? '' : 's') + ' to clipboard');
  });

  // Create modal + Select2 AJAX person search
  const $ = window.jQuery;
  if ($) {
    const $createModal = $('#createaccount');
    const $categorySelect = $('#renewal_category_id');
    const $categoryHelp = $('#renewal_category_help');
    const $personKey = $('#person_key');
    const $form = $('#visaCreateAccountForm');
    const eligibleCategoriesUrlTemplate = @json(route('VisaExpense.eligibleRenewalCategories', ['personType' => '__TYPE__', 'personId' => '__ID__']));
    const personTargets = @json($personTargets ?? []);
    const defaultPersonType = @json($defaultPersonType ?? '');
    const createAccountFormLocked = !personTargets.length || !defaultPersonType;

    function currentPersonType() {
      if (createAccountFormLocked) return '';
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
      if (createAccountFormLocked || !type || personTargets.indexOf(type) === -1) {
        return;
      }
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
      if (createAccountFormLocked) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Person modules disabled',
            text: 'Enable Riders and/or Employees in company module settings before creating a visa expense account.',
          });
        }
        return false;
      }
      const personKey = syncPersonKey();
      if (!personKey) {
        e.preventDefault();
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'warning', title: 'Select a person', text: 'Please select a person first.' });
        }
        return false;
      }
    });
    $createModal.on('shown.bs.modal', function () {
      if (createAccountFormLocked) return;
      applyPersonType(currentPersonType());
    });
    $createModal.on('hidden.bs.modal', function () {
      if (createAccountFormLocked) return;
      $createModal.find('.visa-person-select').val(null).trigger('change');
      $personKey.val('');
      resetCategorySelect();
      if ($createModal.find('input[name="person_type"][type="radio"]').length && defaultPersonType) {
        $createModal.find('input[name="person_type"][value="' + defaultPersonType + '"]').prop('checked', true);
      }
    });

  }
})();
</script>
@endsection
