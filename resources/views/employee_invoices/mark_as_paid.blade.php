@extends('layouts.app')
@section('title','Mark Employee Invoice as Paid')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Record Invoice Payment</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-secondary float-right" href="{{ route('employeeInvoices.index') }}">
                    <i class="fas fa-arrow-left"></i> Back to Invoices
                </a>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Invoice Payment Details</h3>
        </div>
        <div class="card-body">
            @php
                $employee = $invoice->employee;
                $dueAmount = max((float) ($outstanding['balance'] ?? 0), 0);
                $paidSoFar = (float) ($outstanding['paid_amount'] ?? 0);
            @endphp
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Invoice Information</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Invoice:</strong></td>
                            <td>{{ $invoice->invoice_number }}</td>
                        </tr>
                        <tr>
                            <td><strong>Invoice Date:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($invoice->inv_date)->format('d M Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Billing Month:</strong></td>
                            <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}</td>
                        </tr>
                        <tr>
                            <td><strong>Description:</strong></td>
                            <td>{{ $invoice->descriptions ?? '-' }}</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Employee Information</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Employee ID:</strong></td>
                            <td>{{ $employee->employee_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Name:</strong></td>
                            <td>{{ $employee->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Payable:</strong></td>
                            <td><span class="badge bg-primary fs-6">{{ \App\Helpers\Currency::format($outstanding['final_amount'] ?? 0, 2) }}</span></td>
                        </tr>
                        @if($paidSoFar > 0)
                        <tr>
                            <td><strong>Amount Paid:</strong></td>
                            <td><span class="badge bg-info fs-6">{{ \App\Helpers\Currency::format($paidSoFar, 2) }}</span></td>
                        </tr>
                        @endif
                        <tr>
                            <td><strong>Amount Due:</strong></td>
                            <td><span class="badge bg-warning fs-6">{{ \App\Helpers\Currency::format($dueAmount, 2) }}</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr>

            <form action="{{ route('employeeInvoices.markAsPaid', $invoice->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_account_id" class="required">Select Bank Account <span class="text-danger">*</span></label>
                            <select class="form-control @error('bank_account_id') is-invalid @enderror"
                                id="bank_account_id" name="bank_account_id" required>
                                <option value="">-- Select Bank Account --</option>
                                @foreach($bankAccounts as $accountId => $name)
                                @if($accountId === '')
                                    @continue
                                @endif
                                <option value="{{ $accountId }}" {{ old('bank_account_id') == $accountId ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                                @endforeach
                            </select>
                            @error('bank_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="payment_amount" class="required">Payment Amount <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="{{ $dueAmount }}"
                                class="form-control @error('payment_amount') is-invalid @enderror"
                                id="payment_amount" name="payment_amount"
                                value="{{ old('payment_amount', $dueAmount) }}"
                                required>
                            @error('payment_amount')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-warning">
                            <strong>Not paying cash?</strong>
                            Use
                            <a href="javascript:void(0);" class="show-modal" data-size="lg" data-title="Mark Settled (no payment)" data-action="{{ route('employeeInvoices.markAsSettled', $invoice->id) }}">Mark Settled (no payment)</a>
                            instead — this form creates a Payment and PV voucher.
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle"></i> Record Payment
                    </button>
                    <a href="{{ route('employeeInvoices.index') }}" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
