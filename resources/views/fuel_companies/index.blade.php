@extends('layouts.app')

@section('title','Fuel Companies')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

<section class="content-header">
    @include('flash::message')
    <div>
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-12">
                <div class="action-buttons d-flex justify-content-end">
                    <div class="action-dropdown-container">
                        <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                            <i class="ti ti-plus"></i>
                            <span>Add Fuel Company</span>
                            <i class="ti ti-chevron-down"></i>
                        </button>
                        <div class="action-dropdown-menu" id="addBikeDropdown">
                            @can('fuel_cards_companies_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);"
                               data-size="lg" data-title="Add New Fuel Company"
                               data-action="{{ route('fuelCompanies.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Add Fuel Company</div>
                                    <div class="action-dropdown-item-desc">Create a new fuel company</div>
                                </div>
                            </a>
                            @endcan
                            @can('cash_&_banks_payments_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);"
                               data-size="lg" data-title="Fuel Company Top-Up"
                               data-action="{{ route('fuelCompanies.topUp.create') }}">
                                <i class="ti ti-wallet"></i>
                                <div>
                                    <div class="action-dropdown-item-text">Top-Up</div>
                                    <div class="action-dropdown-item-desc">Create a payment voucher to top up a fuel company</div>
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

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Fuel Companies</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('fuelCompanies.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="name">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ request('name') }}" placeholder="Filter by name">
                </div>
                <div class="form-group col-md-12">
                    <label for="email">Email</label>
                    <input type="text" name="email" class="form-control" value="{{ request('email') }}" placeholder="Filter by email">
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="" selected>All</option>
                        <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                @if(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-12">
                    <label for="branch_id">Branch</label>
                    <select class="form-control" id="branch_id" name="branch_id">
                        @foreach(auth()->user()->branchDropdown() as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="content">
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar"><i class="fa fa-search"></i> Filter Companies</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-building"></i> Total Companies</div>
                <div class="value" id="total_companies">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i> Active</div>
                <div class="value" id="active_companies">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i> Inactive</div>
                <div class="value" id="inactive_companies">{{ $stats['inactive'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('fuel_companies.table', ['data' => $data])
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
            text: "This will move the fuel company to the Recycle Bin!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').show();
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        $('#loading-overlay').hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            html: response.message,
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        }).then(() => { location.reload(); });
                    },
                    error: function(xhr) {
                        $('#loading-overlay').hide();
                        let errorMessage = 'An error occurred while deleting.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'Error!', html: errorMessage });
                    }
                });
            }
        });
    }

    $(document).ready(function() {
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Status",
            allowClear: true
        });
        @if(auth()->user()->hasMultiplebranches())
        $('#branch_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Branch",
            allowClear: true
        });
        @endif

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            $('#loading-overlay').show();
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
            const loaderStartTime = Date.now();
            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);
            $.ajax({
                url: "{{ route('fuelCompanies.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    if (data.stats) {
                        $('#total_companies').text(data.stats.total ?? 0);
                        $('#active_companies').text(data.stats.active ?? 0);
                        $('#inactive_companies').text(data.stats.inactive ?? 0);
                    }
                    let newUrl = "{{ route('fuelCompanies.index') }}" + (formData ? '?' + formData : '');
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
</script>
@endsection
