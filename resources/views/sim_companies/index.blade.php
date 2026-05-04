@extends('layouts.app')

@section('title','SIM Companies')
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>SIM Companies</h3>
            </div>
            <div class="col-sm-6">
                @can('sim_create')
                <a class="btn btn-primary float-right show-modal action-btn"
                    href="javascript:void(0);" data-action="{{ route('simCompanies.create') }}" data-title="Add SIM company" data-size="lg">
                    Add New
                </a>
                @endcan
                <div class="modal modal-default filtetmodal fade" id="searchModal" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
                    <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Filter SIM companies</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body" id="searchTopbody">
                                <form id="filterForm" action="{{ route('simCompanies.index') }}" method="GET">
                                    <div class="row">
                                        <div class="form-group col-md-4">
                                            <label for="name">Name</label>
                                            <input type="text" name="name" class="form-control" value="{{ request('name') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="email">Email</label>
                                            <input type="text" name="email" class="form-control" value="{{ request('email') }}">
                                        </div>
                                        <div class="form-group col-md-4">
                                            <label for="status">Status</label>
                                            <select class="form-control" id="status" name="status">
                                                <option value="" selected>All</option>
                                                <option value="1" {{ request('status') == 1 ? 'selected' : '' }}>Active</option>
                                                <option value="2" {{ request('status') == 2 ? 'selected' : '' }}>Inactive</option>
                                            </select>
                                        </div>
                                        @if(auth()->user()->hasMultiplebranches())
                                        <div class="form-group col-md-4">
                                            <label for="branch_id">Branch</label>
                                            <select class="form-control" id="branch_id" name="branch_id">
                                                @foreach(auth()->user()->branchDropdown() as $id => $name)
                                                <option value="{{ $id }}" {{ request('branch_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        @endif
                                        <div class="col-md-12 form-group text-center">
                                            <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter</button>
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
            @include('sim_companies.table', ['data' => $data])
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
            text: "This will move the SIM company to the Recycle Bin!",
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
                    data: { _token: '{{ csrf_token() }}' },
                    success: function(response) {
                        $('#loading-overlay').hide();
                        Swal.fire({
                            icon: 'success',
                            title: 'Deleted!',
                            html: response.message,
                            showConfirmButton: true,
                            confirmButtonText: 'OK'
                        }).then(() => { location.reload(); });
                    },
                    error: function(xhr) {
                        $('#loading-overlay').hide();
                        let errorMessage = 'An error occurred while deleting.';
                        if (xhr.responseJSON && xhr.responseJSON.errors) {
                            errorMessage = Object.values(xhr.responseJSON.errors).join('<br>');
                        } else if (xhr.responseJSON && xhr.responseJSON.message) {
                            errorMessage = xhr.responseJSON.message;
                        }
                        Swal.fire({ icon: 'error', title: 'Error!', html: errorMessage });
                    }
                });
            }
        });
    }
    $(document).ready(function() {
        $('#status').select2({
            dropdownParent: $('#searchTopbody'),
            placeholder: "Status",
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
            let filteredFields = $(this).serializeArray().filter(field => field.name !== '_token' && field.value.trim() !== '');
            let formData = $.param(filteredFields);
            $.ajax({
                url: "{{ route('simCompanies.index') }}",
                type: "GET",
                data: formData,
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    let newUrl = "{{ route('simCompanies.index') }}" + (formData ? '?' + formData : '');
                    history.pushState(null, '', newUrl);
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 500 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                },
                error: function() {
                    const elapsed = Date.now() - loaderStartTime;
                    const remaining = 500 - elapsed;
                    setTimeout(() => $('#loading-overlay').hide(), remaining > 0 ? remaining : 0);
                }
            });
        });
    });
</script>
@endsection
