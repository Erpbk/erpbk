@extends('customers.view')

@section('page_content')
<div class="card-action mb-0">
    @can('customers_documents_view')
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
                                @can('customers_documents_create')
                                <a class="btn btn-primary show-modal action-btn"
                                    href="javascript:void(0);"
                                    data-action="{{ route('files.create',['type_id'=> $customer->id,'type'=>'customer']) }}"
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
                @php
                    $counter = 0;
                @endphp
                <tbody id="files-table-body">
                    @foreach($files as $riderFile)
                    <tr class="file-row" data-name="{{ strtolower($riderFile->name) }}">
                        <td class="row-counter">{{ $counter++ }}</td>
                        <td class="text-start">
                            <a href="{{ storage_url($riderFile->type . '/'.$riderFile->type_id.'/'.$riderFile->file_name) }}" target="_blank">
                                {{ ucwords(str_replace('_', ' ', $riderFile->name)) }}
                            </a>
                        </td>
                        @include('partials.entity_file_expiry_cell', ['file' => $riderFile])
                        <td class="text-end">
                            @can('customers_documents_delete')
                            <a href="javascript:void(0);"
                                data-url="{{ route('files.destroy', $riderFile->id) }}"
                                target="_blank"
                                class='btn btn-danger btn-sm delete-file'>
                                <i class="fa fa-trash my-1"></i>
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                    @if(!empty($missingFiles))
                    @foreach($missingFiles as $key => $fileName)
                    <tr class="file-row" data-name="{{ strtolower($fileName) }}">
                        <td class="row-counter">{{ $counter++ }}</td>
                        <td class="text-start">{{ $fileName }}</td>
                        @include('partials.entity_file_expiry_cell')
                        <td class="text-end">
                            @can('customers_documents_create')
                            <a class="btn btn-sm btn-primary show-modal action-btn"
                                href="javascript:void(0);"
                                data-action="{{ route('files.create', [
                                            'type_id' => request()->segment(3),
                                            'type' => 'customer',
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
    @else
    <div class="alert alert-warning text-center m-3">
        <i class="fa fa-warning"></i> You don't have permission.
    </div>
    @endcan
</div>
@endsection
