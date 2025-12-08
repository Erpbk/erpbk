$('.cr_amount').on('focus keyup change', function () {
  getTotal();
});
$('.dr_amount').on('focus keyup change', function () {
  getTotal();
});
$('.amount').on('focus keyup change', function () {
  getTotal();
});

function getTotal() {
  var cr_sum = 0;
  var dr_sum = 0;
  var amount_sum = 0; // Separate sum for amount fields
  
  //iterate through each textboxes and add the values
  $('.cr_amount').each(function () {
    //add only if the value is number
    if (!isNaN(this.value) && this.value.length != 0) {
      cr_sum += parseFloat(this.value);
    }
  });
  //iterate through each textboxes and add the values
  $('.dr_amount').each(function () {
    //add only if the value is number
    if (!isNaN(this.value) && this.value.length != 0) {
      dr_sum += parseFloat(this.value);
    }
  });
  
  // Calculate subtotal from amount fields (for rider invoices)
  $('.amount').each(function () {
    let amountValue = 0;
    const $amountField = $(this);
    
    // First, try to use the data-numeric-value attribute if available (more reliable)
    const numericValue = $amountField.attr('data-numeric-value');
    if (numericValue && !isNaN(numericValue)) {
      amountValue = parseFloat(numericValue);
    } else {
      // Fall back to parsing the value field
      let valueText = this.value || '';
      
      // Handle "AED 123.45" or "AED 1,234.56" format
      if (valueText.includes('AED')) {
        valueText = valueText.replace('AED', '').trim();
      }
      
      // Remove commas and any other non-numeric characters except decimal point and minus sign
      valueText = valueText.replace(/[^\d.-]/g, '');
      
      // Remove multiple decimal points (keep only the first one)
      const parts = valueText.split('.');
      if (parts.length > 2) {
        valueText = parts[0] + '.' + parts.slice(1).join('');
      }
      
      if (valueText && !isNaN(valueText) && valueText.length != 0) {
        amountValue = parseFloat(valueText);
      }
    }
    
    // Add to subtotal if valid
    if (!isNaN(amountValue) && amountValue > 0) {
      amount_sum += amountValue;
    }
  });
  
  // Use amount_sum for subtotal if we're in a rider invoice form (has #sub_total)
  if ($('#sub_total').length > 0) {
    $('#sub_total').val(amount_sum.toFixed(2));
  }
  
  //.toFixed() method will roundoff the final sum to 2 decimal places
  $('#total_cr').val(cr_sum.toFixed(2));
  $('#total_dr').val(dr_sum.toFixed(2));
}

function rider_price(g) {
  rider_id = $('#rider_id').val();
  item_id = $(g).val();
  $.ajax({
    url: $('#base_url').val() + '/search_item_price/' + rider_id + '/' + item_id,
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    type: 'GET',
    dataType: 'JSON',
    success: function (data) {
      if (data.price) {
        $(g).closest('.row').find('.rate').val(data.price);
      } else {
        $(g).closest('.row').find('.rate').val(data.pirce);
      }
      
      let qty = $(g).closest('.row').find('.qty').val();
      if (qty == '') {
        qty = 1;
        $(g).closest('.row').find('.qty').val(qty);
      }
      let rate = $(g).closest('.row').find('.rate').val();
      let discount = $(g).closest('.row').find('.discount').val();
      let tax = $(g).closest('.row').find('.tax').val();
      
      // Set default values if empty
      if (discount == '') discount = 0;
      if (tax == '') tax = 0;
      
      // Calculate amount: (qty * rate) - discount + tax (consistent with calculate_price)
      let amount = (Number(qty) * Number(rate)) - Number(discount) + Number(tax);
      
      $(g).closest('.row').find('.amount').val('AED ' + amount.toFixed(2));
      // Store the numeric value in a data attribute for proper calculation
      $(g).closest('.row').find('.amount').attr('data-numeric-value', amount.toFixed(2));
      getTotal();
    }
  });
}
function calculate_price(g) {
  let qty = $(g).closest('.row').find('.qty').val();
  let rate = $(g).closest('.row').find('.rate').val();
  let discount = $(g).closest('.row').find('.discount').val();
  let tax = $(g).closest('.row').find('.tax').val();

  // Set default values if empty
  if (qty == '') qty = 1;
  if (rate == '') rate = 0;
  if (discount == '') discount = 0;
  if (tax == '') tax = 0;

  // Calculate amount: (qty * rate) - discount + tax
  let amount = (Number(qty) * Number(rate)) - Number(discount) + Number(tax);

  $(g).closest('.row').find('.amount').val('AED ' + amount.toFixed(2));
  // Store the numeric value in a data attribute for proper calculation
  $(g).closest('.row').find('.amount').attr('data-numeric-value', amount.toFixed(2));
  getTotal();
}
