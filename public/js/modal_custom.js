$(document).ready(function () {
  $('.select2').select2({
    dropdownParent: $('#modalTopbody'),
            allowClear: true
  });

  $('#checkall').on('change', function () {
    var checked = $(this).is(':checked'); // Checkbox state
    // Select all
    if (checked) {
      $('input:checkbox').each(function () {
        $(this).prop('checked', true);
      });
    } else {
      // Deselect All
      $('input:checkbox').each(function () {
        $(this).prop('checked', false);
      });
    }
  });

  /*   var inquiry_country = $('#inquiry_country').val();
  var base_url = $('#base_url').val();

  if (inquiry_country) {
    getServices(inquiry_country);
  } */
});

function block() {
  $('#modalTopbody').block({
    message:
      '<div style="width=100%;margin:0 auto; padding:50px;"><div class="spinner-border text-primary text-center" role="status" ></div></div>',
    css: {
      backgroundColor: 'transparent',
      border: '0'
    },
    overlayCSS: {
      backgroundColor: '#fff',
      opacity: 0.8
    }
  });
}
function unblock() {
  $('#modalTopbody').unblock();
}

$('#show_hide_password a').on('click', function (event) {
  event.preventDefault();
  if ($('#show_hide_password input').attr('type') == 'text') {
    $('#show_hide_password input').attr('type', 'password');
    $('#show_hide_password i').addClass('ti-eye-off');
    $('#show_hide_password i').removeClass('ti-eye');
  } else if ($('#show_hide_password input').attr('type') == 'password') {
    $('#show_hide_password input').attr('type', 'text');
    $('#show_hide_password i').removeClass('ti-eye-off');
    $('#show_hide_password i').addClass('ti-eye');
  }
});

$('#show_hide_confirm_password a').on('click', function (event) {
  event.preventDefault();
  if ($('#show_hide_confirm_password input').attr('type') == 'text') {
    $('#show_hide_confirm_password input').attr('type', 'password');
    $('#show_hide_confirm_password i').addClass('ti-eye-off');
    $('#show_hide_confirm_password i').removeClass('ti-eye');
  } else if ($('#show_hide_confirm_password input').attr('type') == 'password') {
    $('#show_hide_confirm_password input').attr('type', 'text');
    $('#show_hide_confirm_password i').removeClass('ti-eye-off');
    $('#show_hide_confirm_password i').addClass('ti-eye');
  }
});

function selectCC(pk) {
  var specific_val = $(pk).val();
  $("#mobileCode option[data-countryCode='" + specific_val + "']").prop('selected', true);
}

$(document).ready(function () {
  // Initialize select2 for the existing select elements
  $('.select2').select2({
    dropdownParent: $('#modalTopbody'),
            allowClear: true
  });

  // Add new row by cloning the first row
  $('#add-new-row').click(function () {
    // Clone the first row
    const newRow = $('#rows-container .row:first').clone();

    // Destroy select2 and clean up in the cloned row
    if (newRow.find('.select2').data('select2')) {
      newRow.find('.select2').select2('destroy');
    }
    //newRow.find('.select2').select2('destroy').end();
    newRow
      .find('select')
      .removeAttr('data-select2-id')
      .removeClass('select2-hidden-accessible')
      .next('.select2')
      .remove();

    // Clear input, textarea, and select values in the cloned row
    newRow.find('input, textarea').val(''); // Clear inputs and textareas
    newRow.find('select').val(null).trigger('change'); // Reset the select value and trigger change

    // Append the new row to the container
    $('#rows-container').append(newRow);

    // Reinitialize select2 for the newly added select element
    $('.select2').select2({
      dropdownParent: $('#modalTopbody'),
            allowClear: true
    });
  });

  // Remove a row
  $(document).on('click', '.btn-remove-row', function () {
    if ($('#rows-container .row').length > 1) {
      $(this).closest('.row').remove();
    } else {
      alert('At least one row is required.');
    }
  });
});

