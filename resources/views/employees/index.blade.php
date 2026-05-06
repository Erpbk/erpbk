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
            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-secondary btn-sm openColumnControlSidebar" href="javascript:void(0);">
                    <i class="ti ti-columns me-1"></i> Column Control
                </a>
                @can('employees_create')
                    <a href="{{ route('employees.create') }}" class="btn btn-primary btn-sm " data-action="" data-size="lg" data-title="Add new employee">
                        <i class="icon-base ti ti-plus me-1"></i> Add New Employee
                    </a>
                @endcan
            </div>
        </div>
        <div class="card-body table-responsive" id="tableData">
            @include('employees.table')
        </div>
    </div>

    @php
        $tableColumns = [
            ['data' => 'employee_id', 'title' => $employeeTableLabels['employee_id'] ?? 'Employee ID'],
            ['data' => 'name', 'title' => $employeeTableLabels['name'] ?? 'Name'],
            ['data' => 'company_contact', 'title' => $employeeTableLabels['company_contact'] ?? 'Contact'],
            ['data' => 'branch_id', 'title' => $employeeTableLabels['branch_id'] ?? 'Branch'],
            ['data' => 'department_id', 'title' => $employeeTableLabels['department_id'] ?? 'Department'],
            ['data' => 'designation', 'title' => $employeeTableLabels['designation'] ?? 'Designation'],
            ['data' => 'doj', 'title' => $employeeTableLabels['doj'] ?? 'Date of Joining'],
            ['data' => 'documents_expiry', 'title' => $employeeTableLabels['documents_expiry'] ?? 'Documents Expiry'],
            ['data' => 'status', 'title' => $employeeTableLabels['status'] ?? 'Status'],
            ['data' => 'action', 'title' => $employeeTableLabels['actions'] ?? 'Actions'],
        ];
    @endphp
    @include('components.column-control-panel', [
        'tableId' => 'dataTableBuilder',
        'tableColumns' => $tableColumns,
        'tableIdentifier' => 'employees_index_table',
    ])
@endsection

