<script src="{{ asset('js/modal_custom.js') }}"></script>

<input type="hidden" name="voucher_type" id="voucher_type" value="VP">
@if(!empty($vatReturnId))
<input type="hidden" name="vat_return_id" value="{{ $vatReturnId }}">
@endif

<div class="row mt-0 mb-2">
    <div class="form-group col-md-2">
        <label for="trans_date">Date</label>
        <input type="date" name="trans_date" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="form-group col-md-2">
        <label for="billing_month">Billing Month</label>
        <input type="month" name="billing_month" class="form-control" value="{{ date('Y-m') }}" required>
    </div>
    <div class="form-group col-md-2">
        <label for="reference_number">Reference # <span class="text-danger">*</span></label>
        <input type="text" name="reference_number" class="form-control" id="reference_number" placeholder="Reference Number" value="{{ $prefillReference ?? '' }}" required>
    </div>
    <div class="form-group col-md-3">
        <label for="credit_account_id">Credit Account (Bank/Cash) <span class="text-danger">*</span></label>
        <select name="credit_account_id" id="credit_account_id" class="form-control form-select select2" required>
            <option value="">Select Bank/Cash Account</option>
            @foreach($bankCashAccounts as $id => $name)
                @if($id !== '')
                    <option value="{{ $id }}">{{ $name }}</option>
                @endif
            @endforeach
        </select>
    </div>
    <div class="form-group col-md-2">
        <label for="payment_type">Payment Type</label>
        {!! Form::select('payment_type', App\Helpers\Account::payment_type_list(), null, ['class' => 'form-select form-select-sm select2', 'id' => 'payment_type']) !!}
    </div>
</div>

<hr>

<div class="scrollbar">
    <h6 class="mb-3">Debit Entries (1027 by default; add more below)</h6>

    <div id="vat-voucher-rows">
        {{-- First row: default 1027 debited --}}
        <div class="vat-voucher-entry border rounded p-3 mb-2" data-default-row="1">
            <div class="row">
                <div class="form-group col-md-4">
                    <label>Debit Account <span class="text-danger">*</span></label>
                    <input type="hidden" name="debit_account_id[]" value="{{ $defaultDebitAccountId }}">
                    <input type="text" class="form-control" value="{{ $defaultDebitAccountName }}" readonly disabled>
                </div>
                <div class="form-group col-md-3">
                    <label>Narration</label>
                    <input type="text" name="debit_narration[]" class="form-control" placeholder="Narration" value="VAT Payment">
                </div>
                <div class="form-group col-md-2">
                    <label>Amount <span class="text-danger">*</span></label>
                    <input type="number" step="any" name="amount[]" class="form-control vat-amount" placeholder="Amount" value="{{ $prefillAmount ?? '' }}" required>
                </div>
                <div class="form-group col-md-1 d-flex align-items-end">
                    <span class="text-muted small">(default)</span>
                </div>
            </div>
        </div>
    </div>

    <button type="button" id="add-vat-voucher-row" class="btn btn-success btn-sm mt-2 mb-3">
        <i class="fa fa-plus me-1"></i> Add Entry
    </button>
</div>

{{-- Template for Add Entry rows: account dropdown (debit) + Bank credited in total --}}
<template id="vat-voucher-row-tpl">
    <div class="vat-voucher-entry border rounded p-3 mb-2">
        <div class="row">
            <div class="form-group col-md-4">
                <label>Debit Account <span class="text-danger">*</span></label>
                <select name="debit_account_id[]" class="form-control form-select select2 vat-debit-account" required>
                    <option value="">Select account</option>
                    @foreach($allAccounts as $id => $name)
                        @if($id !== '')
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endif
                    @endforeach
                </select>
            </div>
            <div class="form-group col-md-3">
                <label>Narration</label>
                <input type="text" name="debit_narration[]" class="form-control" placeholder="Narration">
            </div>
            <div class="form-group col-md-2">
                <label>Amount <span class="text-danger">*</span></label>
                <input type="number" step="any" name="amount[]" class="form-control vat-amount" placeholder="Amount" required>
            </div>
            <div class="form-group col-md-1 d-flex align-items-end">
                <a href="javascript:void(0);" class="text-danger btn-remove-vat-entry"><i class="fa fa-trash"></i></a>
            </div>
        </div>
    </div>
</template>

<div class="row mt-3">
    <div class="col-md-6"></div>
    <div class="col-md-6">
        <table class="table table-sm table-borderless">
            <tr>
                <td class="text-end"><strong>Total (Bank credited):</strong></td>
                <td style="width: 150px;"><input type="number" step="any" class="form-control form-control-sm fw-bold" id="vat_voucher_total" readonly></td>
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
        var options = { width: '100%', placeholder: 'Select...', allowClear: true };
        if ($modal.length) options.dropdownParent = $modal;
        $container.find('select.select2').each(function() {
            if (!$(this).hasClass('select2-hidden-accessible')) $(this).select2(options);
        });
    }

    function vatVoucherTotals() {
        var total = 0;
        $('.vat-voucher-entry').each(function() {
            total += parseFloat($(this).find('.vat-amount').val()) || 0;
        });
        $('#vat_voucher_total').val(total.toFixed(2));
    }

    $(document).ready(function() {
        initSelect2($('#vat-voucher-rows'));
        initSelect2($('.row').first());
        vatVoucherTotals();

        $('#add-vat-voucher-row').on('click', function() {
            var tpl = document.getElementById('vat-voucher-row-tpl');
            if (!tpl || !tpl.content) return;
            var $new = $(tpl.content.cloneNode(true));
            $('#vat-voucher-rows').append($new);
            initSelect2($new);
        });

        $(document).on('click', '.btn-remove-vat-entry', function() {
            $(this).closest('.vat-voucher-entry').remove();
            vatVoucherTotals();
        });

        $(document).on('change keyup', '.vat-amount', function() { vatVoucherTotals(); });
    });

    window.vatVoucherTotals = vatVoucherTotals;
})();
</script>
