<input type="hidden" name="asset_account_id" id="asset_account_id" value="{{ $assetAccountId }}">
<div class="row">
    <div class="form-group col-sm-6">
        <label>Debit Account (Asset) <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="asset_account_display" value="{{ $assetAccountName }}" readonly>
        <small class="text-muted">Fixed from the selected category.</small>
    </div>

    <div class="form-group col-sm-6">
        <label for="acquisition_credit_account_id">Credit Account <span class="text-danger">*</span></label>
        <select name="acquisition_credit_account_id" id="acquisition_credit_account_id" class="form-control select2-credit-account">
            <option value="">Select account</option>
            @foreach($creditAccounts as $id => $name)
                @if($id !== '')
                    <option value="{{ $id }}">{{ $name }}</option>
                @endif
            @endforeach
        </select>
    </div>

    <div class="form-group col-sm-6">
        <label for="voucher_trans_date">Voucher Date <span class="text-danger">*</span></label>
        <input type="date" name="voucher_trans_date" id="voucher_trans_date" class="form-control" value="{{ date('Y-m-d') }}">
    </div>

    <div class="form-group col-sm-6">
        <label for="voucher_billing_month">Billing Month <span class="text-danger">*</span></label>
        <input type="month" name="voucher_billing_month" id="voucher_billing_month" class="form-control" value="{{ date('Y-m') }}">
    </div>

    <div class="form-group col-sm-6">
        <label for="voucher_reference_number">Reference # <span class="text-danger">*</span></label>
        <input type="text" name="voucher_reference_number" id="voucher_reference_number" class="form-control" placeholder="Reference number">
    </div>

    <div class="form-group col-sm-6">
        <label>Amount</label>
        <input type="text" class="form-control" id="acquisition_voucher_amount" readonly>
        <small class="text-muted">Matches acquisition cost.</small>
    </div>
</div>
