// Right Side Modal Handler - Slide in from right
$(document).ready(function() {
    // Create modal HTML if not exists
    if ($('#rightSideModal').length === 0) {
        $('body').append(`
            <div class="modal fade right-side-modal" id="rightSideModal" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-slide-right" role="document">
                    <div class="modal-content">
                        <div class="modal-body" id="rightSideModalBody" style="padding: 0;">
                            <div class="text-center p-5">
                                <div class="spinner-border text-primary" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <p class="mt-2">Loading content...</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `);
        
        // Add custom CSS for right side slide animation
        $('head').append(`
            <style>
                /* Right side modal slide animation */
                .right-side-modal .modal-dialog.modal-slide-right {
                    position: fixed;
                    margin: 0;
                    top: 0;
                    right: 0;
                    bottom: 0;
                    left: auto;
                    width: 70%;
                    max-width: 900px;
                    height: 100%;
                    transform: translateX(100%);
                    transition: transform 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                }
                
                .right-side-modal.show .modal-dialog.modal-slide-right {
                    transform: translateX(0);
                }
                
                .right-side-modal .modal-content {
                    height: 100%;
                    border-radius: 0;
                    border: none;
                }
                
                .right-side-modal .modal-body {
                    overflow-y: auto;
                    flex: 1;
                }
                
                .right-side-modal .modal-header {
                    border-radius: 0;
                    padding: 15px 20px;
                }
                
                .right-side-modal .close {
                    font-size: 28px;
                    font-weight: 300;
                    text-shadow: none;
                    opacity: 1;
                    transition: all 0.3s ease;
                }
                
                .right-side-modal .close:hover {
                    transform: rotate(90deg);
                    opacity: 0.8;
                }
                
                /* Overlay animation */
                .right-side-modal.fade .modal-backdrop {
                    opacity: 0;
                    transition: opacity 0.3s ease;
                }
                
                .right-side-modal.fade.show .modal-backdrop {
                    opacity: 0.5;
                }
                
                /* Responsive */
                @media (max-width: 768px) {
                    .right-side-modal .modal-dialog.modal-slide-right {
                        width: 100%;
                    }
                }
                
                /* Print styles */
                @media print {
                    .right-side-modal .modal-dialog.modal-slide-right {
                        position: absolute;
                        transform: none;
                        width: 100%;
                    }
                    .modal-header, .close {
                        display: none !important;
                    }
                }
            </style>
        `);
    }
});

// Centralized function to open right side modal
function openRightSideModal(action, title, size = 'lg', callback = null) {
    // Reset modal size classes
    $('#rightSideModal .modal-dialog').removeClass('modal-sm modal-md modal-lg modal-xl');
    
    // Add size class if specified
    if (size) {
        $('#rightSideModal .modal-dialog').addClass('modal-' + size);
    }
    
    // Set title
    $('#rightSideModalTitle').text(title);
    
    // Show loading state
    $('#rightSideModalBody').html(`
        <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="sr-only">Loading...</span>
            </div>
            <p class="mt-2">Loading content...</p>
        </div>
    `);
    
    // Load content
    $('#rightSideModalBody').load(action, function(response, status, xhr) {
        if (status === 'error') {
            $('#rightSideModalBody').html(`
                <div class="text-center p-5 text-danger">
                    <i class="fas fa-exclamation-circle fa-3x"></i>
                    <p class="mt-2">Error loading content. Please try again.</p>
                    <button class="btn btn-primary" onclick="location.reload()">Refresh</button>
                </div>
            `);
        }
        
        // Execute callback if provided
        if (callback && typeof callback === 'function') {
            callback();
        }
        
        // Re-initialize any components in the loaded content
        if (typeof initializeModalContent === 'function') {
            initializeModalContent();
        }
    });
    
    // Show modal
    $('#rightSideModal').modal('show');
}

// Close right side modal function
function closeRightSideModal() {
    $('#rightSideModal').modal('hide');
}

