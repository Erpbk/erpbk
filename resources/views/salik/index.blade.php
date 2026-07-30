@extends('layouts.app')
@section('title','Salik')
@push('third_party_stylesheets')
<style>
    #dataTableBuilder { margin-bottom: 0; min-width: 800px; width: 100%; }
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
                <h3>Salik Records</h3>
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
                            @can('rta_saliks_salik_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Salik" data-action="{{ route('salik.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Add New Salik</div>
                                    <div class="action-dropdown-item-desc">Add a new salik against a bike</div>
                                </div>
                            </a>
                            @endcan
                            @can('rta_saliks_payment_create')
                            <a class="action-dropdown-item" href="{{ route('salik.payment') }}">
                                <i class="ti ti-cash"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Salik Payment</div>
                                    <div class="action-dropdown-item-desc">Record payment against unpaid saliks</div>
                                </div>
                            </a>
                            @endcan
                            @if((user_can('rta_saliks_salik_create') || user_can('rta_saliks_salik_edit')) && (user_can('rta_saliks_payment_create') || user_can('rta_saliks_payment_edit')))
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);"
                               data-size="lg" data-title="Salik Top-Up"
                               data-action="{{ route('salik.topUp.create') }}">
                                <i class="ti ti-wallet"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Top-Up</div>
                                    <div class="action-dropdown-item-desc">Create a payment voucher to top up Salik wallet</div>
                                </div>
                            </a>
                            @endif
                            @can('rta_saliks_salik_create')
                            <a class="action-dropdown-item" href="{{ route('salik.import.form') }}">
                                <i class="ti ti-file-upload"></i>
                                <span>Import Saliks</span>
                            </a>
                            <a class="action-dropdown-item" href="{{ route('salik.missing.records') }}">
                                <i class="fas fa-exclamation-triangle"></i>
                                <span>Missing Salik Records</span>
                            </a>
                            @endcan
                            @can('rta_saliks_salik_delete')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Delete Monthly Saliks" data-action="{{ route('salik.deleteMonthlyForm') }}">
                                <i class="ti ti-trash"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Delete Monthly Saliks</div>
                                    <div class="action-dropdown-item-desc">Remove unpaid saliks for a billing month</div>
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
        <h5>Filter Saliks</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('salik.index') }}" method="GET">
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
                    <label for="status">Payment Status</label>
                    <select name="status" id="status" class="form-control">
                        <option value="">All</option>
                        <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
                        <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="transaction_id">Transaction ID</label>
                    <input type="text" name="transaction_id" id="transaction_id" class="form-control" value="{{ request('transaction_id') }}">
                </div>
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
                <div class="form-group col-md-12">
                    <label for="filter_trip_date">Trip Date</label>
                    <input type="date" name="trip_date" id="filter_trip_date" class="form-control" value="{{ request('trip_date') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="plate">Plate</label>
                    <input type="text" name="plate" id="plate" class="form-control" value="{{ request('plate') }}">
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
            <button class="btn btn-primary openFilterSidebar" id="openFilterSidebar"><i class="fa fa-search"></i> Filter Saliks</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i>Unpaid Saliks</div>
                <div class="value">{{ $unpaidCount ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="far fa-money-bill-alt"></i>Unpaid Amount</div>
                <div class="value">{{ \App\Helpers\Currency::format($unpaidAmount ?? 0, 2) }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fas fa-stamp"></i>Paid Saliks</div>
                <div class="value">{{ $paidCount ?? 0 }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-ticket"></i>Paid Amount</div>
                <div class="value">{{ \App\Helpers\Currency::format($paidAmount ?? 0, 2) }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('salik.table', ['data' => $data])
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
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                    success: function() { location.reload(); },
                    error: function(xhr) {
                        Swal.fire('Error!', xhr.responseJSON?.message || 'Delete failed', 'error');
                    }
                });
            }
        });
    }
    $(document).ready(function() {
        $('#rider_id, #branch_id, #status').select2({ dropdownParent: $('#searchTopbody'), allowClear: true });
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
