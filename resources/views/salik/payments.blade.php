@extends('layouts.app')
@section('title','Salik Payments')
@push('third_party_stylesheets')
<style>
    #dataTableBuilder { margin-bottom: 0; min-width: 900px; width: 100%; }
    #dataTableBuilder td, #dataTableBuilder th { white-space: nowrap; padding: 8px 12px; vertical-align: middle; }
    #dataTableBuilder thead th {
        font-weight: bold; position: sticky; top: 0; z-index: 10;
        background-color: #f8f9fa; box-shadow: 0 2px 2px -1px rgba(0, 0, 0, 0.1);
    }
    .table-responsive { max-height: calc(100vh - 240px); overflow-y: auto; overflow-x: auto; position: relative; }
</style>
@endpush
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Salik Payment Records</h3>
            </div>
            <div class="col-sm-6">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Salik Actions</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('rta_saliks_payment_create')
                            <a class="action-dropdown-item" href="{{ route('salik.payment') }}">
                                <i class="ti ti-cash"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Salik Payment</div>
                                    <div class="action-dropdown-item-desc">Record payment against unpaid saliks</div>
                                </div>
                            </a>
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

@include('salik.partials.nav_tabs')

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Payments</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('salik.payments') }}" method="GET">
            <div class="row">
                @if(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-12">
                    <label for="branch_id">Filter by Branch</label>
                    <select class="form-control" id="branch_id" name="branch_id">
                        @foreach(auth()->user()->branchDropdown() as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="trans_date_from">Payment Date From</label>
                    <input type="date" name="trans_date_from" id="trans_date_from" class="form-control" value="{{ request('trans_date_from') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="trans_date_to">Payment Date To</label>
                    <input type="date" name="trans_date_to" id="trans_date_to" class="form-control" value="{{ request('trans_date_to') }}">
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
            <button class="btn btn-primary openFilterSidebar" id="openFilterSidebar"><i class="fa fa-search"></i> Filter Payments</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-2">
                <div class="label"><i class="ti ti-receipt"></i>Payment Vouchers</div>
                <div class="value">{{ $totalCount ?? 0 }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-ticket"></i>Saliks Paid</div>
                <div class="value">{{ $totalSaliks ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="far fa-money-bill-alt"></i>Total Paid Amount</div>
                <div class="value">{{ \App\Helpers\Currency::format($totalAmount ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('salik.payments_table', ['data' => $data, 'accounts' => $accounts])
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#branch_id').select2({ dropdownParent: $('#searchTopbody'), allowClear: true });
        $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
        });
        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });
    });
</script>
@endsection
