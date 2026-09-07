@php
$showVisaSection = !empty($showVisaExpenseSection);
$visaAccount = $visaAccount ?? null;
$visaExpenseData = $visaExpenseData ?? collect();
$activeRenewalCategory = $activeRenewalCategory ?? null;
$visaSiblingAccounts = $visaSiblingAccounts ?? collect();
$visaTotalUnpaid = (float) ($visaExpenseTotals['unpaid_amount'] ?? 0);
$visaTotalPaid = (float) ($visaExpenseTotals['paid_amount'] ?? 0);
$visaUnpaidCount = (int) ($visaExpenseTotals['unpaid_count'] ?? 0);
$visaPaidCount = (int) ($visaExpenseTotals['paid_count'] ?? 0);
$visaInstallmentData = $visaInstallmentData ?? collect();
$visaInstallmentStats = $visaInstallmentStats ?? [
    'unpaid_amount' => 0,
    'paid_amount' => 0,
    'paid_count' => 0,
    'unpaid_count' => 0,
];
@endphp

@if($showVisaSection && $visaAccount)
  @if($visaSiblingAccounts->count() > 0)
  <div class="alert alert-light border mb-3">
    <span class="text-muted me-2">Other renewal accounts for this rider:</span>
    @foreach($visaSiblingAccounts as $sibling)
    <a href="{{ request()->fullUrlWithQuery(['visa_account_id' => $sibling->id]) }}" class="btn btn-sm btn-outline-secondary me-1 mb-1">
      {{ $sibling->renewalCategory->name ?? 'Account #' . $sibling->id }}
    </a>
    @endforeach
  </div>
  @endif

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">
        Visa Expense - {{ $visaAccount->name }}
        <span class="text-muted">({{ $activeRenewalCategory->name ?? 'New Visa' }})</span>
      </h3>
      @can('visaexpense_create')
      <a class="btn btn-primary action-btn show-modal"
        href="javascript:void(0);"
        data-action="{{ route('VisaExpense.create', ['id' => $visaAccount->id]) }}"
        data-size="lg"
        data-title="New expense entry — {{ $activeRenewalCategory->name ?? 'Visa' }}">
        Add New Expense
      </a>
      @endcan
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($visaTotalUnpaid, 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($visaTotalPaid, 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Expenses</div>
        <div class="value">{{ $visaUnpaidCount }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Expenses</div>
        <div class="value">{{ $visaPaidCount }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="visa-table-data">
      @include('visa_expenses.table', ['data' => $visaExpenseData, 'account' => $visaAccount])
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h3 class="mb-0">Visa Installments</h3>
      <div class="d-flex flex-wrap gap-2">
        @can('visa_expense_create')
        <a class="btn btn-sm btn-success action-btn show-modal"
          href="javascript:void(0);"
          data-action="{{ route('Installments.createInstallmentPlanForm', $visaAccount->id) }}"
          data-size="lg"
          data-title="Create Installment Entry">
          <i class="fa fa-plus"></i> Installment Plan
        </a>
        @endcan
        @if($visaInstallmentData->count() > 0)
        <a href="javascript:void(0);"
          class="btn btn-sm btn-info action-btn show-modal"
          data-action="{{ route('Installments.generateInstallmentInvoice', ['riderId' => $visaAccount->id]) }}"
          data-size="xl"
          data-title="Installment plan invoice — {{ $visaAccount->name ?? 'Person' }}">
          <i class="fa fa-file-invoice"></i> Invoice
        </a>
        @endif
      </div>
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Total Unpaid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $visaInstallmentStats['unpaid_amount'], 2) }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Total Paid Amount</div>
        <div class="value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $visaInstallmentStats['paid_amount'], 2) }}</div>
      </div>
      <div class="total-card total-red">
        <div class="label">Unpaid Installments</div>
        <div class="value">{{ (int) $visaInstallmentStats['unpaid_count'] }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Paid Installments</div>
        <div class="value">{{ (int) $visaInstallmentStats['paid_count'] }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0">
      @include('visa_expenses.installmentPlanTable', [
        'data' => $visaInstallmentData,
        'account' => $visaAccount,
        'installmentPayRoute' => 'Installments.payInstallment',
        'installmentUpdateFieldRoute' => 'Installments.updateInstallmentField',
        'installmentDeleteRoute' => 'Installments.deleteInstallment',
        'installmentPlanModel' => \App\Models\visa_installment_plan::class,
      ])
    </div>
  </div>
@endif
