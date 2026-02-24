<table class="table" id="dataTableBuilder">
    <thead>
        <tr>
            <th>Code</th>
            <th>Name</th>
            <th>Contact</th>
            <th>Address</th>
            <th>Parent Branch</th>
            <th>Type</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($branches as $branch)
        <tr>
            <td>{{ $branch->code ?? '-' }}</td>
            <td>{{ $branch->name }}</td>
            <td>{{ $branch->contact ?? '-' }}</td>
            <td>{{ $branch->address }}</td>
            <td>{{ $branch->parent->name ?? '-' }}</td>
            <td><span class="badge bg-label-primary">{{ $branch->type }}</span></td>
            <td>
                @if($branch->is_active)
                    <span class="badge bg-label-success">Active</span>
                @else   
                    <span class="badge bg-label-danger">Inactive</span>
                @endif
            </td>
            <td>{{ $branch->description }}</td>
            <td style="position: relative;">
                <div class="dropdown">
                <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $branch->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $branch->id }}" style="z-index: 1050;">
                    @can('branches_edit')
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Edit {{ ucwords($branch->name) }} Branch" data-action="{{ route('branches.edit', $branch->id) }}">
                            <i class="fa fa-edit my-1"></i> Edit
                        </a>
                    @endcan
                    @can('branches_delete')
                    <a href="javascript:void(0);" class='dropdown-item waves-effect delete-branch' 
                        data-url="{{ route('branches.destroy', $branch) }}">
                        <i class="fa fa-trash my-1"></i> Delete
                    </a>
                    @endcan
                </div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#dataTableBuilder').DataTable({
        pageLength: 50,
        dom: "<'row'<'col-md-12'tr>>" +
            "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on the Actions column
        ]
    });
    $('#quickSearch').on('keyup change', function() {
        $('#dataTableBuilder').DataTable().search(this.value).draw();
    });

    // Individual branch actions
    $(document).on('click', '.delete-branch', function(e) {
        e.preventDefault();
        var url = $(this).data('url');

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
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            window.location.reload();
                        } else {
                            toastr.error('Error deleting branch.');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
                    }
                });
            }
        });
    });
});
</script>
@endsection