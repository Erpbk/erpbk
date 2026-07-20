@extends('employees.view')

@section('page-content')
<div class="card-action mb-0">
    @can('employees_document_view')
        <div class="card mb-4 border-warning">
            <div class="table-responsive my-3">
                <table class="table table-hover mb-0" id="files-table">
                    <thead class="table-light">
                        <tr class="row flex align-items-center m-0">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h4 class="mb-1"><i class="ti ti-file text-primary me-2"></i>Documents</h4>
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        {{ count($missingFiles ?? []) }} documents pending
                                    </small>
                                </div>
                                <div class="text-end">
                                    <div class="mb-2">
                                        <div class="input-group input-group-sm" style="max-width: 250px;">
                                            <input type="text" class="form-control" id="file-search" placeholder="Search documents...">
                                            <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear SearchBox">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    @can('employees_document_create')
                                    <a class="btn btn-primary show-modal action-btn"
                                        href="javascript:void(0);"
                                        data-action="{{ route('files.create', ['type_id' => $employee->id, 'type' => 'employee']) }}"
                                        data-size="sm"
                                        data-title="Upload File">
                                        <i class="ti ti-upload me-1"></i>Upload File
                                    </a>
                                    @endcan
                                </div>
                            </div>
                        </tr>
                        <tr>
                            <th width="50">#</th>
                            <th class="text-start">Document</th>
                            <th width="120" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="files-table-body">
                        @php $counter = 1; @endphp
                        @foreach ($files as $employeeFile)
                            <tr class="file-row" data-name="{{ strtolower($employeeFile->name) }}">
                                <td class="row-counter">{{ $counter++ }}</td>
                                <td class="text-start">
                                    <a href="{{ storage_url($employeeFile->type . '/' . $employeeFile->type_id . '/' . $employeeFile->file_name) }}" target="_blank">
                                        {{ ucwords(str_replace('_', ' ', $employeeFile->name)) }}
                                    </a>
                                </td>
                                <td class="text-end">
                                    @can('employees_document_delete')
                                    <a href="javascript:void(0);"
                                        data-url="{{ route('files.destroy', $employeeFile->id) }}"
                                        target="_blank"
                                        class="btn btn-danger btn-sm delete-file">
                                        <i class="fa fa-trash my-1"></i>
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                        @if(!empty($missingFiles))
                            @foreach($missingFiles as $fileName)
                                <tr class="file-row" data-name="{{ strtolower($fileName) }}">
                                    <td class="row-counter">{{ $counter++ }}</td>
                                    <td class="text-start">{{ $fileName }}</td>
                                    <td class="text-end">
                                        @can('employees_document_create')
                                        <a class="btn btn-sm btn-primary show-modal action-btn"
                                            href="javascript:void(0);"
                                            data-action="{{ route('files.create', ['type_id' => $employee->id, 'type' => 'employee', 'suggested_name' => $fileName]) }}"
                                            data-size="md"
                                            data-title="Upload {{ $fileName }}">
                                            <i class="ti ti-upload"></i>
                                        </a>
                                        @endcan
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                    <tfoot id="no-results" style="display: none;">
                        <tr>
                            <td colspan="3" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="ti ti-search-off fs-4 mb-2"></i>
                                    <p class="mb-0">No documents found</p>
                                </div>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @else
        <div class="alert alert-warning text-center m-3">
            <i class="fa fa-warning"></i> You don't have permission.
        </div>
    @endcan
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script type="text/javascript">
    $(document).ready(function() {
        $(document).on('click', '.delete-file', function(e) {
            e.preventDefault();
            const url = $(this).data('url');

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
                            _token: '{{ csrf_token() }}'
                        },
                        success: function() {
                            Swal.fire('Deleted!', 'File has been deleted.', 'success').then(() => {
                                location.reload();
                            });
                        },
                        error: function() {
                            Swal.fire('Error!', 'Failed to delete file.', 'error');
                        }
                    });
                }
            });
        });

        $('#file-search').on('keyup', function() {
            const searchTerm = $(this).val().toLowerCase();
            const rows = $('.file-row');
            let visibleRows = 0;

            rows.each(function() {
                const fileName = $(this).data('name');
                if (fileName.includes(searchTerm)) {
                    $(this).show();
                    visibleRows++;
                } else {
                    $(this).hide();
                }
            });

            let counter = 1;
            rows.filter(':visible').each(function() {
                $(this).find('.row-counter').text(counter++);
            });

            if (visibleRows === 0 && searchTerm !== '') {
                $('#no-results').show();
                $('#files-table-body').hide();
            } else {
                $('#no-results').hide();
                $('#files-table-body').show();
            }
        });

        $('#clear-search').on('click', function() {
            $('#file-search').val('');
            $('.file-row').show();
            $('#no-results').hide();
            $('#files-table-body').show();

            let counter = 1;
            $('.file-row').each(function() {
                $(this).find('.row-counter').text(counter++);
            });
        });
    });
</script>
@endsection

