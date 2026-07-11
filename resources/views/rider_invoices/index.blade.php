@extends('layouts.app')
@section('title','Rider Invoices')
@push('third_party_stylesheets')
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
@can('riders_invoices_view')
<section class="content-header ">
    @include('flash::message')
    <div>
        <div class="row my-3">
            <div class="col-sm-12 col-lg-12">
                <div class="action-buttons d-flex justify-content-end" >
                <div class="action-dropdown-container">
                    <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Add New</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @can('riders_invoices_create')
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Rider Invoice" data-action="{{ route('riderInvoices.create') }}">
                            <i class="ti ti-plus"></i>
                            <div>
                                <div class="action-dropdown-item-text">New</div>
                                <div class="action-dropdown-item-desc">Add New Invoice</div>
                            </div>
                        </a>
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="sm" data-title="Import Invoices" data-action="{{ route('rider.invoice_import') }}">
                            <i class="ti ti-plus"></i>
                            <div>
                                <div class="action-dropdown-item-text">Import</div>
                                <div class="action-dropdown-item-desc">Import Invoices</div>
                            </div>
                        </a>
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Import Paid Invoices" data-action="{{ route('riderInvoices.importPaid') }}">
                            <i class="ti ti-plus"></i>
                            <div>
                                <div class="action-dropdown-item-text">Paid Vouchers</div>
                                <div class="action-dropdown-item-desc">Import Payment vouchers</div>
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
            <form id="filterForm" action="{{ route('riderInvoices.index') }}" method="GET">
                @csrf
                <div class="row">
                    <div class="form-group col-md-12 col-sm-12">
                        <label for="name">ID</label>
                        <input type="text" name="id" class="form-control" placeholder="Filter By ID" value="{{ request('id') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="rider_id">Filter by Rider</label>
                        <select class="form-control " id="rider_id" name="rider_id">
                            @php
                            $riders = \App\Models\Riders::active()
                            ->select('rider_id', 'name', 'id')
                            ->get();
                            @endphp
                            <option value="" selected>Select</option>
                            @foreach($riders as $rider)
                                <option value="{{ $rider->id }}" {{ request('rider_id') == $rider->id ? 'selected' : '' }}>{{ $rider->rider_id . '-' . $rider->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="billing_month">Billing Month</label>
                        <input type="month" name="billing_month" class="form-control" placeholder="Filter By Billing Month" value="{{ request('billing_month') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="zone">Filter by Zone</label>
                        <select class="form-control " id="zone" name="zone">
                            @php
                            $zones = company_table('rider_invoices')
                            ->whereNotNull('zone')
                            ->where('zone', '!=', '')
                            ->pluck('zone')
                            ->unique();
                            @endphp
                            <option value="">Select</option>
                            @foreach($zones as $zone)
                                <option value="{{ $zone }}" {{ request('zone') == $zone ? 'selected' : '' }}>{{ $zone}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="performance">Filter by Performance</label>
                        <select class="form-control " id="performance" name="performance">
                            @php
                            $performances = company_table('rider_invoices')
                            ->whereNotNull('performance')
                            ->where('performance', '!=', '')
                            ->pluck('performance')
                            ->unique();
                            @endphp
                            <option value="">Select</option>
                            @foreach($performances as $performance)
                                <option value="{{ $performance }}" {{ request('performance') == $performance ? 'selected' : '' }}>{{ $performance}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="status">Status</label>
                        <select class="form-control " id="status" name="status">
                            <option value="" selected>Select</option>
                            <option value='1' >Active</option>
                            <option value='0' >Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-12 form-group text-center">
                        <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<div class="content">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header text-end">
            <button id="deleteSelectedBtn" class="btn btn-danger me-2" style="display: none;" onclick="deleteSelectedInvoices()">
                <i class="fa fa-trash"></i> Delete Selected
            </button>
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter</button>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('rider_invoices.table', [
            'data' => $data,
            'currentMonthTotal' => $currentMonthTotal
            ])
        </div>
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
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<style>
    .invoice-checkbox {
        transform: scale(1.2);
        cursor: pointer;
    }

    #selectAllCheckbox {
        transform: scale(1.2);
        cursor: pointer;
    }

    #deleteSelectedBtn {
        transition: all 0.3s ease;
    }
</style>
<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = url;
            }
        })
    }
    $(document).ready(function() {
        $('#rider_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Rider",
            allowClear: true, // ✅ cross icon enable
        });
        $('#billing_month').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Billing Month",
            allowClear: true, // ✅ cross icon enable
        });
        $('#vendor_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Vendor",
            allowClear: true, // ✅ cross icon enable
        });
        $('#zone').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Zone",
            allowClear: true, // ✅ cross icon enable
        });
        $('#performance').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Performance",
            allowClear: true, // ✅ cross icon enable
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By status",
            allowClear: true, // ✅ cross icon enable
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
                url: "{{ route('riderInvoices.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);

                    // 🔹 Update Current Month Total in header
                    if (data.currentMonthTotal !== undefined) {
                        $('#current-month-total').text('Current Month Total: ' + data.currentMonthTotal);
                    }

                    // Update URL
                    let newUrl = "{{ route('riderInvoices.index') }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);

                    // Loader timing
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

    // Bulk delete functionality
    function toggleSelectAll(selectAllCheckbox) {
        const checkboxes = document.querySelectorAll('.invoice-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = selectAllCheckbox.checked;
        });
        updateDeleteButton();
    }

    function updateDeleteButton() {
        const selectedCheckboxes = document.querySelectorAll('.invoice-checkbox:checked');
        const deleteBtn = document.getElementById('deleteSelectedBtn');
        const selectAllCheckbox = document.getElementById('selectAllCheckbox');

        if (selectedCheckboxes.length > 0) {
            deleteBtn.style.display = 'inline-block';
            deleteBtn.textContent = `Delete Selected (${selectedCheckboxes.length})`;
        } else {
            deleteBtn.style.display = 'none';
        }

        // Update select all checkbox state
        const allCheckboxes = document.querySelectorAll('.invoice-checkbox');
        if (selectedCheckboxes.length === 0) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = false;
        } else if (selectedCheckboxes.length === allCheckboxes.length) {
            selectAllCheckbox.indeterminate = false;
            selectAllCheckbox.checked = true;
        } else {
            selectAllCheckbox.indeterminate = true;
        }
    }

    function deleteSelectedInvoices() {
        const selectedCheckboxes = document.querySelectorAll('.invoice-checkbox:checked');
        const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);

        if (selectedIds.length === 0) {
            Swal.fire('Error', 'Please select invoices to delete', 'error');
            return;
        }

        Swal.fire({
            title: 'Are you sure?',
            text: `You are about to delete ${selectedIds.length} invoice(s). This action cannot be undone!`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete them!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'Deleting...',
                    text: 'Please wait while we delete the selected invoices.',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Make AJAX request
                $.ajax({
                    url: '{{ route("riderInvoices.bulkDelete") }}',
                    type: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        invoice_ids: selectedIds
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'Success!',
                            text: response.message,
                            icon: 'success',
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload the page or refresh the table
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        let errorMessage = 'An error occurred while deleting invoices.';
                        if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            title: 'Error!',
                            text: errorMessage,
                            icon: 'error',
                            confirmButtonText: 'OK'
                        });
                    }
                });
            }
        });
    }
</script>
@endsection