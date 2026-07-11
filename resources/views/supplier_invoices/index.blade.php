@extends('layouts.app')

@section('title','Supplier Invoices')
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
<section class="content-header">
    <div class="">
        <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6 text-right">
                @can('suppliers_invoices_create')
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add New</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Invoice" data-action="{{ route('supplier_invoices.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Invoice</div>
                                    <div class="action-dropdown-item-desc">Add a new Spplier Invoice</div>
                                </div>
                            </a>
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Invoice" data-action="{{ route('supplier_invoices.import') }}">
                                <i class="ti ti-arrow-up"></i>
                                <div class="action-dropdown-item-text">Import Invoice</div>
                            </a>
                        </div>
                    </div>
                </div>
                @endcan
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
        <form id="filterForm" action="{{ route('supplier_invoices.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="company_name">Filter by Supplier</label>
                    <select class="form-control" id="supplier_id" name="supplier_id">
                        @php
                        $customers = \App\Models\Supplier::all();
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
                    <label for="garage_id">Garage (internal)</label>
                    <select class="form-control" id="garage_id" name="garage_id">
                        <option value="">All</option>
                        @foreach(\App\Models\Garages::query()->where('status', 1)->where('garage_type', 'internal')->orderBy('name')->get() as $g)
                        <option value="{{ $g->id }}" {{ (string) request('garage_id') === (string) $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="inv_id">Invoice ID</label>
                    <input type="text" name="inv_id" class="form-control" placeholder="Filter By Invoice ID" value="{{ request('inv_id') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="inv_date_from">Invoice Date From</label>
                    <input type="date" name="inv_date_from" class="form-control" placeholder="Filter By Invoice Date From" value="{{ request('inv_date_from') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="inv_date_to">Invoice Date To</label>
                    <input type="date" name="inv_date_to" class="form-control" placeholder="Filter By Invoice Date To" value="{{ request('inv_date_to') }}">
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
            @include('supplier_invoices.table')
        </div>
    </div>
</div>
@endsection

@section('page-script')

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'GET',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire(
                            'Deleted!',
                            'Invoice has been deleted.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        Swal.fire(
                            'Error!',
                            'Failed to delete Invoice. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
                            'error'
                        );
                    }
                });
            }
        })
    }
    $(document).ready(function() {
        $('#supplier_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Supplier",
            allowClear: true
        });
        $('#garage_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Garage",
            allowClear: true
        });
    });
</script>
@endsection