/**
 * Payment create/edit form helpers (invoice allocation).
 * Loaded globally; invoked after modal HTML is injected (no inline script in AJAX HTML).
 */
(function (window, $) {
  'use strict';

  if (!$) {
    return;
  }

  function currencyCode($root) {
    var fromBoot = $root.find('[data-payment-fields-init]').attr('data-currency');
    if (fromBoot) {
      return fromBoot;
    }
    var fromHtml = document.documentElement.getAttribute('data-app-currency-code');
    if (fromHtml) {
      return fromHtml;
    }
    return 'AED';
  }

  window.initPaymentFieldsForm = function (root) {
    var $root = root ? $(root) : $('#formajax');
    if (!$root.length) {
      $root = $(document);
    }

    if (!$root.find('#payment_amount, [data-payment-fields-init]').length && !$('#payment_amount').length) {
      return;
    }

    // Prefer scoping to the payment form when present
    var $form = $root.is('form') ? $root : $root.find('form#formajax').addBack('form#formajax').first();
    if (!$form.length) {
      $form = $('#formajax');
    }
    var $ctx = $form.length ? $form : $(document);
    var currency = currencyCode($ctx);
    var invoiceTypeVal = $ctx.find('input[name="invoice_type"]').val() || '';
    var isRiderPayment = $ctx.find('[data-salary-invoice-payment="1"]').length > 0
      || $ctx.find('[data-rider-payment="1"]').length > 0
      || ['rider', 'employee'].indexOf(invoiceTypeVal) !== -1;
    var isRiderOnlyPayment = $ctx.find('[data-rider-payment="1"]').length > 0
      || invoiceTypeVal === 'rider';
    var $boot = $ctx.find('[data-payment-fields-init]').first();
    var preservePrimaryAmount = $boot.attr('data-preserve-payment-amount') === '1';
    var savedPrimaryAmount = parseFloat($boot.attr('data-saved-payment-amount'));
    if (isNaN(savedPrimaryAmount)) {
      savedPrimaryAmount = parseFloat($ctx.find('#payment_amount').attr('data-saved-amount'));
    }
    if (isNaN(savedPrimaryAmount)) {
      savedPrimaryAmount = null;
    }

    function getCreatePaymentType() {
      if (!isRiderOnlyPayment || !$ctx.find('#create_payment_type').length) {
        return 'payment';
      }
      return $ctx.find('#create_payment_type').val() || '';
    }

    function isAdvanceLoanType() {
      var type = getCreatePaymentType();
      return type === 'advance_payment' || type === 'advance_loan';
    }

    function isPaymentCreateType() {
      var type = getCreatePaymentType();
      return type === 'salary_payment' || type === 'payment' || type === 'c3';
    }

    function enforceSingleInvoiceSelection($keepCheckbox) {
      if (!isRiderOnlyPayment) {
        return;
      }
      $ctx.find('#invoices-table tbody .invoice-checkbox:checked').each(function () {
        if ($keepCheckbox && this === $keepCheckbox[0]) {
          return;
        }
        $(this).prop('checked', false);
        var $otherRow = $(this).closest('tr');
        var $otherInput = $otherRow.find('.payment-amount');
        $otherInput.prop('disabled', true).val(0);
        var otherRef = $otherRow.data('reference') || '';
        if (otherRef) {
          var refVal = ($ctx.find('#reference').val() || '').replace(otherRef, '').trim();
          $ctx.find('#reference').val(refVal);
        }
      });
    }

    /**
     * Rider single-invoice focus: keep only the selected row visible while checked;
     * show all payee-matching rows again when nothing is selected.
     */
    function syncRiderInvoiceRowVisibility() {
      if (!isRiderOnlyPayment) {
        return;
      }

      var $checked = $ctx.find('#invoices-table tbody .invoice-checkbox:checked');
      if ($checked.length === 1) {
        var keepId = String($checked.first().closest('tr').data('invoice-id') || '');
        $ctx.find('#invoices-table tbody tr').each(function () {
          var $row = $(this);
          if (String($row.data('invoice-id') || '') === keepId) {
            $row.show();
          } else {
            $row.hide();
          }
        });
        return;
      }

      // No selection: restore rows using the normal payee filter.
      filterInvoiceRows({ skipSelectionSync: true });
    }

    function normalizeMonthValue(value) {
      var raw = String(value || '').trim();
      if (!raw) {
        return '';
      }
      // type="month" requires YYYY-MM (not YYYY-MM-DD)
      var match = raw.match(/^(\d{4})-(\d{2})/);
      if (match) {
        return match[1] + '-' + match[2];
      }
      return raw;
    }

    function setBillingMonthValue(value) {
      var $billingInput = $ctx.find('#billing_month');
      if (!$billingInput.length) {
        return;
      }
      var month = normalizeMonthValue(value);
      if (!month) {
        return;
      }
      $billingInput.val(month);
      // Some browsers keep a stale empty display if value was previously invalid (e.g. Y-m-d).
      if ($billingInput.val() !== month) {
        $billingInput.attr('value', month);
        $billingInput.val(month);
      }
    }

    function syncBillingMonthFromSelectedInvoice() {
      if (!isRiderOnlyPayment || !isPaymentCreateType()) {
        return;
      }
      var $checked = $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox:checked').first().closest('tr');
      var billingMonth = $checked.data('billing-month') || '';
      if (billingMonth) {
        setBillingMonthValue(billingMonth);
      }
    }

    function applyCreatePaymentTypeUi() {
      if (!isRiderOnlyPayment || !$ctx.find('#create_payment_type').length) {
        return;
      }
      var type = getCreatePaymentType();
      var $billingGroup = $ctx.find('#billing-month-group');
      var $billingInput = $ctx.find('#billing_month');
      var $selectAll = $ctx.find('#select-all-invoices');

      $selectAll.addClass('d-none').hide().prop('checked', false).prop('disabled', true);

      if (isAdvanceLoanType()) {
        $billingGroup.css('display', '').removeClass('d-none').show();
        $billingInput.prop('disabled', false).prop('required', true);
        if (!normalizeMonthValue($billingInput.val())) {
          setBillingMonthValue($billingInput.attr('value') || new Date().toISOString().slice(0, 7));
        } else {
          setBillingMonthValue($billingInput.val());
        }
      } else if (isPaymentCreateType()) {
        $billingGroup.hide();
        // Hidden required fields block HTML5 submit — always clear required when hidden.
        $billingInput.prop('required', false);
        syncBillingMonthFromSelectedInvoice();
      } else {
        $billingGroup.hide();
        $billingInput.prop('required', false);
      }
    }

    /**
     * Payment Type options depend on the selected rider's payable invoices:
     * - has invoices  → Payment only (Advance Loan hidden)
     * - no invoices   → Advance Loan only (Payment hidden)
     * - no payee yet  → both options visible
     */
    function selectedPayeeState() {
      var selectedCustomerId = getSelectedCustomerId();
      var selectedCustomerName = getSelectedCustomerName();
      var $payee = $ctx.find('#payee_account_id, [name="payee_account_id"]').first();
      var payeeValue = $payee.length ? String($payee.val() || '') : '';
      var hasPayee = !!(selectedCustomerId || selectedCustomerName || payeeValue);

      if (!hasPayee) {
        return { hasPayee: false, hasInvoices: false };
      }

      var $rows = $ctx.find('#invoices-table tbody tr');
      if (!$rows.length) {
        return { hasPayee: true, hasInvoices: false };
      }

      var found = false;
      $rows.each(function () {
        var $row = $(this);
        var invoiceCustomerId = $row.data('customer-id');
        var invoiceCustomerName = $row.data('customer-name');
        var matches = false;

        if (selectedCustomerId && invoiceCustomerId) {
          matches = String(invoiceCustomerId) === String(selectedCustomerId);
        }
        if (!matches && selectedCustomerName && invoiceCustomerName) {
          matches = String(invoiceCustomerName).toLowerCase() === String(selectedCustomerName).toLowerCase();
        }
        // Locked payee with no customer metadata: treat any row as belonging to that payee
        // when the form was scoped to a single rider (no selectable payee options).
        if (!matches && !selectedCustomerId && !selectedCustomerName && payeeValue && $payee.is('input')) {
          matches = true;
        }

        if (matches) {
          found = true;
          return false;
        }
      });

      return { hasPayee: true, hasInvoices: found };
    }

    function ensurePaymentTypeOption($typeSelect, value, label, dataKey) {
      var $opt = $typeSelect.find('option[value="' + value + '"]');
      if ($opt.length) {
        return $opt;
      }
      var optionHtml = $typeSelect.data(dataKey) || ('<option value="' + value + '">' + label + '</option>');
      $typeSelect.append(optionHtml);
      return $typeSelect.find('option[value="' + value + '"]');
    }

    function removePaymentTypeOption($typeSelect, value, dataKey) {
      var $opt = $typeSelect.find('option[value="' + value + '"]');
      if ($opt.length) {
        if (!$typeSelect.data(dataKey)) {
          $typeSelect.data(dataKey, $opt.prop('outerHTML'));
        }
        $opt.remove();
      }
    }

    function syncAdvanceLoanOptionVisibility() {
      if (!isRiderOnlyPayment) {
        return;
      }

      var $typeSelect = $ctx.find('#create_payment_type');
      if (!$typeSelect.length) {
        return;
      }

      var state = selectedPayeeState();
      var currentVal = $typeSelect.val() || '';
      var nextVal = currentVal;

      if (!state.hasPayee) {
        ensurePaymentTypeOption($typeSelect, 'salary_payment', 'Salary Payment', 'salaryPaymentOptionHtml');
        ensurePaymentTypeOption($typeSelect, 'advance_payment', 'Advance Payment', 'advancePaymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'c3', 'c3OptionHtml');
        removePaymentTypeOption($typeSelect, 'payment', 'paymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'advance_loan', 'advanceLoanOptionHtml');
      } else if (state.hasInvoices) {
        // Rider has invoices: Salary Payment only.
        ensurePaymentTypeOption($typeSelect, 'salary_payment', 'Salary Payment', 'salaryPaymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'advance_payment', 'advancePaymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'advance_loan', 'advanceLoanOptionHtml');
        removePaymentTypeOption($typeSelect, 'payment', 'paymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'c3', 'c3OptionHtml');
        if (currentVal !== 'salary_payment') {
          nextVal = 'salary_payment';
        }
      } else {
        // Rider has no invoices: Advance Payment only.
        ensurePaymentTypeOption($typeSelect, 'advance_payment', 'Advance Payment', 'advancePaymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'c3', 'c3OptionHtml');
        removePaymentTypeOption($typeSelect, 'salary_payment', 'salaryPaymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'payment', 'paymentOptionHtml');
        removePaymentTypeOption($typeSelect, 'advance_loan', 'advanceLoanOptionHtml');
        nextVal = 'advance_payment';
      }

      if (nextVal && nextVal !== currentVal) {
        $typeSelect.val(nextVal);
        applyCreatePaymentTypeUi();
      } else if (!$typeSelect.val() && nextVal) {
        $typeSelect.val(nextVal);
        applyCreatePaymentTypeUi();
      }

      if ($typeSelect.hasClass('select2-hidden-accessible')) {
        $typeSelect.trigger('change.select2');
      }
    }

    function invoiceAmountCap($input) {
      // Prefer explicit invoice total (TOTAL AMOUNT), not remaining balance.
      var maxBalance = parseFloat($input.attr('data-max'));
      if (isNaN(maxBalance)) {
        maxBalance = parseFloat($input.closest('tr').data('invoice-total'));
      }
      if (isNaN(maxBalance)) {
        maxBalance = parseFloat($input.closest('tr').data('balance')) || 0;
      }
      return maxBalance;
    }

    function capAmountToBalance($input, amount) {
      var maxBalance = invoiceAmountCap($input);
      if (maxBalance >= 0 && amount > maxBalance + 0.009) {
        if (typeof toastr !== 'undefined') {
          toastr.warning('Payment amount cannot exceed the selected invoice amount (' + currency + ' ' + maxBalance.toFixed(2) + ').');
        }
        return maxBalance;
      }
      return amount;
    }

    function applyPrimaryAmountDisplay(amount) {
      var value = parseFloat(amount);
      if (isNaN(value)) {
        value = 0;
      }
      $ctx.find('#payment_amount').val(value.toFixed(2));
      $ctx.find('#display_amount').text(value.toFixed(2));
      var bankCharges = parseFloat($ctx.find('#bank_charges').val()) || 0;
      $ctx.find('#total_debit').text((value + bankCharges).toFixed(2));
    }

    function restoreSavedPrimaryAmount() {
      if (!preservePrimaryAmount || savedPrimaryAmount === null) {
        return;
      }
      applyPrimaryAmountDisplay(savedPrimaryAmount);
      validatePaymentDistribution();
    }

    $ctx.find('.select2').each(function () {
      var $el = $(this);
      if ($el.hasClass('select2-hidden-accessible')) {
        try {
          $el.select2('destroy');
        } catch (e) {
          // Ignore destroy errors from prior modal inits.
        }
      }
      // allowClear requires a placeholder + empty <option>, or Select2 throws on clear.
      var emptyOptionText = $.trim($el.find('option[value=""]').first().text() || '');
      if (!emptyOptionText) {
        $el.prepend('<option value=""></option>');
        emptyOptionText = 'Select';
      }
      $el.select2({
        dropdownParent: $form.length ? $form : ($('#modalTopbody').length ? $('#modalTopbody') : $(document.body)),
        allowClear: true,
        placeholder: emptyOptionText,
        width: '100%'
      });
    });

    function getSelectedCustomerId() {
      var $payeeSelect = $ctx.find('#payee_account_id, [name="payee_account_id"]');
      var selectedOption = $payeeSelect.find('option:selected');

      var customerId = selectedOption.data('customerid') ||
        selectedOption.data('customer-id') ||
        selectedOption.data('customerId');

      if (!customerId && $ctx.find('input[name="payee_account_id"]').length) {
        customerId = $ctx.find('input[name="payee_account_id"]').data('customer-id') ||
          $ctx.find('input[name="payee_account_id"]').data('customerId');
      }

      return customerId;
    }

    function getSelectedCustomerName() {
      var $payeeSelect = $ctx.find('#payee_account_id, [name="payee_account_id"]');
      var selectedOption = $payeeSelect.find('option:selected');
      if (!selectedOption.val()) return null;

      var customerName = selectedOption.data('customername') ||
        selectedOption.data('customer-name') ||
        selectedOption.data('customerName');

      if (!customerName && selectedOption.text()) {
        var text = selectedOption.text();
        customerName = text.split(' - ')[1] || text;
      }

      return customerName;
    }

    function updateSelectAllState() {
      if (isRiderOnlyPayment) {
        $ctx.find('#select-all-invoices').prop('checked', false).prop('disabled', true).addClass('d-none').hide();
        return;
      }
      var totalVisible = $ctx.find('#invoices-table tbody tr:visible').length;
      var checkedVisible = $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox:checked').length;

      if (totalVisible === 0) {
        $ctx.find('#select-all-invoices').prop('checked', false).prop('disabled', true);
      } else {
        $ctx.find('#select-all-invoices').prop('disabled', false);
        $ctx.find('#select-all-invoices').prop('checked', checkedVisible === totalVisible && totalVisible > 0);
      }
    }

    function updateTotalPayment() {
      var total = 0;
      $ctx.find('.payment-amount:not(:disabled)').each(function () {
        var val = parseFloat($(this).val()) || 0;
        total += val;
      });
      $ctx.find('#total_invoice_payment').text(total.toFixed(2));
      $ctx.find('#total-selected-payment').text(total.toFixed(2));
      return total;
    }

    function validatePaymentDistribution() {
      var paymentAmount = parseFloat($ctx.find('#payment_amount').val()) || 0;
      var totalPayment = parseFloat($ctx.find('#total_invoice_payment').text()) || 0;
      var difference = paymentAmount - totalPayment;

      var $diff = $ctx.find('#payment_difference');
      var $diffAmt = $diff.find('[data-payment-diff-amount]');
      if ($diffAmt.length) {
        $diffAmt.text(difference.toFixed(2));
      } else {
        $diff.text(currency + ' ' + difference.toFixed(2));
      }

      if (Math.abs(difference) < 0.01) {
        $ctx.find('#payment_difference').removeClass('text-danger').removeClass('text-warning').addClass('text-success');
      } else if (difference > 0) {
        $ctx.find('#payment_difference').removeClass('text-success').removeClass('text-danger').addClass('text-warning');
      } else if (difference < 0) {
        $ctx.find('#payment_difference').removeClass('text-success').removeClass('text-warning').addClass('text-danger');
      }
    }

    function syncInvoicePaymentsToAmount(paymentAmount) {
      var totalPayment = parseFloat($ctx.find('#total_invoice_payment').text()) || 0;

      if (totalPayment > paymentAmount && paymentAmount > 0) {
        var excess = totalPayment - paymentAmount;
        var $lastCheckedInput = $ctx.find('.payment-amount:not(:disabled)').last();

        if ($lastCheckedInput.length) {
          var currentVal = parseFloat($lastCheckedInput.val()) || 0;
          var newVal = currentVal - excess;

          if (newVal < 0) {
            newVal = 0;
          }

          $lastCheckedInput.val(newVal.toFixed(2));
          $lastCheckedInput.trigger('change');
          if (typeof toastr !== 'undefined') {
            toastr.warning('Total payment adjusted to match payment amount');
          }
        }
      }
    }

    function syncRiderPaymentAmountFromInvoices() {
      if (!isRiderPayment) {
        return;
      }

      var total = updateTotalPayment();
      var existing = parseFloat($ctx.find('#payment_amount').val()) || 0;

      // Edit mode: never overwrite the primary amount from invoice lines.
      if (preservePrimaryAmount) {
        applyPrimaryAmountDisplay(existing);
        validatePaymentDistribution();
        return;
      }

      // Create mode: raise primary amount to cover invoice lines, never lower it.
      if (total > existing + 0.009) {
        applyPrimaryAmountDisplay(total);
      } else {
        applyPrimaryAmountDisplay(existing);
      }
      validatePaymentDistribution();
    }

    function resolvePayeeLedgerBalance() {
      var $payee = $ctx.find('#payee_account_id, [name="payee_account_id"]').first();
      if (!$payee.length) {
        return 0;
      }
      // Locked payee (hidden input) carries data-ledger-balance directly.
      if ($payee.is('input')) {
        return parseFloat($payee.attr('data-ledger-balance')) || 0;
      }
      var $selected = $payee.find('option:selected');
      if (!$selected.length || !$selected.val()) {
        return 0;
      }
      return parseFloat($selected.attr('data-ledger-balance')) || 0;
    }

    function parsePaymentBreakdown($row) {
      if (!$row || !$row.length) {
        return null;
      }
      var raw = $row.attr('data-payment-breakdown');
      if (!raw) {
        // jQuery .data() may already have decoded/parsed the attribute.
        var fromData = $row.data('paymentBreakdown') || $row.data('payment-breakdown');
        if (fromData && typeof fromData === 'object') {
          return fromData;
        }
        if (typeof fromData === 'string' && fromData) {
          raw = fromData;
        } else {
          return null;
        }
      }
      if (typeof raw === 'object') {
        return raw;
      }
      try {
        return JSON.parse(raw);
      } catch (e) {
        // Recover from accidental HTML double-encoding (&quot; → ").
        try {
          var textarea = document.createElement('textarea');
          textarea.innerHTML = raw;
          return JSON.parse(textarea.value);
        } catch (e2) {
          return null;
        }
      }
    }

    function moneyText(amount) {
      var value = parseFloat(amount);
      if (isNaN(value)) {
        value = 0;
      }
      return value.toFixed(2);
    }

    function escapeHtml(text) {
      return $('<div>').text(text == null ? '' : String(text)).html();
    }

    function formatSignedMoney(amount, sign) {
      var value = Math.abs(parseFloat(amount) || 0);
      return sign + moneyText(value).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function updateRiderPaymentBreakdown() {
      if (!isRiderOnlyPayment) {
        return;
      }
      var $panels = $ctx.find('#rpf-adj-panels');
      if (!$panels.length) {
        return;
      }
      var $checked = $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox:checked').first().closest('tr');
      var breakdown = parsePaymentBreakdown($checked);
      if (!$checked.length || !breakdown) {
        $panels.addClass('d-none');
        return;
      }

      $panels.removeClass('d-none');
      $ctx.find('#rpf-breakdown-month').text(breakdown.billing_month_label || '');
      $ctx.find('#rpf-breakdown-payable').text(moneyText(breakdown.payable));

      var previous = parseFloat(breakdown.previous_balance) || 0;
      var deductions = Array.isArray(breakdown.deductions) ? breakdown.deductions : [];
      var additions = Array.isArray(breakdown.additions) ? breakdown.additions : [];
      var monthDeductions = parseFloat(breakdown.month_deductions_total) || 0;
      var monthAdditions = parseFloat(breakdown.month_additions_total) || 0;
      var totalDeductions = !isNaN(parseFloat(breakdown.total_deductions))
        ? parseFloat(breakdown.total_deductions)
        : (monthDeductions + (previous > 0 ? previous : 0));
      var totalAdditions = !isNaN(parseFloat(breakdown.total_additions))
        ? parseFloat(breakdown.total_additions)
        : (monthAdditions + (previous < 0 ? Math.abs(previous) : 0));

      var deductionRows = '';
      if (previous > 0.009) {
        deductionRows += '<tr><td>Previous Balance (Deduction)</td>'
          + '<td class="rpf-inv-num">' + formatSignedMoney(previous, '-') + '</td></tr>';
      }
      deductions.forEach(function (line) {
        deductionRows += '<tr><td>' + escapeHtml(line.name || 'Deduction') + '</td>'
          + '<td class="rpf-inv-num">' + formatSignedMoney(line.amount, '-') + '</td></tr>';
      });
      if (!deductionRows) {
        deductionRows = '<tr><td colspan="2" class="rpf-adj-empty">No ledger deductions for this billing month</td></tr>';
      }
      $ctx.find('#rpf-deductions-body').html(deductionRows);
      $ctx.find('#rpf-total-deductions').text(formatSignedMoney(totalDeductions, '-'));

      var additionRows = '';
      if (previous < -0.009) {
        additionRows += '<tr><td>Previous Balance (Addition)</td>'
          + '<td class="rpf-inv-num">' + formatSignedMoney(Math.abs(previous), '+') + '</td></tr>';
      }
      additions.forEach(function (line) {
        additionRows += '<tr><td>' + escapeHtml(line.name || 'Addition') + '</td>'
          + '<td class="rpf-inv-num">' + formatSignedMoney(line.amount, '+') + '</td></tr>';
      });

      var $additionsWrap = $ctx.find('#rpf-additions-wrap');
      if (additionRows) {
        $additionsWrap.removeClass('d-none');
        $ctx.find('#rpf-additions-body').html(additionRows);
        $ctx.find('#rpf-total-additions').text(formatSignedMoney(totalAdditions, '+'));
      } else {
        $additionsWrap.addClass('d-none');
        $ctx.find('#rpf-additions-body').empty();
        $ctx.find('#rpf-total-additions').text('+0.00');
      }
    }

    function applyPayableFromSelectedInvoice($row) {
      if (!isRiderOnlyPayment || !isPaymentCreateType() || preservePrimaryAmount) {
        return;
      }
      if (!$row || !$row.length) {
        return;
      }
      var breakdown = parsePaymentBreakdown($row);
      var payable = breakdown
        ? (parseFloat(breakdown.payable) || 0)
        : (parseFloat($row.data('balance')) || 0);
      payable = Math.max(0, payable);
      var $paymentInput = $row.find('.payment-amount');
      payable = capAmountToBalance($paymentInput, payable);
      if (payable > 0.009) {
        $paymentInput.val(payable.toFixed(2));
        applyPrimaryAmountDisplay(payable);
      } else {
        $paymentInput.val('');
        applyPrimaryAmountDisplay(0);
      }
      $paymentInput.trigger('change');
    }

    function updateRiderBalanceDueDisplay() {
      if (!isRiderOnlyPayment) {
        return;
      }
      var $checked = $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox:checked').first().closest('tr');
      var remaining = $checked.length ? (parseFloat($checked.data('balance')) || 0) : 0;
      var breakdown = parsePaymentBreakdown($checked);
      if (breakdown && !isNaN(parseFloat(breakdown.payable))) {
        remaining = parseFloat(breakdown.payable) || 0;
      }
      $ctx.find('#rider_balance_due').text(remaining.toFixed(2));
      $ctx.find('#rider_ledger_due').text(resolvePayeeLedgerBalance().toFixed(2));

      // Cap primary payment amount to invoice TOTAL AMOUNT (not remaining).
      var invoiceTotal = 0;
      if ($checked.length) {
        invoiceTotal = invoiceAmountCap($checked.find('.payment-amount').first());
      }
      var $paymentAmount = $ctx.find('#payment_amount');
      if ($checked.length && invoiceTotal > 0) {
        $paymentAmount.attr('max', invoiceTotal.toFixed(2));
        $paymentAmount.attr('data-invoice-max', invoiceTotal.toFixed(2));
      } else {
        $paymentAmount.removeAttr('max');
        $paymentAmount.removeAttr('data-invoice-max');
      }

      updateRiderPaymentBreakdown();
    }

    function applyInvoiceDateFromSelection() {
      if (!isRiderPayment) {
        return;
      }

      var $checked = $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox:checked').first().closest('tr');
      var invoiceDate = $checked.data('invoice-date') || '';
      $ctx.find('#date_of_invoice, [name="date_of_invoice"]').val(invoiceDate || '');
      syncBillingMonthFromSelectedInvoice();
      updateRiderBalanceDueDisplay();
    }

    function filterInvoiceRows(options) {
      options = options || {};
      var selectedCustomerId = getSelectedCustomerId();
      var selectedCustomerName = getSelectedCustomerName();
      var visibleCount = 0;
      var hasPayeeFilter = !!(selectedCustomerId || selectedCustomerName);

      // While a rider invoice is focused, don't re-expand the list via payee filter.
      if (
        !options.skipSelectionSync
        && isRiderOnlyPayment
        && $ctx.find('#invoices-table tbody .invoice-checkbox:checked').length === 1
      ) {
        syncRiderInvoiceRowVisibility();
        updateSelectAllState();
        updateTotalPayment();
        validatePaymentDistribution();
        applyInvoiceDateFromSelection();
        syncAdvanceLoanOptionVisibility();
        return;
      }

      $ctx.find('#invoices-table tbody tr').each(function () {
        var $row = $(this);
        var invoiceCustomerId = $row.data('customer-id');
        var invoiceCustomerName = $row.data('customer-name');
        var matches = true;

        if (hasPayeeFilter) {
          matches = false;
          if (selectedCustomerId && invoiceCustomerId) {
            matches = (String(invoiceCustomerId) === String(selectedCustomerId));
          }
          if (!matches && selectedCustomerName && invoiceCustomerName) {
            matches = (invoiceCustomerName.toLowerCase() === selectedCustomerName.toLowerCase());
          }
        }

        if (matches) {
          $row.show();
          visibleCount++;
        } else {
          var $checkbox = $row.find('.invoice-checkbox');
          if ($checkbox.prop('checked')) {
            $checkbox.prop('checked', false).trigger('change');
          }
          $row.hide();
        }
      });

      if (visibleCount === 0 && hasPayeeFilter && typeof toastr !== 'undefined') {
        toastr.info(isRiderPayment
          ? 'No pending invoices found for this payee'
          : 'No invoices found for this customer');
      }

      updateSelectAllState();
      updateTotalPayment();
      validatePaymentDistribution();
      applyInvoiceDateFromSelection();
      syncAdvanceLoanOptionVisibility();
      if (isRiderPayment && !options.skipSelectionSync) {
        syncRiderPaymentAmountFromInvoices();
      }
    }

    function calculateTotals() {
      var bankCharges = parseFloat($ctx.find('#bank_charges').val()) || 0;
      $ctx.find('#display_charges').text(bankCharges.toFixed(2));
    }

    $ctx.off('.paymentFields');

    $ctx.on('change.paymentFields', '#payee_account_id, [name="payee_account_id"]', function () {
      filterInvoiceRows();
      updateRiderBalanceDueDisplay();
    });
    // Select2 may fire these instead of native change in modals.
    $ctx.on(
      'select2:select.paymentFields select2:clear.paymentFields',
      '#payee_account_id, [name="payee_account_id"]',
      function () {
        filterInvoiceRows();
        updateRiderBalanceDueDisplay();
      }
    );

    function onCreatePaymentTypeChanged() {
      applyCreatePaymentTypeUi();
      // Re-apply single selection when switching types.
      var $checked = $ctx.find('#invoices-table tbody .invoice-checkbox:checked');
      if ($checked.length > 1) {
        enforceSingleInvoiceSelection($checked.first());
        updateTotalPayment();
        validatePaymentDistribution();
        if (isRiderPayment) {
          syncRiderPaymentAmountFromInvoices();
        }
      }
      applyInvoiceDateFromSelection();
    }

    $ctx.on('change.paymentFields', '#create_payment_type', onCreatePaymentTypeChanged);
    // Select2 often fires these instead of (or in addition to) native change in modals.
    $ctx.on('select2:select.paymentFields select2:clear.paymentFields', '#create_payment_type', onCreatePaymentTypeChanged);

    $ctx.on('change.paymentFields', '.invoice-checkbox', function () {
      var $row = $(this).closest('tr');
      var $paymentInput = $row.find('.payment-amount');
      var paymentAmount = parseFloat($ctx.find('#payment_amount').val()) || 0;
      var totalSelectedPayment = parseFloat($ctx.find('#total-selected-payment').text()) || 0;
      var difference = (paymentAmount > 0) ? paymentAmount - totalSelectedPayment : 0;
      var rowRef = $row.data('reference') || '';
      var Reference = $ctx.find('#reference').val() || '';
      var advanceLoan = isAdvanceLoanType();

      if ($(this).prop('checked')) {
        if (isRiderOnlyPayment) {
          enforceSingleInvoiceSelection($(this));
        }

        $paymentInput.prop('disabled', false);

        if (!Reference.includes(rowRef)) {
          $ctx.find('#reference').val((Reference + ' ' + rowRef).trim());
        }

        if (advanceLoan) {
          // Advance loan uses the primary amount; line amount is reference-only (0).
          $paymentInput.val('0.00');
        } else if (isRiderOnlyPayment && isPaymentCreateType()) {
          // Auto-fill payable from invoice total − deductions − previous balance − paid.
          applyPayableFromSelectedInvoice($row);
        } else if (!$paymentInput.val() || parseFloat($paymentInput.val()) === 0) {
          var balanceDue = parseFloat($row.data('balance')) || 0;
          var fillAmount = balanceDue;

          // Cap to remaining payment amount when a top-level amount is already set.
          if (fillAmount > difference && difference > 0) {
            $paymentInput.val(difference.toFixed(2));
            if (typeof toastr !== 'undefined') {
              toastr.warning('Total Selected Payment cannot exceed Payment Amount.');
            }
          } else if (Math.abs(fillAmount) >= 0.01) {
            $paymentInput.val(fillAmount.toFixed(2));
          }
          $paymentInput.trigger('change');
        }

        var invoiceCustomerId = $row.data('customer-id');
        var $payeeSelect = $ctx.find('#payee_account_id, [name="payee_account_id"]');
        var currentSelection = $payeeSelect.val();

        if (invoiceCustomerId && !currentSelection) {
          var matchedOption = null;
          $payeeSelect.find('option').each(function () {
            var optionCustomerId = $(this).data('customerid') ||
              $(this).data('customer-id') ||
              $(this).data('customerId');
            if (optionCustomerId && String(optionCustomerId) === String(invoiceCustomerId)) {
              matchedOption = $(this);
              return false;
            }
          });

          if (matchedOption && matchedOption.length) {
            $payeeSelect.val(matchedOption.val()).trigger('change');
            if (typeof toastr !== 'undefined') {
              toastr.info('Customer "' + $row.data('customer-name') + '" selected automatically');
            }
          }
        }
      } else {
        Reference = Reference.replace(rowRef, '').trim();
        $ctx.find('#reference').val(Reference);
        $paymentInput.prop('disabled', true);
        $paymentInput.val(0);
        $paymentInput.trigger('change');
      }

      if (isRiderOnlyPayment) {
        syncRiderInvoiceRowVisibility();
        updateRiderBalanceDueDisplay();
        updateRiderPaymentBreakdown();
      }

      updateTotalPayment();
      validatePaymentDistribution();
      updateSelectAllState();
      applyInvoiceDateFromSelection();
      if (isRiderPayment && !advanceLoan && !(isRiderOnlyPayment && isPaymentCreateType())) {
        syncRiderPaymentAmountFromInvoices();
      } else if (advanceLoan) {
        validatePaymentDistribution();
      } else if (isRiderOnlyPayment && isPaymentCreateType()) {
        validatePaymentDistribution();
      }
    });

    $ctx.on('keyup.paymentFields change.paymentFields', '.payment-amount', function () {
      var enteredAmount = parseFloat($(this).val()) || 0;
      var paymentAmount = parseFloat($ctx.find('#payment_amount').val()) || 0;
      var total = updateTotalPayment();

      if (isRiderOnlyPayment && isPaymentCreateType()) {
        var capped = capAmountToBalance($(this), enteredAmount);
        if (capped !== enteredAmount) {
          $(this).val(capped.toFixed(2));
          enteredAmount = capped;
        }
      }

      // Salary payments sync the top amount from invoice lines; leasing caps lines to the top amount.
      if (!isRiderPayment && total > paymentAmount && paymentAmount > 0) {
        var excess = total - paymentAmount;
        var newVal = enteredAmount - excess;
        if (newVal > 0) {
          $(this).val(newVal);
          enteredAmount = newVal;
          if (typeof toastr !== 'undefined') {
            toastr.warning('Total Selected Payment cannot exceed Payment Amount. Adjusted to fit remaining amount.');
          }
        } else {
          $(this).val(0);
          enteredAmount = 0;
        }
      }

      if (enteredAmount < 0) {
        var rowBalance = parseFloat($(this).closest('tr').data('balance')) || 0;
        if (rowBalance >= 0) {
          $(this).val(0);
        }
      }

      updateTotalPayment();
      validatePaymentDistribution();
      if (isRiderPayment && !isAdvanceLoanType()) {
        syncRiderPaymentAmountFromInvoices();
      }
    });

    $ctx.on('keyup.paymentFields change.paymentFields', '#payment_amount', function () {
      var amount = parseFloat($(this).val()) || 0;
      var bankCharges = parseFloat($ctx.find('#bank_charges').val()) || 0;

      if (preservePrimaryAmount) {
        savedPrimaryAmount = amount;
      }

      if (isRiderOnlyPayment && isPaymentCreateType()) {
        var $activeLine = $ctx.find('.payment-amount:not(:disabled)').first();
        var invoiceMax = parseFloat($(this).attr('data-invoice-max'));
        if (isNaN(invoiceMax) && $activeLine.length) {
          invoiceMax = invoiceAmountCap($activeLine);
        }
        if (!isNaN(invoiceMax) && invoiceMax >= 0 && amount > invoiceMax + 0.009) {
          amount = invoiceMax;
          $(this).val(amount.toFixed(2));
          if (typeof toastr !== 'undefined') {
            toastr.warning('Payment amount cannot exceed the selected invoice amount (' + currency + ' ' + invoiceMax.toFixed(2) + ').');
          }
        } else if ($activeLine.length) {
          amount = capAmountToBalance($activeLine, amount);
          $(this).val(amount.toFixed(2));
        }
      }

      $ctx.find('#display_amount').text(amount.toFixed(2));
      $ctx.find('#total_debit').text((amount + bankCharges).toFixed(2));

      if (isRiderPayment && !isAdvanceLoanType()) {
        var $activeAmounts = $ctx.find('.payment-amount:not(:disabled)');
        if ($activeAmounts.length === 1 && !preservePrimaryAmount) {
          // Single selected invoice on create: keep the line in sync with the primary amount.
          var $line = $activeAmounts.first();
          var rowBalance = parseFloat($line.closest('tr').data('balance')) || 0;
          var lineAmount = amount;
          if (isRiderOnlyPayment && isPaymentCreateType()) {
            lineAmount = capAmountToBalance($line, amount);
          }
          if (Math.abs(rowBalance) >= 0.01 || lineAmount > 0) {
            $line.val(lineAmount.toFixed(2));
          }
          updateTotalPayment();
          validatePaymentDistribution();
        } else {
          // Multi-invoice (or edit): allow primary > allocated total; only trim lines if primary drops.
          updateTotalPayment();
          validatePaymentDistribution();
          syncInvoicePaymentsToAmount(amount);
        }
      } else if (isAdvanceLoanType()) {
        updateTotalPayment();
        validatePaymentDistribution();
      } else {
        updateTotalPayment();
        validatePaymentDistribution();
        syncInvoicePaymentsToAmount(amount);
      }
    });

    $ctx.on('keyup.paymentFields change.paymentFields', '#bank_charges', function () {
      var bankCharges = parseFloat($(this).val()) || 0;
      var paymentAmount = parseFloat($ctx.find('#payment_amount').val()) || 0;

      $ctx.find('#display_charges').text(bankCharges.toFixed(2));
      $ctx.find('#total_debit').text((paymentAmount + bankCharges).toFixed(2));

      // Bank charges are ledger-only — never change invoice allocation lines.
      calculateTotals();
      validatePaymentDistribution();
    });

    $ctx.on('change.paymentFields', '#select-all-invoices', function () {
      if (isRiderOnlyPayment) {
        $(this).prop('checked', false);
        return;
      }
      var isChecked = $(this).prop('checked');
      $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox').prop('checked', isChecked).trigger('change');
    });

    setTimeout(function () {
      applyCreatePaymentTypeUi();
      var $payeeSelect = $ctx.find('#payee_account_id, [name="payee_account_id"]');
      if ($payeeSelect.val() || isRiderPayment) {
        filterInvoiceRows();
      } else {
        syncAdvanceLoanOptionVisibility();
      }
      var openedInvoiceId = $ctx.find('[data-opened-invoice-id]').attr('data-opened-invoice-id');
      if (openedInvoiceId) {
        var $openedCb = $ctx.find('#invoices-table tbody tr[data-invoice-id="' + openedInvoiceId + '"] .invoice-checkbox');
        if ($openedCb.length && $openedCb.closest('tr').is(':visible')) {
          if (!$openedCb.prop('checked')) {
            $openedCb.prop('checked', true);
          }
          $openedCb.trigger('change');
        }
      }
      if (!isRiderPayment) {
        $ctx.find('#payment_amount').trigger('change');
      }
      updateSelectAllState();
      calculateTotals();
      applyInvoiceDateFromSelection();
      if (isRiderOnlyPayment) {
        updateRiderBalanceDueDisplay();
        updateRiderPaymentBreakdown();
      }
      // After all invoice sync, put the DB primary amount back on edit.
      restoreSavedPrimaryAmount();
    }, 100);
  };

  window.validatePaymentForm = function () {
    var totalCredit = parseFloat($('#payment_amount').val()) || 0;
    var totalInvoicePayment = parseFloat($('#total_invoice_payment').text()) || 0;
    var invoiceType = $('input[name="invoice_type"]').val() || '';
    var isRiderPayment = $('[data-salary-invoice-payment="1"]').length > 0
      || $('[data-rider-payment="1"]').length > 0
      || ['rider', 'employee'].indexOf(invoiceType) !== -1;
    var isRiderOnly = $('[data-rider-payment="1"]').length > 0 || invoiceType === 'rider';
    var createType = $('#create_payment_type').length ? ($('#create_payment_type').val() || '') : 'salary_payment';
    var isAdvanceCreate = createType === 'advance_payment' || createType === 'advance_loan';
    var isInvoiceCreate = createType === 'salary_payment' || createType === 'payment' || createType === 'c3';
    var $checkedInvoices = $('.invoice-checkbox:checked');

    if (isRiderOnly && $('#create_payment_type').length && !createType) {
      alert('Please select a payment type');
      $('#create_payment_type').addClass('is-invalid');
      return false;
    }

    if (totalCredit <= 0) {
      alert('Please enter a valid payment amount greater than zero');
      $('#payment_amount').addClass('is-invalid');
      return false;
    }

    if (isRiderOnly && isAdvanceCreate) {
      // Mirror UI rule: Advance Payment is unavailable when the selected rider has invoices.
      var hasPayeeSelected = !!($('#payee_account_id, [name="payee_account_id"]').val());
      if (hasPayeeSelected && $('#invoices-table tbody tr:visible').length > 0) {
        alert('Advance Payment is not available when the selected rider has unpaid invoices. Please use C3 or Salary Payment instead.');
        $('#create_payment_type').addClass('is-invalid');
        return false;
      }
      if (!$('#billing_month').val()) {
        alert('Billing month is required for Advance Payment');
        $('#billing_month').addClass('is-invalid');
        return false;
      }
      // Invoice selection is optional for Advance Payment.
      if ($checkedInvoices.length > 1) {
        alert('Please select at most one invoice for the advance payment');
        return false;
      }
      $('.is-invalid').removeClass('is-invalid');
      return true;
    }

    // Compare against payment amount only — bank charges must not affect invoice allocation.
    if ($('#invoices-table').length && totalInvoicePayment > totalCredit + 0.01) {
      var bankCharges = parseFloat($('#bank_charges').val()) || 0;
      var excess = totalInvoicePayment - totalCredit;
      // Auto-trim lines that still include bank charges instead of blocking submit.
      if (bankCharges > 0.009 && excess <= bankCharges + 0.01) {
        var $line = $('.payment-amount:not(:disabled)').last();
        if ($line.length) {
          var lineVal = parseFloat($line.val()) || 0;
          $line.val(Math.max(0, lineVal - excess).toFixed(2));
          if (typeof window.jQuery !== 'undefined') {
            $line.trigger('change');
          }
        }
      } else {
        alert('Total selected invoice payments cannot exceed the payment amount');
        return false;
      }
    }

    if (isRiderOnly && isInvoiceCreate) {
      var hasPayeeForPayment = !!($('#payee_account_id, [name="payee_account_id"]').val());
      if (hasPayeeForPayment && $('#invoices-table tbody tr:visible').length === 0) {
        alert('C3 / Salary Payment is not available when the selected rider has no unpaid invoices. Please use Advance Payment instead.');
        $('#create_payment_type').addClass('is-invalid');
        return false;
      }
      if ($checkedInvoices.length !== 1) {
        alert('Please select exactly one invoice for payment');
        return false;
      }
      var $row = $checkedInvoices.first().closest('tr');
      var $lineInput = $row.find('.payment-amount');
      // Cap against invoice TOTAL AMOUNT (data-max / data-invoice-total), not remaining.
      var invoiceAmount = parseFloat($lineInput.attr('data-max'));
      if (isNaN(invoiceAmount)) {
        invoiceAmount = parseFloat($row.data('invoice-total'));
      }
      if (isNaN(invoiceAmount)) {
        invoiceAmount = parseFloat($row.data('balance')) || 0;
      }
      if (totalCredit > invoiceAmount + 0.01) {
        alert('Payment amount cannot exceed the selected invoice amount (' + currencyCode($(document)) + ' ' + invoiceAmount.toFixed(2) + ')');
        $('#payment_amount').addClass('is-invalid');
        return false;
      }
      var lineAmount = parseFloat($lineInput.val()) || 0;
      if (lineAmount > invoiceAmount + 0.01) {
        alert('Payment amount cannot exceed the selected invoice amount (' + currencyCode($(document)) + ' ' + invoiceAmount.toFixed(2) + ')');
        $lineInput.addClass('is-invalid');
        return false;
      }
      if (totalInvoicePayment <= 0) {
        alert('Please enter a payment amount against the selected invoice');
        return false;
      }
      $('.is-invalid').removeClass('is-invalid');
      return true;
    }

    if (isRiderPayment && !$checkedInvoices.length) {
      alert('Please select at least one invoice for payment');
      return false;
    }

    if (isRiderPayment && $('#invoices-table').length && totalInvoicePayment <= 0) {
      alert('Please enter a payment amount against at least one selected invoice');
      return false;
    }

    $('.is-invalid').removeClass('is-invalid');
    return true;
  };

  $(function () {
    if ($('[data-payment-fields-init]').length && !$('#modalTopbody [data-payment-fields-init]').length) {
      window.initPaymentFieldsForm(document.getElementById('formajax') || document);
    }
  });
})(window, window.jQuery);
