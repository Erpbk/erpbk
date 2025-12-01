@extends('upload_files.view')

@section('page_content')
<div class="card p-4 shadow-sm">
  <div class="row">
    <div class="form-group col-md-6">
      <label>File Name:</label>
      <p>{{ $file->name }}</p>
    </div>
    <div class="form-group col-md-6">
      <label>Uploaded By:</label>
      <p>{{$file->uploader->name}}</p>
    </div>
    <div class="form-group col-md-6">
      <label>Uploaded At:</label>
      <p>{{ $file->created_at->format('d M Y, h:i A') }}</p>
    </div>
    <div class="form-group col-md-6">
      <label>Details:</label>
      <p>{{ $file->details }}</p>
    </div>
   <div class="form-group col-md-12">
  <div style="text-align:center;"><label>File Preview:</label><br></div>
  @php
    $fileUrl = asset('storage/' . $file->path);
    $extension = strtolower(pathinfo($file->path, PATHINFO_EXTENSION));
@endphp

{{-- Inline Preview --}}
<div class="file-preview mt-4 text-center">
    @if ($extension === 'pdf')
        <object data="{{ $fileUrl }}" type="application/pdf" width="100%" height="600px">
            <p>
                PDF preview not supported by your browser. 
                <a href="{{ $fileUrl }}" target="_blank">Open PDF</a>
            </p>
        </object>
    @elseif(in_array($extension, ['doc', 'docx']))
        <iframe 
            src="https://view.officeapps.live.com/op/embed.aspx?src={{ urlencode($fileUrl) }}" 
            width="100%" 
            height="600px" 
            frameborder="0">
        </iframe>
    @elseif(in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp']))
        <img src="{{ $fileUrl }}" alt="Image Preview" style="max-width: 100%; height: auto; border: 1px solid #ccc; padding: 5px;">
    @else
        <p>Preview not available for this file type.</p>
    @endif
</div>

{{-- Download Link --}}
<div class="mt-3 text-center">
    <a href="{{ $fileUrl }}" class="btn btn-primary" download>Download File</a>
</div>

</div>
  </div>
</div>
@endsection
