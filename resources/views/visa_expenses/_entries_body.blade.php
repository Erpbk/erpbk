@php
$accountId = $account->rider_id;
$categoryId = (int) ($activeRenewalCategory->id ?? 0);
$categoryExpenseQuery = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
(int) $account->id,
$accountId ? (int) $accountId : null,
$categoryId
);
$totalUnpaid = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->sum('amount');
$totalPaid = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->sum('amount');
$unpaidCount = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->count();
$paidCount = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->count();

$showLicenseSection = !empty($showLicenseExpenseSection) && !empty($licenseAccount);
$installmentStats = $installmentStats ?? ['unpaid_amount'=>0,'paid_amount'=>0,'paid_count'=>0,'unpaid_count'=>0];
$licInstallmentData = $licInstallmentData ?? collect();
$licInstallmentStats = $licInstallmentStats ?? ['unpaid_amount'=>0,'paid_amount'=>0,'paid_count'=>0,'unpaid_count'=>0];

// Dynamic category tabs — only accounts that exist for this rider/employee
$categoryAccounts = collect($visaCategoryAccounts ?? (
    isset($siblingAccounts)
        ? collect([$account])->merge($siblingAccounts)->unique('id')->sortBy('id')->values()
        : collect([$account])
));
$useOverviewSwitch = !empty($visaRiderOverview) && !empty($visaOverviewPersonId);

// Active tab: license only when requested AND rider has a license account; else active visa category
$requestedTab = request('tab', 'visa');
$activeTab = ($requestedTab === 'license' && $showLicenseSection) ? 'license' : 'visa';
if (request()->filled('license_account_id') && $showLicenseSection) {
    $activeTab = 'license';
}
$activeCategoryName = $activeRenewalCategory->name ?? ($account->renewalCategory->name ?? 'Visa');
@endphp

