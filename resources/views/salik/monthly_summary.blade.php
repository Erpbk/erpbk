@extends('layouts.app')

@section('title','Monthly Salik Invoices')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 240px);
        overflow-y: auto;
        overflow-x: auto;
        position: relative;
    }
    .total-card {
        flex: 1 1 calc(16.66% - 8px) !important;
    }
</style>
@endpush
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Monthly Salik Summary</h3>
            </div>
            <div class="col-sm-6">
                @include('salik.partials.actions_dropdown')
            </div>
        </div>
    </div>
</section>

@include('salik.partials.nav_tabs')

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Summary</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('salik.summary') }}" method="GET">
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
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div></div>
            <button class="btn btn-primary openFilterSidebar" id="openFilterSidebar"><i class="fa fa-search"></i> Filter Summary</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-2">
                <div class="label"><i class="ti ti-receipt"></i>Total Invoices</div>
                <div class="value">{{ $totalInvoices ?? 0 }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-ticket"></i>Salik</div>
                <div class="value">{{ $totalSaliks ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="far fa-money-bill-alt"></i>Total Amount</div>
                <div class="value">{{ \App\Helpers\Currency::format($totalAmount ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-1">
                <div class="label"><i class="fa fa-road"></i>Salik Amount</div>
                <div class="value">{{ \App\Helpers\Currency::format($salikAmount ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-blue">
                <div class="label"><i class="fas fa-percentage"></i>Admin Charges</div>
                <div class="value">{{ \App\Helpers\Currency::format($adminCharges ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fas fa-stamp"></i>VAT</div>
                <div class="value">{{ \App\Helpers\Currency::format($vat ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            <table class="table dataTable" id="invoiceSummaryTable">
                <thead class="text-center">
                    <tr>
                        <th>Invoice #</th>
                        <th>Charged To</th>
                        <th>Billing Month</th>
                        <th>Trip Count</th>
                        <th>Toll Amount ({{ \App\Helpers\Currency::code() }})</th>
                        <th>Admin Charges ({{ \App\Helpers\Currency::code() }})</th>
                        <th>VAT ({{ \App\Helpers\Currency::code() }})</th>
                        <th>Total ({{ \App\Helpers\Currency::code() }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($summaries as $invoice)
                    @php
                        $billingMonth = \Carbon\Carbon::parse($invoice->billing_month)->format('Y-m');
                        $invoiceRoute = $invoice->rider_id
                            ? route('salik.rider_monthly_summary', [$invoice->rider_id, $billingMonth])
                            : route('salik.company_monthly_summary', [$invoice->rental_company_id, $billingMonth]);
                    @endphp
                    <tr class="text-center">
                        <td>
                            <a href="javascript:void(0);" data-action="{{ $invoiceRoute }}" class="show-modal-right" data-size="xl" data-title="Salik Invoice for {{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}">
                                {{ $invoice->inv_id }}
                            </a>
                        </td>
                        <td>
                            @if($invoice->rider)
                            <a href="{{ route('rider.ledger', $invoice->rider->id) }}" target="_blank">{{ $invoice->rider->name }}</a>
                            @elseif($invoice->rentalCompany)
                            <a href="{{ route('bikeRentCompanies.files', $invoice->rentalCompany->id) }}" target="_blank">{{ $invoice->rentalCompany->name }}</a>
                            @else
                            N/A
                            @endif
                        </td>
                        <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('F Y') }}</td>
                        <td><span class="badge bg-info">{{ $invoice->transaction_count }}</span></td>
                        <td class="num">{{ number_format($invoice->total_amount, 2) }}</td>
                        <td class="num">{{ number_format($invoice->total_admin_charges, 2) }}</td>
                        <td class="num">{{ number_format($invoice->total_vat, 2) }}</td>
                        <td class="num"><strong>{{ number_format($invoice->total_grand, 2) }}</strong></td>
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
                        <th class="text-center">{{ number_format($totalSaliks ?? 0) }}</th>
                        <th class="num">{{ number_format($salikAmount ?? 0, 2) }}</th>
                        <th class="num">{{ number_format($adminCharges ?? 0, 2) }}</th>
                        <th class="num">{{ number_format($vat ?? 0, 2) }}</th>
                        <th class="num">{{ number_format($totalAmount ?? 0, 2) }}</th>
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
