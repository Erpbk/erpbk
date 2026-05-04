// $('.cr_amount').on('focus keyup change', function () {
//   getTotal();
// });
// $('.dr_amount').on('focus keyup change', function () {
//   getTotal();
// });
// $('.amount').on('focus keyup change', function () {
//   getTotal();
// });

// /**
//  * Tenant routes (e.g. search_item_price) are registered under app/{company_slug}/.
//  * #base_url is often the site root only, so we derive /app/{slug} from the current path.
//  */
// function resolveTenantAppBase() {
//   var base = ($('#base_url').val() || window.location.origin || '').replace(/\/$/, '');
//   var match = (window.location.pathname || '').match(/\/app\/([^/]+)/);
//   if (match && match[1]) {
//     return base + '/app/' + match[1];
//   }
//   return base;
// }

// window.resolveTenantAppBase = resolveTenantAppBase;

// function getTotal() {
//   var cr_sum = 0;
//   var dr_sum = 0;
//   var amount_sum = 0; // Separate sum for amount fields
  
//   //iterate through each textboxes and add the values
//   $('.cr_amount').each(function () {
//     //add only if the value is number
//     if (!isNaN(this.value) && this.value.length != 0) {
//       cr_sum += parseFloat(this.value);
//     }
//   });
//   //iterate through each textboxes and add the values
//   $('.dr_amount').each(function () {
//     //add only if the value is number
//     if (!isNaN(this.value) && this.value.length != 0) {
//       dr_sum += parseFloat(this.value);
//     }
//   });
  
//   // Calculate subtotal from amount fields (for rider invoices)
//   $('.amount').each(function () {
//     let amountValue = 0;
//     const $amountField = $(this);
    
//     // First, try to use the data-numeric-value attribute if available (more reliable)
//     const numericValue = $amountField.attr('data-numeric-value');
//     if (numericValue && !isNaN(numericValue)) {
//       amountValue = parseFloat(numericValue);
//     } else {
//       // Fall back to parsing the value field
//       let valueText = this.value || '';
      
//       // Handle "AED 123.45" or "AED 1,234.56" format
//       if (valueText.includes('AED')) {
//         valueText = valueText.replace('AED', '').trim();
//       }
      
//       // Remove commas and any other non-numeric characters except decimal point and minus sign
//       valueText = valueText.replace(/[^\d.-]/g, '');
      
//       // Remove multiple decimal points (keep only the first one)
//       const parts = valueText.split('.');
//       if (parts.length > 2) {
//         valueText = parts[0] + '.' + parts.slice(1).join('');
//       }
      
//       if (valueText && !isNaN(valueText) && valueText.length != 0) {
//         amountValue = parseFloat(valueText);
//       }
//     }
    
//     // Add to subtotal if valid - round each amount to 2 decimals to avoid floating-point accumulation
//     if (!isNaN(amountValue) && amountValue > 0) {
//       amount_sum += Math.round(parseFloat(amountValue) * 100) / 100;
//     }
//   });
  
//   // Use amount_sum for subtotal if we're in a rider invoice form (has #sub_total)
//   // Round final sum to ensure displayed total matches sum of displayed amounts
//   if ($('#sub_total').length > 0) {
//     $('#sub_total').val((Math.round(amount_sum * 100) / 100).toFixed(2));
//   }
  
//   //.toFixed() method will roundoff the final sum to 2 decimal places
//   $('#total_cr').val(cr_sum.toFixed(2));
//   $('#total_dr').val(dr_sum.toFixed(2));
// }

// function rider_price(g) {
//   rider_id = $('#rider_id').val() || $('#employee_id').val();
//   item_id = $(g).val();
//   $.ajax({
//     url: resolveTenantAppBase() + '/search_item_price/' + rider_id + '/' + item_id,
//     headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//     type: 'GET',
//     dataType: 'JSON',
//     success: function (data) {
//       if (data.price) {
//         $(g).closest('.row').find('.rate').val(data.price);
//       } else {
//         $(g).closest('.row').find('.rate').val(data.pirce);
//       }
      
//       let qty = $(g).closest('.row').find('.qty').val();
//       if (qty == '') {
//         qty = 1;
//         $(g).closest('.row').find('.qty').val(qty);
//       }
//       let rate = $(g).closest('.row').find('.rate').val();
//       let discount = $(g).closest('.row').find('.discount').val();
//       let tax = $(g).closest('.row').find('.tax').val();
      
//       // Set default values if empty
//       if (discount == '') discount = 0;
//       if (tax == '') tax = 0;
      
//       // Calculate amount: (qty * rate) - discount + tax (consistent with calculate_price)
//       // Round to 2 decimals to avoid floating-point precision issues
//       let amount = Math.round(((Number(qty) * Number(rate)) - Number(discount) + Number(tax)) * 100) / 100;
//       let amountStr = amount.toFixed(2);
      