<style>
  /* ── Expense panel (neutral / professional) ───────────────── */
  .exp-panel {
    --exp-ink: #1e293b;
    --exp-muted: #64748b;
    --exp-line: #e2e8f0;
    --exp-surface: #f8fafc;
    --exp-accent: #334155;
    --exp-warn: #b45309;
    --exp-ok: #047857;
  }

  .exp-tab-nav {
    border-bottom: 1px solid var(--exp-line);
    margin-bottom: 1.25rem;
  }

  .exp-tab-nav .nav-link {
    border: none;
    border-bottom: 2px solid transparent;
    border-radius: 0;
    padding: .6rem 1rem;
    font-weight: 600;
    font-size: .875rem;
    color: var(--exp-muted);
    transition: color .15s, border-color .15s;
  }

  .exp-tab-nav .nav-link:hover {
    color: var(--exp-ink);
  }

  .exp-tab-nav .nav-link.active {
    color: var(--exp-ink);
    border-bottom-color: var(--exp-accent);
    background: none;
  }

  .exp-tab-nav .nav-link .badge {
    font-size: .65rem;
    font-weight: 600;
    vertical-align: middle;
    background: #475569 !important;
    color: #fff;
  }

  .exp-card {
    border: 1px solid var(--exp-line) !important;
    border-radius: .65rem;
    overflow: hidden;
    box-shadow: 0 1px 2px rgba(15, 23, 42, .04) !important;
  }

  .section-header {
    display: flex;
    align-items: center;
    gap: .75rem;
    padding: .9rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid var(--exp-line);
  }

  .section-header .section-icon {
    width: 2.15rem;
    height: 2.15rem;
    border-radius: .4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: .8rem;
    color: var(--exp-muted);
    background: var(--exp-surface);
    border: 1px solid var(--exp-line);
    flex-shrink: 0;
  }

  .section-header h5 {
    margin: 0;
    font-size: .95rem;
    font-weight: 650;
    color: var(--exp-ink);
    letter-spacing: -.01em;
  }

  .section-header .btn-exp-primary {
    background: var(--exp-accent);
    border-color: var(--exp-accent);
    color: #fff;
    font-weight: 600;
  }

  .section-header .btn-exp-primary:hover {
    background: #1e293b;
    border-color: #1e293b;
    color: #fff;
  }

  .section-header .btn-exp-ghost {
    background: #fff;
    border: 1px solid var(--exp-line);
    color: var(--exp-ink);
    font-weight: 600;
  }

  .section-header .btn-exp-ghost:hover {
    background: var(--exp-surface);
    border-color: #cbd5e1;
    color: var(--exp-ink);
  }

  .stat-strip {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    padding: 1rem 1.25rem;
    background: var(--exp-surface);
    border-bottom: 1px solid var(--exp-line);
  }

  .stat-pill {
    display: flex;
    align-items: center;
    gap: .75rem;
    background: #fff;
    border-radius: .5rem;
    padding: .65rem .9rem;
    min-width: 150px;
    flex: 1;
    border: 1px solid var(--exp-line);
    transition: border-color .15s, box-shadow .15s;
  }

  .stat-pill:hover {
    border-color: #cbd5e1;
    box-shadow: 0 1px 3px rgba(15, 23, 42, .06);
  }

  .stat-pill .sp-icon {
    font-size: .8rem;
    width: 2.1rem;
    height: 2.1rem;
    flex-shrink: 0;
    border-radius: .4rem;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--exp-surface);
    border: 1px solid var(--exp-line);
    color: var(--exp-muted);
  }

  .stat-pill.danger .sp-icon {
    color: var(--exp-warn);
    background: #fffbeb;
    border-color: #fde68a;
  }

  .stat-pill.success .sp-icon {
    color: var(--exp-ok);
    background: #ecfdf5;
    border-color: #a7f3d0;
  }

  .stat-pill .sp-body {
    display: flex;
    flex-direction: column;
    min-width: 0;
  }

  .stat-pill .sp-label {
    font-size: .62rem;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: var(--exp-muted);
    line-height: 1.2;
    font-weight: 600;
    white-space: nowrap;
  }

  .stat-pill .sp-value {
    font-size: 1rem;
    font-weight: 700;
    color: var(--exp-ink);
    line-height: 1.25;
    margin-top: .12rem;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
  }

  .stat-pill.danger .sp-value {
    color: #92400e;
  }

  .stat-pill.success .sp-value {
    color: #065f46;
  }

  .exp-card > .card-body {
    background: #fff;
  }

  /* Soften table status chips inside this panel */
  .exp-panel .badge.bg-danger,
  .exp-panel .badge.bg-success,
  .exp-panel .badge.bg-primary,
  .exp-panel .badge.bg-warning,
  .exp-panel .badge.bg-info {
    font-weight: 600;
    letter-spacing: .02em;
    border-radius: .35rem;
    padding: .35em .65em;
  }

  .exp-panel .badge.bg-danger {
    background: #fff7ed !important;
    color: #9a3412 !important;
    border: 1px solid #fed7aa;
  }

  .exp-panel .badge.bg-success {
    background: #ecfdf5 !important;
    color: #065f46 !important;
    border: 1px solid #a7f3d0;
  }

  .exp-panel .badge.bg-primary {
    background: #f1f5f9 !important;
    color: #334155 !important;
    border: 1px solid #cbd5e1;
  }

  .exp-panel .badge.bg-warning {
    background: #fffbeb !important;
    color: #92400e !important;
    border: 1px solid #fde68a;
  }

  .exp-panel .badge.bg-info {
    background: #f8fafc !important;
    color: #475569 !important;
    border: 1px solid #e2e8f0;
  }

  .exp-panel .table thead th {
    font-size: .72rem;
    text-transform: uppercase;
    letter-spacing: .04em;
    color: #64748b;
    font-weight: 650;
    border-bottom-color: #e2e8f0;
    background: #fff;
  }

  .exp-panel .table tbody td {
    vertical-align: middle;
    color: #1e293b;
    border-color: #f1f5f9;
  }
</style>

