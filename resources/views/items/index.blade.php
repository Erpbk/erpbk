@extends('layouts.app')
@section('title','Items')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 280px);
    }
</style>
@endpush
@section('content')
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
                            @can('item_create')
                            <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Item" data-action="{{ route('items.create') }}">
                                <i class="ti ti-plus"></i>
                                <div>
                                    <div class="action-dropdown-item-text">New</div>
                                    <div class="action-dropdown-item-desc">Add New Item</div>
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
            <h5>Filter Sims</h5>
            <button type="button" class="btn-close" id="closeSidebar"></button>
        </div>
        <div class="filter-body" id="searchTopbody">
            <form id="filterForm" action="{{ route('items.index') }}" method="GET">
                @csrf
                <div class="row">
                    <div class="form-group col-md-12 col-sm-12">
                        <label for="name">Item Name</label>
                        <input type="text" name="name" class="form-control" placeholder="Filter By Item Name" value="{{ request('name') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="code">Code</label>
                        <input type="text" name="code" class="form-control" placeholder="Filter By Code" value="{{ request('code') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="owner_type">Owner Type</label>
                        <select class="form-control " id="owner_type1" name="owner">
                            <option value="">Select</option>
                            <option value="customer">Customer</option>
                            <option value="leasingCompany">Leasing Company</option>
                            <option value="supplier">Supplier</option>
                            <option value="garage">Garage</option>
                            <option value="rider">Rider</option>
                        </select>
                    </div>
                    <div class="form-group col-sm-12">
                        <label for="owner_id">Owner</label>
                        <select name="ref_id" id="owner_id1" class="form-control" disabled>
                            <option value="">First select owner type</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="supplier_id">Supplier</label>
                        <select class="form-control " id="supplier_id" name="supplier_id">
                            @php
                            $supplierid = App\Models\Items::whereNotNull('supplier_id')
                                ->pluck('supplier_id')
                                ->unique();

                            $suppliers = App\Models\Supplier::whereIn('id', $supplierid)
                                ->select('id', 'name')
                                ->get();
                            @endphp
                            <option value="" selected>Select</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="status">Status</label>
                        <select class="form-control " id="status" name="status">
                            <option value="" selected>Select</option>
                            <option value='1' >Active</option>
                            <option value='0' >Inactive</option>
                        </select>
                    </div>
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
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter Cards</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-motorcycle"></i>Total Cards</div>
                <div class="value" id="total_orders">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i>Active</div>
                <div class="value" id="avg_ontime">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i>Inactive</div>
                <div class="value" id="total_rejected">{{ $stats['inactive'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('items.table', ['data' => $data,])
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
            window.location.href = url;
        }
    })
}
$(document).ready(function () {
    $('#owner_type1').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Owner",
            allowClear: true
    });
    $('#owner_id1').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Owner",
            allowClear: true
    });
    $('#supplier_id').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Supplier",
            allowClear: true
    });
    $('#status').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By status",
            allowClear: true
    });
    $('#owner_type1').on('change', function() {
        var ownerType = $(this).val();
        var $ownerSelect = $('#owner_id1');
        if(ownerType == '') {
          $ownerSelect.html('<option value="">All</option>').prop('disabled', true);
          return;
        }
        
        if (ownerType) {
            // Reset and disable owner select while loading
            $ownerSelect.html('<option value="">Loading...</option>').prop('disabled', true);
            
            // Make AJAX request to get owners
            $.ajax({
                url: "{{ route('items.get-owners') }}",
                type: "GET",
                data: {
                    _token: "{{ csrf_token() }}",
                    owner_type: ownerType
                },
                dataType: "json",
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var options = '<option value="">Select Owner</option>';
                        $.each(response.data, function(key, owner) {
                            var name = owner.name || owner.company_name || owner.title || owner.full_name;
                            options += '<option value="' + owner.id + '">' + name + '</option>';
                        });
                        $ownerSelect.html(options).prop('disabled', false);
                    } else {
                        $ownerSelect.html('<option value="">No owners found</option>').prop('disabled', false);
                    }
                },
                error: function(xhr) {
                    console.error('Error loading owners:', xhr);
                    $ownerSelect.html('<option value="">Error loading owners. Please try again.</option>').prop('disabled', false);
                }
            });
        } else {
            $ownerSelect.html('<option value="">First select owner type</option>').prop('disabled', true);
        }
    });
});
</script>
@endsection


