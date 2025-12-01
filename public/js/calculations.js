function calculate() {
  let method = $("input[name='cbm_method']:checked").val();

  let product_length = $('#product_length').val();
  let product_width = $('#product_width').val();
  let product_height = $('#product_height').val();
  let cbm = $('#cbm').val();
  let product_weight = $('#product_weight').val();
  let cbm_per_container = $('#cbm_per_container').val();
  let total_product_per_container = $('#total_product_per_container').val();
  let container_size = $('#container_size').val();
  let cost_per_product = $('#cost_per_product').val();
  let freight_cost = $('#freight_cost').val();
  let cost_of_duty = $('#cost_of_duty').val();
  let fx_rate = $('#fx_rate').val();
  let total_cost = $('#total_cost').val();
  let inland_freight_charges = $('#inland_freight_charges').val();
  let misc_charges = $('#misc_charges').val();
  let misc_charges_percentage = $('#misc_charges_percentage').val();
  let grand_total = $('#grand_total').val();

  let total_cost_sum = 0.0;
  let grand_total_sum = 0.0;
  if (method == 'auto') {
    let cbm_unit = $("input[name='cbm_unit']:checked").val();

    if (cbm_unit == 'cm') {
      cbm = parseFloat(product_length) * parseFloat(product_width) * parseFloat(product_height);
      cbm = parseFloat(cbm) / 1000000;
      $('#cbm').val(cbm.toFixed(5));
    } else {
      cbm = parseFloat(product_length) * parseFloat(product_width) * parseFloat(product_height);
      $('#cbm').val(cbm.toFixed(5));
    }

    $('#cbm').prop('readonly', true);
    $('#cbm').addClass('bg-light');
  } else {
    $('#cbm').prop('readonly', false);
    $('#cbm').removeClass('bg-light');
  }

  if (cbm != '' && method == 'auto') {
    if (container_size == '20 FCL') {
      total_product_per_container = 33 / cbm;
      cbm_per_container = 33;
      weight_per_container = 28310;
    }
    if (container_size == '40 FCL') {
      total_product_per_container = 67 / cbm;
      cbm_per_container = 67;
      weight_per_container = 26730;
    }
    $('#cbm_per_container').val(cbm_per_container.toFixed(2));
    $('#total_product_per_container').val(total_product_per_container.toFixed(2));
  } else {
    if (container_size == '20 FCL') {
      cbm_per_container = 33;
      weight_per_container = 28310;
    }
    if (container_size == '40 FCL') {
      cbm_per_container = 67;
      weight_per_container = 26730;
    }
    $('#cbm_per_container').val(cbm_per_container.toFixed(2));
  }
  if (product_weight != '') {
    weight_per_container = product_weight * total_product_per_container;
    $('#weight_per_container').val(weight_per_container.toFixed(2));
    $('#weight_per_container').prop('readonly', true);
    $('#weight_per_container').addClass('bg-light');
  } else {
    $('#weight_per_container').val(weight_per_container.toFixed(2));
    $('#weight_per_container').prop('readonly', false);
    $('#weight_per_container').removeClass('bg-light');
  }
  if (total_product_per_container != '' && cost_per_product != '') {
    total_cost = total_product_per_container * cost_per_product;
    total_cost_sum = total_cost;
    $('#total_product_cost').val(total_cost.toFixed(2));
  }
  if (freight_cost != '') {
    total_cost_sum = parseFloat(total_cost_sum) + parseFloat(freight_cost);
  }
  if (cost_of_duty != '') {
    let duty = (parseFloat(total_cost) * parseFloat(cost_of_duty)) / 100;
    total_cost_sum = parseFloat(total_cost_sum) + parseFloat(duty);
  }

  $('#gross_total').val(total_cost_sum.toFixed(2));
  let gross_total_per_product = total_cost_sum / total_product_per_container;
  $('#gross_total_per_product').val(gross_total_per_product.toFixed(2));

  if (fx_rate != '') {
    total_cost_sum = parseFloat(total_cost_sum) / parseFloat(fx_rate);
    grand_total = total_cost_sum;
    grand_total_sum = grand_total;
  }
  if (inland_freight_charges != '') {
    grand_total_sum = parseFloat(grand_total) + parseFloat(inland_freight_charges);
    grand_total = grand_total_sum;
  }
  if (misc_charges != '') {
    grand_total_sum = parseFloat(grand_total) + parseFloat(misc_charges);
    grand_total = grand_total_sum;
  }

  if (misc_charges_percentage != '') {
    let misc_charges_percentage_amount = (parseFloat(total_cost_sum) * parseFloat(misc_charges_percentage)) / 100;
    grand_total_sum = parseFloat(grand_total) + parseFloat(misc_charges_percentage_amount);
  }

  if (fx_rate != '') {
    $('#total_cost').val(total_cost_sum.toFixed(2));

    $('#grand_total').val(grand_total_sum.toFixed(2));
  }
  let grand_total_per_product = grand_total_sum / total_product_per_container;
  $('#grand_total_per_product').val(grand_total_per_product.toFixed(2));
  /* if (grand_total_sum > 0) {
    $('#submitbtn').prop('disabled', false);
  } else {
    $('#submitbtn').prop('disabled', true);
  } */
}

