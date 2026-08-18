@extends('layouts.app')
@section('title', 'Employees')

@push('third_party_stylesheets')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<link rel="stylesheet" href="{{ asset('css/riders-styles.css') }}">
@endpush

@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@can('employees_employee_view')
<section class="content-header">
    <div>
        <div class="filter-tabs-section mb-4" id="filter-tabs-section">
            <div class="d-flex justify-content-between">
                @php
                $activeFiltersCount = count(request('employee_status', []));
                if (request('employee_top_column')) {
                    $activeFiltersCount++;
                }
                if (request()->filled('employee_id') || request()->filled('name') || request()->filled('branch_id') || request()->filled('department_id')) {
                    $activeFiltersCount++;
                }
                @endphp
                <div class="filter-tabs">
                    @if($activeFiltersCount > 0)
                    <div class="filter-status">
                        <div class="filter-info">
                            <i class="ti ti-filter"></i>
                            <span>{{ $activeFiltersCount }} filter{{ $activeFiltersCount > 1 ? 's' : '' }} applied</span>
                            <a href="{{ route('employees.index') }}" class="btn btn-sm btn-outline-secondary ms-2">
                                <i class="ti ti-x"></i>
                                Clear All
                            </a>
                        </div>
                    </div>
                    @endif
                    <a href="{{ route('employees.index') }}" class="filter-tab {{ !request('employee_status') && !request('employee_top_column') ? 'active' : '' }}">
                        <i class="ti ti-users"></i>
                        All Employees
                    </a>
                </div>
                <div class="fleet-supervisor-header-right d-flex align-items-center">
                    <div class="action-buttons">
                        <div class="action-dropdown-container">
                            <button class="action-dropdown-btn" id="addEmployeeDropdownBtn" type="button">
                                <i class="ti ti-plus"></i>
                                <span>Add Employee</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addEmployeeDropdown">
                                @can('employees_employee_create')
                                <a class="action-dropdown-item" href="{{ route('employees.create') }}">
                                    <i class="ti ti-user-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Create New Employee</div>
                                        <div class="action-dropdown-item-desc">Add a new employee to the system</div>
                                    </div>
                                </a>
                                @endcan
                                <a class="action-dropdown-item openColumnControlSidebar" href="javascript:void(0);">
                                    <i class="ti ti-columns"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">Column Control</div>
                                        <div class="action-dropdown-item-desc">Show or hide table columns</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
            <div class="filter-header">
                <h5>Filter Employees</h5>
                <button type="button" class="btn-close" id="closeSidebar"></button>
            </div>
            <div class="filter-body" id="searchTopbody">
                <form id="filterForm" action="{{ route('employees.index') }}" method="GET" data-rfp-skip-lock="1">
                    <div class="row">
                        @fieldVisible('employees', 'employee_id')
                        <div class="form-group col-md-12">
                            <label for="employee_id">Employee ID</label>
                            <input type="text" name="employee_id" class="form-control" placeholder="Filter by Employee ID" value="{{ request('employee_id') }}">
                        </div>
                        @endfieldVisible
                        @fieldVisible('employees', 'name')
                        <div class="form-group col-md-12">
                            <label for="name">Employee Name</label>
                            <input type="text" name="name" class="form-control" placeholder="Filter by Name" value="{{ request('name') }}">
                        </div>
                        @endfieldVisible
                        @fieldVisible('employees', 'branch_id')
                        <div class="form-group col-md-12">
                            <label for="branch_id">Branch</label>
                            <select class="form-control" id="branch_id" name="branch_id">
                                <option value="">Select</option>
                                @foreach(\App\Models\Branch::active()->orderBy('name')->get() as $branch)
                                <option value="{{ $branch->id }}" {{ (string) request('branch_id') === (string) $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endfieldVisible
                        @fieldVisible('employees', 'department_id')
                        <div class="form-group col-md-12">
                            <label for="department_id">Department</label>
                            <select class="form-control" id="department_id" name="department_id">
                                <option value="">Select</option>
                                @foreach(\App\Models\Departments::orderBy('name')->get() as $department)
                                <option value="{{ $department->id }}" {{ (string) request('department_id') === (string) $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endfieldVisible
                        <div class="col-md-12 form-group text-center">
                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div id="filterOverlay" class="filter-overlay"></div>
    </div>
</section>

@include('components.column-control-panel', [
    'tableColumns' => $tableColumns ?? [],
    'tableIdentifier' => 'employees_index_table',
])

<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <button class="btn btn-primary openFilterSidebar" type="button"><i class="fa fa-search"></i> Filter Employees</button>
        </div>
        <div class="card-body table-responsive px-2 py-0">
            <div class="riders-table-container">
                @include('employees.table', ['data' => $data, 'tableColumns' => $tableColumns ?? []])
            </div>
            <div class="filter-loading-overlay" style="display: none;">
                <div class="filter-loading-content">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Applying filters...</p>
                </div>
            </div>
        </div>
    </div>
</div>
@else
<div class="alert alert-danger mt-4" role="alert">
    You do not have permission to view employees.
</div>
@endcan
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function confirmDeleteEmployee(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this! The employee will be moved to Recycle Bin.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (!result.isConfirmed) return;
            fetch(url, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            }).then(function(r) {
                return r.json();
            }).then(function(data) {
                if (data.success) {
                    Swal.fire({ icon: 'success', title: 'Deleted', text: data.message || 'Employee deleted.' }).then(function() {
                        window.location.reload();
                    });
                } else {
                    Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not delete employee.' });
                }
            }).catch(function() {
                Swal.fire({ icon: 'error', title: 'Error', text: 'Could not delete employee.' });
            });
        });
    }

    $(document).ready(function() {
        $('#branch_id, #department_id').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: 'Select'
        });

        $(document).on('mouseenter click', '#openFilterSidebar, .openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
        });
        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });
        $('#filterForm').on('submit', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });

        $('#quickSearch').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const url = new URL(window.location);
                const searchValue = $(this).val();
                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }
                window.location.href = url.toString();
            }
        });

        $('#addEmployeeDropdownBtn').on('click', function(e) {
            e.stopPropagation();
            const dropdown = $('#addEmployeeDropdown');
            const btn = $(this);
            if (dropdown.hasClass('show')) {
                dropdown.removeClass('show');
                btn.removeClass('open');
            } else {
                $('.action-dropdown-menu').removeClass('show');
                $('.action-dropdown-btn').removeClass('open');
                dropdown.addClass('show');
                btn.addClass('open');
            }
        });
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.action-dropdown-container').length) {
                $('.action-dropdown-menu').removeClass('show');
                $('.action-dropdown-btn').removeClass('open');
            }
        });
    });

    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('#dataTableBuilder');
        if (!table) return;
        const headers = table.querySelectorAll('th.sorting');
        const tbody = table.querySelector('tbody');
        if (!tbody) return;
        headers.forEach(function(header, colIndex) {
            header.addEventListener('click', function() {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = header.classList.contains('sorted-asc');
                headers.forEach(function(h) { h.classList.remove('sorted-asc', 'sorted-desc'); });
                header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');
                rows.sort(function(a, b) {
                    var aText = (a.children[colIndex] && a.children[colIndex].textContent || '').trim().toLowerCase();
                    var bText = (b.children[colIndex] && b.children[colIndex].textContent || '').trim().toLowerCase();
                    var aVal = isNaN(aText) ? aText : parseFloat(aText);
                    var bVal = isNaN(bText) ? bText : parseFloat(bText);
                    if (aVal < bVal) return isAsc ? 1 : -1;
                    if (aVal > bVal) return isAsc ? -1 : 1;
                    return 0;
                });
                rows.forEach(function(row) { tbody.appendChild(row); });
            });
        });
    });
