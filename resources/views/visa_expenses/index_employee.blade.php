@extends('layouts.app')
@section('title','Visa Expenses')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Visa Expense - {{ $account->name }}</h3>
                <p class="text-muted small mb-0">
                    Employee: {{ $account->employee->employee_id ?? '—' }}
                    @if($account->employee)
                    &middot; {{ $account->employee->name ?? '' }}
                    @endif
                    <span class="ms-1">({{ $activeRenewalCategory->name ?? 'New Visa' }})</span>
                </p>
            </div>
            <a href="{{ route('VisaExpense.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Back to Accounts
            </a>
        </div>
    </div>
</section>
<div class="mt-3">
@include('visa_expenses._entries_body')
</div>
@endsection
@section('page-script')
@include('visa_expenses._entries_scripts')
@endsection
