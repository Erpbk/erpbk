@extends($layout ?? 'layouts.app')

@section('content')
@php $visaRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses'; @endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Visa Status Management</h1>
            </div>
            <div class="col-sm-6">
                @can('visaexpense_create')
                <a class="btn btn-primary float-end" href="{{ route($visaRoute . '.create') }}">
                    Add New Status
                </a>
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped" id="visa-statuses-table">
                    <thead>
                        <tr>
                            <th style="width: 32px;" class="text-center" title="Drag to reorder"></th>
                            <th class="sorting">Code</th>
                            <th class="sorting">Name</th>
                            <th class="sorting">Category</th>
                            <th class="sorting">Default Fee</th>
                            <th class="sorting">Required</th>
                            <th class="sorting">Status</th>
                            <th class="sorting">Display Order</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="visa-statuses-tbody">
                        @foreach($visaStatuses as $status)
                        <tr data-id="{{ $status->id }}">
                            <td class="text-center visa-drag-handle" style="cursor: grab; user-select: none;">
                                <i class="ti ti-grip-vertical ti-sm text-muted"></i>
                            </td>
                            <td>{{ $status->code ?? 'N/A' }}</td>
                            <td>{{ $status->name }}</td>
                            <td>{{ $status->category }}</td>
                            <td>{{ number_format($status->default_fee, 2) }}</td>
                            <td>
                                <span class="badge bg-{{ $status->is_required ? 'primary' : 'secondary' }}">
                                    {{ $status->is_required ? 'Yes' : 'No' }}
                                </span>
                            </td>
                            <td>
                                <span class="badge bg-{{ $status->is_active ? 'success' : 'danger' }}">
                                    {{ $status->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td>{{ $status->display_order }}</td>
                            <td>
                                <div class='btn-group'>
                                    @can('visaexpense_edit')
                                    <a href="{{ route($visaRoute . '.edit', $status->id) }}" class='btn btn-sm btn-primary'>
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route($visaRoute . '.toggle-active', $status->id) }}" class='btn btn-sm btn-{{ $status->is_active ? 'warning' : 'success' }}' title="{{ $status->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="fas fa-{{ $status->is_active ? 'ban' : 'check' }}"></i>
                                    </a>
                                    @endcan
                                    @can('visaexpense_delete')
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route($visaRoute . '.destroy', $status->id) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $status->id }}" action="{{ route($visaRoute . '.destroy', $status->id) }}" method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
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

    document.addEventListener('DOMContentLoaded', function() {
        var tbody = document.getElementById('visa-statuses-tbody');
        if (!tbody) return;
        var reorderUrl = '{{ route($visaRoute . ".reorder") }}';
        var token = '{{ csrf_token() }}';

        new Sortable(tbody, {
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
                                var orderCell = row.cells[8];
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
    });
</script>
@endsection