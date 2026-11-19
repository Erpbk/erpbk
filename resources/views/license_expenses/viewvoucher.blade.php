@extends('layouts.app')

@section('title','Traffic Fine Details')
@section('content')
@include('license_expenses.partials.viewvoucher_body')
@endsection
@section('page-script')
@include('delete_requests._confirm_delete_script', [
    'entityName' => 'License Expense',
    'confirmText' => 'This will submit a delete request or move the license expense to the Recycle Bin.',
    'method' => 'GET',
])
@endsection
