@extends('banks.view')

@section('page_content')
<div class="card-action mb-0">
    @can('rider_document')
        <!-- FILES SECTION -->
        <div class="card mb-4 border-warning">   
            <div class="table-responsive my-3">
                <table class="table table-hover mb-0" id="files-table">
                    <thead class="table-light">
                        <tr class="row flex align-items-center m-0">
                            <div class="d-flex justify-content-between align-items-center p-3">
                                <div>
                                    <h4 class="mb-1"><i class="ti ti-file text-primary me-2"></i>Documents</h4>
                                    @isset($missingFiles)
                                        <small class="text-muted">
                                            <i class="ti ti-info-circle me-1"></i>
                                            {{ count($missingFiles) ?? 0 }} documents pending
                                        </small>
                                    @endisset
                                </div>
                                
                                <div class="text-end">
                                    <!-- Search Box -->
                                    <div class="mb-2">
                                        <div class="input-group input-group-sm" style="max-width: 250px;">
                                            <input type="text" 
                                                   class="form-control" 
                                                   id="file-search" 
                                                   placeholder="Search documents...">
                                            <button class="btn btn-outline-secondary" type="button" id="clear-search" title="Clear SearchBox">
                                                <i class="ti ti-x"></i>
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- Upload Button -->
                                    <a class="btn btn-primary show-modal action-btn"
                                       href="javascript:void(0);" 
                                       data-action="{{ route('files.create',['type_id'=>request()->segment(3),'type'=>'bank']) }}" 
                                       data-size="sm" 
                                       data-title="Upload File">
                                        <i class="ti ti-upload me-1"></i>Upload File
                                    </a>
                                </div>
                            </div>
                        </tr>
                        <tr>
                            <th width="50">#</th>
                            <th>Document</th>
                            <th width="120" class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody id="files-table-body">
                        @php $counter = 1; @endphp
                        @foreach ($files as $riderFile)
                            <tr class="file-row" data-name="{{ strtolower($riderFile->name) }}">
                                <td class="row-counter">{{ $counter++ }}</td>
                                <td>
                                    <a href="{{ url('storage2/' . $riderFile->type . '/'.$riderFile->type_id.'/'.$riderFile->file_name) }}" target="_blank" >
                                        {{ ucwords(str_replace('_', ' ', $riderFile->name)) }}
                                    </a>
                                </td>
                                <td class="text-end">
                                    <a href="javascript:void(0);" 
                                       data-url="{{ route('files.destroy', $riderFile->id) }}" 
                                       target="_blank" 
                                       class='btn btn-danger btn-sm delete-file'>
                                        <i class="fa fa-trash my-1"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach       
                        @if(!empty($missingFiles))
                            @foreach($missingFiles as $key => $fileName)
                                <tr class="file-row" data-name="{{ strtolower($fileName) }}">
                                    <td class="row-counter">{{ $counter++ }}</td>
                                    <td>{{ $fileName }}</td>
                                    <td class="text-end">
                                        <a class="btn btn-sm btn-primary show-modal action-btn"
                                            href="javascript:void(0);" 
                                            data-action="{{ route('files.create', [
                                                'type_id' => request()->segment(3),
                                                'type' => 'rider',
                                                'suggested_name' => $fileName
                                            ]) }}" 
                                            data-size="md" 
                                            data-title="Upload {{ $fileName }}">
                                            <i class="ti ti-upload"></i>
                                        </a>
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
    @endcan
    @cannot('rider_document')
        <div class="alert alert-warning text-center m-3">
            <i class="fa fa-warning"></i> You don't have permission.
        </div>
    @endcannot
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
        
        // Search functionality
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
            
            // Update row numbers for visible rows
            let counter = 1;
            rows.filter(':visible').each(function() {
                $(this).find('.row-counter').text(counter++);
            });
            
            // Show/hide no results message
            if (visibleRows === 0 && searchTerm !== '') {
                $('#no-results').show();
                $('#files-table-body').hide();
            } else {
                $('#no-results').hide();
                $('#files-table-body').show();
            }
        });

        if($('#files-table-body tr').length==0){
                $('#no-results').show();
                $('#files-table-body').hide();
            } else {
                $('#no-results').hide();
                $('#files-table-body').show();
            }
        
        // Clear search functionality
        $('#clear-search').on('click', function() {
            $('#file-search').val('');
            $('.file-row').show();
            $('#no-results').hide();
            $('#files-table-body').show();
            
            // Reset row numbers
            let counter = 1;
            $('.file-row').each(function() {
                $(this).find('.row-counter').text(counter++);
            });
        });
    });
</script>

<style>
    #file-search:focus {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
    }
    
    .file-row {
        transition: all 0.2s ease;
    }
    
    .file-row:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
</style>
@endsection