<div class="content exp-panel">
  @include('flash::message')

  {{-- ── Dynamic category tabs (only accounts that exist for this rider) ── --}}
  <div class="d-flex align-items-center exp-tab-nav" id="expenseTabs" role="tablist">
    <ul class="nav mb-0 border-0 flex-grow-1 flex-wrap" style="border-bottom:none;">
      @foreach($categoryAccounts as $catAccount)
      @php
        $isActiveCat = $activeTab === 'visa' && (int) $catAccount->id === (int) $account->id;
        $catLabel = $catAccount->renewalCategory->name ?? ('Account #'.$catAccount->id);
        $catUnpaid = \App\Support\VisaRenewalCategoryService::unpaidCountForAccount($catAccount);
        $catUrl = $useOverviewSwitch
          ? route('VisaExpense.showForRider', array_filter([
              'riderId' => $visaOverviewPersonId,
              'type' => ($visaOverviewType ?? 'rider') === 'employee' ? 'employee' : null,
              'account_id' => $catAccount->id,
              'tab' => 'visa',
            ]))
          : route('VisaExpense.generatentries', ['id' => $catAccount->id, 'tab' => 'visa']);
      @endphp
      <li class="nav-item">
        <a class="nav-link {{ $isActiveCat ? 'active' : '' }}"
          href="{{ $catUrl }}"
          id="tab-visa-cat-{{ $catAccount->id }}">
          <i class="fa fa-passport me-1"></i>
          {{ $catLabel }}
          @if($catUnpaid > 0)
          <span class="badge rounded-pill ms-1">{{ $catUnpaid }}</span>
          @endif
        </a>
      </li>
      @endforeach

      @if($showLicenseSection)
      @foreach(($licenseCategoryAccounts ?? collect($licenseAccount ? [$licenseAccount] : [])) as $licAccount)
      @php
        $isActiveLic = $activeTab === 'license' && (int) $licAccount->id === (int) ($licenseAccount->id ?? 0);
        $licLabel = $licAccount->licenseCategory->name ?? ('License #'.$licAccount->id);
        $licUnpaid = \App\Support\LicenseCategoryService::unpaidCountForAccount($licAccount);
        $licUrl = request()->fullUrlWithQuery([
          'tab' => 'license',
          'license_account_id' => $licAccount->id,
        ]);
      @endphp
      <li class="nav-item">
        <a class="nav-link {{ $isActiveLic ? 'active' : '' }}"
          href="{{ $licUrl }}"
          id="tab-license-cat-{{ $licAccount->id }}">
          <i class="fa fa-id-card me-1"></i>
          {{ $licLabel }}
          @if($licUnpaid > 0)
          <span class="badge rounded-pill ms-1">{{ $licUnpaid }}</span>
          @endif
        </a>
      </li>
      @endforeach
      @endif
    </ul>
  </div>

  {{-- ════════════════════════════════════════════════════════════
       ACTIVE VISA CATEGORY PANE
  ════════════════════════════════════════════════════════════ --}}

  <div id="pane-visa" class="{{ $activeTab === 'visa' ? '' : 'd-none' }}">

    {{-- ── Visa Expenses card ── --}}
    <div class="card exp-card mb-4">
      <div class="section-header">
        <div class="section-icon">
          <i class="fa fa-passport"></i>
        </div>
        <div class="flex-grow-1">
          <h5>{{ $activeCategoryName }}</h5>
        </div>
        @can('visaexpense_create')
        <a class="btn btn-sm btn-exp-primary action-btn show-modal"
          href="javascript:void(0);"
          data-action="{{ route('VisaExpense.create', ['id' => $account->id]) }}"
          data-size="lg"
          data-title="New expense entry — {{ $activeCategoryName }}">
          <i class="fa fa-plus me-1"></i> Add New Expense
        </a>
        @endcan
      </div>

      <div class="stat-strip">
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-arrow-down"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)$totalUnpaid,2) }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)$totalPaid,2) }}</span>
          </div>
        </div>
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-exclamation"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Expenses</span>
            <span class="sp-value">{{ $unpaidCount }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Expenses</span>
            <span class="sp-value">{{ $paidCount }}</span>
          </div>
        </div>
      </div>

      <div class="card-body table-responsive px-2 py-0" id="table-data">
        @include('visa_expenses.table', ['data' => $data])
      </div>
    </div>

    {{-- ── Visa Installments card ── --}}
    <div class="card exp-card mb-4">
      <div class="section-header">
        <div class="section-icon">
          <i class="fa fa-calendar-check"></i>
        </div>
        <div class="flex-grow-1">
          <h5>Installments of loans</h5>
        </div>
        <div class="d-flex gap-2">
          @can('visa_expense_create')
          <a class="btn btn-sm btn-exp-primary action-btn show-modal"
            href="javascript:void(0);"
            data-action="{{ route('Installments.createInstallmentPlanForm', $account->id) }}"
            data-size="lg"
            data-title="Create Installment Entry">
            <i class="fa fa-plus me-1"></i> Installment Plan
          </a>
          @endcan
          @if(isset($installmentData) && $installmentData->count() > 0)
          <a href="javascript:void(0);"
            class="btn btn-sm btn-exp-ghost action-btn show-modal"
            data-action="{{ route('Installments.generateInstallmentInvoice', ['riderId' => $account->id]) }}"
            data-size="xl"
            data-title="Installment plan invoice — {{ $account->name ?? 'Person' }}">
            <i class="fa fa-file-invoice me-1"></i> Invoice
          </a>
          @endif
        </div>
      </div>

      <div class="stat-strip">
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-arrow-down"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)$installmentStats['unpaid_amount'],2) }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)$installmentStats['paid_amount'],2) }}</span>
          </div>
        </div>
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-exclamation"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Installments</span>
            <span class="sp-value">{{ (int)$installmentStats['unpaid_count'] }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Installments</span>
            <span class="sp-value">{{ (int)$installmentStats['paid_count'] }}</span>
          </div>
        </div>
      </div>

      <div class="card-body table-responsive px-2 py-0">
        @include('visa_expenses.installmentPlanTable', [
        'data' => $installmentData ?? collect(),
        'account' => $account,
        'installmentPayRoute' => 'Installments.payInstallment',
        'installmentUpdateFieldRoute' => 'Installments.updateInstallmentField',
        'installmentDeleteRoute' => 'Installments.deleteInstallment',
        'installmentPaymentReceivingRoute' => 'Installments.paymentReceivingModal',
        'installmentPlanModel' => \App\Models\visa_installment_plan::class,
        ])
      </div>
    </div>

  </div>{{-- /pane-visa --}}

  {{-- ════════════════════════════════════════════════════════════
       LICENSE TAB
  ════════════════════════════════════════════════════════════ --}}
  @if($showLicenseSection)
  <div id="pane-license" class="{{ $activeTab==='license' ? '' : 'd-none' }}">

    @if($licenseAccount ?? null)
    {{-- ── License Expenses card ── --}}
    <div class="card exp-card mb-4">
      <div class="section-header">
        <div class="section-icon">
          <i class="fa fa-file-invoice-dollar"></i>
        </div>
        <div class="flex-grow-1">
          <h5>{{ $activeLicenseCategory->name ?? 'License Expense' }}</h5>
        </div>
        @can('license_expense_create')
        <a class="btn btn-sm btn-exp-primary action-btn show-modal"
          href="javascript:void(0);"
          data-action="{{ route('LicenseExpense.create', $licenseAccount->id) }}"
          data-size="lg"
          data-title="New expense entry — {{ $activeLicenseCategory->name ?? 'License' }}">
          <i class="fa fa-plus me-1"></i> Add New Expense
        </a>
        @endcan
      </div>

      <div class="stat-strip">
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-arrow-down"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)($licenseExpenseTotals['unpaid_amount'] ?? 0),2) }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)($licenseExpenseTotals['paid_amount'] ?? 0),2) }}</span>
          </div>
        </div>
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-exclamation"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Expenses</span>
            <span class="sp-value">{{ (int)($licenseExpenseTotals['unpaid_count'] ?? 0) }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Expenses</span>
            <span class="sp-value">{{ (int)($licenseExpenseTotals['paid_count'] ?? 0) }}</span>
          </div>
        </div>
      </div>

      <div class="card-body table-responsive px-2 py-0" id="license-table-data">
        @include('license_expenses.table', ['data' => $licenseExpenseData ?? collect()])
      </div>
    </div>

    {{-- ── License Installments card ── --}}
    <div class="card exp-card mb-4">
      <div class="section-header">
        <div class="section-icon">
          <i class="fa fa-calendar-check"></i>
        </div>
        <div class="flex-grow-1">
          <h5>License Installments</h5>
        </div>
        <div class="d-flex gap-2">
          @can('license_expense_create')
          <a class="btn btn-sm btn-exp-primary action-btn show-modal"
            href="javascript:void(0);"
            data-action="{{ route('LicenseExpense.createInstallmentPlanForm', $licenseAccount->id) }}"
            data-size="lg"
            data-title="Create License Installment Plan">
            <i class="fa fa-plus me-1"></i> Installment Plan
          </a>
          @endcan
          @if($licInstallmentData->count() > 0)
          <a class="btn btn-sm btn-exp-ghost action-btn show-modal"
            href="javascript:void(0);"
            data-action="{{ route('LicenseExpense.generateInstallmentInvoice', ['riderId' => $licenseAccount->id]) }}"
            data-size="xl"
            data-title="License Installment Invoice — {{ $licenseAccount->name }}">
            <i class="fa fa-file-invoice me-1"></i> Invoice
          </a>
          @endif
        </div>
      </div>

      <div class="stat-strip">
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-arrow-down"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)$licInstallmentStats['unpaid_amount'],2) }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Amount</span>
            <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float)$licInstallmentStats['paid_amount'],2) }}</span>
          </div>
        </div>
        <div class="stat-pill danger">
          <div class="sp-icon"><i class="fa fa-exclamation"></i></div>
          <div class="sp-body">
            <span class="sp-label">Unpaid Installments</span>
            <span class="sp-value">{{ (int)$licInstallmentStats['unpaid_count'] }}</span>
          </div>
        </div>
        <div class="stat-pill success">
          <div class="sp-icon"><i class="fa fa-check"></i></div>
          <div class="sp-body">
            <span class="sp-label">Paid Installments</span>
            <span class="sp-value">{{ (int)$licInstallmentStats['paid_count'] }}</span>
          </div>
        </div>
      </div>

      <div class="card-body table-responsive px-2 py-0">
        @include('visa_expenses.installmentPlanTable', [
        'data' => $licInstallmentData,
        'account' => $licenseAccount,
        'installmentPayRoute' => 'LicenseExpense.payInstallment',
        'installmentUpdateFieldRoute' => 'LicenseExpense.updateInstallmentField',
        'installmentDeleteRoute' => 'LicenseExpense.deleteInstallment',
        'installmentPaymentReceivingRoute' => 'LicenseExpense.paymentReceivingModal',
        'installmentPlanModel' => \App\Models\license_installment_plan::class,
        'canEditInstallment' => user_can('license_expense_edit'),
        'canDeleteInstallment' => user_can('license_expense_delete'),
        ])
      </div>
    </div>

    @else
    {{-- No license account found but module is enabled --}}
    <div class="card exp-card">
      <div class="card-body text-center py-5 text-muted">
        <i class="fa fa-id-card fa-2x mb-2 d-block opacity-50"></i>
        No license expense account found for this rider.
        @can('license_expense_create')
        <a href="{{ route('LicenseExpense.index') }}" class="btn btn-sm btn-exp-ghost ms-2">
          Create License Account
        </a>
        @endcan
      </div>
    </div>
    @endif

  </div>{{-- /pane-license --}}
  @endif

</div>{{-- /content --}}