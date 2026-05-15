{{-- resources/views/branches/index.blade.php --}}
@extends('layouts.app')

@section('title', 'Employees')

@section('content')
    @include('flash::message')
    
    <h3 class="px-3 mb-3">Employees</h3>

    @php
        $activeStatusFilters = request('employee_status', []);
        if (!is_array($activeStatusFilters)) {
            $activeStatusFilters = $activeStatusFilters ? [$activeStatusFilters] : [];
        }
        $statusCategory = ($employeeTopCategories ?? collect())->firstWhere('employee_column', 'status');
    @endphp

    @if(($employeeTopCategories ?? collect())->isNotEmpty())
    <div class="card mb-3">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                <a href="{{ route('employees.index', request()->except(['employee_status', 'employee_top_column', 'employee_top_value'])) }}"
                   class="btn btn-sm {{ empty($activeStatusFilters) && !request('employee_top_column') ? 'btn-primary' : 'btn-outline-secondary' }}">
                    All Employees
                </a>
                @if($statusCategory)
                    @foreach($statusCategory->options as $option)
                        @php
                            $isActive = in_array($option->name, $activeStatusFilters, true);
                            $params = request()->except(['employee_status', 'employee_top_column', 'employee_top_value']);
                            if ($isActive) {
                                $next = array_values(array_diff($activeStatusFilters, [$option->name]));
                                if (!empty($next)) {
                                    $params['employee_status'] = $next;
                                }
                            } else {
                                $params['employee_status'] = array_merge($activeStatusFilters, [$option->name]);
                            }
                        @endphp
                        <a href="{{ route('employees.index', $params) }}"
                           class="btn btn-sm {{ $isActive ? 'btn-primary' : 'btn-outline-secondary' }}">
                            {{ $option->name }}
                        </a>
                    @endforeach
                @endif
            </div>
            @foreach(($employeeTopCategories ?? collect()) as $topCategory)
                @if(($topCategory->employee_column ?? '') === 'status' || $topCategory->options->isEmpty())
                    @continue
                @endif
                <div class="small text-muted mb-1">{{ $topCategory->name }}</div>
                <div class="d-flex flex-wrap gap-2 mb-2">
                    @foreach($topCategory->options as $option)
                        @php
                            $col = $topCategory->employee_column;
                            $isTopActive = request('employee_top_column') === $col && request('employee_top_value') == $option->name;
                        @endphp
                        <a href="{{ route('employees.index', array_merge(request()->except(['employee_top_column', 'employee_top_value']), ['employee_top_column' => $col, 'employee_top_value' => $option->name])) }}"
                           class="btn btn-sm {{ $isTopActive ? 'btn-info' : 'btn-outline-info' }}">
                            {{ $option->name }}
                        </a>
                    @endforeach
                </div>
            @endforeach
        </div>
    </div>
    @endif

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

