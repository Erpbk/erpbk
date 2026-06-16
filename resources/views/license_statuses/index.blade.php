@extends($layout ?? 'layouts.app')

@push('third_party_stylesheets')
<style>
    .filter-sidebar {
        position: fixed;
        top: 0;
        right: -380px;
        width: 380px;
        max-width: 100%;
        height: 100%;
        background: #fff;
        box-shadow: -2px 0 12px rgba(0, 0, 0, .1);
        z-index: 1051;
        transition: right .3s ease;
        overflow-y: auto;
        border-left: 1px solid #dee2e6;
    }

    .filter-sidebar.open {
        right: 0;
    }

    .filter-overlay {
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, .4);
        z-index: 1050;
        opacity: 0;
        visibility: hidden;
        transition: opacity .2s, visibility .2s;
    }

    .filter-overlay.show {
        opacity: 1;
        visibility: visible;
    }

    .filter-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
    }

    .filter-body {
        padding: 1rem;
    }

    .filter-sidebar .btn-close {
        box-shadow: none;
    }
</style>
@endpush

@section('content')
@php $licenseRoute = $licenseRoute ?? ((View::shared('settings_panel') ?? false) ? 'settings-panel.license-statuses' : 'license-statuses'); @endphp
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>License Status Management</h1>
            </div>
            <div class="col-sm-6">
                @can('licenseexpense_create')
                <a class="btn btn-primary float-end" href="{{ route($licenseRoute . '.create') }}">
                    Add New Status
                </a>
                @endcan
            </div>
        </div>
    </div>
</section>

