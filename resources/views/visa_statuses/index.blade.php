@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Visa Status Management</h1>
            </div>
            <div class="col-sm-6">
                @can('visaexpense_create')
                <a class="btn btn-primary float-end" href="{{ route('visa-statuses.create') }}">
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
                <table class="table table-striped" id="dataTableBuilder">
                    <thead>
                        <tr>
                            <th class="sorting">ID</th>
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
                    <tbody>
                        @foreach($visaStatuses as $status)
                        <tr>
                            <td>{{ $status->id }}</td>
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
                                    <a href="{{ route('visa-statuses.edit', $status->id) }}" class='btn btn-sm btn-primary'>
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="{{ route('visa-statuses.toggle-active', $status->id) }}" class='btn btn-sm btn-{{ $status->is_active ? 'warning' : 'success' }}' title="{{ $status->is_active ? 'Deactivate' : 'Activate' }}">
                                        <i class="fas fa-{{ $status->is_active ? 'ban' : 'check' }}"></i>
                                    </a>
                                    @endcan
                                    @can('visaexpense_delete')
                                    <button type="button" class="btn btn-sm btn-danger" onclick="confirmDelete('{{ route('visa-statuses.destroy', $status->id) }}')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <form id="delete-form-{{ $status->id }}" action="{{ route('visa-statuses.destroy', $status->id) }}" method="POST" style="display: none;">
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
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = url;
                form.style.display = 'none';

                const csrfToken = document.createElement('input');
                csrfToken.type = 'hidden';
                csrfToken.name = '_token';
                csrfToken.value = '{{ csrf_token() }}';
                form.appendChild(csrfToken);

                const method = document.createElement('input');
                method.type = 'hidden';
                method.name = '_method';
                method.value = 'DELETE';
                form.appendChild(method);

                document.body.appendChild(form);
                form.submit();
            }
        });
    }
</script>
@endsection