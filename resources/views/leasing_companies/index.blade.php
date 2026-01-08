@extends('layouts.app')

@section('title','Leasing Companies')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Leasing Companies</h3>
            </div>
            <div class="col-sm-6">
                @can('leasing_create')
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="md" data-title="New" data-action="{{ route('leasingCompanies.create') }}">
                    Add New
                </a>
                @endcan
                <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter Leasing Companies</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="{{ route('leasingCompanies.index') }}" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="name">Name</label>
                                            <input type="text" name="name" class="form-control" placeholder="Filter By Name" value="{{ request('name') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="contact_person">Filter by Contact Person</label>
                                            <select class="form-control " id="contact_person" name="contact_person">
                                                @php
                                                $leasingcompanies = DB::table('leasing_companies')
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
                                        <div class="form-group col-md-4">
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">

    @include('flash::message')

    <div class="clearfix"></div>

    <div class="card">
        <div class="card-body table-responsive px-2 py-0" id="table-data">
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
            text: "This will move the leasing company to the Recycle Bin!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                $('#loading-overlay').show();

                // Send DELETE request via AJAX
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        $('#loading-overlay').hide();

                        // Show success message with HTML content
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            html: response.message,
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload the page to refresh the table
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
        })
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