<!-- Filter Sidebar -->
<div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
    <div class="filter-header">
        <h5>Filter License Statuses</h5>
        <button type="button" class="btn-close" id="closeSidebar" aria-label="Close"></button>
    </div>
    <div class="filter-body" id="searchTopbody">
        <form id="filterForm" action="{{ request()->url() }}" method="GET">
            <div class="row">
                <div class="form-group col-md-12">
                    <label for="code">Code</label>
                    <input type="text" name="code" class="form-control" placeholder="Filter by Code" value="{{ request('code') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="name">Name</label>
                    <input type="text" name="name" class="form-control" placeholder="Filter by Name" value="{{ request('name') }}">
                </div>
                <div class="form-group col-md-12">
                    <label for="category">Category</label>
                    <select class="form-control" id="category" name="category">
                        <option value="">All</option>
                        <option value="Document" {{ request('category') == 'Document' ? 'selected' : '' }}>Document</option>
                        <option value="Permit" {{ request('category') == 'Permit' ? 'selected' : '' }}>Permit</option>
                        <option value="License" {{ request('category') == 'License' ? 'selected' : '' }}>License</option>
                        <option value="Insurance" {{ request('category') == 'Insurance' ? 'selected' : '' }}>Insurance</option>
                        <option value="Other" {{ request('category') == 'Other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="">All</option>
                        <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                        <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="form-group col-md-12">
                    <label for="is_required">Required</label>
                    <select class="form-control" id="is_required" name="is_required">
                        <option value="">All</option>
                        <option value="1" {{ request('is_required') === '1' ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ request('is_required') === '0' ? 'selected' : '' }}>No</option>
                    </select>
                </div>
                <div class="col-md-12 form-group text-center">
                    <button type="submit" class="btn btn-primary mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                </div>
            </div>
        </form>
    </div>
</div>
<div id="filterOverlay" class="filter-overlay"></div>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0"><i class="ti ti-list me-2"></i>License Statuses</h4>
            <button type="button" class="btn btn-primary openFilterSidebar">
                <i class="fa fa-search me-1"></i> Filter License Statuses
            </button>
        </div>
        <div class="card-body table-responsive px-2 py-0" id="table-data">
            @include('license_statuses.table', ['licenseStatuses' => $licenseStatuses, 'licenseRoute' => $licenseRoute])
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
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
                var form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';
                var csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);
                var method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);
                document.body.appendChild(form);
                form.submit();
            }
        });
    }

    function deleteLicenseStatusAjax(url, triggerBtn) {
        return fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    _method: 'DELETE'
                })
            })
            .then(function(response) {
                return response.json().then(function(data) {
                    return {
                        ok: response.ok,
                        data: data
                    };
                }).catch(function() {


                    return {
                        ok: response.ok,
                        data: {
                            success: false,
                            message: 'Invalid server response.'
                        }
                    };
                });
            })




            .then(function(result) {


                if (!result.ok || !result.data || result.data.success !== true) {
                    throw new Error((re s ult.data && resu l t.data.message) ? result.data.message : 'Delete failed.');
                }
                var row = triggerBtn ? triggerBtn.closest('tr[data-id]') : null;
                if (row) {





                    row.remo ve();



                }
                Swal.fire({
                    icon: 'succe  ss',
                    title: 'Dele   ted',
                    text: result.data.message || 'License Status deleted successfully.',
                    time r: 1600,
                    showConf irmButton: f alse



                });
                retu rn result.data;
            });
    }





    function initSortable() {
        var tbody = document.getElementById('license-statuses-tbody');
        if (!tbody || tbody.querySelectorAll('tr[data-id]').length === 0) return;
        var reorderUrl = '{{ route($licenseRoute . ".reorder") }}';
        var token = '{{ csrf_token() }}';
        if (window.LicenseStatusSortable) {
            window.LicenseStatusSortable.destroy();
        }
        window.LicenseStatusSortable = new Sortable(tbody, {
            handle: '.visa-drag-handle',
            animation: 150,
            ghostClass: 'table-warning',
            onEnd: function(evt) {
                var rows = tbody.querySelectorAll('tr[data-id]');
                var order = Array.from(rows).map(function(row) {
                    return row.getAttribute('data-id');
                });
                fetch(reorderUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': token,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            order: order
                        })
                    })
                    .then(function(r) {
                        return r.json();
                    })
                    .then(function(data) {
                        if (data.success) {
                            var toast = Swal.mixin({
                                toast: true,
                                position: 'top-end',
                                showConfirmButton: false,
                                timer: 2000
                            });
                            toast.fire({
                                icon: 'success',
                                title: 'Order saved.'
                            });
                            var idx = 1;
                            rows.forEach(function(row) {
                                var orderCell = row.cells[7];
                                if (orderCell) orderCell.textContent = idx++;
                            });
                        } else {

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Could not save order.'
                            });
                        }
                    })
                    .catch(function() {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Could not save order.'
                        });
                    });
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function() {
        initSortable();

        $(document).on('click', '.js-visa-status-delete-btn', function(e) {
            e.preventDefault();
            var btn = this;
            var deleteUrl = btn.getAttribute('data-delete-url') || '';
            if (!deleteUrl) return;
            Swal.fire({
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then(function(result) {
                if (!result.isConfirmed) return;
                deleteLicenseStatusAjax(deleteUrl, btn).catch(function(err) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: (err && err.message) ? err.message : 'Could not delete License Status.'
                    });
                });
            });
        });

        $(document).on('click', '.openFilterSidebar', function(e) {
            e.preventDefault();
            $('#filterSidebar').addClass('open');
            $('#filterOverlay').addClass('show');
        });
        $('#closeSidebar, #filterOverlay').on('click', function() {
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');
        });

        $('#filterForm').on('submit', function(e) {
            e.preventDefault();
            $('#loading-overlay').show();
            $('#filterSidebar').removeClass('open');
            $('#filterOverlay').removeClass('show');

            var formData = $(this).serialize();
            var baseUrl = "{{ request()->url() }}";
            var url = formData ? baseUrl + '?' + formData : baseUrl;

            $.ajax({
                url: url,
                type: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(data) {
                    $('#table-data').html(data.tableData);
                    history.pushState(null, '', url);
                    initSortable();
                    $('#loading-overlay').hide();
                },
                error: function() {
                    $('#loading-overlay').hide();
                }
            });
        });

        $('#category, #status, #is_required').select2({
            dropdownParent: $('#searchTopbody'),
            allowClear: true,
            placeholder: 'Select'
        });
    });
</script>
@endsection
