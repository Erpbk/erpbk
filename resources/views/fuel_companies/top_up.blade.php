{!! Form::open(['route' => 'fuelCompanies.topUp.store', 'id' => 'formajax', 'files' => true]) !!}

<div class="card-body px-3">
    <div class="row">
        <div class="form-group col-md-12">
            <label for="fuel_company_id">Fuel Company <span class="text-danger">*</span></label>
            <select name="fuel_company_id" id="fuel_company_id" class="form-control select2" required>
                <option value="">-- Select Fuel Company --</option>
                @foreach($fuelCompanies as $company)
                    @php
                        $accountLabel = $company->account
                            ? trim(($company->account->account_code ? $company->account->account_code . ' — ' : '') . ($company->account->name ?: $company->name))
                            : '';
                    @endphp
                    <option
                        value="{{ $company->id }}"
                        data-account-id="{{ $company->account_id }}"
                        data-account-label="{{ $accountLabel }}"
                        {{ old('fuel_company_id') == $company->id ? 'selected' : '' }}
                    >
                        {{ $company->name }}
                    </option>
                @endforeach
            </select>
            @if($fuelCompanies->isEmpty())
                <small class="text-danger">No active fuel companies with a linked chart account were found.</small>
            @endif
        </div>
    </div>

    <div id="topup-voucher-section" class="d-none">
        <div class="row mt-2">
            <div class="col-md-12">
                <h6 class="bg-light p-2 mb-3 mb-0">Payment Voucher (PV)</h6>
                <p class="small text-muted mb-3">Debit the fuel company wallet and credit the selected bank account.</p>
            </div>
        </div>

        <div class="row">
            <div class="form-group col-md-6">
                <label>Debit Account (Fuel Company)</label>
                <input type="text" id="debit_account_display" class="form-control bg-light" readonly value="">
                <small class="text-muted">Pre-selected from the fuel company chart account.</small>
            </div>

            <div class="form-group col-md-6">
                <label for="bank_id">Credit Account (Bank) <span class="text-danger">*</span></label>
                <select name="bank_id" id="bank_id" class="form-control select2" required>
                    <option value="">-- Select Bank Account --</option>
                    @foreach($banks as $bank)
                        <option value="{{ $bank->id }}" {{ old('bank_id') == $bank->id ? 'selected' : '' }}>
                            {{ $bank->name }}
                            @if($bank->account)
                                ({{ $bank->account->account_code }} — {{ $bank->account->name }})
                            @endif
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-4">
                <label for="amount_type">Payment Mode <span class="text-danger">*</span></label>
                <select name="amount_type" id="amount_type" class="form-control select2" required>
                    <option value="">Select Payment Mode</option>
                    @php $amountType = old('amount_type', 'Online'); @endphp
                    <option value="Cash" {{ $amountType == 'Cash' ? 'selected' : '' }}>Cash</option>
                    <option value="Online" {{ $amountType == 'Online' ? 'selected' : '' }}>Online</option>
                    <option value="Cheque" {{ $amountType == 'Cheque' ? 'selected' : '' }}>Cheque</option>
                    <option value="Credit" {{ $amountType == 'Credit' ? 'selected' : '' }}>Credit</option>
                </select>
            </div>

            <div class="form-group col-md-4">
                <label for="date_of_payment">Payment Date <span class="text-danger">*</span></label>
                <input type="date" name="date_of_payment" id="date_of_payment" class="form-control"
                       value="{{ old('date_of_payment', date('Y-m-d')) }}" required>
            </div>

            <div class="form-group col-md-4">
                <label for="billing_month">Billing Month <span class="text-danger">*</span></label>
                <input type="month" name="billing_month" id="billing_month" class="form-control"
                       value="{{ old('billing_month', date('Y-m')) }}" required>
            </div>

            <div class="form-group col-md-4">
                <label for="amount">Amount <span class="text-danger">*</span></label>
                <div class="input-group">
                    <span class="input-group-text">{{ \App\Helpers\Currency::code() }}</span>
                    <input type="number" name="amount" id="amount" class="form-control" step="any" min="0.01"
                           value="{{ old('amount') }}" placeholder="Enter amount" required>
                </div>
            </div>

            <div class="form-group col-md-4">
                <label for="reference">Reference</label>
                <input type="text" name="reference" id="reference" class="form-control" maxlength="255"
                       value="{{ old('reference') }}" placeholder="Optional reference">
            </div>

            <div class="form-group col-md-4">
                <label for="attachment">Attachment</label>
                <input type="file" name="attachment" id="attachment" class="form-control"
                       accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="form-group col-md-12">
                <label for="description">Narration <span class="text-danger">*</span></label>
                <textarea name="description" id="description" class="form-control" rows="3" maxlength="500"
                          placeholder="Enter narration for this top-up..." required>{{ old('description') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Create Top-Up PV', ['class' => 'btn btn-primary', 'id' => 'topup-submit-btn', 'disabled' => true]) !!}
</div>

{!! Form::close() !!}

<script>
(function () {
    function initFuelCompanyTopUpForm() {
        var $form = $('#formajax');
        if (!$form.length) {
            return;
        }

        var $modalBody = $form.closest('.modal-body');
        var dropdownParent = $modalBody.length ? $modalBody : $('body');

        if ($.fn.select2) {
            $form.find('.select2').each(function () {
                var $el = $(this);
                if ($el.hasClass('select2-hidden-accessible')) {
                    $el.select2('destroy');
                }
                $el.select2({
                    dropdownParent: dropdownParent,
                    width: '100%',
                    allowClear: true,
                    placeholder: $el.find('option:first').text()
                });
            });
        }

        function syncVoucherSection() {
            var $option = $('#fuel_company_id').find('option:selected');
            var companyId = $option.val();
            var accountId = $option.data('account-id');
            var accountLabel = $option.data('account-label') || '';

            if (companyId && accountId) {
                $('#debit_account_display').val(accountLabel);
                $('#topup-voucher-section').removeClass('d-none');
                $('#topup-submit-btn').prop('disabled', false);
            } else {
                $('#debit_account_display').val('');
                $('#topup-voucher-section').addClass('d-none');
                $('#topup-submit-btn').prop('disabled', true);
            }
        }

        $('#fuel_company_id').off('change.fuelTopUp').on('change.fuelTopUp', syncVoucherSection);
        syncVoucherSection();
    }

    initFuelCompanyTopUpForm();
})();
</script>
