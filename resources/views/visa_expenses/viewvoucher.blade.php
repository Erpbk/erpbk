@extends('layouts.app')

@section('title','Traffic Fine Details')
@section('content')
@include('visa_expenses.partials.viewvoucher_body')
@endsection
@section('page-script')
@include('delete_requests._confirm_delete_script', [
    'entityName' => 'Visa Expense',
    'confirmText' => 'This will submit a delete request or move the visa expense to the Recycle Bin.',
    'method' => 'GET',
])
@endsection
