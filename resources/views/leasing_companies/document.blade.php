@extends('leasing_companies.view')

@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <h5>Documents</h5>
                <button class="btn btn-primary btn-sm" onclick="$('#uploadModal').modal('show')">
                    <i class="fa fa-upload"></i> Upload Document
                </button>
            </div>
            <div class="card-body table-responsive py-0">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>File Name</th>
                            <th>Uploaded Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($files as $file)
                        <tr>
                            <td>{{ $file->file_name }}</td>
                            <td>{{ \Carbon\Carbon::parse($file->created_at)->format('d M Y') }}</td>
                            <td>
                                <a href="{{ url('storage/files/' . $file->file_path) }}" class="btn btn-sm btn-primary" target="_blank">
                                    <i class="fa fa-download"></i> Download
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center">No documents found</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Upload Document</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="type" value="leasing_company">
                    <input type="hidden" name="type_id" value="{{ $leasingCompany->id }}">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Select File</label>
                            <input type="file" name="file" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Upload</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
