<!-- resources/views/suppliers/document.blade.php -->

@extends('suppliers.view')

@section('page_content')
    <!-- Main content -->
    <div class=" card-action mb-0">
  <div class="card-header align-items-center">
    <h5 class="card-action-title mb-0"><i class="ti ti-file-upload ti-lg text-body me-2"></i>Files</h5>
    <a class="btn btn-primary show-modal action-btn"
       href="javascript:void(0);" data-action="{{ route('files.create' ,['type_id'=>request()->segment(3),'type'=>3]) }}" data-size="sm" data-title="Upload Document">
        Add New
    </a>
  </div>
  <div class="card-body pt-0 px-2">
    @push('third_party_stylesheets')
      @include('layouts.datatables_css')
    @endpush

    <div class="card-body px-0 pt-0">
      {!! $dataTable->table(['width' => '100%', 'class' => 'table table-striped dataTable']) !!}
    </div>

    @push('third_party_scripts')
      @include('layouts.datatables_js')
      {!! $dataTable->scripts() !!}
    @endpush
  </div>
</div>

@endsection
