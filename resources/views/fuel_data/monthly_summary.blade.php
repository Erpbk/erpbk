@extends('layouts.app')

@section('title','Monthly Fuel Summary')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 240px);
        overflow-y: auto;
        overflow-x: auto;
        position: relative;
    }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div>
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-12">
                @include('fuel_cards.partials.actions_dropdown')
            </div>
        </div>
    </div>
</section>
@include('fuel_cards.partials.nav_tabs')

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Summary</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('fuel_data.summary') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="rider_id">Rider</label>
                    <select name="rider_id" id="rider_id" class="form-control">
                        <option value="">All Riders</option>
                        @foreach(company_table('riders')->select('id', 'rider_id', 'name')->get() as $rider)
                        <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>{{ $rider->rider_id }} - {{ $rider->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="filterOverlay" class="filter-overlay"></div>

<div class="content">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            <button class="btn btn-primary openFilterSidebar" id="openFilterSidebar"><i class="fa fa-search"></i> Filter Summary</button>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            <table class="table dataTable" id="invoiceSummaryTable">
                <thead class="text-center">
                    <tr role="row">
                        <th>Invoice #</th>
                        <th>Rider Name</th>
                        <th>Billing Month</th>
                        <th>Transaction Count</th>
                        <th>Total Quantity (L)</th>
                        <th>Subtotal ({{ \App\Helpers\Currency::code() }})</th>
                        <th>VAT ({{ \App\Helpers\Currency::code() }})</th>
                        <th>Total Amount ({{ \App\Helpers\Currency::code() }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $index => $invoice)
                    <tr class="text-center">
                        <td>
                            <a href="javascript:void(0);" data-action="{{ route('fuel_data.rider_monthly_summary', [$invoice->rider_id, $invoice->billing_month]) }}" class="show-modal-right" data-size="xl" data-title="Fuel Invoice for {{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}">
                                {{ $invoice->inv_id }}
                            </a>
                        </td>
                        <td><a href="{{ route('rider.ledger', $invoice->rider->id) }}" target="_blank">{{ $invoice->rider->name ?? 'N/A' }}</a></td>
                        <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('F Y') }}</td>
                        <td>
                            <span class="badge bg-info">
                                {{ $invoice->transaction_count }}
                            </span>
                        </td>
                        <td class="num">{{ number_format($invoice->total_qty, 2) }}</td>
                        <td class="num">{{ number_format($invoice->total_subtotal, 2) }}</td>
                        <td class="num">{{ number_format($invoice->total_vat, 2) }}</td>
                        <td class="num">
                            <strong>{{ number_format($invoice->total_amount, 2) }}</strong>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <h3>No invoice records found</h3>
                            <p class="text-muted">Try adjusting your filters</p>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-light">
                        <th colspan="3" class="text-end">Total:</th>
                        <th class="text-center">{{ number_format($summaries->sum('transaction_count')) }}</th>
                        <th class="num">{{ number_format($summaries->sum('total_qty'), 2) }}</th>
                        <th class="num">{{ number_format($summaries->sum('total_subtotal'), 2) }}</th>
                        <th class="num">{{ number_format($summaries->sum('total_vat'), 2) }}</th>
                        <th class="num">{{ number_format($summaries->sum('total_amount'), 2) }}</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#rider_id').select2({ dropdownParent: $('#searchTopbody'), allowClear: true });
        $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
        });
        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });

        $('#invoiceSummaryTable').DataTable({
            responsive: true,
            paging: true,
            pageLength: 50,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
            searching: false,
            ordering: false,
            info: true,
            dom: '<"row"<"col-sm-12"tr>>' +
                '<"row mt-2"<"col-sm-12 col-md-6"i><"col-sm-12 col-md-6 d-flex justify-content-end"p>>',
        });
    });
</script>
@endsection
