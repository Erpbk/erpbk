@extends('loans.viewindex')
@section('page_actions')
<a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">
    <i class="ti ti-arrow-left me-1"></i> Back to Loans
</a>
@endsection
@section('page_content')
<div class="content py-1">
    @include('flash::message')
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Upcoming Installments ({{ $days }} days)</h5>
            <div>
                <a href="{{ route('loans.upcomingInstallments', ['days' => 7]) }}" class="btn btn-sm {{ $days == 7 ? 'btn-primary' : 'btn-outline-primary' }}">7 days</a>
                <a href="{{ route('loans.upcomingInstallments', ['days' => 30]) }}" class="btn btn-sm {{ $days == 30 ? 'btn-primary' : 'btn-outline-primary' }}">30 days</a>
            </div>
        </div>
        <div class="card-body table-responsive py-0">
            @include('loans.upcoming_table', ['data' => $data])
        </div>
    </div>
</div>
@endsection
