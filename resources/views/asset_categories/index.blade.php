@extends('layouts.app')

@section('title', 'Asset Categories')

@section('content')
<section class="content-header">
    @include('flash::message')
    <div class="row mb-2">
        <div class="col-sm-12">
            <div class="action-buttons d-flex justify-content-between align-items-center">
                <a href="{{ route('fixed-assets.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="ti ti-arrow-left"></i> Back to Assets
                </a>
                @can('asset_create')
                <a href="javascript:void(0);" class="btn btn-primary show-modal" data-size="lg" data-title="Add Asset Category" data-action="{{ route('asset-categories.create') }}">
                    <i class="ti ti-plus"></i> New Category
                </a>
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content">
    <div class="card">
        <div class="card-body table-responsive" id="table-data">
            @include('asset_categories.table', ['categories' => $categories])
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
        text: 'This category will be deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) window.location.href = url;
    });
}
</script>
@endsection
