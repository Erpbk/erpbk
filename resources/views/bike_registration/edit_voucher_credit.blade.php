<div class="p-3">
    <div class="mb-2 small text-muted">
        Debit remains <strong>{{ $debitAccountName }}</strong> · Current credit: <strong>{{ $currentCreditName }}</strong>
    </div>
    <form method="post" action="{{ route('BikeRegistration.updateVoucherCredit') }}" class="bike-registration-voucher-credit-form">
        @csrf
        <input type="hidden" name="bike_registration_id" value="{{ $expense->id }}">
        <div class="mb-3">
            <label class="form-label" for="br_credit_account_id">New credit (payment) account</label>
            <select name="credit_account_id" id="br_credit_account_id" class="form-select form-select-sm" required>
                @foreach($paymentAccounts as $accId => $accName)
                <option value="{{ $accId }}" @if((int)$accId === (int)$creditTransaction->account_id) selected @endif>{{ $accName }}</option>
                @endforeach
            </select>
        </div>
        <div class="d-flex justify-content-end gap-2">
            <button type="submit" class="btn btn-primary btn-sm">Update payment account</button>
        </div>
    </form>
</div>
