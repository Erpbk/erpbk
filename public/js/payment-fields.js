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
    var isRiderPayment = $ctx.find('[data-salary-invoice-payment="1"]').length > 0
      || $ctx.find('[data-rider-payment="1"]').length > 0
      || ['rider', 'employee'].indexOf($ctx.find('input[name="invoice_type"]').val()) !== -1;

    $ctx.find('.select2').each(function () {
      var $el = $(this);
      if ($el.hasClass('select2-hidden-accessible')) {
        return;
      }
      $el.select2({
        dropdownParent: $form.length ? $form : $('#modalTopbody'),
        allowClear: true
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

    function getSelectedBillingMonth() {
      return ($ctx.find('#billing_month, [name="billing_month"]').val() || '').toString().trim();
    }

    function updateSelectAllState() {
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

      $ctx.find('#payment_difference').text(currency + ' ' + difference.toFixed(2));

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
      $ctx.find('#payment_amount').val(total > 0 ? total.toFixed(2) : '');
      $ctx.find('#display_amount').text(total.toFixed(2));
      var bankCharges = parseFloat($ctx.find('#bank_charges').val()) || 0;
      $ctx.find('#total_debit').text((total + bankCharges).toFixed(2));
      validatePaymentDistribution();
    }

    function applyInvoiceDateFromSelection() {
      if (!isRiderPayment) {
        return;
      }

      var $checked = $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox:checked').first().closest('tr');
      var invoiceDate = $checked.data('invoice-date') || '';
      $ctx.find('#date_of_invoice, [name="date_of_invoice"]').val(invoiceDate || '');
    }

    function filterInvoiceRows() {
      var selectedCustomerId = getSelectedCustomerId();
      var selectedCustomerName = getSelectedCustomerName();
      var selectedBillingMonth = isRiderPayment ? getSelectedBillingMonth() : '';
      var visibleCount = 0;
      var hasPayeeFilter = !!(selectedCustomerId || selectedCustomerName);

      $ctx.find('#invoices-table tbody tr').each(function () {
        var $row = $(this);
        var invoiceCustomerId = $row.data('customer-id');
        var invoiceCustomerName = $row.data('customer-name');
        var invoiceBillingMonth = ($row.data('billing-month') || '').toString();
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

        if (matches && selectedBillingMonth && invoiceBillingMonth) {
          matches = invoiceBillingMonth === selectedBillingMonth;
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
          ? 'No pending invoices found for this payee and billing month'
          : 'No invoices found for this customer');
      }

      updateSelectAllState();
      updateTotalPayment();
      validatePaymentDistribution();
      applyInvoiceDateFromSelection();
      if (isRiderPayment) {
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
    });

    $ctx.on('change.paymentFields', '#billing_month, [name="billing_month"]', function () {
      if (isRiderPayment) {
        filterInvoiceRows();
      }
    });

    $ctx.on('change.paymentFields', '.invoice-checkbox', function () {
      var $row = $(this).closest('tr');
      var $paymentInput = $row.find('.payment-amount');
      var paymentAmount = parseFloat($ctx.find('#payment_amount').val()) || 0;
      var totalSelectedPayment = parseFloat($ctx.find('#total-selected-payment').text()) || 0;
      var difference = (paymentAmount > 0) ? paymentAmount - totalSelectedPayment : 0;
      var rowRef = $row.data('reference') || '';
      var Reference = $ctx.find('#reference').val() || '';

      if ($(this).prop('checked')) {
        $paymentInput.prop('disabled', false);

        if (!Reference.includes(rowRef)) {
          $ctx.find('#reference').val((Reference + ' ' + rowRef).trim());
        }

        if (!$paymentInput.val() || parseFloat($paymentInput.val()) === 0) {
          var maxAllowed = parseFloat($paymentInput.data('max')) || parseFloat($row.data('balance')) || 0;
          if (!isRiderPayment && maxAllowed > difference && difference > 0) {
            $paymentInput.val(difference.toFixed(2));
            if (typeof toastr !== 'undefined') {
              toastr.warning('Total Selected Payment cannot exceed Payment Amount.');
            }
          } else if (maxAllowed > 0) {
            $paymentInput.val(maxAllowed.toFixed(2));
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

      updateTotalPayment();
      validatePaymentDistribution();
      updateSelectAllState();
      applyInvoiceDateFromSelection();
      if (isRiderPayment) {
        syncRiderPaymentAmountFromInvoices();
      }
    });

    $ctx.on('keyup.paymentFields change.paymentFields', '.payment-amount', function () {
      if (isRiderPayment && $(this).is('[type="hidden"]')) {
        updateTotalPayment();
        validatePaymentDistribution();
        return;
      }

      var maxAmount = parseFloat($(this).data('max')) || 0;
      var enteredAmount = parseFloat($(this).val()) || 0;
      var paymentAmount = parseFloat($ctx.find('#payment_amount').val()) || 0;
      var total = updateTotalPayment();

      if (enteredAmount > maxAmount) {
        $(this).val(maxAmount);
        enteredAmount = maxAmount;
        if (typeof toastr !== 'undefined') {
          toastr.warning('Payment amount cannot exceed invoice balance');
        }
      }

      if (total > paymentAmount && paymentAmount > 0) {
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
        $(this).val(0);
      }

      updateTotalPayment();
      validatePaymentDistribution();
    });

    $ctx.on('keyup.paymentFields change.paymentFields', '#payment_amount', function () {
      var amount = parseFloat($(this).val()) || 0;
      var bankCharges = parseFloat($ctx.find('#bank_charges').val()) || 0;

      $ctx.find('#display_amount').text(amount.toFixed(2));
      $ctx.find('#total_debit').text((amount + bankCharges).toFixed(2));

      if (isRiderPayment) {
        var $activeAmounts = $ctx.find('.payment-amount:not(:disabled)');
        if ($activeAmounts.length === 1) {
          var maxAllowed = parseFloat($activeAmounts.first().data('max')) || 0;
          var capped = amount;
          if (maxAllowed > 0 && capped > maxAllowed) {
            capped = maxAllowed;
            $(this).val(capped.toFixed(2));
            if (typeof toastr !== 'undefined') {
              toastr.warning('Payment amount cannot exceed invoice balance');
            }
          }
          $activeAmounts.first().val(capped.toFixed(2));
        }
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

      calculateTotals();
    });

    $ctx.on('change.paymentFields', '#select-all-invoices', function () {
      var isChecked = $(this).prop('checked');
      $ctx.find('#invoices-table tbody tr:visible .invoice-checkbox').prop('checked', isChecked).trigger('change');
    });

    setTimeout(function () {
      var $payeeSelect = $ctx.find('#payee_account_id, [name="payee_account_id"]');
      if ($payeeSelect.val() || isRiderPayment) {
        filterInvoiceRows();
      }
      if (!isRiderPayment) {
        $ctx.find('#payment_amount').trigger('change');
      }
      updateSelectAllState();
      calculateTotals();
      applyInvoiceDateFromSelection();
    }, 100);
  };

  window.validatePaymentForm = function () {
    var totalCredit = parseFloat($('#payment_amount').val()) || 0;
    var bankCharges = parseFloat($('#bank_charges').val()) || 0;
    var totalInvoicePayment = parseFloat($('#total_invoice_payment').text()) || 0;
    var isRiderPayment = $('[data-salary-invoice-payment="1"]').length > 0
      || $('[data-rider-payment="1"]').length > 0
      || ['rider', 'employee'].indexOf($('input[name="invoice_type"]').val()) !== -1;

    if (totalCredit <= 0) {
      alert('Please enter a valid payment amount greater than zero');
      $('#payment_amount').addClass('is-invalid');
      return false;
    }

    if ($('#invoices-table').length && Math.abs(totalCredit - totalInvoicePayment) > 0.01) {
      alert('Payment amount must equal total selected invoice payments');
      return false;
    }

    if (isRiderPayment && !$('.invoice-checkbox:checked').length) {
      alert('Please select at least one invoice for payment');
      return false;
    }

    if (bankCharges > 0) {
      var chargesAccount = $('select[name="bank_charges_account"]').val();
      if (!chargesAccount) {
        alert('Please select a bank charges account');
        $('select[name="bank_charges_account"]').addClass('is-invalid');
        return false;
      }
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
