@extends('layouts.app')

@section('title','Leasing Company Invoices')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Leasing Company Invoices</h3>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-size="xl" data-title="Create Leasing Company Invoice" data-action="{{ route('leasingCompanyInvoices.create') }}">
                    Create Invoice
                </a>
                <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter Leasing Company Invoices</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="{{ route('leasingCompanyInvoices.index') }}" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="leasing_company_id">Filter by Leasing Company</label>
                                            <select class="form-control" id="leasing_company_id" name="leasing_company_id">
                                                <option value="" selected>Select</option>
                                                @foreach($leasingCompanies as $company)
                                                <option value="{{ $company->id }}" {{ request('leasing_company_id') == $company->id ? 'selected' : '' }}>{{ $company->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="billing_month">Billing Month</label>
                                            <input type="month" name="billing_month" class="form-control" placeholder="Filter By Billing Month" value="{{ request('billing_month') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="status">Filter by Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="">Select</option>
                                                <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Paid</option>
                                                <option value="0" {{ request('status') == 0 ? 'selected' : '' }}>Unpaid</option>
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
            @include('leasing_company_invoices.table', [
            'data' => $data,
            ])
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
    $(document).ready(function() {
        $('#leasing_company_id').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Leasing Company",
            allowClear: true,
        });
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Filter By Status",
            allowClear: true,
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

            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);

            $.ajax({
                url: "{{ route('leasingCompanyInvoices.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);

                    let newUrl = "{{ route('leasingCompanyInvoices.index') }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);

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
@endsection
