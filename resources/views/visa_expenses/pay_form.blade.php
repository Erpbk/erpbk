{{-- Shared Visa Expense pay / unpaid-edit / LV-edit form (UVF/RPF chrome). --}}
@php
    $formMode = $formMode ?? 'pay';
    $isPayMode = $formMode === 'pay';
    $isLvEditMode = $formMode === 'lv_edit';
    $isTicketEditMode = $formMode === 'edit';
    $isPaymentForm = $isPayMode || $isLvEditMode;
    $expense = $expense ?? ($data ?? null);
    $accounts = $accounts ?? null;
    $voucher = $voucher ?? null;
    $visaStatuses = $visaStatuses ?? collect();
    $bankCashAccounts = $bankCashAccounts ?? collect();
    $selectedPayAccountId = old('account', $selectedPayAccountId ?? ($expense->pay_account ?? ''));
    $paymentTypeOptions = \App\Helpers\Account::payment_type_list();
    $selectedPaymentType = old('payment_type', $selectedPaymentType ?? ($voucher->payment_type ?? ''));
    if ((string) $selectedPaymentType === '0' || $selectedPaymentType === 0) {
        $selectedPaymentType = '';
    }

    $visaHead = company_table('accounts')->where('id', ga_id('VISA_EXPENSE_ACCOUNT'))->first();
    $isEmployee = !empty($accounts?->employee_id) || !empty($expense?->employee_id);
    if (!isset($rider) || !$rider) {
        $rider = $isEmployee
            ? ($accounts->employee ?? company_table('employees')->where('id', $accounts->employee_id ?? $expense->employee_id)->first())
            : ($accounts->rider ?? company_table('riders')->where('id', $accounts->rider_id ?? $expense->rider_id)->first());
    }
    $personCode = $isEmployee
        ? ($rider->employee_id ?? '')
        : ($rider->rider_id ?? '');
    $personName = $rider->name ?? ($accounts->name ?? '—');

    $defaultNarration = old(
        $isTicketEditMode ? 'detail' : 'narration',
        $expense->detail
            ?: ($isPayMode ? ('Visa Expense Payment — ' . ($expense->visa_status ?? '')) : ($voucher->remarks ?? ''))
    );
    $billingMonthValue = old(
        'billing_month',
        $expense->billing_month ? \Carbon\Carbon::parse($expense->billing_month)->format('Y-m') : date('Y-m')
    );
    $billingMonthLabel = $expense->billing_month
        ? \App\Helpers\Common::MonthFormat($expense->billing_month)
        : '—';
    $baseAmount = old('amount', round((float) $expense->amount, 2));
    $defaultVatPercent = old('vat_percent', $defaultVatPercent ?? 0);
    $defaultVatAmount = old('vat_amount', $defaultVatAmount ?? 0);
    $currencyMark = \App\Helpers\Currency::mark();
    $expenseDate = old('date', \Carbon\Carbon::parse($expense->date ?? now())->format('Y-m-d'));
    $referenceNumber = old('reference_number', $expense->reference_number ?? ($voucher->reference_number ?? ''));
    $existingAttachUrl = !empty($voucher?->attach_file)
        ? url('storage/' . ltrim((string) $voucher->attach_file, '/'))
        : null;
    $existingAttachName = !empty($voucher?->attach_file) ? basename((string) $voucher->attach_file) : null;

    $formAction = match ($formMode) {
        'lv_edit' => route('VisaExpense.updateLv'),
        'edit' => route('VisaExpense.update'),
        default => route('VisaExpense.payfine'),
    };
    $formTitle = match ($formMode) {
        'lv_edit' => 'Edit LV Voucher',
        'edit' => 'Edit Visa Expense',
        default => 'Pay Visa Expense',
    };
    $formSubtitle = match ($formMode) {
        'lv_edit' => (($expense->visa_status ?? 'Visa Expense') . ' · ' . ($voucher ? ('LV-' . str_pad((string) $voucher->id, 4, '0', STR_PAD_LEFT)) : $billingMonthLabel)),
        'edit' => 'Update ticket details before payment',
        default => (($expense->visa_status ?? 'Visa Expense') . ' · ' . $billingMonthLabel),
    };
    $submitLabel = match ($formMode) {
        'lv_edit' => 'Update LV',
        'edit' => 'Save',
        default => 'Pay Visa Expense',
    };
