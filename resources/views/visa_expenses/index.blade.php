@extends('riders.view')
@section('title','Visa Expenses')
@section('page_content')
@include('visa_expenses._entries_body')
@endsection
@section('page-script')
@include('visa_expenses._entries_scripts')
@endsection