</script>
<script>
    $('<style>').prop('type', 'text/css').html(`
        .fleet-supervisor-header-right { position: relative; overflow: visible; }
        .action-dropdown-container { position: relative; display: inline-block; }
        .action-dropdown-btn { display: inline-flex; align-items: center; gap: 8px; }
        .action-dropdown-menu {
            position: absolute; top: calc(100% + 8px); right: 0; min-width: 260px; max-width: 320px;
            background: #fff; border: 1px solid #e5e7eb; border-radius: 8px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.12); padding: 8px 0; z-index: 3000; display: none;
        }
        .action-dropdown-menu.show { display: block; }
        .action-dropdown-item { display: flex; align-items: flex-start; gap: 12px; padding: 10px 14px; color: #111827; text-decoration: none; }
        .action-dropdown-item:hover { background: #f3f4f6; }
        .action-dropdown-item i { color: #2563eb; margin-top: 2px; }
        .action-dropdown-item-text { font-weight: 600; }
        .action-dropdown-item-desc { font-size: 12px; color: #6b7280; }
        .fleet-stat.active-selected { background: linear-gradient(135deg, #10b981, #059669); color: white; transform: scale(1.05); }
        .fleet-supervisor-card.filtered { background: linear-gradient(135deg, #e0f2fe, #b3e5fc); border: 2px solid #29b6f6; }
    `).appendTo('head');

    function initFleetSupervisorSlider() {
        const sliderTrack = document.getElementById('sliderTrack');
        if (!sliderTrack || sliderTrack.dataset.tickerInit === '1') return;
        const cards = Array.from(sliderTrack.querySelectorAll('.fleet-supervisor-card'));
        if (!cards.length) return;
        const container = sliderTrack.closest('.fleet-supervisor-slider-container');
        if (container) container.classList.add('ticker-mode');
        sliderTrack.dataset.tickerInit = '1';
        if (cards.length < 2) return;
        let isAnimating = false;
        const gap = parseFloat(window.getComputedStyle(sliderTrack).columnGap || window.getComputedStyle(sliderTrack).gap || '16') || 16;
        function slideNextCard() {
            if (isAnimating) return;
            const firstCard = sliderTrack.querySelector('.fleet-supervisor-card');
            if (!firstCard) return;
            isAnimating = true;
            const shiftAmount = firstCard.offsetWidth + gap;
            sliderTrack.style.transition = 'transform 520ms ease';
            sliderTrack.style.transform = 'translateX(-' + shiftAmount + 'px)';
            window.setTimeout(function() {
                sliderTrack.style.transition = 'none';
                sliderTrack.style.transform = 'translateX(0)';
                sliderTrack.appendChild(firstCard);
                void sliderTrack.offsetWidth;
                isAnimating = false;
            }, 540);
        }
        window.setInterval(slideNextCard, 2600);
    }
</script>
@endsection
