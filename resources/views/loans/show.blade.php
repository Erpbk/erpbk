@extends('layouts.app')
@section('title', 'Loan '.$loan->loan_number)

@section('content')
@include('loans.view', ['loan' => $loan])

<div class="content">
    @include('flash::message')

    <div class="row mb-3">
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">Principal</small>
                <h5>{{ number_format($loan->principal_amount, 2) }}</h5>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">Outstanding</small>
                <h5>{{ number_format($loan->outstanding_principal, 2) }}</h5>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">EMI</small>
                <h5>{{ $loan->emi_amount ? number_format($loan->emi_amount, 2) : '-' }}</h5>
            </div></div>
        </div>
        <div class="col-md-3">
            <div class="card"><div class="card-body">
                <small class="text-muted">COA Balance</small>
                @if($loan->account_id)
                <h5>{{ number_format($coaBalance, 2) }}</h5>
                @if(abs($coaBalance - $loan->outstanding_principal) > 0.01 && $loan->status === 'active')
                <small class="text-danger">Reconciliation mismatch</small>
                @endif
                @else
                <h5 class="text-muted">—</h5>
                <small class="text-muted">Created on disbursement</small>
                @endif
            </div></div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Loan Details</h5>
            <div>
                @if($loan->status === 'draft')
                @can('loan_disburse')
                <form action="{{ route('loans.disburse', $loan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Disburse this loan and post GL entries?');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Disburse Loan</button>
                </form>
                @endcan
                @can('loan_edit')
                <form action="{{ route('loans.regenerateSchedule', $loan->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate Schedule</button>
                </form>
                @endcan
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4"><strong>Bank:</strong> {{ $loan->bank?->name }}</div>
                <div class="col-md-4"><strong>Rate:</strong> {{ number_format($loan->interest_rate, 2) }}% p.a.</div>
                <div class="col-md-4"><strong>Interest Method:</strong> {{ $loan->interest_calculation_method_label }}</div>
                <div class="col-md-4 mt-2"><strong>Term:</strong> {{ $loan->term_months }} months</div>
                <div class="col-md-4 mt-2"><strong>First Payment:</strong> {{ $loan->first_payment_date?->format('d M Y') }}</div>
                <div class="col-md-4 mt-2"><strong>Maturity:</strong> {{ $loan->maturity_date?->format('d M Y') ?? '-' }}</div>
                <div class="col-md-4 mt-2"><strong>Status:</strong> {!! $loan->status_badge !!}</div>
                @if($nextInstallment)
                <div class="col-md-4 mt-2"><strong>Next EMI:</strong> {{ $nextInstallment->due_date->format('d M Y') }} — {{ number_format($nextInstallment->total_amount, 2) }}</div>
                @endif
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h5 class="mb-0">Installment Schedule</h5></div>
        <div class="card-body table-responsive p-0">
            <table class="table table-striped mb-0">
                <thead class="text-center">
                    <tr>
                        <th>#</th>
                        <th>Due Date</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>EMI</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($loan->installments as $inst)
                    <tr class="text-center">
                        <td>{{ $inst->installment_no }}</td>
                        <td>{{ $inst->due_date->format('d M Y') }}</td>
                        <td>{{ number_format($inst->principal_amount, 2) }}</td>
                        <td>{{ number_format($inst->interest_amount, 2) }}</td>
                        <td>{{ number_format($inst->total_amount, 2) }}</td>
                        <td>{!! $inst->status_badge !!}</td>
                        <td>
                            @include('loans.partials.pay_installment_button', ['installment' => $inst, 'loan' => $loan])
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
