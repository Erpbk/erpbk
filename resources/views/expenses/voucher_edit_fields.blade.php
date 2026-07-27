<script src="{{ asset('js/modal_custom.js') }}"></script>

<input type="hidden" name="voucher_type" id="voucher_type" value="EXP">

<div class="row mt-0 mb-2" id="expense-voucher-header">
    <div class="form-group col-md-2">
        <label for="trans_date">Date</label>
        <input type="date" name="trans_date" class="form-control" placeholder="Transaction Date" value="{{ $voucher->trans_date ? \Carbon\Carbon::parse($voucher->trans_date)->format('Y-m-d') : date('Y-m-d') }}">
    </div>

    <div class="form-group col-md-2">
        <label for="billing_month">Billing Month</label>
        <input type="month" name="billing_month" class="form-control" value="{{ $voucher->billing_month ? \Carbon\Carbon::parse($voucher->billing_month)->format('Y-m') : date('Y-m') }}" required>
    </div>

    <div class="form-group col-md-3">
        <label for="credit_account_id">Bank/Cash Account <span class="text-danger">*</span></label>
        <select name="credit_account_id" id="credit_account_id" class="form-control select2" required>
            <option value="">Select Bank/Cash Account</option>
            @foreach($bankCashAccounts as $id => $name)
                @if($id !== '')
                <option value="{{ $id }}" {{ isset($creditEntry) && $creditEntry['account_id'] == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endif
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-2">
        <label for="reference_number">Reference # <span class="text-danger">*</span></label>
        <input type="text" name="reference_number" class="form-control" id="reference_number" placeholder="Reference Number" value="{{ $voucher->reference_number }}" required>
    </div>

    <div class="form-group col-md-3">
        <label for="payment_type">Payment Type</label>
        {!! Form::select('payment_type', App\Helpers\Account::payment_type_list(), $voucher->payment_type, ['class' => 'form-select form-select-sm select2', 'id' => 'payment_type']) !!}
    </div>
</div>

<hr>

<h6 class="mb-2">Debit Entries (Expenses)</h6>

<div class="row g-2 mb-1 fw-semibold small text-muted px-1 expense-voucher-col-header">
    <div class="col-md-3">Account <span class="text-danger">*</span></div>
    <div class="col-md-3">Narration</div>
    <div class="col-md-2">Amount <span class="text-danger">*</span></div>
    <div class="col-md-1">VAT %</div>
    <div class="col-md-2">VAT Amount</div>
    <div class="col-md-1"></div>
</div>

<div class="scrollbar" id="expense-voucher-scroll">
    <div id="expense-voucher-rows">
        @forelse($debitEntries as $index => $entry)
        <div class="expense-voucher-entry row g-2 mb-2 align-items-start">
            <div class="col-md-3">
                <select name="debit_account_id[]" class="form-control form-select select2 debit-account-select" required>
                    <option value="">Select Expense Account</option>
                    {!! App\Helpers\Accounts::expenseAccountsDropdown($expenseAccounts, $entry['account_id']) !!}
                </select>
            </div>
            <div class="col-md-3">
                <textarea name="debit_narration[]" class="form-control debit-narration expense-narration-auto" rows="2" placeholder="Narration">{{ $entry['narration'] }}</textarea>
            </div>
            <div class="col-md-2">
                <input type="number" step="any" name="amount[]" class="form-control expense-amount" placeholder="Amount" value="{{ $entry['amount'] }}" required>
            </div>
            <div class="col-md-1">
                <input type="number" step="any" name="vat_percent[]" class="form-control vat-percent" placeholder="%" value="{{ $entry['vat_percent'] }}">
            </div>
            <div class="col-md-2">
                <input type="number" step="any" name="vat_amount[]" class="form-control vat-amount" placeholder="VAT" value="{{ $entry['vat_amount'] > 0 ? $entry['vat_amount'] : '' }}" readonly>
            </div>
            <div class="col-md-1">
                <a href="javascript:void(0);" class="text-danger btn-remove-expense-entry" title="Remove"><i class="fa fa-trash"></i></a>
            </div>
        </div>
        @empty
        <div class="expense-voucher-entry row g-2 mb-2 align-items-start">
            <div class="col-md-3">
                <select name="debit_account_id[]" class="form-control form-select select2 debit-account-select" required>
                    <option value="">Select Expense Account</option>
                    {!! App\Helpers\Accounts::expenseAccountsDropdown($expenseAccounts) !!}
                </select>
            </div>
            <div class="col-md-3">
                <textarea name="debit_narration[]" class="form-control debit-narration expense-narration-auto" rows="2" placeholder="Narration"></textarea>
            </div>
            <div class="col-md-2">
                <input type="number" step="any" name="amount[]" class="form-control expense-amount" placeholder="Amount" required>
            </div>
            <div class="col-md-1">
                <input type="number" step="any" name="vat_percent[]" class="form-control vat-percent" placeholder="%" value="0">
            </div>
            <div class="col-md-2">
                <input type="number" step="any" name="vat_amount[]" class="form-control vat-amount" placeholder="VAT" readonly>
            </div>
            <div class="col-md-1">
                <a href="javascript:void(0);" class="text-danger btn-remove-expense-entry" title="Remove"><i class="fa fa-trash"></i></a>
            </div>
        </div>
        @endforelse
    </div>
</div>

<button type="button" id="add-expense-row" class="btn btn-success btn-sm mt-2 mb-3">
    <i class="fa fa-plus me-1"></i> Add Entry
</button>

<template id="expense-voucher-row-tpl">
    <div class="expense-voucher-entry row g-2 mb-2 align-items-start">
        <div class="col-md-3">
            <select name="debit_account_id[]" class="form-control form-select select2 debit-account-select" required>
                <option value="">Select Expense Account</option>
                {!! App\Helpers\Accounts::expenseAccountsDropdown($expenseAccounts) !!}
            </select>
        </div>
        <div class="col-md-3">
            <textarea name="debit_narration[]" class="form-control debit-narration expense-narration-auto" rows="2" placeholder="Narration"></textarea>
        </div>
        <div class="col-md-2">
            <input type="number" step="any" name="amount[]" class="form-control expense-amount" placeholder="Amount" required>
        </div>
        <div class="col-md-1">
            <input type="number" step="any" name="vat_percent[]" class="form-control vat-percent" placeholder="%" value="0">
        </div>
        <div class="col-md-2">
            <input type="number" step="any" name="vat_amount[]" class="form-control vat-amount" placeholder="VAT" readonly>
        </div>
        <div class="col-md-1">
            <a href="javascript:void(0);" class="text-danger btn-remove-expense-entry" title="Remove"><i class="fa fa-trash"></i></a>
        </div>
    </div>
</template>

<hr class="mt-0">

<div class="row mt-3">
    <div class="col-md-6"></div>
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <td class="text-end"><strong>Subtotal:</strong></td>
                <td style="width: 150px;"><input type="number" class="form-control form-control-sm" id="subtotal_amount" readonly></td>
            </tr>
            <tr>
                <td class="text-end"><strong>Total VAT:</strong></td>
                <td><input type="number" class="form-control form-control-sm" id="total_vat_amount" readonly></td>
            </tr>
            <tr>
                <td class="text-end"><strong>Grand Total:</strong></td>
                <td><input type="number" class="form-control form-control-sm fw-bold" id="expense_total" readonly></td>
            </tr>
        </table>
    </div>
</div>

<script>
(function() {
    function boot() {
        if (typeof jQuery === 'undefined' || typeof $.fn.select2 === 'undefined') {
            setTimeout(boot, 50);
            return;
        }

        var $formRoot = $('#formajax').length ? $('#formajax') : $('#modalTopbody');

        function initSelect2($container) {
            if (!$container || !$container.length) return;

            var $modal = $container.closest('.modal');
            if (!$modal.length) {
                $modal = $('#modalTopbody');
            }
            var options = {
                width: '100%',
                placeholder: 'Select...',
                allowClear: true
            };
            if ($modal.length) {
                options.dropdownParent = $modal;
            }

            var $selects = $container.is('select.select2')
                ? $container
                : $container.find('select.select2');

            $selects.each(function() {
                var $select = $(this);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }
                $select.select2(options);
            });
        }

        function autoResizeNarration($textarea) {
            $textarea.each(function() {
                var el = this;
                el.style.height = 'auto';
                var minHeight = parseFloat($(el).css('min-height')) || (parseInt($(el).attr('rows'), 10) || 2) * 22;
                el.style.height = Math.max(el.scrollHeight, minHeight) + 'px';
            });
        }

        function initNarrationFields($scope) {
            autoResizeNarration($scope.find('.expense-narration-auto'));
        }

        initSelect2($formRoot);
        initSelect2($('#expense-voucher-header'));
        initSelect2($('#payment_type'));
        initSelect2($('#expense-voucher-rows'));
        initSelect2($('#credit_account_id'));
        initNarrationFields($formRoot);
        calculateTotals();

        $('#add-expense-row').off('click.expenseVoucher').on('click.expenseVoucher', function() {
            var tpl = document.getElementById('expense-voucher-row-tpl');
            if (!tpl || !tpl.content) return;

            var $newEntry = $(tpl.content.cloneNode(true)).children();
            $('#expense-voucher-rows').append($newEntry);
            initSelect2($newEntry);
            initNarrationFields($newEntry);
        });

        $(document).off('click.expenseVoucher', '.btn-remove-expense-entry').on('click.expenseVoucher', '.btn-remove-expense-entry', function() {
            if ($('.expense-voucher-entry').length > 1) {
                var $entry = $(this).closest('.expense-voucher-entry');
                $entry.find('select.select2').each(function() {
                    if ($(this).hasClass('select2-hidden-accessible')) {
                        $(this).select2('destroy');
                    }
                });
                $entry.remove();
                calculateTotals();
            }
        });

        $(document).off('input.expenseVoucher', '.expense-narration-auto').on('input.expenseVoucher', '.expense-narration-auto', function() {
            autoResizeNarration($(this));
        });

        $(document).off('change.expenseVoucher keyup.expenseVoucher', '.expense-amount, .vat-percent')
            .on('change.expenseVoucher keyup.expenseVoucher', '.expense-amount, .vat-percent', function() {
                var $entry = $(this).closest('.expense-voucher-entry');
                calculateRowVat($entry);
                calculateTotals();
            });
    }

        function calculateRowVat($entry) {
            var amount = parseFloat($entry.find('.expense-amount').val()) || 0;
            var vatPercent = parseFloat($entry.find('.vat-percent').val()) || 0;
            var vatAmount = (amount * vatPercent) / 100;
            $entry.find('.vat-amount').val(vatAmount > 0 ? vatAmount.toFixed(2) : '');
        }

        window.calculateTotals = function() {
            var subtotal = 0;
            var totalVat = 0;

            $('.expense-voucher-entry').each(function() {
                var amount = parseFloat($(this).find('.expense-amount').val()) || 0;
                var vatAmount = parseFloat($(this).find('.vat-amount').val()) || 0;
                subtotal += amount;
                totalVat += vatAmount;
            });

            var grandTotal = subtotal + totalVat;

        $('#subtotal_amount').val(subtotal.toFixed(2));
        $('#total_vat_amount').val(totalVat.toFixed(2));
        $('#expense_total').val(grandTotal.toFixed(2));
    };

    boot();
})();
</script>