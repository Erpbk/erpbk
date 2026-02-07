@extends('layouts.app')

@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 150px);
    }
    .total-card {
        flex: 1 1 calc(20% - 8px);
    }
    .maintenance-badge {
        font-size: 0.85rem;
        padding: 4px 8px;
        margin-top: 2px;
    }
    .nav-link.active {
        background-color: #e7ecf0 !important;
        border-bottom: 3px solid #0d6efd;
        font-weight: bold;
    }
    .nav-link {
        cursor: pointer;
    }
    .tab-content {
        display: none;
    }
    .tab-content.active {
        display: block;
    }
</style>
@endpush

@section('content')
    @include('flash::message')
    <div class="clearfix"></div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fa fa-bicycle me-2"></i>Bike Maintenance Overview
            </h4>
            <a class="btn btn-primary action-btn show-modal"
                href="javascript:void(0);"
                data-size="xl"
                data-title="Add Maintenance Record"
                data-action="{{ route('bikeMaintenance.create') }}">
                Add Maintenance Record
            </a>
        </div>
        
        <!-- Stats Cards -->
        <div class="totals-cards px-4 pt-4">
            <div class="total-card total-blue">
                <div class="label">
                    <i class="fa fa-bicycle"></i>Total Active Bikes
                </div>
                <div class="value" id="total_active">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-4">
                <div class="label">
                    <i class="fa fa-exclamation-triangle"></i>Missing Maintenance Data
                </div>
                <div class="value" id="missing_data">{{ $stats['missingData'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label">
                    <i class="fa fa-check-circle"></i>Well Maintained
                </div>
                <div class="value" id="good_status">{{ $stats['good'] ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label">
                    <i class="fa fa-clock"></i>Due for Maintenance
                </div>
                <div class="value" id="due_maintenance">{{ $stats['due'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label">
                    <i class="fa fa-times-circle"></i>Overdue for Maintenance
                </div>
                <div class="value" id="overdue_maintenance">{{ $stats['overdue'] ?? 0 }}</div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="card-header border-top mx-4">
            <ul class="nav nav-tabs card-header-tabs gap-1" id="maintenanceTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-link @if(request()->segment(2) == 'missing_data') active @endif" 
                       href="{{ route('bike-maintenance.missing') }}">
                        <i class="fa fa-exclamation-triangle me-1"></i>
                        Missing Data 
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-link @if(request()->segment(2) == 'overdue_for_maintenance') active @endif" 
                       href="{{ route('bike-maintenance.overdue') }}">
                        <i class="fa fa-times-circle me-1"></i>
                        Overdue 
                    </a>
                </li>
                <li class="nav-item" role="presentation">
                    <a class="nav-link tab-link @if(request()->segment(2) == 'due_for_maintenance') active @endif" 
                       href="{{ route('bike-maintenance.due') }}">
                        <i class="fa fa-clock me-1"></i>
                        Due Soon 
                    </a>
                </li>
            </ul>
        </div>

        <!-- Tab Content -->
        <div class="card-body table-responsive px-4">
            @yield('page-content')
        </div>
    </div>
@endsection
@section('page-script')
<script>
    $.fn.dataTable.ext.errMode = 'none';
    $('#dataTableBuilder').DataTable({
        "paging": true,           // Enable DataTables pagination
        "pageLength": 50,         // Items per page
        "searching": true,        // Enable search
        "ordering": false,         // Enable column sorting
        "info": true,             // Show "Showing X of Y entries"
        "autoWidth": true,       // Better column width handling
        "dom": "<'row'<'col-md-6'><'col-md-6 d-flex justify-content-end'f>>" +
       "<'row'<'col-md-12'tr>>" +
       "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
    });
</script>
@endsection