{{-- Same voucher preview as vouchers.show (AJAX modal); then payment account form. --}}
<div class="visa-edit-voucher-credit-modal">
    <div class="rounded bg-body-secondary mb-3">
        @include('vouchers.show_modal', [
        'voucher' => $voucher,
        'editDeleteFlags' => $editDeleteFlags ?? [],
        'visaCreditEditEmbed' => true,
        ])
    </div>

    <div class="border-top pt-3 px-1">
        <h6 class="small text-muted text-uppercase mb-3">Edit payment account (credit side)</h6>

        @if($paymentAccounts->isEmpty())
        <div class="alert alert-warning mb-0">No cash/bank accounts found. Add accounts or contact admin.</div>
        @else
        <form method="post" action="{{ route('LegalCase.updateVoucherCredit') }}" class="visa-voucher-credit-form">
            @csrf
            <input type="hidden" name="legal_case_id" value="{{ $expense->id }}">

            <div class="mb-2 small">
                <span class="text-muted">Debit (fixed):</span> {{ $debitAccountName }}<br>
                <span class="text-muted">Credit (current):</span> {{ $currentCreditName }}
            </div>

            <div class="mb-3">
                <label class="form-label" for="visa_credit_account_id">New credit (payment) account</label>
                <select name="credit_account_id" id="visa_credit_account_id" class="form-select form-select-sm" required>
                    @foreach($paymentAccounts as $accId => $accName)
                    <option value="{{ $accId }}" @selected((int) $creditTransaction->account_id === (int) $accId)>{{ $accName }}</option>
                    @endforeach
                </select>
                <div class="form-text">Only the payment side changes; the legal case debit line stays on the head account.</div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-sm btn-primary">Save</button>
            </div>
        </form>
        @endif
    </div>
</div>