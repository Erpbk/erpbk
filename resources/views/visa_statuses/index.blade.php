@extends($layout ?? 'layouts.app')

@push('third_party_stylesheets')
@include('visa_statuses._styles')
@endpush

@section('content')
@include('visa_statuses._workspace')
@endsection

@section('page-script')
@include('visa_statuses._workspace_script')
@endsection
