@extends('bikes.view')
@push('third_party_stylesheets')

<style>
    .table-responsive {
        max-height: calc(100vh - 150px);
    }
</style>
@endpush

@section('page_content')
<div class="clearfix"></div>
@can('bikes_maintenance_view')
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">
            <i class="fas fa-tools me-2"></i> Maintenance History
        </h5>

        @can('bikes_maintenance_create')
        <a class="btn btn-primary btn-sm show-modal"
           href="javascript:void(0)"
           data-size="xl"
           data-title="Add Maintenance Record"
           data-action="{{ route('bikeMaintenance.create') }}?id={{ $bikes->id }}">
            <i class="fas fa-plus me-1"></i> Add Maintenance
        </a>
        @endcan
    </div>

    <div class="card-body table-responsive">
        @include('bike-maintenance.table')
    </div>
</div>
@else
<div class="card">
    <div class="card-body">
        <h5 class="card-title">You are not authorized to access this page</h5>
    </div>
</div>
@endcan
@endsection
