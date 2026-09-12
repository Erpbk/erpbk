@php
$accountId = $account->rider_id;
$categoryId = (int) ($activeRenewalCategory->id ?? 0);
$categoryExpenseQuery = \App\Support\VisaRenewalCategoryService::expensesForAccountQuery(
    (int) $account->id,
    $accountId ? (int) $accountId : null,
    $categoryId
);
$totalUnpaid  = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->sum('amount');
$totalPaid    = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->sum('amount');
$unpaidCount  = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->count();
$paidCount    = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->count();

$showLicenseSection  = !empty($showLicenseExpenseSection);
$installmentStats    = $installmentStats    ?? ['unpaid_amount'=>0,'paid_amount'=>0,'paid_count'=>0,'unpaid_count'=>0];
$licInstallmentData  = $licInstallmentData  ?? collect();
$licInstallmentStats = $licInstallmentStats ?? ['unpaid_amount'=>0,'paid_amount'=>0,'paid_count'=>0,'unpaid_count'=>0];

// Determine active tab from query-string; default = visa
$activeTab = request('tab', 'visa');
if (!in_array($activeTab, ['visa','license'])) { $activeTab = 'visa'; }
@endphp

<style>
/* ── Tab pills ─────────────────────────────────────────────── */
.exp-tab-nav { border-bottom: 2px solid #dee2e6; margin-bottom: 1.25rem; }
.exp-tab-nav .nav-link {
    border: none; border-bottom: 3px solid transparent;
    border-radius: 0; padding: .55rem 1.2rem; font-weight: 600;
    color: #6c757d; transition: color .15s, border-color .15s;
}
.exp-tab-nav .nav-link:hover { color: #0d6efd; }
.exp-tab-nav .nav-link.active { color: #0d6efd; border-bottom-color: #0d6efd; background: none; }
.exp-tab-nav .nav-link .badge { font-size: .7rem; vertical-align: middle; }

/* ── Module badge in tab ───────────────────────────────────── */
.tab-badge-visa    { background:#6f42c1; }
.tab-badge-license { background:#0d6efd; }

/* ── Section header ───────────────────────────────────────── */
.section-header {
    display: flex; align-items: center; gap: .5rem;
    padding: .75rem 1.25rem; border-radius: .5rem .5rem 0 0;
}
.section-header .section-icon {
    width: 2rem; height: 2rem; border-radius: .35rem;
    display: flex; align-items: center; justify-content: center;
    font-size: .85rem; color:#fff; flex-shrink:0;
}
.section-header h5 { margin:0; font-size:1rem; font-weight:700; }
.section-header .section-badge { font-size:.75rem; opacity:.85; }

/* ── Stat strip ───────────────────────────────────────────── */
.stat-strip {
    display:flex; flex-wrap:wrap; gap:.85rem;
    padding:1rem 1.25rem;
    background:#f8f9fb;
    border-top:1px solid rgba(0,0,0,.06);
}
.stat-pill {
    display:flex; align-items:center; gap:.85rem;
    background:#fff; border-radius:.55rem;
    padding:.7rem 1rem;
    min-width:160px; flex:1;
    border:1px solid #e9ecef;
    border-left:4px solid #dee2e6;
    box-shadow:0 1px 4px rgba(0,0,0,.05);
    transition: box-shadow .15s, transform .15s;
}
.stat-pill:hover { box-shadow:0 3px 10px rgba(0,0,0,.09); transform:translateY(-1px); }
.stat-pill.danger  { border-left-color:#dc3545; }
.stat-pill.success { border-left-color:#198754; }
.stat-pill .sp-icon {
    font-size:.95rem; width:2.4rem; height:2.4rem; flex-shrink:0;
    border-radius:.45rem; display:flex; align-items:center; justify-content:center; color:#fff;
}
.stat-pill.danger  .sp-icon { background:linear-gradient(135deg,#dc3545 0%,#e8596a 100%); }
.stat-pill.success .sp-icon { background:linear-gradient(135deg,#198754 0%,#28a76a 100%); }
.stat-pill .sp-body { display:flex; flex-direction:column; min-width:0; }
.stat-pill .sp-label {
    font-size:.63rem; text-transform:uppercase; letter-spacing:.06em;
    color:#9aa0ac; line-height:1.2; font-weight:600; white-space:nowrap;
}
.stat-pill .sp-value {
    font-size:1.05rem; font-weight:800; color:#1a1e2d;
    line-height:1.25; margin-top:.15rem; white-space:nowrap;
}
</style>

<div class="content">
  @include('flash::message')

  {{-- ── Sibling visa accounts ──────────────────────────────────── --}}
  @if(isset($siblingAccounts) && $siblingAccounts->count() > 0)
  <div class="alert alert-light border mb-3 py-2">
    <span class="text-muted me-2 small fw-semibold">Other renewal accounts for this {{ !empty($account->employee_id) ? 'employee' : 'rider' }}:</span>
    @foreach($siblingAccounts as $sibling)
    <a href="{{ route('VisaExpense.generatentries', $sibling->id) }}"
       class="btn btn-sm btn-outline-secondary me-1 mb-1">
      {{ $sibling->renewalCategory->name ?? 'Account #'.$sibling->id }}
    </a>
    @endforeach
  </div>
  @endif

  {{-- ── Tab navigation ───────────────────────────────────────── --}}
  <ul class="nav exp-tab-nav" id="expenseTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link {{ $activeTab==='visa' ? 'active':'' }}"
         href="{{ request()->fullUrlWithQuery(['tab'=>'visa']) }}"
         id="tab-visa">
        <i class="fa fa-passport me-1"></i>
        Visa Expense
        @if($unpaidCount > 0)
          <span class="badge rounded-pill tab-badge-visa ms-1">{{ $unpaidCount }}</span>
        @endif
      </a>
    </li>
    @if($showLicenseSection)
    <li class="nav-item">
      <a class="nav-link {{ $activeTab==='license' ? 'active':'' }}"
         href="{{ request()->fullUrlWithQuery(['tab'=>'license']) }}"
         id="tab-license">
        <i class="fa fa-id-card me-1"></i>
        License Expense
        @php $licenseUnpaidCount = (int)($licenseExpenseTotals['unpaid_count'] ?? 0); @endphp
        @if($licenseUnpaidCount > 0)
          <span class="badge rounded-pill tab-badge-license ms-1">{{ $licenseUnpaidCount }}</span>
        @endif
      </a>
    </li>
    @endif
  </ul>

  {{-- ════════════════════════════════════════════════════════════
       VISA TAB
  ════════════════════════════════════════════════════════════ --}}
  <div id="pane-visa" class="{{ $activeTab==='visa' ? '' : 'd-none' }}">

    {{-- ── Visa Expenses card ── --}}
    <div class="card shadow-sm mb-4 border-0">
      <div class="section-header" style="background:linear-gradient(135deg,#6f42c1 0%,#8b5cf6 100%);">
        <div class="section-icon" style="background:rgba(255,255,255,.2);">
          <i class="fa fa-passport"></i>
        </div>
        <div class="flex-grow-1">
          <h5 class="text-white">
            Visa Expense &mdash; {{ $account->name }}
            @if(!empty($activeRenewalCategory))
              <span class="section-badge text-white-50 fw-normal">({{ $activeRenewalCategory->name }})</span>
            @endif
          </h5>
        </div>
        @can('visaexpense_create')
        <a class="btn btn-sm btn-light fw-semibold action-btn show-modal"
           href="javascript:void(0);"
           data-action="{{ route('VisaExpense.create', ['id' => $account->id]) }}"
           data-size="lg"
           data-title="New expense entry — {{ $activeRenewalCategory->name ?? 'Visa' }}">
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
    <div class="card shadow-sm mb-4 border-0">
      <div class="section-header" style="background:linear-gradient(135deg,#0f5132 0%,#198754 100%);">
        <div class="section-icon" style="background:rgba(255,255,255,.2);">
          <i class="fa fa-calendar-check"></i>
        </div>
        <div class="flex-grow-1">
          <h5 class="text-white">Visa Installments</h5>
        </div>
        <div class="d-flex gap-2">
          @can('visa_expense_create')
          <a class="btn btn-sm btn-light fw-semibold action-btn show-modal"
             href="javascript:void(0);"
             data-action="{{ route('Installments.createInstallmentPlanForm', $account->id) }}"
             data-size="lg"
             data-title="Create Installment Entry">
            <i class="fa fa-plus me-1"></i> Installment Plan
          </a>
          @endcan
          @if(isset($installmentData) && $installmentData->count() > 0)
          <a href="javascript:void(0);"
             class="btn btn-sm btn-outline-light fw-semibold action-btn show-modal"
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
          'data'                        => $installmentData ?? collect(),
          'account'                     => $account,
          'installmentPayRoute'         => 'Installments.payInstallment',
          'installmentUpdateFieldRoute' => 'Installments.updateInstallmentField',
          'installmentDeleteRoute'      => 'Installments.deleteInstallment',
          'installmentPlanModel'        => \App\Models\visa_installment_plan::class,
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
      {{-- Sibling license accounts --}}
      @if(isset($licenseSiblingAccounts) && $licenseSiblingAccounts->count() > 0)
      <div class="alert alert-light border mb-3 py-2">
        <span class="text-muted me-2 small fw-semibold">Other license accounts for this rider:</span>
        @foreach($licenseSiblingAccounts as $sibling)
        <a href="{{ request()->fullUrlWithQuery(['license_account_id' => $sibling->id, 'tab' => 'license']) }}"
           class="btn btn-sm btn-outline-secondary me-1 mb-1">
          {{ $sibling->licenseCategory->name ?? 'Account #'.$sibling->id }}
        </a>
        @endforeach
      </div>
      @endif

      {{-- ── License Expenses card ── --}}
      <div class="card shadow-sm mb-4 border-0">
        <div class="section-header" style="background:linear-gradient(135deg,#0d6efd 0%,#1d8cf8 100%);">
          <div class="section-icon" style="background:rgba(255,255,255,.2);">
            <i class="fa fa-file-invoice-dollar"></i>
          </div>
          <div class="flex-grow-1">
            <h5 class="text-white">
              License Expense &mdash; {{ $licenseAccount->name }}
              @if(!empty($activeLicenseCategory))
                <span class="section-badge text-white-50 fw-normal">({{ $activeLicenseCategory->name }})</span>
              @endif
            </h5>
          </div>
          @can('license_expense_create')
          <a class="btn btn-sm btn-light fw-semibold action-btn show-modal"
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
      <div class="card shadow-sm mb-4 border-0">
        <div class="section-header" style="background:linear-gradient(135deg,#0f5132 0%,#198754 100%);">
          <div class="section-icon" style="background:rgba(255,255,255,.2);">
            <i class="fa fa-calendar-check"></i>
          </div>
          <div class="flex-grow-1">
            <h5 class="text-white">License Installments</h5>
          </div>
          <div class="d-flex gap-2">
            @can('license_expense_create')
            <a class="btn btn-sm btn-light fw-semibold action-btn show-modal"
               href="javascript:void(0);"
               data-action="{{ route('LicenseExpense.createInstallmentPlanForm', $licenseAccount->id) }}"
               data-size="lg"
               data-title="Create License Installment Plan">
              <i class="fa fa-plus me-1"></i> Installment Plan
            </a>
            @endcan
            @if($licInstallmentData->count() > 0)
            <a class="btn btn-sm btn-outline-light fw-semibold action-btn show-modal"
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
            'data'                        => $licInstallmentData,
            'account'                     => $licenseAccount,
            'installmentPayRoute'         => 'LicenseExpense.payInstallment',
            'installmentUpdateFieldRoute' => 'LicenseExpense.updateInstallmentField',
            'installmentDeleteRoute'      => 'LicenseExpense.deleteInstallment',
            'installmentPlanModel'        => \App\Models\license_installment_plan::class,
            'canEditInstallment'          => user_can('license_expense_edit'),
            'canDeleteInstallment'        => user_can('license_expense_delete'),
          ])
        </div>
      </div>

    @else
      {{-- No license account found but module is enabled --}}
      <div class="card shadow-sm border-0">
        <div class="card-body text-center py-5 text-muted">
          <i class="fa fa-id-card fa-2x mb-2 d-block opacity-50"></i>
          No license expense account found for this rider.
          @can('license_expense_create')
          <a href="{{ route('LicenseExpense.index') }}" class="btn btn-sm btn-outline-primary ms-2">
            Create License Account
          </a>
          @endcan
        </div>
      </div>
    @endif

  </div>{{-- /pane-license --}}
  @endif

</div>{{-- /content --}}
