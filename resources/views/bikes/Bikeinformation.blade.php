@extends('bikes.edit')
@section('page_content')
<div class="card-header align-items-center">
  <h5 class="card-action-title mb-0"><i class="ti ti-file-upload ti-lg text-body me-2"></i>Files</h5>
  <a class="btn btn-primary show-modal action-btn"
   href="{{ route('bikes.edit') }}" >
    Upload File
</a>
</div>
@endsection
