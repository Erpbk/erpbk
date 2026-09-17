@extends('riders.view')
@section('title','License Expenses')
@section('page_content')
@php
$accountId = $account->rider_id;
$categoryId = (int) ($activeLicenseCategory->id ?? 0);
$categoryExpenseQuery = \App\Support\LicenseCategoryService::expensesForAccountQuery(
(int) $account->id,
$accountId ? (int) $accountId : null,
$categoryId
);
$totalUnpaid = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->sum('amount');
$totalPaid = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->sum('amount');
$unpaidCount = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->count();
$paidCount = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->count();

$licenseCategoryAccounts = collect($licenseCategoryAccounts ?? (
    isset($siblingAccounts)
        ? collect([$account])->merge($siblingAccounts)->unique('id')->sortBy('id')->values()
        : collect([$account])
));
$visaCategoryAccounts = collect($visaCategoryAccounts ?? (
    isset($visaAccount)
        ? collect([$visaAccount])->merge($visaSiblingAccounts ?? [])->unique('id')->sortBy('id')->values()
        : collect()
));
$showVisaSection = !empty($showVisaExpenseSection) && $visaCategoryAccounts->isNotEmpty();
$licInstallmentData = $licInstallmentData ?? collect();
$licInstallmentStats = $licInstallmentStats ?? ['unpaid_amount'=>0,'paid_amount'=>0,'paid_count'=>0,'unpaid_count'=>0];

$requestedTab = request('tab', 'license');
$activeTab = ($requestedTab === 'visa' && $showVisaSection) ? 'visa' : 'license';
// When a visa category was picked via visa_account_id, keep visa tab active
if (request()->filled('visa_account_id') && $showVisaSection) {
    $activeTab = 'visa';
}
$activeLicenseName = $activeLicenseCategory->name ?? 'License';
@endphp

@include('partials.expense_panel_styles')

<div class="content exp-panel">
  @include('flash::message')

  {{-- ── Dynamic category tabs (only accounts that exist for this rider) ── --}}
  <div class="d-flex align-items-center exp-tab-nav" id="expenseTabs" role="tablist">
    <ul class="nav mb-0 border-0 flex-grow-1 flex-wrap" style="border-bottom:none;">
      @foreach($licenseCategoryAccounts as $licAccount)
      @php
        $isActiveLic = $activeTab === 'license' && (int) $licAccount->id === (int) $account->id;
        $licLabel = $licAccount->licenseCategory->name ?? ('Account #'.$licAccount->id);
        $licUnpaid = \App\Support\LicenseCategoryService::unpaidCountForAccount($licAccount);
        $licUrl = route('LicenseExpense.generatentries', ['id' => $licAccount->id, 'tab' => 'license']);
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

      @foreach($visaCategoryAccounts as $visaCat)
      @php
        $isActiveVisaCat = $activeTab === 'visa' && isset($visaAccount) && (int) $visaCat->id === (int) $visaAccount->id;
        $visaLabel = $visaCat->renewalCategory->name ?? ('Account #'.$visaCat->id);
        $visaCatUnpaid = \App\Support\VisaRenewalCategoryService::unpaidCountForAccount($visaCat);
        $visaUrl = request()->fullUrlWithQuery([
          'tab' => 'visa',
          'visa_account_id' => $visaCat->id,
        ]);
      @endphp
      <li class="nav-item">
        <a class="nav-link {{ $isActiveVisaCat ? 'active' : '' }}"
          href="{{ $visaUrl }}"
          id="tab-visa-cat-{{ $visaCat->id }}">
          <i class="fa fa-passport me-1"></i>
          {{ $visaLabel }}
          @if($visaCatUnpaid > 0)
          <span class="badge rounded-pill ms-1">{{ $visaCatUnpaid }}</span>
          @endif
        </a>
      </li>
      @endforeach
    </ul>
  </div>

  {{-- ════════════════════════════════════════════════════════════
       LICENSE TAB
  ════════════════════════════════════════════════════════════ --}}
  <div id="pane-license" class="{{ $activeTab==='license' ? '' : 'd-none' }}">

    {{-- ── License Expenses card ── --}}
    <div class="card exp-card mb-4">
      <div class="section-header">
        <div class="section-icon">
          <i class="fa fa-file-invoice-dollar"></i>
        </div>
        <div class="flex-grow-1">
          <h5>
            {{ $activeLicenseCategory->name ?? 'License Expense' }}
          </h5>
        </div>
        @can('license_expense_create')
        <a class="btn btn-sm btn-exp-primary action-btn show-modal"
          href="javascript:void(0);"
          data-action="{{ route('LicenseExpense.create', $account->id) }}"
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
        @include('license_expenses.table', ['data' => $data])
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
            data-action="{{ route('LicenseExpense.createInstallmentPlanForm', $account->id) }}"
            data-size="lg"
            data-title="Create License Installment Plan">
            <i class="fa fa-plus me-1"></i> Installment Plan
          </a>
          @endcan
          @if($licInstallmentData->count() > 0)
          <a class="btn btn-sm btn-exp-ghost action-btn show-modal"
            href="javascript:void(0);"
            data-action="{{ route('LicenseExpense.generateInstallmentInvoice', ['riderId' => $account->id]) }}"
            data-size="xl"
            data-title="License Installment Invoice — {{ $account->name }}">
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
        'account' => $account,
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

  </div>{{-- /pane-license --}}

  {{-- ════════════════════════════════════════════════════════════
       VISA TAB
  ════════════════════════════════════════════════════════════ --}}
  @if($showVisaSection)
  <div id="pane-visa" class="{{ $activeTab==='visa' ? '' : 'd-none' }}">
    @include('visa_expenses._embedded_on_license')
  </div>
  @endif

