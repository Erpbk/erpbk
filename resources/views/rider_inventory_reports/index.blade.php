@extends('layouts.app')

@section('title', 'Rider Inventory Reports')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="mb-0">Rider Inventory Reports</h3>
            <a href="{{ route('RiderInventory.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left"></i> Back to Inventory
            </a>
        </div>
    </div>
</section>

<div class="content">
    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="inventoryReportTabs">
                <li class="nav-item"><a class="nav-link {{ $reportType === 'assigned' ? 'active' : '' }}" data-type="assigned" href="#">Assigned Inventory</a></li>
                <li class="nav-item"><a class="nav-link {{ $reportType === 'returned' ? 'active' : '' }}" data-type="returned" href="#">Returned Inventory</a></li>
                <li class="nav-item"><a class="nav-link {{ $reportType === 'lost' ? 'active' : '' }}" data-type="lost" href="#">Lost Inventory</a></li>
                <li class="nav-item"><a class="nav-link {{ $reportType === 'rider_history' ? 'active' : '' }}" data-type="rider_history" href="#">Rider-wise History</a></li>
                <li class="nav-item"><a class="nav-link {{ $reportType === 'loss_vouchers' ? 'active' : '' }}" data-type="loss_vouchers" href="#">Inventory Loss Vouchers</a></li>
            </ul>
        </div>
        <div class="card-body">
            <form id="reportFilterForm" class="row g-3 mb-3">
                <input type="hidden" name="type" id="report_type" value="{{ $reportType }}">
                <div class="col-md-3 rider-history-only assigned-filters returned-filters lost-filters" id="riderFilterCol">
                    <label class="form-label">Rider</label>
                    <select name="rider_id" class="form-control select2">
                        <option value="">All Riders</option>
                        @foreach(\App\Models\Riders::orderBy('name')->get(['id','name','rider_id']) as $r)
                        <option value="{{ $r->id }}">{{ $r->rider_id }} - {{ $r->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 assigned-filters returned-filters lost-filters">
                    <label class="form-label">Inventory Item</label>
                    <select name="inventory_item_id" class="form-control">
                        <option value="">All Items</option>
                        @foreach(\App\Models\RiderInventoryItem::orderBy('name')->get() as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date From</label>
                    <input type="date" name="date_from" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Date To</label>
                    <input type="date" name="date_to" class="form-control">
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">Load Report</button>
                </div>
            </form>
            <div id="reportTableWrapper"></div>
            <div id="reportPaginationLinks"></div>
        </div>
    </div>
</div>
@endsection

@push('page_scripts')
<script>
let currentReportType = @json($reportType);

function toggleReportFilters() {
    const isRiderHistory = currentReportType === 'rider_history';
    const isLossVouchers = currentReportType === 'loss_vouchers';
    document.querySelectorAll('.assigned-filters, .returned-filters, .lost-filters').forEach(function (el) {
        el.style.display = (isRiderHistory || isLossVouchers) ? 'none' : '';
    });
    const riderCol = document.getElementById('riderFilterCol');
    if (riderCol) {
        riderCol.style.display = isLossVouchers ? 'none' : '';
        const label = riderCol.querySelector('label');
        if (label) label.textContent = isRiderHistory ? 'Rider (required)' : 'Rider';
    }
}

function loadReport(page) {
    const form = document.getElementById('reportFilterForm');
    const data = new FormData(form);
    if (page) data.set('page', page);
    data.set('type', currentReportType);

    fetch(@json(route('RiderInventory.reports.data')) + '?' + new URLSearchParams(data), {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(res => {
        document.getElementById('reportTableWrapper').innerHTML = res.tableData;
        document.getElementById('reportPaginationLinks').innerHTML = res.paginationLinks || '';
    });
}

document.querySelectorAll('#inventoryReportTabs .nav-link').forEach(function (tab) {
    tab.addEventListener('click', function (e) {
        e.preventDefault();
        document.querySelectorAll('#inventoryReportTabs .nav-link').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        currentReportType = this.getAttribute('data-type');
        document.getElementById('report_type').value = currentReportType;
        toggleReportFilters();
        loadReport(1);
    });
});

document.getElementById('reportFilterForm').addEventListener('submit', function (e) {
    e.preventDefault();
    if (currentReportType === 'rider_history' && !this.rider_id.value) {
        alert('Please select a rider for rider-wise history.');
        return;
    }
    loadReport(1);
});

document.addEventListener('click', function (e) {
    const link = e.target.closest('#reportPaginationLinks a.page-link');
    if (!link) return;
    e.preventDefault();
    const url = new URL(link.href);
    loadReport(url.searchParams.get('page'));
});

toggleReportFilters();
loadReport(1);
</script>
@endpush
