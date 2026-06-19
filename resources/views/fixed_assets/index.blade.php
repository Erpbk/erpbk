@extends('layouts.app')

@section('title', 'Fixed Assets')

@section('content')
<section class="content-header">
    @include('flash::message')
    <div class="row mb-2">
        <div class="col-sm-12">
            <div class="action-buttons d-flex justify-content-end">
                <div class="action-dropdown-container">
                    <button type="button" class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Add New</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @can('asset_create')
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add Fixed Asset" data-action="{{ route('fixed-assets.create') }}">
                            <i class="ti ti-box"></i>
                            <div>
                                <div class="action-dropdown-item-text">New Asset</div>
                                <div class="action-dropdown-item-desc">Register a fixed asset</div>
                            </div>
                        </a>
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add Asset Category" data-action="{{ route('asset-categories.create') }}">
                            <i class="ti ti-category"></i>
                            <div>
                                <div class="action-dropdown-item-text">New Category</div>
                                <div class="action-dropdown-item-desc">Create category with COA accounts</div>
                            </div>
                        </a>
                        <a class="action-dropdown-item" href="{{ route('asset-categories.index') }}">
                            <i class="ti ti-list"></i>
                            <div>
                                <div class="action-dropdown-item-text">Manage Categories</div>
                                <div class="action-dropdown-item-desc">View and edit asset categories</div>
                            </div>
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Assets</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('fixed-assets.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="name">Asset Name</label>
                    <input type="text" name="name" class="form-control" value="{{ request('name') }}" placeholder="Filter by name">
                </div>
                <div class="form-group col-md-12">
                    <label for="asset_category_id">Category</label>
                    <select name="asset_category_id" class="form-control">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected(request('asset_category_id') == $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Status</label>
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="draft" @selected(request('status') === 'draft')>Draft</option>
                        <option value="active" @selected(request('status') === 'active')>Active</option>
                        <option value="fully_depreciated" @selected(request('status') === 'fully_depreciated')>Fully Depreciated</option>
                        <option value="disposed" @selected(request('status') === 'disposed')>Disposed</option>
                    </select>
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="content">
    <div class="card">
        <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar"><i class="fa fa-search"></i> Filter</button>
        </div>
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label"><i class="ti ti-box"></i> Total Assets</div>
                <div class="value">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="ti ti-check"></i> Active</div>
                <div class="value">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="ti ti-calendar-stats"></i> Fully Depreciated</div>
                <div class="value">{{ $stats['fully_depreciated'] ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('fixed_assets.table', ['data' => $data])
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmDelete(url) {
    Swal.fire({
        title: 'Are you sure?',
        text: "This asset will be deleted.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}
</script>
@endsection
