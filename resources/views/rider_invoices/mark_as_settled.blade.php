<form action="{{ route('riderInvoices.markAsSettled', $invoice->id) }}" method="POST" id="formajax">
    @csrf
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-6">
                <h6 class="mb-2">Invoice</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="ps-0"><strong>Number:</strong></td>
                        <td>{{ $invoice->invoice_number }}</td>
                    </tr>
                    <tr>
                        <td class="ps-0"><strong>Billing month:</strong></td>
                        <td>{{ \Carbon\Carbon::parse($invoice->billing_month)->format('M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="ps-0"><strong>Rider:</strong></td>
                        <td>{{ ($invoice->rider->rider_id ?? '-') }} - {{ ($invoice->rider->name ?? '-') }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6">
                <h6 class="mb-2">Net position (after deductions)</h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="ps-0"><strong>Final amount:</strong></td>
                        <td>{{ \App\Helpers\Currency::format($outstanding['final_amount'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-0"><strong>Paid (month):</strong></td>
                        <td>{{ \App\Helpers\Currency::format($outstanding['paid_amount'] ?? 0, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="ps-0"><strong>Net balance:</strong></td>
                        <td>
                            <span class="badge {{ ($outstanding['balance'] ?? 0) > 0.01 ? 'bg-warning' : 'bg-success' }}">
                                {{ \App\Helpers\Currency::format($outstanding['balance'] ?? 0, 2) }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <div class="alert alert-info mb-3">
            Marks the invoice paid for reporting only. No Payment record and no bank voucher will be created.
        </div>

        <div class="form-group mb-0">
            <label for="reason">
                Reason
                @if($needsReason)
                    <span class="text-danger">*</span>
                @endif
            </label>
            <textarea class="form-control" id="reason" name="reason" rows="3" maxlength="255"
                @if($needsReason) required @endif
                placeholder="{{ $needsReason ? 'Required: why settle without paying the remaining balance?' : 'Optional note (e.g. absorbed by advance/penalty)' }}">{{ old('reason') }}</textarea>
            @if($needsReason)
            <small class="form-text text-muted">Net balance is still positive. A reason is required.</small>
            @endif
        </div>
    </div>
    <div class="card-footer text-end mt-3">
        <button type="submit" class="btn btn-primary">Mark Settled (no payment)</button>
    </div>
</form>
