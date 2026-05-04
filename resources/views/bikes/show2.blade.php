@extends('bikes.view')

@section('page_content')
@php
// Show configured bike fixed/custom fields (grouped by category).
$fieldsByCategory = $fieldsByCategory ?? \App\Models\BikeCustomField::fieldsByCategoryForForm();
@endphp



@include('bikes.show_fields_by_category', ['fieldsByCategory' => $fieldsByCategory, 'bikes' => $bikes])


@endsection