@extends('employees.view')

@section('page-content')
@if(isset($fieldsByCategory) && count($fieldsByCategory) > 0)
    @include('employees.show_fields_by_category')
@else
    <div class="alert alert-info mb-0">No assigned employee fields found in settings.</div>
@endif
@endsection
