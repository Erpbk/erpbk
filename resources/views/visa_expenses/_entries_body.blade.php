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
@endphp

<div class="content">
  @include('flash::message')

  @if(isset($siblingAccounts) && $siblingAccounts->count() > 0)
  <div class="alert alert-light border mb-3">
    <span class="text-muted me-2">Other renewal accounts for this {{ !empty($account->employee_id) ? 'employee' : 'rider' }}:</span>
    @foreach($siblingAccounts as $sibling)
    <a href="{{ route('VisaExpense.generatentries', $sibling->id) }}" class="btn btn-sm btn-outline-secondary me-1 mb-1">
      {{ $sibling->renewalCategory->name ?? 'Account #' . $sibling->id }}
    </a>
    @endforeach
  </div>
  @endif

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Visa Expense - {{ $account->name }} <span class="text-muted">({{ $activeRenewalCategory->name ?? 'New Visa' }})</span></h3>
      @can('visaexpense_create')
      <a class="btn btn-primary action-btn show-modal"
        href="javascript:void(0);"
        data-action="{{ route('VisaExpense.create', ['id' => $account->id]) }}"
        data-size="lg"
        data-title="New expense entry — {{ $activeRenewalCategory->name }}">
        Add New Expense
      </a>
      @endcan
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $totalUnpaid, 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $totalPaid, 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Expenses</div>
        <div class="value">{{ $unpaidCount }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Expenses</div>
        <div class="value">{{ $paidCount }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('visa_expenses.table', ['data' => $data])
    </div>
  </div>

  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Visa Installments</h3>
      <div class="d-flex flex-wrap gap-2">
        @can('visa_expense_create')
        <a class="btn btn-sm btn-success action-btn show-modal"
          href="javascript:void(0);"
          data-action="{{ route('Installments.createInstallmentPlanForm', $account->id) }}"
          data-size="lg"
          data-title="Create Installment Entry">
          <i class="fa fa-plus"></i> Installment Plan
        </a>
        @endcan
        @if(isset($installmentData) && $installmentData->count() > 0)
        <a href="javascript:void(0);"
          class="btn btn-sm btn-info action-btn show-modal"
          data-action="{{ route('Installments.generateInstallmentInvoice', ['riderId' => $account->id]) }}"
          data-size="xl"
          data-title="Installment plan invoice — {{ $account->name ?? 'Person' }}">
          <i class="fa fa-file-invoice"></i> Invoice
        </a>
        @endif
      </div>
    </div>
    @php
      $installmentStats = $installmentStats ?? [
          'unpaid_amount' => 0,
          'paid_amount' => 0,
          'paid_count' => 0,
          'unpaid_count' => 0,
      ];
    @endphp
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $installmentStats['unpaid_amount'], 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $installmentStats['paid_amount'], 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Installments</div>
        <div class="value">{{ (int) $installmentStats['unpaid_count'] }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Installments</div>
        <div class="value">{{ (int) $installmentStats['paid_count'] }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0">
      @include('visa_expenses.installmentPlanTable', ['data' => $installmentData ?? collect(), 'account' => $account])
    </div>
  </div>
</div>
