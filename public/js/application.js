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
  $('.amount').each(function () {
    // Handle amount fields that may have "AED" prefix
    let amountValue = this.value;
    if (amountValue.includes('AED')) {
      // Extract numeric value from "AED 123.45" format
      amountValue = amountValue.replace('AED', '').trim();
    }
    //add only if the value is number
    if (!isNaN(amountValue) && amountValue.length != 0) {
      cr_sum += parseFloat(amountValue);
    }
  });
  //.toFixed() method will roundoff the final sum to 2 decimal places
  $('#sub_total').val(cr_sum.toFixed(2));
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
      if (discount == '') {
        discount = 0;
        $(g).closest('.row').find('.discount').val(discount);
      }
      
      let amount = Number(qty) * Number(rate) - Number(discount);
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
