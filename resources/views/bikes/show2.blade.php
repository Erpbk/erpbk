@extends('bikes.view')

@section('page_content')
@php
  // Show configured bike fixed/custom fields (grouped by category).
  $fieldsByCategory = $fieldsByCategory ?? \App\Models\BikeCustomField::fieldsByCategoryForForm();
@endphp

<div class="card mb-4">
  <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0">Bike Fields</h5>
    <div class="d-flex align-items-center gap-2 ms-auto">
      @php
        $mulkiyaAuthorized = auth()->user()->can('bike_document');
      @endphp
      @if($mulkiyaFile)
        <a href="{{ url('storage2/' . $mulkiyaFile->type . '/' . $mulkiyaFile->type_id . '/' . $mulkiyaFile->file_name) }}" target="_blank" class="btn btn-light btn-sm">
          <i class="ti ti-download"></i> Mulkiya
        </a>
      @elseif($mulkiyaAuthorized)
        <a class="btn btn-light btn-sm show-modal action-btn"
          href="javascript:void(0);"
          data-action="{{ route('files.create', ['type_id' => $bikes->id, 'type' => 'bike', 'suggested_name' => 'Mulkiya']) }}"
          data-size="sm"
          data-title="Upload File">
          <i class="ti ti-upload"></i> Upload Mulkiya
        </a>
      @else
        <span class="small text-white-50">No Mulkiya</span>
      @endif
    </div>
  </div>
  <div class="card-body">
    <div class="row">
      @include('bikes.show_fields_by_category', ['fieldsByCategory' => $fieldsByCategory, 'bikes' => $bikes])
    </div>
  </div>
</div>

@endsection