// Enhanced click handler with more options
$('body').on('click', '.show-modal-right', function() {
    var action = $(this).data('action');
    var title = $(this).data('title');
    var size = $(this).data('size') || 'lg';
    var reloadTable = $(this).data('reload-table');
    var collapseSidebar = $(this).data('collapse-sidebar');
    var onLoadCallback = $(this).data('callback');
    
    // Open modal
    openRightSideModal(action, title, size, function() {
        // Reload table if specified
        if (reloadTable && $.fn.DataTable.isDataTable('#dataTableBuilder')) {
            $('#dataTableBuilder').DataTable().ajax.reload(null, false);
        }
        
        // Collapse sidebar if specified
        if (collapseSidebar) {
            $('.layout-wrapper').addClass('layout-menu-collapsed');
        }
        
        // Execute custom callback if defined in data-callback attribute
        if (onLoadCallback && window[onLoadCallback]) {
            window[onLoadCallback]();
        }
    });
});

// Reset sidebar when modal closes
$('#rightSideModal').on('hidden.bs.modal', function() {
    $('.layout-wrapper').removeClass('layout-menu-collapsed');
    
    // Clear modal content to free memory
    setTimeout(function() {
        if (!$('#rightSideModal').hasClass('show')) {
            $('#rightSideModalBody').html(`
                <div class="text-center p-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="sr-only">Loading...</span>
                    </div>
                    <p class="mt-2">Loading content...</p>
                </div>
            `);
        }
    }, 300);
});

// Handle Escape key
$(document).on('keydown', function(e) {
    if (e.key === 'Escape' && $('#rightSideModal').hasClass('show')) {
        closeRightSideModal();
    }
});

// Prevent modal close when clicking inside modal content
$('#rightSideModal').on('click', '.modal-content', function(e) {
    e.stopPropagation();
});

