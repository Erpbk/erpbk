document.addEventListener('DOMContentLoaded', function() {
  var targetHash = window.location.hash;
  if (targetHash === '#tab-bike-registration-top' || targetHash === '#tab-br-statuses') {
    var brTabBtn = document.querySelector('[data-bs-target="' + targetHash + '"]');
    if (brTabBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
      bootstrap.Tab.getOrCreateInstance(brTabBtn).show();
    } else if (brTabBtn) {
      brTabBtn.click();
    }
  }

  var topForm = document.getElementById('bikeRegistrationTopAjaxForm');
  var topCount = document.getElementById('bikeRegistrationTopSelectedCount');
  var topToggle = document.getElementById('bikeRegistrationTopEnabled');
  var topList = document.getElementById('bikeRegistrationTopSelectedList');
  var topModalSelect = document.getElementById('bikeRegistrationTopStatusSelect');
  var topAddBtn = document.getElementById('btnAddBikeRegistrationTopOption');

  function refreshBikeRegistrationTopCount() {
    if (!topList || !topCount) return;
    var count = topList.querySelectorAll('li[data-selected-id]').length;
    topCount.textContent = String(count);
    var emptyRow = document.getElementById('bikeRegistrationTopNoOptions');
    if (count === 0) {
      if (!emptyRow) {
        var li = document.createElement('li');
        li.className = 'list-group-item px-0 py-2 text-muted';
        li.id = 'bikeRegistrationTopNoOptions';
        li.textContent = 'No options added yet.';
        topList.appendChild(li);
      }
    } else if (emptyRow) {
      emptyRow.remove();
    }
  }

  function saveBikeRegistrationTopAjax() {
    if (!topForm) return;
    var fd = new FormData(topForm);
    var isChecked = document.getElementById('bikeRegistrationTopEnabled')?.checked;
    fd.set('show_in_top_bar', isChecked ? '1' : '0');
    if (topToggle) topToggle.disabled = true;
    if (topModalSelect) topModalSelect.disabled = true;
    if (topAddBtn) topAddBtn.disabled = true;

    fetch(topForm.action, {
        method: 'POST',
        body: fd,
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      })
      .then(function(r) {
        return r.json().then(function(d) {
          return { ok: r.ok, data: d };
        });
      })
      .then(function(result) {
        if (topToggle) topToggle.disabled = false;
        if (topModalSelect) topModalSelect.disabled = false;
        if (topAddBtn) topAddBtn.disabled = false;
        if (!result.ok || !result.data || result.data.success !== true) {
          throw new Error((result.data && result.data.message) ? result.data.message : 'Could not save.');
        }
        refreshBikeRegistrationTopCount();
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Saved',
            text: result.data.message || 'Updated successfully.',
            timer: 1400,
            showConfirmButton: false
          });
        }
      })
      .catch(function(err) {
        if (topToggle) topToggle.disabled = false;
        if (topModalSelect) topModalSelect.disabled = false;
        if (topAddBtn) topAddBtn.disabled = false;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: (err && err.message) ? err.message : 'Could not save.'
          });
        }
      });
  }

  if (topToggle) {
    topToggle.addEventListener('change', function() {
      saveBikeRegistrationTopAjax();
    });
  }
  if (topList) {
    if (typeof Sortable !== 'undefined') {
      new Sortable(topList, {
        handle: '.bike-registration-top-drag-handle',
        draggable: 'li[data-selected-id]',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function() {
          refreshBikeRegistrationTopCount();
          saveBikeRegistrationTopAjax();
        }
      });
    }
    topList.addEventListener('click', function(e) {
      var removeBtn = e.target.closest('.js-remove-bike-registration-top-option');
      if (!removeBtn) return;
      var row = removeBtn.closest('li[data-selected-id]');
      if (row) {
        row.remove();
        refreshBikeRegistrationTopCount();
        saveBikeRegistrationTopAjax();
      }
    });
  }

  function initBikeRegistrationTopModalSelect2() {
    if (!(typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) || !topModalSelect) return;
    var $modal = jQuery('#addBikeRegistrationTopOptionModal');
    var $select = jQuery(topModalSelect);
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }
    $select.select2({
      dropdownParent: $modal,
      placeholder: 'Select registration status',
      allowClear: true,
      width: '100%',
      dropdownCssClass: 'br-registration-top-select2-dropdown'
    });
    var $container = $select.next('.select2');
    if ($container.length) {
      $container.addClass('br-registration-top-select2-wrap');
    }
  }
  var addBikeRegistrationTopModal = document.getElementById('addBikeRegistrationTopOptionModal');
  if (addBikeRegistrationTopModal) {
    addBikeRegistrationTopModal.addEventListener('shown.bs.modal', function() {
      initBikeRegistrationTopModalSelect2();
      requestAnimationFrame(function() {
        if (typeof jQuery !== 'undefined' && topModalSelect && jQuery.fn.select2 && jQuery(topModalSelect).data('select2')) {
          jQuery(topModalSelect).select2('open');
        }
      });
    });
    addBikeRegistrationTopModal.addEventListener('hidden.bs.modal', function() {
      if (!(typeof jQuery !== 'undefined' && topModalSelect)) return;
      var $s = jQuery(topModalSelect);
      if ($s.hasClass('select2-hidden-accessible')) {
        $s.select2('close');
      }
    });
  }
  if (topAddBtn && topModalSelect && topList) {
    topAddBtn.addEventListener('click', function() {
      var rawVal = (typeof jQuery !== 'undefined' && jQuery(topModalSelect).length && jQuery(topModalSelect).hasClass('select2-hidden-accessible'))
        ? jQuery(topModalSelect).val()
        : topModalSelect.value;
      var selectedId = parseInt(rawVal || '0', 10);
      if (!selectedId) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Select status',
            text: 'Please select a registration status first.'
          });
        }
        return;
      }
      if (topList.querySelector('li[data-selected-id="' + selectedId + '"]')) {
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'info',
            title: 'Already added',
            text: 'This status is already added.'
          });
        }
        return;
      }
      var selectedOption = topModalSelect.options[topModalSelect.selectedIndex];
      var name = selectedOption ? (selectedOption.getAttribute('data-name') || selectedOption.text || 'Status') : 'Status';
      var li = document.createElement('li');
      li.className = 'list-group-item px-0 py-2 d-flex align-items-center justify-content-between';
      li.setAttribute('data-selected-id', String(selectedId));
      li.innerHTML =
        '<div class="d-flex align-items-center">' +
        '<span class="bike-registration-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;"><i class="ti ti-grip-vertical"></i></span>' +
        '<i class="ti ti-point-filled me-1 text-muted"></i>' +
        '<span>' + name + '</span>' +
        '<input type="hidden" name="status_ids[]" value="' + selectedId + '">' +
        '</div>' +
        '<div class="d-flex align-items-center gap-1">' +
        '<button type="button" class="btn btn-xs btn-outline-danger js-remove-bike-registration-top-option" data-remove-id="' + selectedId + '" title="Remove option"><i class="ti ti-trash"></i></button>' +
        '</div>';
      topList.appendChild(li);
      refreshBikeRegistrationTopCount();
      if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
        jQuery(topModalSelect).val('').trigger('change');
      } else {
        topModalSelect.value = '';
      }
      var modalEl = document.getElementById('addBikeRegistrationTopOptionModal');
      if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = bootstrap.Modal.getInstance(modalEl);
        if (modal) modal.hide();
      }
      saveBikeRegistrationTopAjax();
    });
  }
  refreshBikeRegistrationTopCount();
});
