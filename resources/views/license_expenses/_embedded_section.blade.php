@php
$showLicenseSection = !empty($showLicenseExpenseSection);
$licenseAccount = $licenseAccount ?? null;
$licenseExpenseData = $licenseExpenseData ?? collect();
$activeLicenseCategory = $activeLicenseCategory ?? null;
$licenseSiblingAccounts = $licenseSiblingAccounts ?? collect();
$licenseTotalUnpaid = (float) ($licenseExpenseTotals['unpaid_amount'] ?? 0);
$licenseTotalPaid = (float) ($licenseExpenseTotals['paid_amount'] ?? 0);
$licenseUnpaidCount = (int) ($licenseExpenseTotals['unpaid_count'] ?? 0);
$licensePaidCount = (int) ($licenseExpenseTotals['paid_count'] ?? 0);
$licenseCategoryName = $activeLicenseCategory->name ?? 'License Expense';
@endphp

@if($showLicenseSection)
@if($licenseSiblingAccounts->count() > 0 && $licenseAccount)
<div class="alert alert-light border mb-3">
  <span class="text-muted me-2">Other license accounts for this rider:</span>
  @foreach($licenseSiblingAccounts as $sibling)
  <a href="{{ request()->fullUrlWithQuery(['license_account_id' => $sibling->id]) }}" class="btn btn-sm btn-outline-secondary me-1 mb-1">
    {{ $sibling->licenseCategory->name ?? 'Account #' . $sibling->id }}
  </a>
  @endforeach
</div>
@endif

@if($licenseAccount)
<div class="card exp-card mb-4">
  <div class="section-header">
    <div class="section-icon">
      <i class="fa fa-file-invoice-dollar"></i>
    </div>
    <div class="flex-grow-1">
      <h5>{{ $licenseCategoryName }}</h5>
    </div>
    @can('license_expense_create')
    <a class="btn btn-sm btn-exp-primary action-btn show-modal"
      href="javascript:void(0);"
      data-action="{{ route('LicenseExpense.create', $licenseAccount->id) }}"
      data-size="lg"
      data-title="New expense entry — {{ $licenseCategoryName }}">
      <i class="fa fa-plus me-1"></i> Add New Expense
    </a>
    @endcan
  </div>
  <div class="stat-strip">
    <div class="stat-pill danger">
      <div class="sp-icon"><i class="fa fa-arrow-down"></i></div>
      <div class="sp-body">
        <span class="sp-label">Unpaid Amount</span>
        <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($licenseTotalUnpaid, 2) }}</span>
      </div>
    </div>
    <div class="stat-pill success">
      <div class="sp-icon"><i class="fa fa-check"></i></div>
      <div class="sp-body">
        <span class="sp-label">Paid Amount</span>
        <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($licenseTotalPaid, 2) }}</span>
      </div>
    </div>
    <div class="stat-pill danger">
      <div class="sp-icon"><i class="fa fa-exclamation"></i></div>
      <div class="sp-body">
        <span class="sp-label">Unpaid Expenses</span>
        <span class="sp-value">{{ $licenseUnpaidCount }}</span>
      </div>
    </div>
    <div class="stat-pill success">
      <div class="sp-icon"><i class="fa fa-check"></i></div>
      <div class="sp-body">
        <span class="sp-label">Paid Expenses</span>
        <span class="sp-value">{{ $licensePaidCount }}</span>
      </div>
    </div>
  </div>
  <div class="card-body table-responsive px-2 py-0" id="license-table-data">
    @include('license_expenses.table', ['data' => $licenseExpenseData])
  </div>
</div>
@else
<div class="card exp-card">
  <div class="card-body text-center py-5 text-muted">
    <i class="fa fa-id-card fa-2x mb-2 d-block opacity-50"></i>
    No license expense account found for this rider.
    @can('license_expense_create')
    <a href="{{ route('LicenseExpense.index') }}" class="btn btn-sm btn-exp-ghost ms-2">
      Create License Expense Account
    </a>
    @endcan
  </div>
</div>
@endif
@endif
