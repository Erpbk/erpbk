/**
 * Funds transfer voucher form totals.
 * Loaded globally; invoked after the voucher modal HTML is injected.
 */
(function (window, $) {
  'use strict';

  if (!$) {
    return;
  }

  function money(value) {
    var amount = parseFloat(value);
    if (isNaN(amount)) {
      amount = 0;
    }
    return Math.round((amount + Number.EPSILON) * 100) / 100;
  }

  window.initFundsTransferForm = function (root) {
    var $root = root ? $(root) : $('#formajax');
    if (!$root.length) {
      $root = $(document);
    }

    var $form = $root.is('form') ? $root : $root.find('form#formajax').addBack('form#formajax').first();
    if (!$form.length) {
      $form = $('#formajax');
    }
    var $ctx = $form.length ? $form : $(document);
    if (!$ctx.find('[data-funds-transfer-init]').length && !$ctx.find('#transfer_amount').length) {
      return;
    }

    function refresh() {
      var amount = money($ctx.find('#transfer_amount').val());
      var charges = Math.max(0, money($ctx.find('#bank_charges').val()));
      $ctx.find('#display_amount').text(amount.toFixed(2));
      $ctx.find('#display_charges').text(charges.toFixed(2));
      $ctx.find('#total_debit').text((amount + charges).toFixed(2));
    }

    $ctx.off('.fundsTransfer');
    $ctx.on('input.fundsTransfer change.fundsTransfer', '#transfer_amount, #bank_charges', refresh);
    refresh();
  };

  window.validateFundsTransferForm = function (root) {
    var $root = root ? $(root) : $('#formajax');
    if (!$root.find('[data-funds-transfer-init]').length && !$root.find('#transfer_amount').length) {
      return true;
    }

    var paymentType = String($root.find('[name="payment_type"]').val() || '');
    var fromAccount = String($root.find('#from_account_id').val() || '');
    var toAccount = String($root.find('#to_account_id').val() || '');
    var amount = money($root.find('#transfer_amount').val());
    var charges = money($root.find('#bank_charges').val());

    function fail(message) {
      if (typeof toastr !== 'undefined') {
        toastr.error(message);
      }
      return false;
    }

    if (!paymentType || paymentType === '0') {
      return fail('Please select a payment mode.');
    }
    if (!fromAccount || fromAccount === '0') {
      return fail('Please select a sending account.');
    }
    if (!toAccount || toAccount === '0') {
      return fail('Please select a receiving account.');
    }
    if (fromAccount === toAccount) {
      return fail('Sending and receiving accounts must be different.');
    }
    if (amount < 0.01) {
      return fail('Transfer amount must be greater than zero.');
    }
    if (charges < 0) {
      return fail('Bank charges cannot be negative.');
    }

    return true;
  };
})(window, window.jQuery);
