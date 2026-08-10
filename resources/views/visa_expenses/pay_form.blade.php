{{-- Expense-voucher-style payment form for a single Visa Expense entry. --}}
@php
    $visaHead = company_table('accounts')->where('id', ga_id('VISA_EXPENSE_ACCOUNT'))->first();
    $rider = $rider ?? ($accounts->rider ?? company_table('riders')->where('id', $accounts->rider_id ?? $data->rider_id)->first());
    $defaultNarration = old('narration', $data->detail ?: ('Visa Expense Payment — ' . ($data->visa_status ?? '')));
    $billingMonthValue = $data->billing_month
        ? \Carbon\Carbon::parse($data->billing_month)->format('Y-m')
        : date('Y-m');
    $baseAmount = round((float) $data->amount, 2);
    $defaultVatPercent = old('vat_percent', 0);
    $defaultVatAmount = old('vat_amount', 0);
@endphp

<script src="{{ asset('js/modal_custom.js') }}"></script>

<form enctype="multipart/form-data"
      action="{{ route('VisaExpense.payfine') }}"
      method="POST"
      id="formajax"
      class="form-with-fixed-footer visa-expense-pay-form">
    @csrf
    <input type="hidden" id="reload_page" value="1">
    <input type="hidden" name="id" value="{{ $data->id }}">
    <input type="hidden" name="rider_id" value="{{ $accounts->rider_id ?? $data->rider_id }}">
    <input type="hidden" name="trans_date" value="{{ $data->trans_date ?? $data->date }}">
    <input type="hidden" name="trans_code" value="{{ $data->trans_code }}">
    <input type="hidden" name="billing_month" value="{{ $data->billing_month }}">
    <input type="hidden" name="voucher_type" value="LV">
    <input type="hidden" name="amount" id="visa_pay_amount" value="{{ number_format($baseAmount, 2, '.', '') }}">
    <input type="hidden" name="Created_By" value="{{ Auth::user()->id }}">
    <input type="hidden" name="payment_type" id="visa_pay_payment_type" value="Asset">

    <div class="card-body card-body-with-footer">
        <div class="row mt-0 mb-2" id="visa-pay-voucher-header">
            <div class="form-group col-md-4">
                <label for="credit_account_id">Bank/Payment Account <span class="text-danger">*</span></label>
                <select name="account" id="credit_account_id" class="form-control select2" required>
                    <option value="">Select Bank/Cash Account</option>
                    @foreach($bankCashAccounts as $id => $name)
                        @if($id !== '' && $id !== null)
                            <option value="{{ $id }}" @selected((string) old('account') === (string) $id)>{{ $name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div class="form-group col-md-2">
                <label>Date</label>
                <input type="date" class="form-control" value="{{ \Carbon\Carbon::parse($data->date ?? now())->format('Y-m-d') }}" readonly tabindex="-1">
            </div>

            <div class="form-group col-md-2">
                <label>Billing Month</label>
                <input type="month" class="form-control" value="{{ $billingMonthValue }}" readonly tabindex="-1">
            </div>

            <div class="form-group col-md-2">
                <label>Reference #</label>
                <input type="text" class="form-control" value="{{ $data->reference_number }}" readonly tabindex="-1">
            </div>
        </div>

        <div class="row mb-2">
            <div class="form-group col-md-4">
                <label for="visa_pay_attach_file">Document</label>
                <input type="file"
                       name="attach_file"
                       id="visa_pay_attach_file"
                       class="form-control"
                       accept=".pdf,.jpg,.jpeg,.png">
            </div>

            <div class="form-group col-md-3">
                <label>Document Expiry <span class="text-danger">*</span></label>
                <input type="date"
                       name="expiry_date"
                       class="form-control"
                       value="{{ old('expiry_date', optional($data->expiry_date)->format('Y-m-d')) }}"
                       required>
            </div>
        </div>

        <div class="alert alert-light border py-2 mb-3">
            <div class="row small g-2">
                <div class="col-md-4">
                    <span class="text-muted">Rider:</span>
                    <strong>{{ $rider ? trim(($rider->rider_id ?? '') . ' — ' . ($rider->name ?? '')) : '—' }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted">Visa Status:</span>
                    <strong>{{ $data->visa_status ?? '—' }}</strong>
                </div>
                <div class="col-md-4">
                    <span class="text-muted">Payment Status:</span>
                    <strong class="text-danger">Unpaid</strong>
                </div>
            </div>
        </div>

        <hr>

        <h6 class="mb-2">Visa Expense Entry</h6>

        <div class="row g-2 mb-1 fw-semibold small text-muted px-1">
            <div class="col-md-3">Account</div>
            <div class="col-md-4">Narration <span class="text-danger">*</span></div>
            <div class="col-md-2">Amount</div>
            <div class="col-md-1">VAT %</div>
            <div class="col-md-2">VAT Amount</div>
        </div>

        <div class="row g-2 mb-2 align-items-start visa-pay-expense-entry">
            <div class="col-md-3">
                <input type="text"
                       class="form-control"
                       value="{{ $visaHead->name ?? 'Visa Expense Account' }}"
                       readonly
                       tabindex="-1">
                <input type="hidden" name="expense_account_id" value="{{ ga_id('VISA_EXPENSE_ACCOUNT') }}">
            </div>
            <div class="col-md-4">
                <textarea name="narration"
                          id="visa_pay_narration"
                          class="form-control expense-narration-auto"
                          rows="2"
                          required
                          placeholder="Narration">{{ $defaultNarration }}</textarea>
            </div>
            <div class="col-md-2">
                <input type="number"
                       step="0.01"
                       class="form-control fw-semibold expense-amount"
                       id="visa_pay_amount_display"
                       value="{{ number_format($baseAmount, 2, '.', '') }}"
                       readonly
                       tabindex="-1">
            </div>
            <div class="col-md-1">
                <input type="number"
                       step="any"
                       min="0"
                       name="vat_percent"
                       id="visa_pay_vat_percent"
                       class="form-control vat-percent"
                       placeholder="%"
                       value="{{ $defaultVatPercent }}">
            </div>
            <div class="col-md-2">
                <input type="number"
                       step="0.01"
                       min="0"
                       name="vat_amount"
                       id="visa_pay_vat_amount"
                       class="form-control vat-amount"
                       placeholder="VAT"
                       value="{{ $defaultVatAmount }}"
                       readonly
                       tabindex="-1">
            </div>
        </div>

        <hr class="mt-0">

        <div class="row mt-3">
            <div class="col-md-6"></div>
            <div class="col-md-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-end"><strong>Subtotal:</strong></td>
                        <td style="width: 160px;">
                            <input type="number" class="form-control form-control-sm" id="subtotal_amount" readonly tabindex="-1">
                        </td>
                    </tr>
                    <tr>
                        <td class="text-end"><strong>Total VAT:</strong></td>
                        <td>
                            <input type="number" class="form-control form-control-sm" id="total_vat_amount" readonly tabindex="-1">
                        </td>
                    </tr>
                    <tr>
                        <td class="text-end"><strong>Grand Total:</strong></td>
                        <td>
                            <input type="number" class="form-control form-control-sm fw-bold" id="expense_total" readonly tabindex="-1">
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="card-footer fixed-footer" style="z-index: 1 !important; text-align: right;">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">
            <i class="fa fa-check me-1"></i> Pay Visa Expense
        </button>
    </div>
</form>

<script>
    (function() {
        function boot() {
            if (typeof jQuery === 'undefined' || typeof $.fn.select2 === 'undefined') {
                setTimeout(boot, 50);
                return;
            }

            var $form = $('.visa-expense-pay-form').last();
            var $modal = $form.closest('.modal');
            if (!$modal.length) {
                $modal = $('#modalTopbody');
            }

            var $bank = $form.find('#credit_account_id');
            if ($bank.hasClass('select2-hidden-accessible')) {
                $bank.select2('destroy');
            }
            $bank.select2({
                width: '100%',
                placeholder: 'Select Bank/Cash Account',
                allowClear: true,
                dropdownParent: $modal.length ? $modal : $(document.body)
            });

            var $narration = $form.find('#visa_pay_narration');
            function autoResize() {
                var el = $narration.get(0);
                if (!el) return;
                el.style.height = 'auto';
                el.style.height = Math.max(el.scrollHeight, 48) + 'px';
            }
            $narration.off('input.visaPay').on('input.visaPay', autoResize);
            autoResize();

            function calculateVat() {
                var amount = parseFloat($form.find('#visa_pay_amount_display').val()) || 0;
                var vatPercent = parseFloat($form.find('#visa_pay_vat_percent').val()) || 0;
                var vatAmount = Math.round((amount * vatPercent) / 100 * 100) / 100;
                $form.find('#visa_pay_vat_amount').val(vatAmount > 0 ? vatAmount.toFixed(2) : '0.00');
                $form.find('#subtotal_amount').val(amount.toFixed(2));
                $form.find('#total_vat_amount').val(vatAmount.toFixed(2));
                $form.find('#expense_total').val((amount + vatAmount).toFixed(2));
            }

            $form.find('#visa_pay_vat_percent')
                .off('change.visaPayVat keyup.visaPayVat input.visaPayVat')
                .on('change.visaPayVat keyup.visaPayVat input.visaPayVat', calculateVat);

            calculateVat();
        }

        boot();
    })();
</script>
