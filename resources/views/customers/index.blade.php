@extends('layouts.app')

@section('title','Customers')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Customers</h3>
            </div>
            <div class="col-sm-6">
                @can('customer_create')
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="lg" data-title="New Customer" data-action="{{ route('customers.create') }}">
                    Add New
                </a>
                @endcan
                <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter Customers</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="{{ route('customers.index') }}" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="name">Customers Name</label>
                                            <input type="text" name="name" class="form-control" placeholder="Filter By Customers Name" value="{{ request('name') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="company_name">Filter by Company Name</label>
                                            <select class="form-control " id="company_name" name="company_name">
                                                @php
                                                $companyname = DB::table('customers')
                                                ->whereNotNull('company_name')
                                                ->where('company_name', '!=', '')
                                                ->pluck('company_name')
                                                ->unique();
                                                @endphp
                                                <option value="" selected>Select</option>
                                                @foreach($companyname as $company)
                                                <option value="{{ $company }}" {{ request('company_name') == $company ? 'selected' : '' }}>{{ $company }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="account_id">Filter by Accounts</label>
                                            <select class="form-control " id="account_id" name="account_id">
                                                @php
                                                $accountid = DB::table('customers')
                                                ->whereNotNull('account_id')
                                                ->where('account_id', '!=', '')
                                                ->pluck('account_id')
                                                ->unique();

                                                $accounts = DB::table('accounts')
                                                ->whereIn('id', $accountid)
                                                ->select('id', 'name')
                                                ->get();
                                                @endphp
                                                <option value="" selected>Select</option>
                                                @foreach($accounts as $account)
                                                <option value="{{ $account->id }}" {{ request('account_id') == $account->id ? 'selected' : '' }}>{{ $account->name }}</option>
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
            @include('customers.table', ['data' => $data])
        </div>
    </div>
</div>

@endsection
@section('page-script')
<script type="text/javascript">
    $(document).ready(function() {
        $('#company_name').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Company Name",
            allowClear: true
        });
        $('#account_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Account Name",
            allowClear: true
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By status",
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