// Right Side Modal Handler - Slide in from right
$(document).ready(function () {
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
  $('#rightSideModalBody').load(action, function (response, status, xhr) {
    if (status === 'error') {
      $('#rightSideModalBody').html(`
                <div class="text-center p-5 text-danger">
                    <i class="fas fa-exclamation-circle fa-3x"></i>
                    <p class="mt-2">Error loading content. Please try again.</p>
                    <p class="mt-2">Error: ${xhr.response}</p>
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
$('body').on('click', '.show-modal-right', function () {
  var action = $(this).data('action');
  var title = $(this).data('title');
  var size = $(this).data('size') || 'lg';
  var reloadTable = $(this).data('reload-table');
  var collapseSidebar = $(this).data('collapse-sidebar');
  var onLoadCallback = $(this).data('callback');

  // Open modal
  openRightSideModal(action, title, size, function () {
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
$('#rightSideModal').on('hidden.bs.modal', function () {
  $('.layout-wrapper').removeClass('layout-menu-collapsed');

  // Clear modal content to free memory
  setTimeout(function () {
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
$(document).on('keydown', function (e) {
  if (e.key === 'Escape' && $('#rightSideModal').hasClass('show')) {
    closeRightSideModal();
  }
});

// Prevent modal close when clicking inside modal content
$('#rightSideModal').on('click', '.modal-content', function (e) {
  e.stopPropagation();
});

// Print invoice/content from right-side modal or standalone invoice pages (global for onclick handlers).
window.printModalContent = function printModalContent() {
  var title = (document.title || 'Print').replace(/</g, '');
  var bodyHtml = '';
  var embeddedStyles = '';

  if (window.jQuery && $('#rightSideModalBody').length && $('#rightSideModalBody').find('.invoice-box').length) {
    bodyHtml = $('#rightSideModalBody').html();
    var modalTitle = ($('#rightSideModalTitle').text() || '').trim();
    if (modalTitle) {
      title = modalTitle.replace(/</g, '');
    }
  } else {
    var invoiceBox = document.querySelector('.invoice-box');
    if (!invoiceBox) {
      window.print();
      return;
    }
    document.querySelectorAll('style').forEach(function (node) {
      embeddedStyles += node.outerHTML;
    });
    bodyHtml = embeddedStyles + invoiceBox.outerHTML;
  }

  if (!bodyHtml || !String(bodyHtml).trim()) {
    window.print();
    return;
  }

  var printWindow = window.open('', '_blank');
  if (!printWindow) {
    window.print();
    return;
  }

  printWindow.document.open();
  printWindow.document.write(
    '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' +
      title +
      '</title><style>' +
      'body{font-family:Calibri,Arial,sans-serif;margin:0;padding:20px;color:#000;}' +
      '.no-print{display:none!important;}' +
      '.invoice-box{max-width:100%;margin:0 auto;}' +
      'table{width:100%;border-collapse:collapse;margin-bottom:10px;}' +
      'th,td{border:1px solid #000;padding:8px;text-align:left;}' +
      'th{background:#004aad;color:#fff;}' +
      '.text-center{text-align:center;}' +
      '@media print{body{margin:0;padding:0;}.no-print{display:none!important;}}' +
      '</style></head><body>' +
      bodyHtml +
      '</body></html>'
  );
  printWindow.document.close();

  setTimeout(function () {
    try {
      printWindow.focus();
      printWindow.print();
    } catch (e) {}
    printWindow.onafterprint = function () {
      printWindow.close();
    };
  }, 400);
};

$('body').on('click', '.js-print-modal-content', function (e) {
  e.preventDefault();
  window.printModalContent();
});

/**
 * Print only the Visa installment invoice fragment (modal #modalTopbody or standalone .visa-installment-invo-wrap).
 * Avoids printing the full ERP layout when the invoice is opened in modalTop.
 */
function printVisaInstallmentInvoice() {
  var title = 'Installment Invoice';
  var bodyHtml = '';

  if (window.jQuery && $('#modalTopbody').length && $('#modalTopbody').find('.visa-installment-invo-wrap').length) {
    bodyHtml = $('#modalTopbody').html();
    var t = ($('#modalTopTitle').text() || '').trim();
    if (t) title = t;
  } else {
    var wrap = document.querySelector('.visa-installment-invo-wrap');
    if (!wrap) {
      window.print();
      return;
    }
    var styles = '';
    if (document.head) {
      var styleNodes = document.head.querySelectorAll('style');
      for (var i = 0; i < styleNodes.length; i++) {
        styles += styleNodes[i].outerHTML;
      }
    }
    bodyHtml = styles + wrap.outerHTML;
    if (document.title) title = document.title;
  }

  if (!bodyHtml || !bodyHtml.trim()) {
    window.print();
    return;
  }

  title = String(title).replace(/</g, '');

  var printWindow = window.open('', '_blank');
  if (!printWindow) {
    window.print();
    return;
  }

  printWindow.document.open();
  printWindow.document.write(
    '<!DOCTYPE html><html><head><meta charset="utf-8"><title>' +
      title +
      '</title></head><body>' +
      bodyHtml +
      '</body></html>'
  );
  printWindow.document.close();

  setTimeout(function () {
    try {
      printWindow.focus();
      printWindow.print();
    } catch (e) {}
    printWindow.onafterprint = function () {
      printWindow.close();
    };
  }, 400);
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
    if (window.Helpers && typeof window.Helpers.initPasswordToggle === 'function') {
      window.Helpers.initPasswordToggle();
    }
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
      listBody.html(
        '<div class="p-3 text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mb-0 mt-2 small">Loading…</p></div>'
      );
      var listUrl =
        customListUrl ||
        $('#vouchers_list_sidebar_url').val() ||
        ($('#base_url').val() || '').replace(/\/$/, '') + '/vouchers/list-sidebar';
      listBody.load(listUrl);
    }
    if (collapseSidebar) {
      $('.layout-wrapper').addClass('layout-menu-collapsed');
    }
    voucherOffcanvas.show();
  }

  $('#voucherPanelTitle').text(title || 'Voucher');
  $('#voucherPanelFooter').text('—');
  $('#voucherPanelBody').html(
    '<div class="p-4 text-center text-muted"><div class="spinner-border spinner-border-sm" role="status"></div><p class="mb-0 mt-2 small">Loading…</p></div>'
  );
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

$(document).on('submit', 'form#formajax, form.form-ajax-submit', function (e) {
  // Employee create/edit use dedicated handlers (#employee-store-form / #employee-edit-form)
  if ($(this).is('#employee-store-form, #employee-edit-form')) {
    return;
  }

  e.preventDefault();
  block();

  var $form = $(this);
  let formID = 'formajax';
  var action = $form.attr('action');
  var formData = new FormData(this);
  var shouldReloadTable = String($form.data('reload-table')) !== '0';

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
    headers: {
      'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
      'X-Requested-With': 'XMLHttpRequest',
      Accept: 'application/json'
    },
    type: 'POST',
    data: formData,
    contentType: false,
    cache: false,
    processData: false,
    beforeSend: function () {
      $form.find('.save_rec').hide();
      $form.find('.loader').show();
    },
    success: function (data, _textStatus, jqXHR) {
      unblock();
      var ct = (jqXHR && jqXHR.getResponseHeader('Content-Type')) || '';
      if (typeof data !== 'object' || data === null || (ct && ct.indexOf('application/json') === -1)) {
        toastr.error('Save failed: server did not return JSON. Check validation or try again.');
        return;
      }
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
        setTimeout(function () {
          location.reload();
        }, 1000); // 1000ms = 1 seconds
      }
      if ($form.find('#reload_page').val() == 1) {
        location.reload();
      }
      toggleModalTop('hide');
      if (shouldReloadTable) {
        reloadDataTable();
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
        $form.find('input').css('border', '1px solid #dfdfdf');
        $form.find('input').next('span').remove();

        $.each(vali, function (index, value) {
          $form.find("input[name~='" + index + "']").css('border', '1px solid red');
          //$form.find("input[name~='" + index + "']").after('<span style="color:red;">' + value + '</span>');
          $form
            .find("select[name~='" + index + "']")
            .parent()
            .find('.select2-container--default .select2-selection--single')
            .css('border', '1px solid red');
          toastr.error(value);
        });
      } else {
        // Generic error message if no specific error found
        toastr.error('An error occurred. Please try again.');
      }

      if (shouldReloadTable) {
        reloadDataTable();
      }
    },
    complete: function () {
      $form.find('.save_rec').show();
      $form.find('.loader').hide();
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
      if (data && typeof data === 'object' && data.success !== undefined) {
        if (data.image_url) {
          const $img = $('#output');
          if ($img.length) {
            $img.attr('src', data.image_url);
          }
        }
        unblock();
        if (data.message) {
          toastr.success(data.message);
        } else {
          toastr.success('Action performed successfully.');
        }
        return;
      }

      if ($('#reload_modal').length != 0) {
        $('#modalTopbody').load($('#reload_modal').val(), function () {
          unblock();
        });
      } else {
        $('#modalTopbody').html(data);
        reloadDataTable();
      }

      unblock();
      if (data && data.message) {
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

function setItemTotal(row) {
  const qty = parseFloat(row.find('.qty').val()) || 0;
  const rate = parseFloat(row.find('.rate').val()) || 0;
  const discount = parseFloat(row.find('.discount').val()) || 0;
  const vat = parseFloat(row.find('.vat').val()) || 0;
  const vatAmount = row.find('.vat_amount');

  let subtotal = qty * rate;
  console.log('subtotal: ' + subtotal);
  if (discount > 0) {
    subtotal -= discount;
    console.log('subtotal after discount: ' + subtotal);
  }
  let amount = 0;
  if (vat > 0) {
    amount = subtotal * (vat / 100);
    subtotal += amount;
  }
  vatAmount.val(amount.toFixed(2));

  row.find('.amount').val(subtotal.toFixed(2));
}

function setTotal() {
  let total = 0;
  let subtotal = 0;
  let vat = 0;
  // Calculate sum of all item totals
  $('#rows-container .row').each(function () {
    const itemTotal = parseFloat($(this).find('.amount').val()) || 0;
    const itemVatAmount = parseFloat($(this).find('.vat_amount').val()) || 0;
    const itemQty = parseFloat($(this).find('.qty').val()) || 0;
    const itemPrice = parseFloat($(this).find('.rate').val()) || 0;
    const itemDiscount = parseFloat($(this).find('.discount').val()) || 0;
    const itemSubtotal = itemPrice * itemQty - itemDiscount;
    vat += itemVatAmount;
    subtotal += itemSubtotal;
    total += itemTotal;
  });
  $('#total').val(total.toFixed(2));
  $('#subtotal').val(subtotal.toFixed(2));
  $('#vat_total').val(vat.toFixed(2));
}

$(document).ready(function () {
  // Initialize select2 for the existing select elements
  if (window.jQuery && $.fn && $.fn.select2) {
    $('.select2').select2({
      allowClear: true
    });
  }

  // Add new row by cloning the first row
  $(document).on('click', '#add-new-row', function (event) {
    event.preventDefault();
    // Clone the first row
    const newRow = $('#rows-container .row:first').clone();

    // Destroy select2 and clean up in the cloned row
    if (window.jQuery && $.fn && $.fn.select2 && newRow.find('.select2').data('select2')) {
      newRow.find('.select2').select2('destroy');
    }
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
    newRow.find('.amount').attr('data-numeric-value', '0');

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
        dropdownParent: $('#modalTopbody'),
        allowClear: true
      });
    }

    // Recalculate total after adding new row
    if (typeof getTotal === 'function') {
      setTotal();
    }
  });

  // Remove a row
  $(document).on('click', '.btn-remove-row', function () {
    if ($('#rows-container .row').length > 1) {
      $(this).closest('.row').remove();
      // Recalculate total after removing row
      if (typeof getTotal === 'function') {
        setTotal();
      }
    } else {
      alert('At least one row is required.');
    }
  });

  $(document).on('input change', '.item', function () {
    const row = $(this).closest('.row');
    const selectedOption = $(this).find('option:selected');
    const itemPrice = parseFloat(selectedOption.data('price')) || 0;
    const itemVat = parseFloat(selectedOption.data('vat')) || 0;
    row.find('.rate').val(itemPrice.toFixed(2));
    row.find('.vat').val(itemVat.toFixed(2));
    setItemTotal(row);
    setTotal();
  });

  $(document).on('input change', '.qty, .rate, .discount, .vat', function () {
    const row = $(this).closest('.row');
    setItemTotal(row);
    setTotal();
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

  $(document).on('mouseenter', '#openFilterSidebar, .openFilterSidebar', function (e) {
    e.preventDefault();
    console.log('Filter button hovered!'); // Debug line
    $('#filterSidebar').addClass('open');
    $('#filterOverlay').addClass('show');
    return false;
  });

  $(document).on('click', '#openFilterSidebar, .openFilterSidebar', function (e) {
    e.preventDefault();
    console.log('Filter button clicked!'); // Debug line
    $('#filterSidebar').addClass('open');
    $('#filterOverlay').addClass('show');
    return false;
  });

  $('#closeSidebar, #filterOverlay').on('click', function () {
    $('#filterSidebar').removeClass('open');
    $('#filterOverlay').removeClass('show');
  });

  // Action dropdown functionality
  $(document).on('click', '#addBikeDropdownBtn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const dropdown = $('#addBikeDropdown');
    dropdown.toggleClass('show');
  });

  // Close dropdown when clicking outside
  $(document).on('click', function (e) {
    if (!$(e.target).closest('.action-dropdown-container').length) {
      $('#addBikeDropdown').removeClass('show');
    }
  });

  $(document).on('click', function (e) {
    if (!$(e.target).closest('#filterSidebar').length) {
      $('#filterSidebar').removeClass('open');
    }
  });

  // Close dropdown when pressing escape
  $(document).on('keydown', function (e) {
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

$(document).on('click', '#edit-icon', function (e) {
  e.preventDefault();
  e.stopPropagation();
  const $root = $(this).closest('.user-avatar-section');
  const $panel = $root.length ? $root.find('#photo-upload-form') : $('#photo-upload-form');
  if ($panel.length) {
    $panel.fadeToggle('fast');
  }
});
