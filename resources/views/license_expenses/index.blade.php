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
$totalUnpaid   = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->sum('amount');
$totalPaid     = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->sum('amount');
$unpaidCount   = (clone $categoryExpenseQuery)->where('payment_status', 'unpaid')->count();
$paidCount     = (clone $categoryExpenseQuery)->where('payment_status', 'paid')->count();

$showVisaSection      = !empty($showVisaExpenseSection);
$licInstallmentData   = $licInstallmentData   ?? collect();
$licInstallmentStats  = $licInstallmentStats  ?? ['unpaid_amount'=>0,'paid_amount'=>0,'paid_count'=>0,'unpaid_count'=>0];

// Determine active tab from query-string; default = license
$activeTab = request('tab', 'license');
if (!in_array($activeTab, ['license','visa'])) { $activeTab = 'license'; }
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
.tab-badge-license { background:#0d6efd; }
.tab-badge-visa    { background:#6f42c1; }

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

  {{-- ── Sibling accounts ──────────────────────────────────────── --}}
  @if(isset($siblingAccounts) && $siblingAccounts->count() > 0)
  <div class="alert alert-light border mb-3 py-2">
    <span class="text-muted me-2 small fw-semibold">Other license accounts:</span>
    @foreach($siblingAccounts as $sibling)
    <a href="{{ route('LicenseExpense.generatentries', $sibling->id) }}"
       class="btn btn-sm btn-outline-secondary me-1 mb-1">
      {{ $sibling->licenseCategory->name ?? 'Account #'.$sibling->id }}
    </a>
    @endforeach
  </div>
  @endif

  {{-- ── Tab navigation ──────────────────────────────────────── --}}
  <ul class="nav exp-tab-nav" id="expenseTabs" role="tablist">
    <li class="nav-item">
      <a class="nav-link {{ $activeTab==='license' ? 'active':'' }}"
         href="{{ request()->fullUrlWithQuery(['tab'=>'license']) }}"
         id="tab-license">
        <i class="fa fa-id-card me-1"></i>
        License Expense
        @if($unpaidCount > 0)
          <span class="badge rounded-pill tab-badge-license ms-1">{{ $unpaidCount }}</span>
        @endif
      </a>
    </li>
    @if($showVisaSection)
    <li class="nav-item">
      <a class="nav-link {{ $activeTab==='visa' ? 'active':'' }}"
         href="{{ request()->fullUrlWithQuery(['tab'=>'visa']) }}"
         id="tab-visa">
        <i class="fa fa-passport me-1"></i>
        Visa Expense
        @php $visaUnpaidCount = (int)($visaExpenseTotals['unpaid_count'] ?? 0); @endphp
        @if($visaUnpaidCount > 0)
          <span class="badge rounded-pill tab-badge-visa ms-1">{{ $visaUnpaidCount }}</span>
        @endif
      </a>
    </li>
    @endif
  </ul>

  {{-- ════════════════════════════════════════════════════════════
       LICENSE TAB
  ════════════════════════════════════════════════════════════ --}}
  <div id="pane-license" class="{{ $activeTab==='license' ? '' : 'd-none' }}">

    {{-- ── License Expenses card ── --}}
    <div class="card shadow-sm mb-4 border-0">
      <div class="section-header" style="background:linear-gradient(135deg,#0d6efd 0%,#1d8cf8 100%);">
        <div class="section-icon" style="background:rgba(255,255,255,.2);">
          <i class="fa fa-file-invoice-dollar"></i>
        </div>
        <div class="flex-grow-1">
          <h5 class="text-white">
            License Expense &mdash; {{ $account->name }}
            @if(!empty($activeLicenseCategory))
              <span class="section-badge text-white-50 fw-normal">({{ $activeLicenseCategory->name }})</span>
            @endif
          </h5>
        </div>
        @can('license_expense_create')
        <a class="btn btn-sm btn-light fw-semibold action-btn show-modal"
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
             data-action="{{ route('LicenseExpense.createInstallmentPlanForm', $account->id) }}"
             data-size="lg"
             data-title="Create License Installment Plan">
            <i class="fa fa-plus me-1"></i> Installment Plan
          </a>
          @endcan
          @if($licInstallmentData->count() > 0)
          <a class="btn btn-sm btn-outline-light fw-semibold action-btn show-modal"
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
          'data'                        => $licInstallmentData,
          'account'                     => $account,
          'installmentPayRoute'         => 'LicenseExpense.payInstallment',
          'installmentUpdateFieldRoute' => 'LicenseExpense.updateInstallmentField',
          'installmentDeleteRoute'      => 'LicenseExpense.deleteInstallment',
          'installmentPlanModel'        => \App\Models\license_installment_plan::class,
          'canEditInstallment'          => user_can('license_expense_edit'),
          'canDeleteInstallment'        => user_can('license_expense_delete'),
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
  'entityName'  => 'License Expense',
  'confirmText' => 'This will submit a delete request or move the license expense to the Recycle Bin.',
  'method'      => 'GET',
])
<script>
$(document).ready(function() {
  $('#payment_status').select2({ dropdownParent: $('#searchTopbody'), placeholder: "Filter By Payment Status", allowClear: true });
  $('#license_status').select2({ dropdownParent: $('#searchTopbody'), placeholder: "Filter By License Status", allowClear: true });
  $('#bike_id').select2({ dropdownParent: $('#searchTopbody'), placeholder: "Filter By Bike Plate", allowClear: true });

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
    method: 'POST', body: formData,
    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
  }).then(r => r.json()).then(data => {
    if (typeof Swal !== 'undefined') {
      Swal.fire({ icon: data.success ? 'success' : 'error',
        title: data.success ? 'Saved' : 'Error',
        text: data.message || (data.success ? 'Updated successfully' : 'Could not update row.'),
        timer: data.success ? 1200 : undefined, showConfirmButton: !data.success });
    }
  }).catch(() => {
    if (typeof Swal !== 'undefined') Swal.fire({ icon:'error', title:'Error', text:'Could not update row.' });
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
