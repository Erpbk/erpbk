@extends('layouts.app')
@section('title','Legal Cases')
@section('content')
@php
$pendingCount = company_table('legal_cases')->where('legal_case_account_id', $account->id)->where('step_status', 'pending')->count();
$completedCount = company_table('legal_cases')->where('legal_case_account_id', $account->id)->where('step_status', 'completed')->count();
@endphp

<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Legal Case - {{ $account->name }}</h3>
                <p class="text-muted small mb-0">
                    Employee: {{ $account->employee->employee_id ?? '—' }}
                    @if($account->employee)
                    &middot; {{ $account->employee->designation ?? '' }}
                    @endif
                </p>
            </div>
            <a href="{{ route('LegalCase.index') }}" class="btn btn-outline-secondary btn-sm">
                <i class="ti ti-arrow-left me-1"></i> Back to Accounts
            </a>
        </div>
    </div>
</section>

<div class="content">
  @include('flash::message')
  <div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
      <h4 class="mb-0">Case Entries</h4>
      @can('legalcase_create')
      <a class="btn btn-primary action-btn show-modal"
        href="javascript:void(0);" data-action="{{ route('LegalCase.create' , $account->id) }}" data-size="lg" data-title="New Legal Case Entry">
        Add New Case
      </a>
      @endcan
    </div>
    <div class="totals-cards pt-3">
      <div class="total-card total-red">
        <div class="label">Pending Steps</div>
        <div class="value">{{ $pendingCount }}</div>
      </div>
      <div class="total-card total-green">
        <div class="label">Completed Steps</div>
        <div class="value">{{ $completedCount }}</div>
      </div>
    </div>
    <div class="card-body table-responsive px-2 py-0" id="table-data">
      @include('legal_cases.table', ['data' => $data])
    </div>
  </div>
</div>
@endsection
