@extends('layouts.app')

@section('title','Customer Invoices')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 210px);
    }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@can('customers_invoices_view')
<section class="content-header">
    <div class="">
        <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add New</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('customers_invoices_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Invoice" data-action="{{ route('customer_invoices.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Customer Invoice</div>
                                    <div class="action-dropdown-item-desc">Add a new Customer Invoice</div>
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

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1100">
    <div class="filter-header">
        <h5>Filter Invoices</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('customer_invoices.index') }}" method="GET">
            <div class="row">
                @if(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-12">
                    <label for="branch_id">Filter by Branch</label>
                    <select class="form-control " id="branch_id" name="branch_id">
                        @foreach(auth()->user()->branchDropdown() as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group col-md-12">
                    <label for="company_name">Filter by Customer</label>
                    <select class="form-control" id="name" name="customer_id">
                        @php
                        $customers = \App\Models\Customers::all();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($customers as $company)
                        <option value="{{ $company->id }}" {{ request('name') == $company->name ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="billing month">Filter by Billing Moth</label>
                    <input type="month" name="billing_month" class="form-control">
                </div>
                <div class="form-group col-md-12">
                    <label for="refrence">Filter by Reference</label>
                    <input type="text" name="reference" class="form-control">
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>
<div class="content mt-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter</button>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('customer_invoices.table')
        </div>
    </div>

</div>
@else
<div class="content">
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-body">
            <h5>You are not authorized to access this page</h5>
        </div>
    </div>
</div>
@endcan
@endsection
@section('page-script')
@endsection