@extends('layouts.app')

@section('title','Customers')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container">
        <div class="row mb-2">
            <div class="col-sm-6">
            </div>
            <div class="col-sm-6">
                @can('customer_create')
                    <div class="action-buttons d-flex justify-content-end">
                        <div class="action-dropdown-container">
                            <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                                <i class="ti ti-plus"></i>
                                <span>Add New</span>
                                <i class="ti ti-chevron-down"></i>
                            </button>
                            <div class="action-dropdown-menu" id="addBikeDropdown">
                                <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Customer" data-action="{{ route('customers.create') }}">
                                    <i class="ti ti-plus"></i>
                                    <div>
                                        <div class="action-dropdown-item-text">New Customer</div>
                                        <div class="action-dropdown-item-desc">Add a new Customer</div>
                                    </div>
                                </a>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>
        </div>
    </div>
</section>

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Customers</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('customers.index') }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="company_name">Filter by Customer</label>
                    <select class="form-control select2" id="name" name="company_name">
                        @php
                        $customers = \App\Models\Customers::active()->get();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($customers as $company)
                        <option value="{{ $company->name }}" {{ request('name') == $company->name ? 'selected' : '' }}>{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Filter by Status</label>
                    <select class="form-control select2" id="status" name="status">
                        <option value="" selected>Select</option>
                        <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>In Active</option>
                    </select>
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<!-- Filter Overlay -->
<div id="filterOverlay" class="filter-overlay"></div>

<div class="content">

    @include('flash::message')

    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter</button>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('customers.table', ['data' => $data])
        </div>
    </div>
</div>

@endsection
@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $('#filterSidebar'),
            allowClear: true
        });
    });
</script>

<script type="text/javascript">
    $(document).ready(function() {
        $('#filterForm').on('submit', function(e) {
            e.preventDefault();

            $('#loading-overlay').show();
            $('#searchModal').modal('hide');

            const loaderStartTime = Date.now();

            // Exclude _token and empty fields
            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);

            $.ajax({
                url: "{{ route('customers.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);

                    // Update URL
                    let newUrl = "{{ route('customers.index') }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);


                    // Ensure loader is visible at least 3s
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                },
                error: function(xhr, status, error) {
                    console.error(error);

                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 1000 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                }
            });
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('#dataTableBuilder');
        const headers = table.querySelectorAll('th.sorting');
        const tbody = table.querySelector('tbody');

        headers.forEach((header, colIndex) => {
            header.addEventListener('click', () => {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAsc = header.classList.contains('sorted-asc');

                // Clear previous sort classes
                headers.forEach(h => h.classList.remove('sorted-asc', 'sorted-desc'));

                // Add new sort direction
                header.classList.add(isAsc ? 'sorted-desc' : 'sorted-asc');

                // Sort logic
                rows.sort((a, b) => {
                    let aText = a.children[colIndex]?.textContent.trim().toLowerCase();
                    let bText = b.children[colIndex]?.textContent.trim().toLowerCase();

                    const aVal = isNaN(aText) ? aText : parseFloat(aText);
                    const bVal = isNaN(bText) ? bText : parseFloat(bText);

                    if (aVal < bVal) return isAsc ? 1 : -1;
                    if (aVal > bVal) return isAsc ? -1 : 1;
                    return 0;
                });

                // Re-append sorted rows
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    });
</script>
@endsection