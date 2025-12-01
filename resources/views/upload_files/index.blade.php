@extends('layouts.app')

@section('title', 'Uploaded Files')

@section('content')
<section class="content-header">
  <div class="container-fluid">
    <div class="row mb-2">
      <div class="col-sm-6">
        <h3>Uploaded Files</h3>
      </div>
      <div class="col-sm-6 text-end">
        <a class="btn btn-primary action-btn show-modal"
           href="javascript:void(0);"
           data-size="lg"
           data-title="Upload File"
           data-action="{{ route('upload_files.create') }}">
          Upload File
        </a>
      </div>
    </div>
  </div>
</section>

<div class="content px-0">
  <div class="card">
    @include('upload_files.table')
  </div>
</div>
@endsection
