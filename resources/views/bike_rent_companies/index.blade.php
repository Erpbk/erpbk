@extends('layouts.app')

@php
    $partyType = ($type ?? null) === 'bike_rental'
        ? ($partyType ?? request('party_type', 'company'))
        : null;
    $isIndividual = ($type ?? null) === 'bike_rental' && $partyType === 'individual';
    $isCompany = ($type ?? null) === 'bike_rental' && $partyType === 'company';
    $pageTitle = $isIndividual ? 'Individuals' : ($isCompany ? 'Companies' : 'Customers');
    $addLabel = $isIndividual ? 'Individual' : ($isCompany ? 'Company' : 'Customer');
@endphp
@section('title', $pageTitle)
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header ">
    @include('flash::message')
    <div>
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-12">
                <div class="action-buttons d-flex justify-content-end" >
                <div class="action-dropdown-container">
                    <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Add New</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @canany(['bike_on_rent_customers_create', 'garages_customers_create'])
                        @php
                            $createQuery = ['type' => $type];
                            if (in_array($partyType, ['company', 'individual'], true)) {
                                $createQuery['party_type'] = $partyType;
                            }
                        @endphp
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-action="{{ route('bikeRentCompanies.create') }}?{{ http_build_query($createQuery) }}" data-title="Add {{ $addLabel }}" data-size="lg">
                            <i class="ti ti-plus"></i>
                            <div>
                                <div class="action-dropdown-item-text">New</div>
                                <div class="action-dropdown-item-desc">Add New {{ $addLabel }}</div>
                            </div>
                        </a>
                        @endcanany
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter {{ $pageTitle }}</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        @php
            $filterAction = ($type ?? null) === 'garage'
                ? route('garage_customer.index')
                : route('bikeRentCompanies.index', array_filter(['party_type' => $partyType]));
        @endphp
        <form id="filterForm" action="{{ $filterAction }}" method="GET">
            @if(in_array($partyType, ['company', 'individual'], true))
                <input type="hidden" name="party_type" value="{{ $partyType }}">
            @endif
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="company_name">Filter by {{ $addLabel }}</label>
                    <select class="form-control select2" id="name" name="name">
                        @php
                        $customersQuery = \App\Models\BikeRentCompany::active()->where('customer_type', $type ?? 'bike_rental');
                        if (in_array($partyType, ['company', 'individual'], true)) {
                            $customersQuery->where('party_type', $partyType);
                        }
                        $customers = $customersQuery->orderBy('name')->get();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($customers as $company)
                        <option value="{{ $company->name }}" {{ request('name') == $company->name ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Filter by Status</label>
                    <select class="form-control select2" id="status" name="status">
                        <option value="" selected>Select</option>
                        <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>In Active</option>
                    </select>
                </div>
                @if(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-12">
                    <label for="branch_id">Branch</label>
                    <select class="form-control select2" id="branch_id" name="branch_id">
                        @foreach(auth()->user()->branchDropdown() as $id => $branchName)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $branchName }}</option>
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
<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>

<div class="content">

    @include('flash::message')

    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter</button>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('bike_rent_companies.table', ['data' => $data, 'partyType' => $partyType ?? null])
        </div>
    </div>
</div>

@endsection
@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $('#filterSidebar'),
            allowClear: true
        });
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will move the record to the Recycle Bin, or queue a delete request if approval is required.",
            icon: 'warning',
            input: 'textarea',
            inputPlaceholder: 'Reason (optional)',
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
                    data: {
                        _token: '{{ csrf_token() }}',
                        delete_reason: result.value || ''
                    },
                    success: function(response) {
                        $('#loading-overlay').hide();
                        var queued = !!(response && response.queued);
                        Swal.fire({
                            icon: queued ? 'warning' : 'success',
                            title: queued ? 'Delete request submitted' : 'Deleted!',
                            html: response.message,
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        $('#loading-overlay').hide();
                        let errorMessage = 'An error occurred while deleting.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage
                        });
                    }
                });
            }
        });
    }
</script>
@endsection
