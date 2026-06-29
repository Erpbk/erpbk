@extends('layouts.app')
@section('title', 'Loan Documents')
@section('content')
@include('loans.view', ['loan' => $loan])
<div class="content">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <h5 class="mb-0">Documents</h5>
            <button class="btn btn-primary btn-sm" onclick="$('#uploadModal').modal('show')"><i class="fa fa-upload"></i> Upload</button>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-striped">
                <thead><tr><th>File</th><th>Uploaded</th><th></th></tr></thead>
                <tbody>
                    @forelse($files as $file)
                    <tr>
                        <td>{{ $file->file_name }}</td>
                        <td>{{ \Carbon\Carbon::parse($file->created_at)->format('d M Y') }}</td>
                        <td><a href="{{ url('storage/files/'.$file->file_path) }}" class="btn btn-sm btn-primary" target="_blank">Download</a></td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="text-center text-muted">No documents.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('files.store') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <input type="hidden" name="type" value="loan">
            <input type="hidden" name="type_id" value="{{ $loan->id }}">
            <div class="modal-header"><h5 class="modal-title">Upload Document</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body"><input type="file" name="file" class="form-control" required></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="submit" class="btn btn-primary">Upload</button>
            </div>
        </form>
    </div>
</div>
@endsection
