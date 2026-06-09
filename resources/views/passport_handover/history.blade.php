@extends('layouts.app')

@section('title', 'Passport Handover History')

@push('third_party_stylesheets')
<link href="https://fonts.googleapis.com/css2?family=Rockwell:wght@400;700&display=swap" rel="stylesheet">
<style>
    #historyTable {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.03);
    }
    #historyTable thead { background: #d4d4d6; }
    #historyTable thead th {
        color: black;
        font-weight: 500;
        font-size: 0.85rem;
        padding: 18px 15px;
        border: none;
        text-align: center;
    }
    #historyTable tbody tr { border-bottom: 1px solid rgba(0, 0, 0, 0.04); }
    #historyTable tbody tr:hover { background-color: #f8f9ff; }
    #historyTable tbody td {
        padding: 16px 15px;
        color: #4a5568;
        font-size: 0.9rem;
        text-align: center;
    }
</style>
@endpush

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <h3 class="mb-0">Passport Handover History</h3>
                <small class="text-muted">
                    {{ ucfirst($holderType) }}: {{ $person->name }}
                    ({{ $holderType === 'rider' ? ($person->rider_id ?? $person->id) : ($person->employee_id ?? $person->id) }})
                </small>
            </div>
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('passportHandover.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to List
                </a>
                @can('passport_handover_issue')
                @if(!$hasOpenIssue)
                <a href="javascript:void(0);" class="btn btn-primary show-modal"
                    data-action="{{ route('passportHandover.issueForm', ['type' => $holderType, 'id' => $holderId]) }}"
                    data-size="lg" data-title="Issue Passport">
                    <i class="ti ti-passport me-1"></i> Issue Passport
                </a>
                @endif
                @endcan
                @can('passport_handover_return')
                @if($hasOpenIssue)
                <a href="javascript:void(0);" class="btn btn-warning show-modal"
                    data-action="{{ route('passportHandover.returnForm', ['type' => $holderType, 'id' => $holderId]) }}"
                    data-size="lg" data-title="Return Passport">
                    <i class="ti ti-arrow-back-up me-1"></i> Return Passport
                </a>
                @endif
                @endcan
            </div>
        </div>
    </div>
</section>

<div class="content">
    @include('flash::message')

    <div class="card mb-3">
        <div class="card-body">
            <div class="row">
                <div class="col-md-3"><strong>Passport Holder:</strong> {{ $person->name }}</div>
                <div class="col-md-3"><strong>Passport No:</strong> {{ $person->passport ?? '-' }}</div>
                <div class="col-md-3"><strong>Type:</strong> {{ ucfirst($holderType) }}</div>
                <div class="col-md-3">
                    <strong>Current Status:</strong>
                    @if($hasOpenIssue)
                    <span class="badge bg-warning">Issued</span>
                    @else
                    <span class="badge bg-success">Returned / None</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Movement Log</h5>
        </div>
        <div class="card-body table-responsive">
            @include('passport_handover.table', ['histories' => $histories])
        </div>
    </div>
</div>
@endsection