// Function to print modal content
function printModalContent() {
    // Get the modal content
    var modalContent = $('#rightSideModalBody').html();
    
    // Create a new print window
    var printWindow = window.open('', '_blank');
    
    // Write content to the new window
    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>${$('#rightSideModalTitle').text()}</title>
            <meta charset="utf-8">
            <style>
                body {
                    font-family: Calibri, Arial, sans-serif;
                    margin: 0;
                    padding: 20px;
                    color: #000;
                }
                .no-print {
                    display: none;
                }
                .invoice-box {
                    max-width: 100%;
                    margin: 0 auto;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-bottom: 10px;
                }
                th, td {
                    border: 1px solid #000;
                    padding: 8px;
                    text-align: left;
                }
                th {
                    background: #004aad;
                    color: white;
                }
                .text-center {
                    text-align: center;
                }
                @media print {
                    body {
                        margin: 0;
                        padding: 0;
                    }
                    .no-print {
                      display: none !important;
                  }
                }
            </style>
        </head>
        <body>
            ${modalContent}
        </body>
        </html>
    `);
    
    // Close the document to finish writing
    printWindow.document.close();
    
    // Wait for content to load then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.onafterprint = function() {
            printWindow.close();
        };
    };
}

// Backward-compatible modal toggler used by .show-modal handlers.
function toggleModalTop(action) {
  var modalEl = document.getElementById('modalTop');
  if (!modalEl) return;

  // Bootstrap 5
  if (window.bootstrap && window.bootstrap.Modal) {
    var instance = bootstrap.Modal.getOrCreateInstance(modalEl);
    if (action === 'show') {
      instance.show();
    } else {
      instance.hide();
    }
    return;
  }

  // Bootstrap 4/jQuery fallback
  if (window.jQuery && $('#modalTop').modal) {
    $('#modalTop').modal(action === 'show' ? 'show' : 'hide');
  }
}

$('body').on('click', '.show-modal', function () {
  var action = $(this).data('action');
  var title = $(this).data('title');
  var size = $(this).data('size');
  var table = $(this).data('table');
  var collapseSidebar = $(this).data('collapse-sidebar');
  // Reset modal size classes
  $('.modal-dialog').removeClass('modal-sm modal-md modal-lg modal-xl');
  if (size) {
    $('.modal-dialog').addClass('modal-' + size);
  }
  $('#modalTopTitle').text(title);
  $('#modalTopbody').load(action, function () {
    unblock();
  });

  if (table) {
    $('#dataTableBuilder').DataTable().ajax.reload(null, false);
  }

  if (collapseSidebar) {
    $('.layout-wrapper').addClass('layout-menu-collapsed');
  }

  toggleModalTop('show');
  block();
});

$('#modalTop').on('hidden.bs.modal', function () {
  $('.layout-wrapper').removeClass('layout-menu-collapsed');
});

// Voucher slide-in panel (from right) + left sidebar (voucher list)
$('body').on('click', '.show-voucher-panel', function (e) {
  e.preventDefault();
  var action = $(this).data('action');
  var title = $(this).data('title');
  var collapseSidebar = $(this).data('collapse-sidebar');
  var customListUrl = $(this).data('list-url');
  if (!action) return;
  var voucherPanelEl = document.getElementById('voucherPanel');
  var voucherOffcanvas = bootstrap.Offcanvas.getOrCreateInstance(voucherPanelEl);
  var panelAlreadyOpen = $(voucherPanelEl).hasClass('show');

  if (!panelAlreadyOpen) {
    // First open: show list sidebar and panel
    var listSidebar = $('#voucherListSidebar');
    var listBody = $('#voucherListSidebarBody');
    if (listSidebar.length && listBody.length) {
      listSidebar.addClass('visible').attr('aria-hidden', 'false');
      $('#voucherListSidebarBackdrop').addClass('visible').attr('aria-hidden', 'false');
      $('body').addClass('voucher-panels-open');
      listBody.html('<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mb-0 mt-2 small">Loading…</p></div>');
      var listUrl = customListUrl || (($('#base_url').val() || '').replace(/\/$/, '') + '/vouchers/list-sidebar');
      listBody.load(listUrl);
    }
    if (collapseSidebar) {
      $('.layout-wrapper').addClass('layout-menu-collapsed');
    }
    voucherOffcanvas.show();
  }

  $('#voucherPanelTitle').text(title || 'Voucher');
  $('#voucherPanelFooter').text('—');
  $('#voucherPanelBody').html('<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mb-0 mt-2 small">Loading…</p></div>');
  $('#voucherPanelBody').load(action, function () {
    var footerEl = $('#voucherPanelBody').find('#voucher-panel-current');
    if (footerEl.length) {
      var num = footerEl.data('number') || '';
      var amt = footerEl.data('amount') || '';
      $('#voucherPanelFooter').text(num ? num + ' · ' + amt : '—');
      footerEl.remove();
    } else {
      $('#voucherPanelFooter').text('—');
    }
  });
});

$('#voucherPanel').on('hidden.bs.offcanvas', function () {
  $('.layout-wrapper').removeClass('layout-menu-collapsed');
  $('body').removeClass('voucher-panels-open');
  $('#voucherListSidebar').removeClass('visible').attr('aria-hidden', 'true');
  $('#voucherListSidebarBackdrop').removeClass('visible').attr('aria-hidden', 'true');
});

// Pagination and other links inside voucher list sidebar: load in place instead of navigating
$(document).on('click', '#voucherListSidebarBody a[href*="list-sidebar"]', function (e) {
  e.preventDefault();
  var href = $(this).attr('href');
  if (href && href.indexOf('list-sidebar') !== -1) {
    $('#voucherListSidebarBody').load(href);
  }
});

// Clicking backdrop closes the voucher detail panel (and thus the left list sidebar)
$(document).on('click', '#voucherListSidebarBackdrop', function () {
  var el = document.getElementById('voucherPanel');
  if (el) {
    var inst = bootstrap.Offcanvas.getInstance(el);
    if (inst) inst.hide();
  }
});

function reloadDataTable() {
  if ($.fn.DataTable.isDataTable('#dataTableBuilder')) {
    var table = $('#dataTableBuilder').DataTable();
    
    // Check if table is in server-side mode
    if (table.page.info().serverSide) {
      // Server-side: use ajax.reload
      table.ajax.reload(null, false);
    } else {
      // Client-side: just redraw
      table.draw();
    }
  }
}

$(document).on('submit', '#formajax', function (e) {
  e.preventDefault();
  block();

  let formID = 'formajax';
  var action = $(this).attr('action');
  var formData = new FormData(this);

  // Dynamic fields ki values ko ek array mein store karein
  var values = [];
  $('.dFields').each(function () {
    values.push($(this).val());
    console.log(values);
  });
  $('#error_message_duplicate_id').html('');
  // Repeat id check karein
  if (values.length !== values.filter((item, index) => values.indexOf(item) === index).length) {
    console.log('Array has duplicates');
    $('#error_message_duplicate_id').html('Array has duplicates');
    return false;
  }

  $.ajax({
    url: action,
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    type: 'POST',
    data: formData,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend: function () {
      $('#' + formID)
        .find('.save_rec')
        .hide();
      $('#' + formID)
        .find('.loader')
        .show();
    },
    success: function (data) {
      unblock();
      if (data.message) {
        toastr.success(data.message);
      } else {
        toastr.success('Action performed successfully.');
      }
      // Check for redirect in response data
      if (data.redirect) {
        window.location = data.redirect;
      }
      if (data.reload === true || data.reload_page == 1) {
        setTimeout(function() {
            location.reload();
        }, 1000); // 1000ms = 1 seconds
      }
      if ($('#reload_page').val() == 1) {
        location.reload();
      }
      toggleModalTop('hide');
      reloadDataTable();
    },
    error: function (ajaxcontent) {
      unblock();
      
      // Handle custom error messages (e.g., inactive entity validation)
      if (ajaxcontent.responseJSON && ajaxcontent.responseJSON.message) {
        toastr.error(ajaxcontent.responseJSON.message, 'Error', {
          timeOut: 8000,
          extendedTimeOut: 2000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
        return false;
      }
      
      // Handle success false response
      if (ajaxcontent.responseJSON && ajaxcontent.responseJSON.success == 'false') {
        if (ajaxcontent.responseJSON.errors) {
          toastr.error(ajaxcontent.responseJSON.errors);
        }
        return false;
      }
      
      // Handle Laravel validation errors
      if (ajaxcontent.responseJSON && ajaxcontent.responseJSON.errors) {
        vali = ajaxcontent.responseJSON.errors;
        $('#' + formID + ' input').css('border', '1px solid #dfdfdf');
        $('#' + formID + ' input')
          .next('span')
          .remove();

        $.each(vali, function (index, value) {
          $('#' + formID + " input[name~='" + index + "']").css('border', '1px solid red');
          //$('#' + formID + " input[name~='" + index + "']").after('<span style="color:red;">' + value + '</span>');
          $('#' + formID + " select[name~='" + index + "']")
            .parent()
            .find('.select2-container--default .select2-selection--single')
            .css('border', '1px solid red');
          toastr.error(value);
        });
      } else {
        // Generic error message if no specific error found
        toastr.error('An error occurred. Please try again.');
      }

      reloadDataTable();
    },
    complete: function () {
      $('#' + formID)
        .find('.save_rec')
        .show();
      $('#' + formID)
        .find('.loader')
        .hide();
    }
  });
});

$(document).on('submit', '#formajax2', function (e) {
  e.preventDefault();
  block();

  let formID = 'formajax2';
  var action = $(this).attr('action');

  var formData = new FormData(this);
  $.ajax({
    url: action,
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
    type: 'POST',
    data: formData,
    contentType: false,
    cache: false,
    processData: false,
    success: function (data) {
      if ($('#reload_modal').length != 0) {
        $('#modalTopbody').load($('#reload_modal').val(), function () {
          unblock();
        });
      } else {
        $('#modalTopbody').html(data);
        reloadDataTable();
      }

      unblock();
      if (data.message) {
        toastr.success(data.message);
      } else {
        toastr.success('Action performed successfully.');
      }
    },
    error: function (ajaxcontent) {
      unblock();
      
      // Handle custom error messages (e.g., inactive entity validation)
      if (ajaxcontent.responseJSON && ajaxcontent.responseJSON.message) {
        toastr.error(ajaxcontent.responseJSON.message, 'Error', {
          timeOut: 8000,
          extendedTimeOut: 2000,
          closeButton: true,
          progressBar: true,
          positionClass: 'toast-top-right'
        });
        return false;
      }
      
      // Handle success false response
      if (ajaxcontent.responseJSON && ajaxcontent.responseJSON.success == 'false') {
        if (ajaxcontent.responseJSON.errors) {
          toastr.error(ajaxcontent.responseJSON.errors);
        }
        return false;
      }
      
      // Handle Laravel validation errors
      if (ajaxcontent.responseJSON && ajaxcontent.responseJSON.errors) {
        vali = ajaxcontent.responseJSON.errors;
        $('#' + formID + ' input').css('border', '1px solid #dfdfdf');
        $('#' + formID + ' input')
          .next('span')
          .remove();

        $.each(vali, function (index, value) {
          $('#' + formID + " input[name~='" + index + "']").css('border', '1px solid red');
          $('#' + formID + " input[name~='" + index + "']").after('<span style="color:red;">' + value + '</span>');
          $('#' + formID + " select[name~='" + index + "']")
            .parent()
            .find('.select2-container--default .select2-selection--single')
            .css('border', '1px solid red');
          toastr.error(value);
        });
      } else {
        // Generic error message if no specific error found
        toastr.error('An error occurred. Please try again.');
      }
    }
  });
});
function alertfunction() {
  alert('Hello alert is working');
}

function block() {
  if (!window.jQuery || !$.fn || !$.fn.block) return;
  $('#modalTopbody').block({
    message: '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>',
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
  if (!window.jQuery || !$.fn || !$.fn.unblock) return;
  $('#modalTopbody').unblock();
}
/* $('.select2').select2({
  dropdownParent: $('#modalTop'),
            allowClear: true
}); */
if (window.jQuery && $.fn && $.fn.select2) {
  $('.select2').select2({
    /* dropdownParent: $('.card ') */
    allowClear: true
  });
}

$("select[name='country']").on('change', function () {
  var country = $(this).val();
  var base_url = $('#base_url').val();
  if (country) {
    bodyblock();
    $.ajax({
      url: base_url + '/getcity?c=' + country,
      success: function (data) {
        $('select[id="cities"]').empty();
        $.each(data, function (key, value) {
          $('select[id="cities"]').append('<option value="' + value + '">' + value + '</option>');
        });
        bodyunblock();
      }
    });
  } else {
    $('select[id="cities"]').empty();
  }
});

function bodyblock() {
  if (!window.jQuery || !$.fn || !$.fn.block) return;
  $('.card').block({
    message: '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>',
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
function bodyunblock() {
  if (!window.jQuery || !$.fn || !$.fn.unblock) return;
  $('.card').unblock();
}

$('#show_hide_password a').on('click', function (event) {
  event.preventDefault();
  if ($('#show_hide_password input').attr('type') == 'text') {
    $('#show_hide_password input').attr('type', 'password');
    $('#show_hide_password i').addClass('bi-eye-slash-fill');
    $('#show_hide_password i').removeClass('bi-eye');
  } else if ($('#show_hide_password input').attr('type') == 'password') {
    $('#show_hide_password input').attr('type', 'text');
    $('#show_hide_password i').removeClass('bi-eye-slash-fill');
    $('#show_hide_password i').addClass('bi-eye-fill');
  }
});

$('#show_hide_confirm_password a').on('click', function (event) {
  event.preventDefault();
  if ($('#show_hide_confirm_password input').attr('type') == 'text') {
    $('#show_hide_confirm_password input').attr('type', 'password');
    $('#show_hide_confirm_password i').addClass('bi-eye-slash-fill');
    $('#show_hide_confirm_password i').removeClass('bi-eye');
  } else if ($('#show_hide_confirm_password input').attr('type') == 'password') {
    $('#show_hide_confirm_password input').attr('type', 'text');
    $('#show_hide_confirm_password i').removeClass('bi-eye-slash-fill');
    $('#show_hide_confirm_password i').addClass('bi-eye-fill');
  }
});

function selectCC(pk) {
  var specific_val = $(pk).val();
  $("#mobileCode option[data-countryCode='" + specific_val + "']").prop('selected', true);
}

/* $(document).ready(function () {
  $(window).keydown(function (event) {
    if (event.keyCode == 13) {
      event.preventDefault();
      return false;
    }
  });
}); */

$(document).ready(function () {
  // Initialize select2 for the existing select elements
  if (window.jQuery && $.fn && $.fn.select2) {
    $('.select2').select2({
      allowClear: true
    });
  }

  // Add new row by cloning the first row
  $('#add-new-row').click(function () {
    // Clone the first row
    const newRow = $('#rows-container .row:first').clone();

    // Destroy select2 and clean up in the cloned row
    if (window.jQuery && $.fn && $.fn.select2 && newRow.find('.select2').data('select2')) {
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
    
    // Reset amount field to default value and remove data attribute
    newRow.find('.amount').val('AED 0.00').removeAttr('data-numeric-value');
    
    // Set default values for qty, rate, discount, tax
    newRow.find('.qty').val('1');
    newRow.find('.rate').val('0');
    newRow.find('.discount').val('0');
    newRow.find('.tax').val('0');

    // Append the new row to the container
    $('#rows-container').append(newRow);

    // Reinitialize select2 for the newly added select element
    if (window.jQuery && $.fn && $.fn.select2) {
      $('.select2').select2({
        allowClear: true
      });
    }
    
    // Recalculate total after adding new row
    if (typeof getTotal === 'function') {
      getTotal();
    }
  });

  // Remove a row
  $(document).on('click', '.btn-remove-row', function () {
    if ($('#rows-container .row').length > 1) {
      $(this).closest('.row').remove();
      // Recalculate total after removing row
      if (typeof getTotal === 'function') {
        getTotal();
      }
    } else {
      alert('At least one row is required.');
    }
  });

  $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function(e) {
      e.preventDefault();
      console.log('Filter button hovered!'); // Debug line
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
      return false;
  });

  $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function(e) {
      e.preventDefault();
      console.log('Filter button clicked!'); // Debug line
      $('#filterSidebar').addClass('open');
      $('#filterOverlay').addClass('show');
      return false;
  });

  $('#closeSidebar, #filterOverlay').on('click', function() {
      $('#filterSidebar').removeClass('open');
      $('#filterOverlay').removeClass('show');
  });

  // Action dropdown functionality
  $(document).on('click', '#addBikeDropdownBtn', function(e) {
      e.preventDefault();
      e.stopPropagation();
      const dropdown = $('#addBikeDropdown');
      dropdown.toggleClass('show');
  });

  // Close dropdown when clicking outside
  $(document).on('click', function(e) {
      if (!$(e.target).closest('.action-dropdown-container').length) {
          $('#addBikeDropdown').removeClass('show');
      }
  });

  $(document).on('click', function(e) {
    if (!$(e.target).closest('#filterSidebar').length) {
        $('#filterSidebar').removeClass('open');
    }
  });

  // Close dropdown when pressing escape
  $(document).on('keydown', function(e) {
      if (e.key === 'Escape') {
          $('#addBikeDropdown').removeClass('show');
          $('#filterSidebar').removeClass('open');
      }
  });

});

function bodyblock() {
  if (!window.jQuery || !$.fn || !$.fn.block) return;
  $('#bodyloader').block({
    message: '<div class="loading-overlay"><div class="spinner-border text-primary" role="status"></div></div>',
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
function bodyunblock() {
  if (!window.jQuery || !$.fn || !$.fn.unblock) return;
  $('#bodyloader').unblock();
}

$(document).on('click', '#edit-icon', function () {
  $('#photo-upload-form').fadeToggle('fast');
});