</div>{{-- /content --}}

@endsection
@section('page-script')

@include('delete_requests._confirm_delete_script', [
'entityName' => 'License Expense',
'confirmText' => 'This will submit a delete request or move the license expense to the Recycle Bin.',
'method' => 'GET',
])
<script>
  $(document).ready(function() {
    $('#payment_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Payment Status",
      allowClear: true
    });
    $('#license_status').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By License Status",
      allowClear: true
    });
    $('#bike_id').select2({
      dropdownParent: $('#searchTopbody'),
      placeholder: "Filter By Bike Plate",
      allowClear: true
    });

    // ── Ajax filter ────────────────────────────────────────────
    $('#filterForm').on('submit', function(e) {
      e.preventDefault();
      $('#loading-overlay').show();
      $('#searchModal').modal('hide');
      const loaderStart = Date.now();
      const filteredFields = $(this).serializeArray().filter(f => f.name !== '_token' && f.value.trim() !== '');
      const formData = $.param(filteredFields);
      $.ajax({
        url: "{{ route('LicenseExpense.generatentries', ['id' => $account->id]) }}",
        type: 'GET',
        data: formData,
        success: function(data) {
          $('#table-data').html(data.tableData);
          const newUrl = "{{ route('LicenseExpense.generatentries', ['id' => $account->id]) }}" + (formData ? '?' + formData : '');
          history.pushState(null, '', newUrl);
          const remaining = 1000 - (Date.now() - loaderStart);
          setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
        },
        error: function() {
          const remaining = 1000 - (Date.now() - loaderStart);
          setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
        }
      });
    });
  });

  // ── Client-side column sort ────────────────────────────────
  document.addEventListener('DOMContentLoaded', function() {
    const table = document.querySelector('#LicenseExpensesDataTable');
    if (!table) return;
    const headers = table.querySelectorAll('th.sorting');
    const tbody = table.querySelector('tbody');
    headers.forEach((header, colIndex) => {
      header.addEventListener('click', () => {
        const rows = Array.from(tbody.querySelectorAll('tr'));
        const isAsc = header.classList.contains('sorted-asc');
        headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));
        header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');
        rows.sort((a, b) => {
          let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
          let bText = b.children[colIndex]?.textContent.trim().toLowerCase();
          const aVal = isNaN(aText) ? aText : parseFloat(aText);
          const bVal = isNaN(bText) ? bText : parseFloat(bText);
          if (aVal < bVal) return isAsc ? 1 : -1;
          if (aVal > bVal) return isAsc ? -1 : 1;
          return 0;
        });
        rows.forEach(row => tbody.appendChild(row));
      });
    });
  });

  // ── Inline row update ──────────────────────────────────────
  document.addEventListener('click', function(e) {
    const btn = e.target.closest('.js-inline-update-btn');
    if (!btn) return;
    const row = btn.closest('tr[data-row-id]');
    if (!row) return;
    const formData = new FormData();
    formData.append('_token', '{{ csrf_token() }}');
    formData.append('id', btn.getAttribute('data-id'));
    formData.append('amount', row.querySelector('.js-inline-amount')?.value || '');
    formData.append('date', row.querySelector('.js-inline-date')?.value || '');
    formData.append('billing_month', row.querySelector('.js-inline-billing-month')?.value || '');
    fetch(btn.getAttribute('data-update-url'), {
      method: 'POST',
      body: formData,
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      }
    }).then(r => r.json()).then(data => {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: data.success ? 'success' : 'error',
          title: data.success ? 'Saved' : 'Error',
          text: data.message || (data.success ? 'Updated successfully' : 'Could not update row.'),
          timer: data.success ? 1200 : undefined,
          showConfirmButton: !data.success
        });
      }
    }).catch(() => {
      if (typeof Swal !== 'undefined') Swal.fire({
        icon: 'error',
        title: 'Error',
        text: 'Could not update row.'
      });
    });
  });

  // ── Delete visa expense ────────────────────────────────────
  document.addEventListener('click', function(e) {
    const link = e.target.closest('.js-delete-visa-expense');
    if (!link) return;
    const url = link.getAttribute('data-delete-url');
    if (url) confirmDelete(url);
  });
</script>
@endsection