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

  <div class="card mt-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">
        License Expense
        @if($licenseAccount)
        - {{ $licenseAccount->name }}
        @endif
      </h3>
      @if($licenseAccount)
      @can('license_expense_create')
      <a class="btn btn-primary action-btn show-modal"
        href="javascript:void(0);"
        data-action="{{ route('LicenseExpense.create', $licenseAccount->id) }}"
        data-size="lg"
        data-title="New expense entry — {{ $activeLicenseCategory->name ?? 'License' }}">
        Add New Expense
      </a>
      @endcan
      @else
      @can('license_expense_create')
      <a class="btn btn-outline-primary btn-sm" href="{{ route('LicenseExpense.index') }}">
        Create License Expense Account
      </a>
      @endcan
      @endif
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($licenseTotalUnpaid, 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($licenseTotalPaid, 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Expenses</div>
        <div class="value">{{ $licenseUnpaidCount }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Expenses</div>
        <div class="value">{{ $licensePaidCount }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="license-table-data">
      @include('license_expenses.table', ['data' => $licenseExpenseData])
    </div>
  </div>
@endif
