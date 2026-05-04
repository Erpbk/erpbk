@extends('bikes.view')

@section('page_content')
@php
// Show configured bike fixed/custom fields (grouped by category).
$fieldsByCategory = $fieldsByCategory ?? \App\Models\BikeCustomField::fieldsByCategoryForForm();
@endphp

<div class="card mb-4">
  <div class="card-header bg-primary text-white d-flex align-items-center justify-content-between flex-wrap gap-2">
    <h5 class="mb-0">Bike Fields</h5>
  </div>
  <div class="card-body">
    <div class="row">
      @include('bikes.show_fields_by_category', ['fieldsByCategory' => $fieldsByCategory, 'bikes' => $bikes])
    </div>
  </div>
</div>

@endsection