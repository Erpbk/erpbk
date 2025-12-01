@extends('layouts.app')

@section('title','Supplier Invoices')
@section('content')
    <section class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h3>supplier Invoices</h3>
                </div>
                <div class="col-sm-6">
                    <a class="btn btn-success action-btn show-modal"
                       href="javascript:void(0);" data-size="sm" data-title="Import supplier Invoices" data-action="{{ route('supplier_invoices.import') }}" >
                        Import Invoices
                    </a>
                    <a class="btn btn-primary action-btn show-modal me-2"
                       href="javascript:void(0);" data-size="xl" data-title="Add Supplier Invoice" data-action="{{ route('supplierInvoices.create') }}">
                        Add New
                    </a>
                    <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
                       <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                          <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">Filter Suppliers Invoices</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                             <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="{{ route('supplierInvoices.index') }}" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="inv_id">Invoice ID</label>
                                            <input type="text" name="inv_id" class="form-control" placeholder="Filter By Invoice ID" value="{{ request('inv_id') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="inv_date_from">Invoice Date From</label>
                                            <input type="date" name="inv_date_from" class="form-control" placeholder="Filter By Invoice Date From" value="{{ request('inv_date_from') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="inv_date_to">Invoice Date To</label>
                                            <input type="date" name="inv_date_to" class="form-control" placeholder="Filter By Invoice Date To" value="{{ request('inv_date_to') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="supplier_id">Filter by Supplier</label>
                                            <select class="form-control " id="supplier_id" name="supplier_id">
                                                @php
                                                $supplierid = DB::table('supplier_invoices')
                                                    ->whereNotNull('supplier_id')
                                                    ->where('supplier_id', '!=', '')
                                                    ->pluck('supplier_id')
                                                    ->unique();
                                                $suppliers = DB::table('suppliers')
                                                    ->whereIn('id', $supplierid)
                                                    ->select('id', 'name')
                                                    ->get();
                                                @endphp
                                                <option value="" selected>Select</option>
                                                @foreach($suppliers as $supplier)
                                                    <option value="{{ $supplier->id }}" {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}>{{ $supplier->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="billing_month">Billing Month</label>
                                            <input type="month" name="billing_month" class="form-control" placeholder="Filter By Billing Month" value="{{ request('billing_month') }}">
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
            <div class="card-body table-responsive px-2 py-0"  id="table-data">
                @include('supplier_invoices.table', ['data' => $data])
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
$(document).ready(function () {
    $('#supplier_id').select2({
        dropdownParent: $('#searchTopbody'),
        placeholder: "Filter By Supplier",
            allowClear: true
    });
});
</script>

<script type="text/javascript">
$(document).ready(function () {
    $('#filterForm').on('submit', function (e) {
        e.preventDefault();

        $('#loading-overlay').show();
        $('#searchModal').modal('hide');

        const loaderStartTime = Date.now();

        // Exclude _token and empty fields
        let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
        let formData = $.param(filteredFields);

        $.ajax({
            url: "{{ route('supplierInvoices.index') }}",
            type: "GET",
            data: formData,
            success: function (data) {
                $('#table-data').html(data.tableData);

                // Update URL
                let newUrl = "{{ route('supplierInvoices.index') }}" + (formData ? '?' + formData : '');
                history.pushState(null, '', newUrl);

                
                // Ensure loader is visible at least 3s
                const elapsed = Date.now() - loaderStartTime;
                const remaining = 1000 - elapsed;
                setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
            },
            error: function (xhr, status, error) {
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
  document.addEventListener('DOMContentLoaded', function () {
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


