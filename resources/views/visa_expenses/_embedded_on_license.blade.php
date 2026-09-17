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
$visaCategoryName = $activeRenewalCategory->name ?? 'Visa Expense';
@endphp

@if($showVisaSection && $visaAccount)
{{-- Category switching is handled by the dynamic top tabs; no inline Switch buttons. --}}

<div class="card exp-card mb-4">
  <div class="section-header">
    <div class="section-icon">
      <i class="fa fa-passport"></i>
    </div>
    <div class="flex-grow-1">
      <h5>{{ $visaCategoryName }}</h5>
    </div>
    @can('visaexpense_create')
    <a class="btn btn-sm btn-exp-primary action-btn show-modal"
      href="javascript:void(0);"
      data-action="{{ route('VisaExpense.create', ['id' => $visaAccount->id]) }}"
      data-size="lg"
      data-title="New expense entry — {{ $visaCategoryName }}">
      <i class="fa fa-plus me-1"></i> Add New Expense
    </a>
    @endcan
  </div>
  <div class="stat-strip">
    <div class="stat-pill danger">
      <div class="sp-icon"><i class="fa fa-arrow-down"></i></div>
      <div class="sp-body">
        <span class="sp-label">Unpaid Amount</span>
        <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($visaTotalUnpaid, 2) }}</span>
      </div>
    </div>
    <div class="stat-pill success">
      <div class="sp-icon"><i class="fa fa-check"></i></div>
      <div class="sp-body">
        <span class="sp-label">Paid Amount</span>
        <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format($visaTotalPaid, 2) }}</span>
      </div>
    </div>
    <div class="stat-pill danger">
      <div class="sp-icon"><i class="fa fa-exclamation"></i></div>
      <div class="sp-body">
        <span class="sp-label">Unpaid Expenses</span>
        <span class="sp-value">{{ $visaUnpaidCount }}</span>
      </div>
    </div>
    <div class="stat-pill success">
      <div class="sp-icon"><i class="fa fa-check"></i></div>
      <div class="sp-body">
        <span class="sp-label">Paid Expenses</span>
        <span class="sp-value">{{ $visaPaidCount }}</span>
      </div>
    </div>
  </div>
  <div class="card-body table-responsive px-2 py-0" id="visa-table-data">
    @include('visa_expenses.table', ['data' => $visaExpenseData, 'account' => $visaAccount])
  </div>
</div>

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
        data-action="{{ route('Installments.createInstallmentPlanForm', $visaAccount->id) }}"
        data-size="lg"
        data-title="Create Installment Entry">
        <i class="fa fa-plus me-1"></i> Installment Plan
      </a>
      @endcan
      @if($visaInstallmentData->count() > 0)
      <a href="javascript:void(0);"
        class="btn btn-sm btn-exp-ghost action-btn show-modal"
        data-action="{{ route('Installments.generateInstallmentInvoice', ['riderId' => $visaAccount->id]) }}"
        data-size="xl"
        data-title="Installment plan invoice — {{ $visaAccount->name ?? 'Person' }}">
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
        <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $visaInstallmentStats['unpaid_amount'], 2) }}</span>
      </div>
    </div>
    <div class="stat-pill success">
      <div class="sp-icon"><i class="fa fa-check"></i></div>
      <div class="sp-body">
        <span class="sp-label">Paid Amount</span>
        <span class="sp-value">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $visaInstallmentStats['paid_amount'], 2) }}</span>
      </div>
    </div>
    <div class="stat-pill danger">
      <div class="sp-icon"><i class="fa fa-exclamation"></i></div>
      <div class="sp-body">
        <span class="sp-label">Unpaid Installments</span>
        <span class="sp-value">{{ (int) $visaInstallmentStats['unpaid_count'] }}</span>
      </div>
    </div>
    <div class="stat-pill success">
      <div class="sp-icon"><i class="fa fa-check"></i></div>
      <div class="sp-body">
        <span class="sp-label">Paid Installments</span>
        <span class="sp-value">{{ (int) $visaInstallmentStats['paid_count'] }}</span>
      </div>
    </div>
  </div>
  <div class="card-body table-responsive px-2 py-0">
    @include('visa_expenses.installmentPlanTable', [
    'data' => $visaInstallmentData,
    'account' => $visaAccount,
    'installmentPayRoute' => 'Installments.payInstallment',
    'installmentUpdateFieldRoute' => 'Installments.updateInstallmentField',
    'installmentDeleteRoute' => 'Installments.deleteInstallment',
    'installmentPaymentReceivingRoute' => 'Installments.paymentReceivingModal',
    'installmentPlanModel' => \App\Models\visa_installment_plan::class,
    ])
  </div>
</div>
@endif
