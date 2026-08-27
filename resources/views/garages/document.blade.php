@extends('garages.view')

@section('page_content')
<div class="card-action mb-0">
    <div class="card mb-4 border-warning">
        <div class="table-responsive my-3">
            <table class="table table-hover mb-0" id="files-table">
                <thead class="table-light">
                    <tr class="row flex align-items-center m-0">
                        <div class="d-flex justify-content-between align-items-center p-3">
                            <div>
                                <h4 class="mb-1"><i class="ti ti-file text-primary me-2"></i>Documents</h4>
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
                                @can('garages_documents_create')
                                <a class="btn btn-primary show-modal action-btn"
                                    href="javascript:void(0);"
                                    data-action="{{ route('files.create', ['type_id' => $garages->id, 'type' => 'garage']) }}"
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
                        <th class="text-start">Expiry</th>
                        <th width="120" class="text-end">Action</th>
                    </tr>
                </thead>
                @php $counter = 0; @endphp
                <tbody id="files-table-body">
                    @foreach($files as $file)
                    <tr class="file-row" data-name="{{ strtolower($file->name) }}">
                        <td class="row-counter">{{ $counter++ }}</td>
                        <td class="text-start">
                            <a href="{{ storage_url($file->type . '/' . $file->type_id . '/' . $file->file_name) }}" target="_blank">
                                {{ ucwords(str_replace('_', ' ', $file->name)) }}
                            </a>
                        </td>
                        @include('partials.entity_file_expiry_cell', ['file' => $file])
                        <td class="text-end">
                            @can('garages_documents_delete')
                            <a href="javascript:void(0);"
                                data-url="{{ route('files.destroy', $file->id) }}"
                                class="btn btn-danger btn-sm delete-file">
                                <i class="fa fa-trash my-1"></i>
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot id="no-results" style="display: none;">
                    <tr>
                        <td colspan="4" class="text-center py-4">
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
</div>
@endsection

@section('page-script')
@include('partials.entity_files_table_script')
@endsection