//       $(g).closest('.row').find('.amount').val('AED ' + amountStr);
//       // Store the numeric value in a data attribute for proper calculation
//       $(g).closest('.row').find('.amount').attr('data-numeric-value', amountStr);
//       getTotal();
//     }
//   });
// }
// function calculate_price(g) {
//   let qty = $(g).closest('.row').find('.qty').val();
//   let rate = $(g).closest('.row').find('.rate').val();
//   let discount = $(g).closest('.row').find('.discount').val();
//   let tax = $(g).closest('.row').find('.tax').val();

//   // Set default values if empty
//   if (qty == '') qty = 1;
//   if (rate == '') rate = 0;
//   if (discount == '') discount = 0;
//   if (tax == '') tax = 0;

//   // Calculate amount: (qty * rate) - discount + tax
//   // Round to 2 decimals to avoid floating-point precision issues
//   let amount = Math.round(((Number(qty) * Number(rate)) - Number(discount) + Number(tax)) * 100) / 100;
//   let amountStr = amount.toFixed(2);

//   $(g).closest('.row').find('.amount').val('AED ' + amountStr);
//   // Store the numeric value in a data attribute for proper calculation
//   $(g).closest('.row').find('.amount').attr('data-numeric-value', amountStr);
//   getTotal();
// }

// // -------------------------------
// // Supplier invoice helpers (shared)
// // -------------------------------
// window.supplier_getTotal = function () {
//   let subtotal = 0;
//   let vatTotal = 0;
//   let grandTotal = 0;

//   $('#row-container .item-row').each(function () {
//     const rowData = supplier_calculate_price(this, true);
//     subtotal += rowData.lineSubtotal;
//     vatTotal += rowData.lineVat;
//     grandTotal += rowData.lineTotal;
//   });

//   $('#subtotal').val(subtotal.toFixed(2));
//   $('#vat_total').val(vatTotal.toFixed(2));
//   $('#total_cost').val(grandTotal.toFixed(2));
// };

// window.supplier_calculate_price = function (el, skipTotal) {
//   const row = $(el).closest('.item-row');
//   let qty = row.find('.quantity').val();
//   const rate = parseFloat(row.find('.rate').val()) || 0;
//   const vat = parseFloat(row.find('.vat').val()) || 0;

//   if (qty === '') {
//     qty = 1;
//     row.find('.quantity').val(qty);
//   }
//   qty = parseFloat(qty) || 0;

//   const lineSubtotal = Math.round((qty * rate) * 100) / 100;
//   const lineVat = Math.round((lineSubtotal * (vat / 100)) * 100) / 100;
//   const lineTotal = Math.round((lineSubtotal + lineVat) * 100) / 100;

//   row.find('.vatAmount').val(lineVat.toFixed(2));
//   row.find('.item-total').val(lineTotal.toFixed(2)).attr('data-numeric-value', lineTotal.toFixed(2));
//   if (!skipTotal) {
//     supplier_getTotal();
//   }

//   return { lineSubtotal, lineVat, lineTotal };
// };

// window.supplier_item_price = function (el) {
//   const supplierId = $('#customer_id').val() || 0;
//   const itemId = $(el).val();
//   const selectedOption = $(el).find('option:selected');
//   const fallbackPrice = parseFloat(selectedOption.data('price')) || 0;
//   const itemVat = parseFloat(selectedOption.data('vat')) || 0;

//   if (!itemId) {
//     $(el).closest('.item-row').find('.rate').val('0');
//     $(el).closest('.item-row').find('.vat').val('0');
//     supplier_calculate_price(el);
//     return;
//   }

//   $(el).closest('.item-row').find('.vat').val(itemVat);

//   // No supplier yet: use catalog price from option data (same as AJAX fallback)
//   if (!supplierId || supplierId === '0') {
//     $(el).closest('.item-row').find('.rate').val(fallbackPrice.toFixed(2));
//     supplier_calculate_price(el);
//     return;
//   }

//   $.ajax({
//     url: resolveTenantAppBase() + '/search_item_price/' + supplierId + '/' + itemId,
//     headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
//     type: 'GET',
//     dataType: 'JSON',
//     success: function (data) {
//       const serverPrice = data && data.price !== undefined ? data.price : data && data.pirce !== undefined ? data.pirce : fallbackPrice;
//       $(el).closest('.item-row').find('.rate').val(parseFloat(serverPrice || 0).toFixed(2));
//       supplier_calculate_price(el);
//     },
//     error: function () {
//       $(el).closest('.item-row').find('.rate').val(fallbackPrice.toFixed(2));
//       supplier_calculate_price(el);
//     },
//   });
// };

