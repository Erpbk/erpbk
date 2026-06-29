@extends('leasing_companies.viewindex')
@section('page_content')

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter Leasing Companies</h5>
        <button type="button" class="btn-close" id="closeSidebar"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ route('leasingCompanies.index') }}" method="GET">
            <div class="row">
                @if(auth()->user()->hasMultiplebranches())
                <div class="form-group col-md-12">
                    <label for="branch_id">Filter by Branch</label>
                    <select class="form-control " id="branch_id" name="branch_id">
                        @foreach(auth()->user()->branchDropdown() as $id => $name)
                        <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group col-md-12">
                    <label for="name">Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="{{ request('name') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="contact_person">Filter by Contact Person</label>
                    <select class="form-control " id="contact_person" name="contact_person">
                        @php
                        $leasingcompanies = company_table('leasing_companies')
                        ->whereNotNull('contact_person')
                        ->where('contact_person', '!=', '')
                        ->pluck('contact_person')
                        ->unique();
                        @endphp
                        <option value="" selected>Select</option>
                        @foreach($leasingcompanies as $leasingcompany)
                        <option value="{{ $leasingcompany }}" {{ request('contact_person') == $leasingcompany ? 'selected' : '' }}>{{ $leasingcompany }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Filter by Status</label>
                    <select class="form-control " id="status" name="status">
                        <option value="" selected>Select</option>
                        <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                        <option value="3" {{ request('status') == 3 ? 'selected' : '' }}>In Active</option>
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

<div class="content py-1">
    @include('flash::message')
    <div class="clearfix"></div>

    <div class="card">
        <div class="card-header text-end">
            <button class="btn btn-primary openFilterSidebar"> <i class="fa fa-search"></i> Filter Leasing Companies</button>
        </div>
        <div class="card-body table-responsive py-0" id="table-data">
            @include('leasing_companies.table', ['data' => $data])
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    function confirmDelete(url) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will move the record to the Recycle Bin!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $('#loading-overlay').show();
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#loading-overlay').hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            html: response.message,
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function(xhr) {
                        $('#loading-overlay').hide();
                        let errorMessage = 'An error occurred while deleting.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            html: errorMessage
                        });
                    }
                });
            }
        });
    }
    $(document).ready(function() {
        $('#contact_person').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Contact Person",
            allowClear: true
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Status",
            allowClear: true
        });
        $('#branch_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By branch",
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
                url: "{{ route('leasingCompanies.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);

                    // Update URL
                    let newUrl = "{{ route('leasingCompanies.index') }}" + (formData ? '?' + formData : '');
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

        // Open filter sidebar
        $('.openFilterSidebar').on('click', function() {
            $('#filterSidebar').addClass('active');
            $('#filterOverlay').addClass('active');
        });

        // Close filter sidebar
        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('active');
            $('#filterOverlay').removeClass('active');
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

    // Add Rider Dropdown Toggle
    $('#addLeasingCompanyDropdownBtn').on('click', function(e) {
        e.stopPropagation();
        const dropdown = $('#addLeasingCompanyDropdown');
        const btn = $(this);

        if (dropdown.hasClass('show')) {
            dropdown.removeClass('show');
            btn.removeClass('open');
        } else {
            // Close other dropdowns
            $('.action-dropdown-menu').removeClass('show');
            $('.action-dropdown-btn').removeClass('open');
            // Show this dropdown
            dropdown.addClass('show');
            btn.addClass('open');
        }
    });
</script>

@endsection