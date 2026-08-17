@extends('layouts.app')

@section('title','SIM Invoices')
@push('third_party_stylesheets')
<style>
    .table-responsive { max-height: calc(100vh - 280px); }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

<section class="content-header">
    @include('flash::message')
    <div>
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-12">
                @include('sims.partials.actions_dropdown')
            </div>
        </div>
    </div>
</section>

@include('sims.partials.nav_tabs')

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter SIM Invoices</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('simInvoices.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="invoice_number">Invoice #</label>
                    <input type="text" name="invoice_number" class="form-control" value="{{ request('invoice_number') }}" placeholder="Filter by invoice number">
                </div>
                <div class="form-group col-md-12">
                    <label for="vendor_id">Company</label>
                    <select class="form-control" id="vendor_id" name="vendor_id">
                        <option value="">All</option>
                        @foreach($companies as $company)
                        <option value="{{ $company->id }}" {{ (string) request('vendor_id') === (string) $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" class="form-control" value="{{ request('billing_month') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All</option>
                        <option value="0" {{ (string) request('status') === '0' ? 'selected' : '' }}>Unpaid</option>
                        <option value="3" {{ (string) request('status') === '3' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="1" {{ (string) request('status') === '1' ? 'selected' : '' }}>Paid</option>
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
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar" type="button"><i class="fa fa-search"></i> Filter Invoices</button>
        </div>
        <div class="totals-cards totals-cards-single-row">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-file-invoice"></i> Total Invoices</div>
                <div class="value" id="total_invoices">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i> Unpaid</div>
                <div class="value" id="unpaid_invoices">{{ $stats['unpaid'] ?? 0 }}</div>
            </div>
            <div class="total-card total-4">
                <div class="label"><i class="fa fa-adjust"></i> Partially Paid</div>
                <div class="value" id="partial_invoices">{{ $stats['partial'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i> Paid</div>
                <div class="value" id="paid_invoices">{{ $stats['paid'] ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="fa fa-coins"></i> Total Amount</div>
                <div class="value" id="total_amount">{{ \App\Helpers\Currency::format($stats['total_amount'] ?? 0) }}</div>
            </div>
            <div class="total-card total-1">
                <div class="label"><i class="fa fa-balance-scale"></i> Outstanding</div>
                <div class="value" id="outstanding_amount">{{ \App\Helpers\Currency::format($stats['outstanding'] ?? 0) }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('sim_invoices.table', ['data' => $data])
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#vendor_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Company",
            allowClear: true
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Status",
            allowClear: true
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            $('#loading-overlay').show();
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
            const loaderStartTime = Date.now();
            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && String(field.value).trim() !== '');
            let formData = $.param(filteredFields);
            $.ajax({
                url: "{{ route('simInvoices.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    if (data.stats) {
                        $('#total_invoices').text(data.stats.total ?? 0);
                        $('#unpaid_invoices').text(data.stats.unpaid ?? 0);
                        $('#partial_invoices').text(data.stats.partial ?? 0);
                        $('#paid_invoices').text(data.stats.paid ?? 0);
                        $('#total_amount').text(formatSimStatAmount(data.stats.total_amount));
                        $('#outstanding_amount').text(formatSimStatAmount(data.stats.outstanding));
                    }
                    let newUrl = "{{ route('simInvoices.index') }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 500 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                },
                error: function() {
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 500 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                }
            });
        });
    });

    function formatSimStatAmount(value) {
        const amount = Number(value || 0);
        return @json(\App\Helpers\Currency::symbol()) + ' ' + amount.toLocaleString(undefined, {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }
</script>
@endsection
