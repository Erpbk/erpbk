@extends('bikes.view')

@section('page_content')

<div class=" card-action mb-0">
    @can('bikes_documents_view')

    <!--FILES SECTION -->
    <div class="card mb-4 border-warning">
        <div class="table-responsive my-3">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr class="row flex align-items-center m-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <div>
                                <h4 class="mb-1"><i class="ti ti-file text-primary me-2"></i>Documents</h4>
                                <small class="text-muted">
                                    <i class="ti ti-info-circle me-1"></i>
                                    {{ count($missingFiles) ?? 0 }} documents pending
                                </small>
                            </div>
                            @can('bikes_documents_create')
                            <a class="btn btn-primary show-modal action-btn"
                                href="javascript:void(0);"
                                data-action="{{ route('files.create',['type_id'=>$bikes->id,'type'=>'bike']) }}"
                                data-size="sm"
                                data-title="Upload File">
                                <i class="ti ti-upload me-1"></i>Upload File
                            </a>
                            @endcan
                        </div>
                    </tr>
                    <tr>
                        <th width="50">#</th>
                        <th class="text-start">Document</th>
                        <th width="120" class="text-end">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @php $counter = 1; @endphp
                    @foreach ($files as $file)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td class="text-start">
                            <a href="{{ storage_url($file->type . '/'.$file->type_id.'/'.$file->file_name) }}" target="_blank">
                                {{ ucwords(str_replace('_', ' ', $file->name)) }}
                            </a>
                        </td>
                        <td class="text-end">
                            @can('bikes_documents_delete')
                            <a href="javascript:void(0);" data-url="{{ route('files.destroy', $file->id) }}" target="_blank" class='btn btn-danger btn-sm delete-file'>
                                <i class="fa fa-trash my-1"></i>
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                    @if(!empty($missingFiles))
                    @foreach($missingFiles as $key => $fileName)
                    <tr>
                        <td>{{ $counter++ }}</td>
                        <td class="text-start">{{ $fileName }}</td>
                        <td class="text-end">
                            @can('bikes_documents_create')
                            <a class="btn btn-sm btn-primary show-modal action-btn"
                                href="javascript:void(0);"
                                data-action="{{ route('files.create', [
                                                'type_id' => $bikes->id,
                                                'type' => 'bike',
                                                'suggested_name' => $fileName
                                            ]) }}"
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
            </table>
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">You are not authorized to access this page</h5>
        </div>
    </div>
    @endcan
</div>

@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script type="text/javascript">
    $(document).ready(function() {
        // Handle delete file functionality with AJAX
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
                    // Send AJAX DELETE request
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'File has been deleted.',
                                'success'
                            ).then(() => {
                                // Reload the page to update the list
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete file.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@endsection