function getCBM() {
  let cbm = $('#cbm').val();
  let product_weight = $('#product_weight').val();
  let container_size = $('#container_size').val();
  let method = $("input[name='cbm_method']:checked").val();

  if (container_size == '20 FCL') {
    if (method == 'auto') {
      if (cbm) {
        product_per = 33 / cbm;
      }
    }
    cbm_per = 33;

    $('#cbm_per_container').val(cbm_per.toFixed(2));
    $('#total_product_per_container').val(product_per.toFixed(2));
    if (product_weight) {
      weight_per = product_weight * product_per;
      $('#weight_per_container').val(weight_per.toFixed(2));
      $('#weight_per_container').attr('readonly', true);
      $('#weight_per_container').addClass('bg-light');
    } else {
      weight_per = 28310;
      $('#weight_per_container').val(weight_per.toFixed(2));
      $('#weight_per_container').attr('readonly', false);
      $('#weight_per_container').removeClass('bg-light');
    }
  } else if (container_size == '40 FCL') {
    if (method == 'auto') {
      if (cbm) {
        product_per = 67 / cbm;
      }
    }
    cbm_per = 67;

    $('#cbm_per_container').val(cbm_per.toFixed(2));
    $('#total_product_per_container').val(product_per.toFixed(2));
    if (product_weight) {
      weight_per = product_weight * product_per;
      $('#weight_per_container').val(weight_per.toFixed(2));
      $('#weight_per_container').attr('readonly', true);
      $('#weight_per_container').addClass('bg-light');
    } else {
      weight_per = 26730;
      $('#weight_per_container').val(weight_per.toFixed(2));
      $('#weight_per_container').attr('readonly', false);
      $('#weight_per_container').removeClass('bg-light');
    }
  } else {
    $('#cbm_per_container').val('');
  }
}

$(document).ready(function () {
  let method = $("input[name='cbm_method']:checked").val();
  let cbm_unit = $("input[name='cbm_unit']:checked").val();
  if (cbm_unit == 'cm') {
    $('.unit_text').text('CM');
  } else {
    $('.unit_text').text('M');
  }

  if (method == 'manual') {
    $('#auto_box').fadeOut('fast');
    $('#cbm_per_box').hide('fast');
    $('#cases_formula').hide('fast');
    $('#calculation_label').text('Case Number Calculation');
    $('#calculation_label').addClass('text-danger');
    $('#total_product_per_container').attr('readonly', false);
    $('#total_product_per_container').removeClass('bg-light');
  } else {
    $('#auto_box').fadeIn('fast');
    $('#cbm_per_box').show('fast');
    $('#cases_formula').show('fast');
    $('#calculation_label').text('CBM Calculation');
    $('#calculation_label').addClass('text-danger');
    $('#cbm').prop('readonly', true);
    $('#cbm').addClass('bg-light');
    $('#total_product_per_container').attr('readonly', true);
    $('#total_product_per_container').addClass('bg-light');
  }

  $('input[name="cbm_method"]:radio').change(function () {
    let method = $("input[name='cbm_method']:checked").val();
    if (method == 'manual') {
      $('#auto_box').fadeOut('fast');
      $('#cbm_per_box').hide('fast');
      $('#cases_formula').hide('fast');
      $('#calculation_label').text('Case Number Calculation');
      $('#calculation_label').addClass('text-danger');
      $('#total_product_per_container').attr('readonly', false);
      $('#total_product_per_container').removeClass('bg-light');
      $('#cbm_per_ct_box').addClass('order-first');
    } else {
      $('#auto_box').fadeIn('fast');
      $('#cbm_per_box').show('fast');
      $('#cases_formula').show('fast');
      $('#calculation_label').text('CBM Calculation');
      $('#calculation_label').addClass('text-danger');
      $('#cbm').prop('readonly', true);
      $('#cbm').addClass('bg-light');
      $('#total_product_per_container').attr('readonly', true);
      $('#total_product_per_container').addClass('bg-light');
      $('#cbm_per_ct_box').removeClass('order-first');
    }
    calculate();
  });

  $('input[name="cbm_unit"]:radio').change(function () {
    let cbm_unit = $("input[name='cbm_unit']:checked").val();
    if (cbm_unit == 'cm') {
      $('.unit_text').text('CM');
    } else {
      $('.unit_text').text('M');
    }
    calculate();
  });
});
