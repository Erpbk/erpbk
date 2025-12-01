@extends('layouts.app')
@section('title','Mark Invoice as Paid')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1>Mark Invoice as Paid</h1>
            </div>
            <div class="col-sm-6">
                <a class="btn btn-secondary float-right"
                    href="{{ route('riderInvoices.index') }}">
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
            <!-- Invoice Information -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <h5>Invoice Information</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Invoice ID:</strong></td>
                            <td>#{{ $invoice->id }}</td>
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
                    <h5>Rider Information</h5>
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Rider ID:</strong></td>
                            <td>{{ $invoice->rider->rider_id ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Rider Name:</strong></td>
                            <td>{{ $invoice->rider->name ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td><span class="badge bg-primary fs-6">AED {{ number_format($invoice->total_amount, 2) }}</span></td>
                        </tr>
                        <tr>
                            <td><strong>Current Status:</strong></td>
                            <td><span class="badge bg-danger">Unpaid</span></td>
                        </tr>
                    </table>
                </div>
            </div>

            <hr>

            <!-- Payment Form -->
            <form action="{{ route('riderInvoices.markAsPaid', $invoice->id) }}" method="POST">
                @csrf
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="bank_account_id" class="required">Select Bank Account <span class="text-danger">*</span></label>
                            <select class="form-control @error('bank_account_id') is-invalid @enderror"
                                id="bank_account_id" name="bank_account_id" required>
                                <option value="">-- Select Bank Account --</option>
                                @foreach($bankAccounts as $id => $name)
                                <option value="{{ $id }}" {{ old('bank_account_id') == $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                                @endforeach
                            </select>
                            @error('bank_account_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">
                                This bank account will be credited with the invoice amount.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Payment Amount</label>
                            <input type="text" class="form-control"
                                value="AED {{ number_format($invoice->total_amount, 2) }}"
                                readonly>
                            <small class="form-text text-muted">
                                This amount will be debited from rider's account and credited to the selected bank account.
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-12">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Payment Process:</h6>
                            <ul class="mb-0">
                                <li>Invoice status will be changed from "Unpaid" to "Paid"</li>
                                <li>Rider's account will be debited with AED {{ number_format($invoice->total_amount, 2) }}</li>
                                <li>Selected bank account will be credited with AED {{ number_format($invoice->total_amount, 2) }}</li>
                                <li>A voucher entry will be created for this transaction</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-success btn-lg">
                        <i class="fas fa-check-circle"></i> Mark as Paid
                    </button>
                    <a href="{{ route('riderInvoices.index') }}" class="btn btn-secondary btn-lg">
                        <i class="fas fa-times"></i> Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        // Add confirmation before submission
        $('form').on('submit', function(e) {
            e.preventDefault();

            Swal.fire({
                title: 'Confirm Payment',
                text: 'Are you sure you want to mark this invoice as paid? This action cannot be undone.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3545',
                confirmButtonText: 'Yes, Mark as Paid',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    this.submit();
                }
            });
        });
    });
</script>
@endsection