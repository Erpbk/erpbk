@extends('layouts.app')
@section('title', 'Loan '.$loan->loan_number)

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>{{ $loan->loan_number }} <small class="text-muted">{{ $loan->bank_name ?: '' }}</small></h3>
            </div>
            <div class="col-sm-6 text-end">
                <a href="{{ route('loans.index') }}" class="btn btn-outline-secondary">
                    <i class="ti ti-arrow-left me-1"></i> Back to Loans
                </a>
            </div>
        </div>
    </div>
</section>

@include('loans.view', ['loan' => $loan])

<div class="content">
    @include('flash::message')

    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">Loan Details</h5>
            <div>
                @if($loan->status === 'draft')
                @canany(['loans_create', 'loans_edit'])
                <form action="{{ route('loans.disburse', $loan->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Disburse this loan and post GL entries?');">
                    @csrf
                    <button type="submit" class="btn btn-success btn-sm">Disburse Loan</button>
                </form>
                @endcanany
                @canany(['loans_create', 'loans_edit'])
                <form action="{{ route('loans.regenerateSchedule', $loan->id) }}" method="POST" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-outline-secondary btn-sm">Regenerate Schedule</button>
                </form>
                @endcanany
                @endif
            </div>
        </div>
        <div class="totals-cards totals-cards-single-row mt-2 mb-0">
            <div class="total-card total-blue">
                <div class="label"><i class="far fa-money-bill-alt"></i>Principal</div>
                <div class="value">{{ \App\Helpers\Currency::format($loan->principal_amount, 2) }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="fa fa-balance-scale"></i>Outstanding</div>
                <div class="value">{{ \App\Helpers\Currency::format($loan->outstanding_principal, 2) }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-calendar-check"></i>EMI</div>
                <div class="value">{{ $loan->emi_amount ? \App\Helpers\Currency::format($loan->emi_amount, 2) : '—' }}</div>
            </div>
            <div class="total-card {{ $loan->account_id && abs($coaBalance - $loan->outstanding_principal) > 0.01 && $loan->status === 'active' ? 'total-red' : 'total-green' }}">
                <div class="label"><i class="fa fa-book"></i>COA Balance</div>
                <div class="value">
                    @if($loan->account_id)
                        {{ \App\Helpers\Currency::format($coaBalance, 2) }}
                    @else
                        —
                    @endif
                </div>
            </div>
        </div>
        <div class="card-body">
            @if($loan->account_id && abs($coaBalance - $loan->outstanding_principal) > 0.01 && $loan->status === 'active')
            <p class="text-danger small mb-3"><i class="fa fa-exclamation-triangle me-1"></i>Reconciliation mismatch between COA balance and outstanding principal.</p>
            @elseif(!$loan->account_id)
            <p class="text-muted small mb-3">COA account is created on disbursement.</p>
            @endif
            @php $vf = static fn (string $f): bool => field_visible('loan', $f); @endphp
            <div class="row">
                @if($vf('bank_name'))<div class="col-md-4"><strong>Lender:</strong> {{ $loan->bank_name ?: '-' }}</div>@endif
                @if($vf('interest_rate'))<div class="col-md-4"><strong>Rate:</strong> {{ number_format($loan->interest_rate, 2) }}% p.a.</div>@endif
                <div class="col-md-4"><strong>Interest Method:</strong> {{ $loan->interest_calculation_method_label }}</div>
                <div class="col-md-4 mt-2"><strong>Term:</strong> {{ $loan->term_months }} months</div>
                <div class="col-md-4 mt-2"><strong>First Payment:</strong> {{ $loan->first_payment_date?->format('d M Y') }}</div>
                @if($vf('maturity_date'))<div class="col-md-4 mt-2"><strong>Maturity:</strong> {{ $loan->maturity_date?->format('d M Y') ?? '-' }}</div>@endif
                @if($vf('status'))<div class="col-md-4 mt-2"><strong>Status:</strong> {!! $loan->status_badge !!}</div>@endif
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
                        <th>Payment Date</th>
                        <th>Billing Month</th>
                        <th>Principal</th>
                        <th>Interest</th>
                        <th>EMI</th>
                        <th>Outstanding Principal</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($loan->installments as $inst)
                    <tr class="text-center">
                        <td>{{ $inst->installment_no }}</td>
                        <td>{{ $inst->due_date->format('d M Y') }}</td>
                        <td>{{ $inst->paid_date ? $inst->paid_date->format('d M Y') : '-' }}</td>
                        <td>{{ $inst->paid_date ? $inst->paid_date->format('M Y') : $inst->due_date->format('M Y') }}</td>
                        <td>{{ number_format($inst->principal_amount, 2) }}</td>
                        <td>{{ number_format($inst->interest_amount, 2) }}</td>
                        <td>{{ number_format($inst->total_amount, 2) }}</td>
                        <td>{{ number_format($inst->outstandingPrincipalAfter(), 2) }}</td>
                        <td>{!! $inst->status_badge !!}</td>
                        <td>
                            @include('loans.partials.pay_installment_button', ['installment' => $inst, 'loan' => $loan])
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="10" class="text-center text-muted">No installments.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