@endphp

<script src="{{ asset('js/modal_custom.js') }}"></script>

<form enctype="multipart/form-data"
      action="{{ $formAction }}"
      method="POST"
      id="formajax"
      class="visa-expense-pay-form"
      data-form-mode="{{ $formMode }}">
    @csrf
    <input type="hidden" id="reload_page" value="1">
    <input type="hidden" name="id" value="{{ $expense->id }}">
    <input type="hidden" name="rider_id" value="{{ $accounts->rider_id ?? $expense->rider_id }}">
    <input type="hidden" name="employee_id" value="{{ $accounts->employee_id ?? $expense->employee_id }}">
    <input type="hidden" name="trans_code" value="{{ $expense->trans_code }}">
    <input type="hidden" name="voucher_type" value="LV">
    <input type="hidden" name="Created_By" value="{{ Auth::user()->id }}">
    @if($isLvEditMode && $voucher)
    <input type="hidden" name="voucher_id" value="{{ $voucher->id }}">
    @endif
    @if($isPaymentForm)
    <input type="hidden" name="expense_account_id" value="{{ ga_id('VISA_EXPENSE_ACCOUNT') }}">
    @endif

    <div class="px-3 pt-2 pb-1">
        <div class="uvf-form rider-payment-form"
             data-modal-title="{{ $formTitle }}"
             data-modal-subtitle="{{ $formSubtitle }}">

            <div class="rpf-header" data-rpf-inline-header>
                <h4 class="rpf-title">{{ $formTitle }}</h4>
                <p class="rpf-subtitle">{{ $formSubtitle }}</p>
            </div>

            <div class="row g-3">
                <div class="col-lg-{{ $isPaymentForm ? '8' : '12' }}">
                    <div class="row g-3">
                        @if($isPaymentForm)
                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="credit_account_id">Paid By (Bank/Cash) <span class="text-danger">*</span></label>
                            <select name="account" id="credit_account_id" class="form-control select2 rpf-input" required>
                                <option value="">Select Bank/Cash Account</option>
                                @foreach($bankCashAccounts as $id => $name)
                                    @if($id !== '' && $id !== null)
                                        <option value="{{ $id }}" @selected((string) $selectedPayAccountId === (string) $id)>{{ $name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="visa_pay_payment_type">Payment Type <span class="text-danger">*</span></label>
                            <select name="payment_type" id="visa_pay_payment_type" class="form-control select2 rpf-input" required>
                                @foreach($paymentTypeOptions as $typeId => $typeLabel)
                                    @if((string) $typeId === '0' || $typeId === 0)
                                        <option value="">{{ $typeLabel }}</option>
                                    @else
                                        <option value="{{ $typeId }}" @selected((string) $selectedPaymentType === (string) $typeId)>{{ $typeLabel }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        @endif

                        <div class="form-group col-md-6">
                            <label class="rpf-label">Person</label>
                            <div class="rpf-payee-box">
                                <div class="rpf-payee-name">{{ $personName }}</div>
                                @if($personCode !== '')
                                    <div class="rpf-payee-id">{{ $personCode }}</div>
                                @endif
                            </div>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="visa_pay_date">Date <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="date"
                                   id="visa_pay_date"
                                   class="form-control rpf-input"
                                   value="{{ $expenseDate }}"
                                   required>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="visa_pay_billing_month">Billing Month <span class="text-danger">*</span></label>
                            <input type="month"
                                   name="billing_month"
                                   id="visa_pay_billing_month"
                                   class="form-control rpf-input"
                                   value="{{ $billingMonthValue }}"
                                   required>
                        </div>

                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="visa_pay_reference">Reference # <span class="text-danger">*</span></label>
                            <input type="text"
                                   name="reference_number"
                                   id="visa_pay_reference"
                                   class="form-control rpf-input"
                                   value="{{ $referenceNumber }}"
                                   placeholder="Reference Number"
                                   required>
                        </div>

                        @if($isPaymentForm)
                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="visa_pay_expiry_date">Document Expiry <span class="text-danger">*</span></label>
                            <input type="date"
                                   name="expiry_date"
                                   id="visa_pay_expiry_date"
                                   class="form-control rpf-input"
                                   value="{{ old('expiry_date', optional($expense->expiry_date)->format('Y-m-d')) }}"
                                   required>
                        </div>
                        @endif

                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="visa_status">Visa Status @if($isTicketEditMode)<span class="text-danger">*</span>@endif</label>
                            @if($isPaymentForm)
                            <input type="text" class="form-control rpf-input bg-light" value="{{ $expense->visa_status ?? '—' }}" readonly tabindex="-1">
                            <input type="hidden" name="visa_status" value="{{ $expense->visa_status }}">
                            @else
                            <select class="form-control select2 rpf-input" id="visa_status" name="visa_status" required>
                                <option value="">Select Status</option>
                                @forelse($visaStatuses as $status)
                                <option value="{{ $status->name }}"
                                    data-fee="{{ $status->default_fee }}"
                                    @selected(old('visa_status', $expense->visa_status) == $status->name)>
                                    {{ $status->name }}
                                </option>
                                @empty
                                <option value="" disabled>No statuses in this visa category</option>
                                @endforelse
                            </select>
                            @endif
                        </div>

                        <div class="form-group col-md-6">
                            <label class="rpf-label" for="visa_pay_amount_display">Amount <span class="text-danger">*</span></label>
                            <div class="input-group rpf-amount-group">
                                <span class="input-group-text">{!! $currencyMark !!}</span>
                                <input type="number"
                                       step="0.01"
                                       min="0"
                                       name="amount"
                                       class="form-control rpf-input fw-semibold"
                                       id="visa_pay_amount_display"
                                       value="{{ number_format((float) $baseAmount, 2, '.', '') }}"
                                       required>
                            </div>
                        </div>
                    </div>
                </div>

                @if($isPaymentForm)
                <div class="col-lg-4">
                    <div class="rpf-attach-box">
                        @include('partials.universal_document_upload', [
                            'name' => 'attach_file',
                            'label' => null,
                            'required' => false,
                            'accept' => '.pdf,.jpg,.jpeg,.png',
                            'inputClass' => 'form-control',
                            'showHint' => true,
                            'variant' => 'dropzone',
                            'uploadLabel' => 'Upload File',
                            'existingUrl' => $existingAttachUrl,
                            'existingName' => $existingAttachName,
                        ])
                        <div class="rpf-attach-hint">PDF or image. Optional attachment for this payment.</div>
                    </div>
                </div>
                @endif
            </div>

            <div class="rpf-section mt-4">
                <h6 class="rpf-section-title">Narration / Description</h6>
                <div class="form-group mb-0">
                    <label class="rpf-label" for="visa_pay_narration">
                        {{ $isTicketEditMode ? 'Detail' : 'Main Narration' }}
                        <span class="text-danger">*</span>
                    </label>
                    <textarea name="{{ $isTicketEditMode ? 'detail' : 'narration' }}"
                              id="visa_pay_narration"
                              class="form-control rpf-input expense-narration-auto"
                              rows="3"
                              required
                              placeholder="{{ $isTicketEditMode ? 'Enter detail...' : 'Enter payment narration...' }}">{{ $defaultNarration }}</textarea>
                </div>
            </div>

            @if($isPaymentForm)
            <div class="rpf-section mt-4">
                <h6 class="rpf-section-title">Visa Expense Entry</h6>
                <div class="table-responsive rpf-invoice-scroll">
                    <table class="table table-bordered rpf-invoice-table mb-0">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-end" style="width: 110px;">Amount</th>
                                <th class="text-end" style="width: 90px;">VAT %</th>
                                <th class="text-end" style="width: 110px;">VAT Amount</th>
                                <th class="text-end" style="width: 110px;">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="visa-pay-expense-entry">
                                <td class="text-start">{{ $visaHead->name ?? 'Visa Expense Account' }}</td>
                                <td class="text-end">
                                    <span id="visa_pay_line_amount">{{ number_format((float) $baseAmount, 2) }}</span>
                                </td>
                                <td>
                                    <input type="number"
                                           step="any"
                                           min="0"
                                           name="vat_percent"
                                           id="visa_pay_vat_percent"
                                           class="form-control form-control-sm rpf-input vat-percent text-end"
                                           placeholder="%"
                                           value="{{ $defaultVatPercent }}">
                                </td>
                                <td>
                                    <input type="number"
                                           step="0.01"
                                           min="0"
                                           name="vat_amount"
                                           id="visa_pay_vat_amount"
                                           class="form-control form-control-sm rpf-input vat-amount text-end bg-light"
                                           placeholder="VAT"
                                           value="{{ $defaultVatAmount }}"
                                           readonly
                                           tabindex="-1">
                                </td>
                                <td class="text-end fw-semibold">
                                    <span id="visa_pay_line_total">{{ number_format((float) $baseAmount, 2) }}</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-6"></div>
                <div class="col-md-6">
                    <div class="rpf-summary">
                        <div class="rpf-summary-row">
                            <span>Subtotal</span>
                            <span>{!! $currencyMark !!} <span id="subtotal_amount">{{ number_format((float) $baseAmount, 2, '.', '') }}</span></span>
                        </div>
                        <div class="rpf-summary-row">
                            <span>Total VAT</span>
                            <span>{!! $currencyMark !!} <span id="total_vat_amount">0.00</span></span>
                        </div>
                        <div class="rpf-summary-total">
                            Grand Total: {!! $currencyMark !!} <span id="expense_total">{{ number_format((float) $baseAmount, 2, '.', '') }}</span>
                        </div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <div class="action-btn rpf-actions">
        <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary rpf-save-btn">
            @if($isPayMode)
            <i class="fa fa-check me-1"></i> {{ $submitLabel }}
            @else
            <i class="fa fa-save me-1"></i> {{ $submitLabel }}
            @endif
        </button>
    </div>
</form>

@include('partials.uvf_styles')

<script>
    (function() {
        function boot() {
            if (typeof jQuery === 'undefined' || typeof $.fn.select2 === 'undefined') {
                setTimeout(boot, 50);
                return;
            }

            var $form = $('.visa-expense-pay-form').last();
            var mode = $form.data('form-mode') || 'pay';
            var $modal = $form.closest('.modal');
            if (!$modal.length) {
                $modal = $('#modalTopbody');
            }

            $form.find('select.select2').each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2({
                    width: '100%',
                    placeholder: $select.find('option:first').text() || 'Select...',
                    allowClear: true,
                    dropdownParent: $modal.length ? $modal : $(document.body)
                });
            });

            var $narration = $form.find('#visa_pay_narration');
            function autoResize() {
                var el = $narration.get(0);
                if (!el) return;
                el.style.height = 'auto';
                el.style.height = Math.max(el.scrollHeight, 72) + 'px';
            }
            $narration.off('input.visaPay').on('input.visaPay', autoResize);
            autoResize();

            if (mode === 'edit') {
                $form.find('#visa_status').off('change.visaFee').on('change.visaFee', function() {
                    var fee = $(this).find('option:selected').data('fee');
                    if (fee !== undefined && fee !== null && fee !== '') {
                        $form.find('#visa_pay_amount_display').val(parseFloat(fee).toFixed(2));
                    }
                });
                return;
            }

            function calculateVat() {
                if (!$form.find('#visa_pay_vat_percent').length) {
                    return;
                }
                var amount = parseFloat($form.find('#visa_pay_amount_display').val()) || 0;
                var vatPercent = parseFloat($form.find('#visa_pay_vat_percent').val()) || 0;
                var vatAmount = Math.round((amount * vatPercent) / 100 * 100) / 100;
                var total = amount + vatAmount;
                $form.find('#visa_pay_vat_amount').val(vatAmount > 0 ? vatAmount.toFixed(2) : '0.00');
                $form.find('#visa_pay_line_amount').text(amount.toFixed(2));
                $form.find('#visa_pay_line_total').text(total.toFixed(2));
                $form.find('#subtotal_amount').text(amount.toFixed(2));
                $form.find('#total_vat_amount').text(vatAmount.toFixed(2));
                $form.find('#expense_total').text(total.toFixed(2));
            }

            $form.find('#visa_pay_vat_percent, #visa_pay_amount_display')
                .off('change.visaPayVat keyup.visaPayVat input.visaPayVat')
                .on('change.visaPayVat keyup.visaPayVat input.visaPayVat', calculateVat);

            calculateVat();
        }

        boot();
    })();
</script>
