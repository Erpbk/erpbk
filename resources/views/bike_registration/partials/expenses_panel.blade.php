{{-- Bike registration expenses: totals + table (used full-page and inside AJAX modal) --}}
@php
$totalUnpaid = $expenseTotals['totalUnpaid'] ?? 0;
$totalPaid = $expenseTotals['totalPaid'] ?? 0;
$unpaidCount = $expenseTotals['unpaidCount'] ?? 0;
$paidCount = $expenseTotals['paidCount'] ?? 0;
$inModal = !empty($embeddedInModal);
@endphp

<div class="card mb-3 br-expenses-panel-card">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Bike Registration — {{ $account->name }}</h3>
            @if($inModal)
            <a href="{{ route('BikeRegistration.generatentries', $account->id) }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary">
                <i class="fa fa-external-link-alt me-1"></i> Open full page
            </a>
            @endif
        </div>
        @can('bike_registration_create')
        <a class="btn btn-primary action-btn show-modal"
            href="javascript:void(0);" data-action="{{ route('BikeRegistration.create' , $account->id) }}" data-size="lg" data-title="New registration expense">
            Add New Expense
        </a>
        @endcan
    </div>
    <div class="totals-cards pt-3 px-3">
        <div class="total-card total-red">
            <div class="label">Total Unpaid Amount</div>
            <div class="value" id="br-total-unpaid">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $totalUnpaid, 2) }}</div>
        </div>
        <div class="total-card total-green">
            <div class="label">Total Paid Amount</div>
            <div class="value" id="br-total-paid">{{ \App\Helpers\Currency::symbol() }} {{ number_format((float) $totalPaid, 2) }}</div>
        </div>
        <div class="total-card total-red">
            <div class="label">Unpaid Expenses</div>
            <div class="value" id="br-count-unpaid">{{ $unpaidCount }}</div>
        </div>
        <div class="total-card total-green">
            <div class="label">Paid Expenses</div>
            <div class="value" id="br-count-paid">{{ $paidCount }}</div>
        </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="table-data">
        @include('bike_registration.table', ['data' => $data])
    </div>
</div>
