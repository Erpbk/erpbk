@extends('layouts.app')

@section('title','Employee Invoices')
@push('third_party_stylesheets')
@include('invoices.partials.status_badge_styles')
<style>
    .table-responsive {
        max-height: calc(100vh - 280px);
    }
</style>
@endpush
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
@can('employees_invoice_view')
<section class="content-header ">
    @include('flash::message')
    <div>
        <div class="row mb-2">
            <div class="col-sm-12 col-lg-12">
                <div class="action-buttons d-flex justify-content-end" >
                <div class="action-dropdown-container">
                    <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Add New</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @can('employees_invoice_create')
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Employee Invoice" data-action="{{ route('employeeInvoices.create') }}">
                            <i class="ti ti-plus"></i>
                            <div>
                                <div class="action-dropdown-item-text">New</div>
                                <div class="action-dropdown-item-desc">Add New Invoice</div>
                            </div>
                        </a>
                        <a class="action-dropdown-item" href="{{ route('employeeInvoices.import.form') }}">
                            <i class="ti ti-upload"></i>
                            <div>
                                <div class="action-dropdown-item-text">Import</div>
                                <div class="action-dropdown-item-desc">Import invoices from sheet</div>
                            </div>
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
            </div>
        </div>
    </div>
</section>

<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Invoices</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('employeeInvoices.index') }}" method="GET">
            @if(request()->filled('quick_search'))
            <input type="hidden" name="quick_search" value="{{ request('quick_search') }}">
            @endif
            <div class="row">
                <div class="form-group col-md-12 col-sm-12">
                    <label for="id">Invoice ID</label>
                    <input type="text" name="id" id="id" class="form-control" placeholder="Filter By ID" value="{{ request('id') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="employee_id">Filter by Employee</label>
                    <select class="form-control" id="employee_id" name="employee_id">
                        @php
                            $employees = \App\Models\Employee::query()
                                ->select('id', 'employee_id', 'name')
                                ->orderBy('name')
                                ->get();
                        @endphp
                        <option value="">Select</option>
                        @foreach($employees as $employee)
                            <option value="{{ $employee->id }}" {{ (string) request('employee_id') === (string) $employee->id ? 'selected' : '' }}>
                                {{ $employee->employee_id }} - {{ $employee->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="billing_month">Billing Month</label>
                    <input type="month" name="billing_month" id="billing_month" class="form-control" value="{{ request('billing_month') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="zone">Filter by Project / Zone</label>
                    <select class="form-control" id="zone" name="zone">
                        @php
                            $zones = company_table('employee_invoices')
                                ->whereNotNull('zone')
                                ->where('zone', '!=', '')
                                ->pluck('zone')
                                ->unique()
                                ->sort()
                                ->values();
                        @endphp
                        <option value="">Select</option>
                        @foreach($zones as $zone)
                            <option value="{{ $zone }}" {{ request('zone') == $zone ? 'selected' : '' }}>{{ $zone }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Payment Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">Select</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Unpaid</option>
                        <option value="3" {{ request('status') === '3' ? 'selected' : '' }}>Partially Paid</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Paid</option>
                    </select>
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="filterOverlay" class="filter-overlay"></div>

<div class="content px-0 mt-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-search">
                <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
            </div>
            <button class="btn btn-primary openFilterSidebar" type="button">
                <i class="fa fa-search"></i> Filter
            </button>
        </div>
        <div class="card-body table-responsive py-0" id="table-data">
            @include('employee_invoices.table', ['data' => $data, 'currentMonthTotal' => $currentMonthTotal])
        </div>
    </div>
</div>
@else
<div class="alert alert-danger mt-4" role="alert">
    You do not have permission to view employee invoices.
</div>
@endcan
@endsection

@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#employee_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Employee",
            allowClear: true
        });
        $('#zone').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Project / Zone",
            allowClear: true
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Status",
            allowClear: true
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            $('#loading-overlay').show();
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');

            const loaderStartTime = Date.now();
            let filteredFields = $(this).serializeArray().filter(function(field) {
                return field.name !== '_token' && String(field.value || '').trim() !== '';
            });
            const quickSearchValue = ($('#quickSearch').val() || '').trim();
            filteredFields = filteredFields.filter(function(field) {
                return field.name !== 'quick_search';
            });
            if (quickSearchValue) {
                filteredFields.push({ name: 'quick_search', value: quickSearchValue });
            }
            const formData = $.param(filteredFields);

            $.ajax({
                url: "{{ route('employeeInvoices.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    if (data.currentMonthTotal !== undefined) {
                        $('#current-month-total').text('Current Month Total: ' + data.currentMonthTotal);
                    }
                    const newUrl = "{{ route('employeeInvoices.index') }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);

                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(function() { $('#loading-overlay').hide(); }, remaining > 0 ? remaining : 0);
                },
                error: function(xhr, status, error) {
                    console.error(error);
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(function() { $('#loading-overlay').hide(); }, remaining > 0 ? remaining : 0);
                }
            });
        });

        $('#quickSearch').on('keyup', function(e) {
            if (e.keyCode === 13 || $(this).val().length === 0) {
                const searchValue = $(this).val();
                const url = new URL(window.location.href);

                if (searchValue) {
                    url.searchParams.set('quick_search', searchValue);
                } else {
                    url.searchParams.delete('quick_search');
                }
                url.searchParams.delete('page');
                window.location.href = url.toString();
            }
        });
    });
</script>
@endsection
