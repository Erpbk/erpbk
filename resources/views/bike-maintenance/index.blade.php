@extends('layouts.app')
@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 150px);
    }
    .total-card {
        flex: 1 1 calc(20% - 8px);
    }
</style>
@endpush


@section('content')
    @include('flash::message')
    <div class="clearfix"></div>
    
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">
                <i class="fa fa-bicycle me-2"></i>Bike Maintenance Records
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
        <div class="totals-cards px-3">
            <div class="total-card total-blue">
                <div class="label">
                    <i class="fa fa-bicycle"></i>Total Active Bikes
                </div>
                <div class="value" id="total_active">{{ $stats['active'] ?? 0 }}</div>
            </div>
            <div class="total-card total-4">
                <div class="label">
                    <i class="fa fa-exclamation-triangle"></i>Total Maint. Records
                </div>
                <div class="value" id="missing_data">{{ $stats['total'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label">
                    <i class="fa fa-check-circle"></i>Maint. this month
                </div>
                <div class="value" id="good_status">{{ $stats['current'] ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label">
                    <i class="fa fa-clock"></i>Total overdue records
                </div>
                <div class="value" id="due_maintenance">{{ $stats['total_overdue'] ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label">
                    <i class="fa fa-times-circle"></i>Overdue this month
                </div>
                <div class="value" id="overdue_maintenance">{{ $stats['current_overdue'] ?? 0 }}</div>
            </div>
            <div class="total-card total-1">
                <div class="label">
                    <i class="fa fa-times-circle"></i>avg overdue/month
                </div>
                <div class="value" id="overdue_maintenance">{{ $stats['avg'] ?? 0 }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label">
                    <i class="fa fa-times-circle"></i>total Overdue cost
                </div>
                <div class="value" id="overdue_maintenance">AED {{ $stats['overdue_cost'] ?? 0 }}</div>
            </div>
            <div class="total-card total-black">
                <div class="label">
                    <i class="fa fa-times-circle"></i>total Overdue charged
                </div>
                <div class="value" id="overdue_maintenance">AED {{ $stats['overdue_charged'] ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label">
                    <i class="fa fa-times-circle"></i>total maint. cost
                </div>
                <div class="value" id="overdue_maintenance">AED {{ $stats['maint_cost'] ?? 0 }}</div>
            </div>
        </div>

        <!-- Tab Content -->
        <div class="card-body table-responsive py-0">
            @include('bike-maintenance.table')
        </div>
    </div>
@endsection
