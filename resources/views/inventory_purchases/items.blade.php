@extends('layouts.app')

@section('title','Inventory Items')
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
{{-- <section class="content-header">
    <div class="container">
        <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6">
                @can('items_inventory_create')
                    <div class="action-buttons d-flex justify-content-end">
                        <div class="action-dropdown-container">
                            <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                                <i class="ti ti-plus"></i>
                                <span>Add New</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addBikeDropdown">
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</section> --}}

<div class="d-flex gap-2 m-3">
    <a href="{{ route('inventory.index') }}" 
       class="btn btn-pill {{ request()->routeIs('inventory.index') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Inventory Items
    </a>
    <a href="{{ route('inventory.indexBatch') }}" 
       class="btn btn-pill {{ request()->routeIs('inventory.indexBatch') ? 'btn-primary' : 'btn-outline-secondary' }}">
        Purchase History
    </a>
</div>
<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1100">
    <div class="filter-header">
        <h5>Filter Inventory</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('inventory.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="company_name">Item</label>
                    <select class="form-control select2" id="item_id" name="item_id">
                        @php
                        $items = \App\Models\Items::dropdown('garage');
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($items as $item)
                        <option value="{{ $item->id }}" {{ request('item_id') == $item->id ? 'selected' : '' }}>{{  $item->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Status</label>
                    <select class="form-control select2" id="status" name="status">
                        <option value="" selected>Select</option>
                        <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="3" {{ request('status') == 2 ? 'selected' : '' }}>Inactive</option>
                    </select>
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
            @include('items.table')
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
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json'
                },
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Deleted!', data.message, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error!', data.message, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error!', 'An error occurred while deleting.', 'error');
            });
        }
    });
}
$(document).ready(function () {

    $('.select2').select2({
        dropdownParent: $('#searchTopbody'),
        allowClear: true
    });
});
</script>
@endsection