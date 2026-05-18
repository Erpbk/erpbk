
<form action="{{ route('rtaFines.fileupload', $fine->id) }}" method="POST" enctype="multipart/form-data" id="formajax">
    @csrf
    <div class="card-body">
        {{-- Fine Attachment --}}
        <div class="row mb-4 pb-3 border-bottom">
            <div class="col-md-6">
                <label class="font-weight-bold">Fine Document:</label>
                <a href="{{ asset('storage/' . $fine->attachment_path) }}" class="btn btn-sm btn-info ml-2" target="_blank">
                    <i class="fa fa-eye"></i> View
                </a>
            </div>
            <div class="col-md-6">
                <input type="file" name="attachment_path" class="form-control" accept=".jpg,.png,.pdf,,image/jpeg,image/png">
            </div>
        </div>
        @if($fine->attachment)

        {{-- Payment Attachment --}}
        <div class="row">
            <div class="col-md-6">
                <label class="font-weight-bold">Payment Document:</label>
                <a href="{{ asset('storage/' . $fine->attachment_path) }}" class="btn btn-sm btn-success ml-2" target="_blank">
                    <i class="fa fa-eye"></i> View
                </a>
            </div>
            <div class="col-md-6">
                <input type="file" name="attachment" class="form-control" accept=".jpg,.png,.pdf,image/jpeg,image/png">
            </div>
        </div>
        @endif

        <div class="card-footer mt-4">
            <div class="d-flex justify-content-end gap-2">
                <button class="btn btn-primary" type="submit">
                    Upload
                </button>
            </div>
        </div>
    </div>
</form>