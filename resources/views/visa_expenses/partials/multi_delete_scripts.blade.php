<script>
(function () {
  function closestDemo(el) {
    return el ? el.closest('.riders-list-demo') : null;
  }

  window.toggleVisaExpenseSelectAll = function (selectAllCheckbox) {
    var demo = closestDemo(selectAllCheckbox);
    if (!demo) return;
    demo.querySelectorAll('.visa-expense-checkbox').forEach(function (checkbox) {
      checkbox.checked = !!selectAllCheckbox.checked;
    });
    window.updateVisaExpenseDeleteButton(demo);
  };

  window.updateVisaExpenseDeleteButton = function (fromEl) {
    var demo = closestDemo(fromEl || document.getElementById('visaExpenseSelectAll'));
    if (!demo) return;
    var selected = demo.querySelectorAll('.visa-expense-checkbox:checked');
    var deleteBtn = demo.querySelector('#visaExpenseDeleteSelectedBtn, .js-visa-expense-delete-selected');
    var selectAll = demo.querySelector('#visaExpenseSelectAll, .js-visa-expense-select-all');
    if (deleteBtn) {
      if (selected.length > 0) {
        deleteBtn.style.display = 'inline-flex';
        deleteBtn.innerHTML = '<i class="fa fa-trash me-1"></i> Delete Selected (' + selected.length + ')';
      } else {
        deleteBtn.style.display = 'none';
      }
    }
    if (!selectAll) return;
    var all = demo.querySelectorAll('.visa-expense-checkbox');
    if (selected.length === 0) {
      selectAll.indeterminate = false;
      selectAll.checked = false;
    } else if (selected.length === all.length) {
      selectAll.indeterminate = false;
      selectAll.checked = true;
    } else {
      selectAll.indeterminate = true;
    }
  };

  window.deleteSelectedVisaExpenses = function (fromEl) {
    var demo = closestDemo(fromEl || document.getElementById('visaExpenseDeleteSelectedBtn'));
    var selectedIds = Array.from((demo || document).querySelectorAll('.visa-expense-checkbox:checked')).map(function (cb) {
      return cb.value;
    });
    if (selectedIds.length === 0) {
      Swal.fire('Error', 'Please select expenses to delete', 'error');
      return;
    }

    Swal.fire({
      title: 'Are you sure?',
      text: 'You are about to delete ' + selectedIds.length + ' visa expense(s).',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete them!'
    }).then(function (result) {
      if (!result.isConfirmed) return;
      Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
      $.ajax({
        url: @json(route('VisaExpense.bulkDelete')),
        type: 'POST',
        dataType: 'json',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        data: { _token: @json(csrf_token()), ids: selectedIds },
        success: function (response) {
          Swal.fire({ title: response.success ? 'Done' : 'Notice', text: response.message || 'Done', icon: response.success ? 'success' : 'warning' })
            .then(function () { if (response.reload !== false) location.reload(); });
        },
        error: function (xhr) {
          Swal.fire({
            title: 'Error!',
            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete selected expenses.',
            icon: 'error'
          });
        }
      });
    });
  };

  window.toggleInstallmentSelectAll = function (selectAllCheckbox) {
    var demo = closestDemo(selectAllCheckbox);
    if (!demo) return;
    demo.querySelectorAll('.installment-checkbox').forEach(function (checkbox) {
      checkbox.checked = !!selectAllCheckbox.checked;
    });
    window.updateInstallmentDeleteButton(demo);
  };

  window.updateInstallmentDeleteButton = function (fromEl) {
    var demo = closestDemo(fromEl || document.querySelector('.installment-checkbox'));
    if (!demo) return;
    var selected = demo.querySelectorAll('.installment-checkbox:checked');
    var deleteBtn = demo.querySelector('.js-installment-delete-selected');
    var selectAll = demo.querySelector('.js-installment-select-all');
    if (deleteBtn) {
      if (selected.length > 0) {
        deleteBtn.style.display = 'inline-flex';
        deleteBtn.innerHTML = '<i class="fa fa-trash me-1"></i> Delete Selected (' + selected.length + ')';
      } else {
        deleteBtn.style.display = 'none';
      }
    }
    if (!selectAll) return;
    var all = demo.querySelectorAll('.installment-checkbox');
    if (selected.length === 0) {
      selectAll.indeterminate = false;
      selectAll.checked = false;
    } else if (selected.length === all.length) {
      selectAll.indeterminate = false;
      selectAll.checked = true;
    } else {
      selectAll.indeterminate = true;
    }
  };

  window.deleteSelectedInstallments = function (fromEl) {
    var demo = closestDemo(fromEl || document.querySelector('.js-installment-delete-selected'));
    var selectedIds = Array.from((demo || document).querySelectorAll('.installment-checkbox:checked')).map(function (cb) {
      return cb.value;
    });
    if (selectedIds.length === 0) {
      Swal.fire('Error', 'Please select installments to delete', 'error');
      return;
    }

    var url = (demo && demo.getAttribute('data-bulk-delete-url')) || @json(route('Installments.bulkDeleteInstallments'));
    if (!url) {
      Swal.fire('Error', 'Bulk delete URL is not configured.', 'error');
      return;
    }

    Swal.fire({
      title: 'Are you sure?',
      text: 'You are about to delete ' + selectedIds.length + ' installment(s).',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#d33',
      cancelButtonColor: '#3085d6',
      confirmButtonText: 'Yes, delete them!'
    }).then(function (result) {
      if (!result.isConfirmed) return;
      Swal.fire({ title: 'Deleting...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
      $.ajax({
        url: url,
        type: 'POST',
        dataType: 'json',
        headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        data: { _token: @json(csrf_token()), ids: selectedIds },
        success: function (response) {
          Swal.fire({ title: response.success ? 'Done' : 'Notice', text: response.message || 'Done', icon: response.success ? 'success' : 'warning' })
            .then(function () { if (response.reload !== false) location.reload(); });
        },
        error: function (xhr) {
          Swal.fire({
            title: 'Error!',
            text: (xhr.responseJSON && xhr.responseJSON.message) || 'Could not delete selected installments.',
            icon: 'error'
          });
        }
      });
    });
  };
})();
</script>
