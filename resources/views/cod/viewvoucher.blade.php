@extends('layouts.app')

@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>COD Voucher</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-default float-right" href="{{ route('cod.index') }}">
                    Back
                </a>
                <button class="btn btn-primary float-right mr-2" onclick="window.print()">
                    <i class="fas fa-print"></i> Print
                </button>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">COD Voucher #{{ $cod->id }}</h3>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>COD Details</h5>
                    <p><strong>Rider:</strong> {{ $cod->rider ? $cod->rider->rider_id . ' - ' . $cod->rider->name : 'N/A' }}</p>
                    <p><strong>Transaction Date:</strong> {{ App\Helpers\General::DateFormat($cod->transaction_date) }}</p>
                    <p><strong>Amount:</strong> AED {{ number_format($cod->amount, 2) }}</p>
                    <p><strong>Status:</strong>
                        <span class="badge badge-{{ $cod->status == 'paid' ? 'success' : ($cod->status == 'unpaid' ? 'danger' : 'warning') }}">
                            {{ ucfirst($cod->status) }}
                        </span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h5>Transaction Details</h5>
                    <p><strong>Billing Month:</strong> {{ $cod->billing_month ? \Carbon\Carbon::parse($cod->billing_month)->format('M Y') : 'N/A' }}</p>
                    <p><strong>Description:</strong> {{ $cod->description ?? 'N/A' }}</p>
                </div>
            </div>

            <h5>Accounting Entries</h5>
            <div class="table-responsive">
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Sr</th>
                            <th>Account Name</th>
                            <th>Particulars</th>
                            <th>Debit</th>
                            <th>Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php
                        $transactions = $cod->transactions;
                        $srNo = 1;
                        @endphp
                        @foreach($transactions as $transaction)
                        <tr>
                            <td>{{ $srNo++ }}</td>
                            <td>{{ $transaction->account ? $transaction->account->account_code . ' - ' . $transaction->account->name : 'N/A' }}</td>
                            <td>{{ $transaction->narration }}</td>
                            <td>{{ $transaction->debit > 0 ? number_format($transaction->debit, 2) : '0.00' }}</td>
                            <td>{{ $transaction->credit > 0 ? number_format($transaction->credit, 2) : '0.00' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr class="font-weight-bold">
                            <td colspan="3" class="text-right">Total:</td>
                            <td>{{ number_format($transactions->sum('debit'), 2) }}</td>
                            <td>{{ number_format($transactions->sum('credit'), 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .btn,
        .card-header,
        .content-header,
        .navbar,
        .sidebar {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }
    }
</style>
@endsection