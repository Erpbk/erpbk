{{-- resources/views/branches/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    @include('flash::message')
    
    <h3 class="px-3 mb-3">Employees</h3>
    <!-- Employees Table Card -->
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            @can('employees_create')
                <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm " data-action="" data-size="lg" data-title="Add new employee">
                    <i class="icon-base ti ti-plus me-1"></i> Add New Employee
                </a>
            @endcan
        </div>
        <div class="card-body table-responsive" id="tableData">
            @include('employees.table')
        </div>
    </div>
@endsection

