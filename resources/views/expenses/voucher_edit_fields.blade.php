<script src="{{ asset('js/modal_custom.js') }}"></script>

<input type="hidden" name="voucher_type" id="voucher_type" value="EXP">

<div class="row mt-0 mb-2">
    <div class="form-group col-md-2">
        <label for="trans_date">Date</label>
        <input type="date" name="trans_date" class="form-control" placeholder="Transaction Date" value="{{ $voucher->trans_date ? \Carbon\Carbon::parse($voucher->trans_date)->format('Y-m-d') : date('Y-m-d') }}">
    </div>

    <div class="form-group col-md-2">
        <label for="billing_month">Billing Month</label>
        <input type="month" name="billing_month" class="form-control" value="{{ $voucher->billing_month ? \Carbon\Carbon::parse($voucher->billing_month)->format('Y-m') : date('Y-m') }}" required>
    </div>

    <div class="form-group col-md-2">
        <label for="reference_number">Reference # <span class="text-danger">*</span></label>
        <input type="text" name="reference_number" class="form-control" id="reference_number" placeholder="Reference Number" value="{{ $voucher->reference_number }}" required>
    </div>

    <div class="form-group col-md-3">
        <label for="credit_account_id">Credit Account (Bank/Cash) <span class="text-danger">*</span></label>
        <select name="credit_account_id" id="credit_account_id" class="form-control form-select select2" required>
            <option value="">Select Bank/Cash Account</option>
            @foreach($bankCashAccounts as $id => $name)
                @if($id !== '')
                <option value="{{ $id }}" {{ isset($creditEntry) && $creditEntry['account_id'] == $id ? 'selected' : '' }}>{{ $name }}</option>
                @endif
            @endforeach
        </select>
    </div>

    <div class="form-group col-md-2">
        <label for="payment_type">Payment Type</label>
        {!! Form::select('payment_type', App\Helpers\Account::payment_type_list(), $voucher->payment_type, ['class' => 'form-select form-select-sm select2', 'id' => 'payment_type']) !!}
    </div>
</div>

<hr>

<div class="scrollbar">
    <h6 class="mb-3">Debit Entries (Expenses)</h6>

    <div id="expense-voucher-rows">
        @forelse($debitEntries as $index => $entry)
        <div class="expense-voucher-entry border rounded p-3 mb-2">
            <div class="row">
                <div class="form-group col-md-3">
                    <label>Debit Account (Expense) <span class="text-danger">*</span></label>
                    <select name="debit_account_id[]" class="form-control form-select select2 debit-account-select" required>
                        <option value="">Select Expense Account</option>
                        {!! App\Helpers\Accounts::expenseAccountsDropdown($expenseAccounts, $entry['account_id']) !!}
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Narration</label>
                    <input type="text" name="debit_narration[]" class="form-control" placeholder="Narration" value="{{ $entry['narration'] }}">
                </div>
                <div class="form-group col-md-2">
                    <label>Amount <span class="text-danger">*</span></label>
                    <input type="number" step="any" name="amount[]" class="form-control expense-amount" placeholder="Amount" value="{{ $entry['amount'] }}" required>
                </div>
                <div class="form-group col-md-1">
                    <label>VAT %</label>
                    <input type="number" step="any" name="vat_percent[]" class="form-control vat-percent" placeholder="%" value="{{ $entry['vat_percent'] }}">
                </div>
                <div class="form-group col-md-2">
                    <label>VAT Amount</label>
                    <input type="number" step="any" name="vat_amount[]" class="form-control vat-amount" placeholder="VAT" value="{{ $entry['vat_amount'] > 0 ? $entry['vat_amount'] : '' }}" readonly>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger btn-remove-expense-entry"><i class="fa fa-trash"></i></a>
                </div>
            </div>
        </div>
        @empty
        <div class="expense-voucher-entry border rounded p-3 mb-2">
            <div class="row">
                <div class="form-group col-md-3">
                    <label>Debit Account (Expense) <span class="text-danger">*</span></label>
                    <select name="debit_account_id[]" class="form-control form-select select2 debit-account-select" required>
                        <option value="">Select Expense Account</option>
                        {!! App\Helpers\Accounts::expenseAccountsDropdown($expenseAccounts) !!}
                    </select>
                </div>
                <div class="form-group col-md-3">
                    <label>Narration</label>
                    <input type="text" name="debit_narration[]" class="form-control" placeholder="Narration">
                </div>
                <div class="form-group col-md-2">
                    <label>Amount <span class="text-danger">*</span></label>
                    <input type="number" step="any" name="amount[]" class="form-control expense-amount" placeholder="Amount" required>
                </div>
                <div class="form-group col-md-1">
                    <label>VAT %</label>
                    <input type="number" step="any" name="vat_percent[]" class="form-control vat-percent" placeholder="%" value="0">
                </div>
                <div class="form-group col-md-2">
                    <label>VAT Amount</label>
                    <input type="number" step="any" name="vat_amount[]" class="form-control vat-amount" placeholder="VAT" readonly>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <a href="javascript:void(0);" class="text-danger btn-remove-expense-entry"><i class="fa fa-trash"></i></a>
                </div>
            </div>
        </div>
        @endforelse
    </div>

    <button type="button" id="add-expense-row" class="btn btn-success btn-sm mt-2 mb-3">
        <i class="fa fa-plus me-1"></i> Add Entry
    </button>
</div>

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
    if (typeof jQuery === 'undefined') {
        setTimeout(arguments.callee, 50);
        return;
    }

    function initSelect2($container) {
        var $modal = $container.closest('.modal');
        var options = {
            width: '100%',
            placeholder: 'Select...',
            allowClear: true
        };
        if ($modal.length) {
            options.dropdownParent = $modal;
        }
        $container.find('select.select2').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) {
                $(this).select2(options);
            }
        });
    }

    $(document).ready(function() {
        initSelect2($('#expense-voucher-rows'));
        initSelect2($('.row').first());
        calculateTotals();

        $('#add-expense-row').on('click', function() {
            var $firstEntry = $('.expense-voucher-entry:first');

            $firstEntry.find('select.select2').each(function() {
                if ($(this).hasClass('select2-hidden-accessible')) {
                    $(this).select2('destroy');
                }
            });

            var $newEntry = $firstEntry.clone();

            $newEntry.find('.select2-container').remove();
            $newEntry.find('select').val('').removeClass('select2-hidden-accessible').removeAttr('data-select2-id').removeAttr('aria-hidden').removeAttr('tabindex');
            $newEntry.find('input[type="number"]').val('');
            $newEntry.find('input[type="text"]').val('');
            $newEntry.find('.vat-percent').val('0');
            $newEntry.find('[data-select2-id]').removeAttr('data-select2-id');

            $('#expense-voucher-rows').append($newEntry);

            initSelect2($firstEntry);
            initSelect2($newEntry);
        });

        $(document).on('click', '.btn-remove-expense-entry', function() {
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

        $(document).on('change keyup', '.expense-amount, .vat-percent', function() {
            var $entry = $(this).closest('.expense-voucher-entry');
            calculateRowVat($entry);
            calculateTotals();
        });
    });

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
})();
</script>
