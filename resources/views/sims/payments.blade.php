@extends('layouts.app')
@section('title', 'SIM Payments')
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
        <h5>Filter SIM Payments</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('sim.payments') }}" method="GET">
            <input type="hidden" name="quick_search" value="{{ request('quick_search') }}">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="date_from">Payment Date From</label>
                    <input type="date" name="date_from" id="date_from" class="form-control" value="{{ request('date_from') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="date_to">Payment Date To</label>
                    <input type="date" name="date_to" id="date_to" class="form-control" value="{{ request('date_to') }}">
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
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <button class="btn btn-primary openFilterSidebar" type="button"><i class="fa fa-search"></i> Filter Payments</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-list"></i> Total Payments</div>
                <div class="value" id="total_payments">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="fa fa-coins"></i> Total Amount</div>
                <div class="value" id="total_payment_amount">{{ \App\Helpers\Currency::format($stats['total_amount'] ?? 0) }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-calendar"></i> This Month</div>
                <div class="value" id="month_payments">{{ $stats['this_month'] ?? 0 }}</div>
            </div>
            <div class="total-card total-1">
                <div class="label"><i class="fa fa-wallet"></i> This Month Amount</div>
                <div class="value" id="month_payment_amount">{{ \App\Helpers\Currency::format($stats['this_month_amount'] ?? 0) }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('payments.table')
        </div>
    </div>
</div>
@endsection

@push('page-scripts')
<script type="text/javascript">
    $(document).ready(function() {
        $('#quickSearch').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const url = new URL(window.location);
                const searchValue = $(this).val().trim();
                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }
        });

        $('#filterForm').on('submit', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });
    });
</script>
@endpush