// /**
//  * Supplier "Add row" — delegated so it works when the form is injected later (modal/AJAX)
//  * and avoids missing direct binds when initSupplierInvoiceForm exited early.
//  */
// $(document)
//   .off('click.supplier-add-row', '#add-row, #add-new-row')
//   .on('click.supplier-add-row', '#add-row, #add-new-row', function (e) {
//     e.preventDefault();
//     const $btn = $(this);
//     const $formAjax = $btn.closest('#formajax').length ? $btn.closest('#formajax') : $('#formajax');
//     const $modalBody = $formAjax.closest('.modal-body').length ? $formAjax.closest('.modal-body') : $('#modalTopbody');

//     const $rc = $('#row-container');
//     if ($rc.length === 0) return;

//     const $firstRow = $rc.find('.item-row:first');
//     if ($firstRow.length === 0) return;

//     const $newRow = $firstRow.clone();
//     $newRow.find('.item-select').val('');
//     $newRow.find('.quantity').val(1);
//     $newRow.find('.rate').val(0);
//     $newRow.find('.vat').val(0);
//     $newRow.find('.vatAmount').val(0);
//     $newRow.find('.item-total').val('');
//     $newRow.find('.remove-row').show();

//     $newRow.find('.select2').removeClass('select2-hidden-accessible').next('.select2').remove();
//     $rc.append($newRow);

//     if ($.fn.select2) {
//       $newRow.find('.select2').select2({
//         allowClear: true,
//         width: '100%',
//         dropdownParent: $modalBody.length ? $modalBody : $('body'),
//       });
//     }

//     if (typeof supplier_getTotal === 'function') {
//       supplier_getTotal();
//     }
//   });

// // Auto-bind supplier invoice events when form exists
// window.initSupplierInvoiceForm = function (rootContext) {
//   const $root = rootContext ? $(rootContext) : $(document);
//   if ($root.find('#row-container').length === 0) return;

//   // Select2 init (safe for modal + normal pages)
//   const $modalBody = $('#formajax').closest('.modal-body').length ? $('#formajax').closest('.modal-body') : $('#modalTopbody');
//   if ($.fn.select2) {
//     $root.find('.select2').each(function () {
//       if (!$(this).hasClass('select2-hidden-accessible')) {
//         $(this).select2({
//           allowClear: true,
//           width: '100%',
//           dropdownParent: $modalBody.length ? $modalBody : $('body'),
//         });
//       }
//     });
//   }

//   supplier_getTotal();
//   if ($('#row-container .item-row').length === 1) {
//     $('.remove-row').hide();
//   } else {
//     $('.remove-row').show();
//   }

//   $(document)
//     .off('change.supplier-item select2:select.supplier-item', '.item-select')
//     .on('change.supplier-item select2:select.supplier-item', '.item-select', function () {
//       supplier_item_price(this);
//     });

//   $(document)
//     .off('keyup.supplier-calc change.supplier-calc', '.quantity, .rate, .vat')
//     .on('keyup.supplier-calc change.supplier-calc', '.quantity, .rate, .vat', function () {
//       supplier_calculate_price(this);
//     });

//   $(document)
//     .off('click.supplier-remove', '.remove-row')
//     .on('click.supplier-remove', '.remove-row', function () {
//       if ($('#row-container .item-row').length <= 1) {
//         alert('At least one item is required');
//         return;
//       }
//       $(this).closest('.item-row').remove();
//       if ($('#row-container .item-row').length === 1) {
//         $('.remove-row').hide();
//       }
//       supplier_getTotal();
//     });

//   // Supplier invoice form validation
//   $('#formajax')
//     .off('submit.supplier-validate')
//     .on('submit.supplier-validate', function (e) {
//       let isValid = true;

//       if ($('#row-container .item-row').length === 0) {
//         alert('Please add at least one item to the invoice.');
//         e.preventDefault();
//         return false;
//       }

//       if (!$('#customer_id').val()) {
//         alert('Please select a Supplier.');
//         e.preventDefault();
//         return false;
//       }

//       $('#row-container .item-row').each(function (index) {
//         const itemSelect = $(this).find('.item-select').val();
//         const quantity = parseFloat($(this).find('.quantity').val()) || 0;
//         const rate = parseFloat($(this).find('.rate').val()) || 0;

//         if (!itemSelect) {
//           alert('Item ' + (index + 1) + ': Please select an item.');
//           isValid = false;
//           return false;
//         }
//         if (quantity <= 0) {
//           alert('Item ' + (index + 1) + ': Please enter a valid quantity.');
//           isValid = false;
//           return false;
//         }
//         if (rate <= 0) {
//           alert('Item ' + (index + 1) + ': Please enter a valid rate.');
//           isValid = false;
//           return false;
//         }
//       });

//       if (!isValid) {
//         e.preventDefault();
//         return false;
//       }
//       return true;
//     });
// };

// $(document).ready(function () {
//   initSupplierInvoiceForm(document);
// });
