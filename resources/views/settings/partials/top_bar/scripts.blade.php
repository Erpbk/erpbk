@php
  $topBarRoutes = $topBarRoutes ?? [];
@endphp
<script>
(function() {
  if (window.__erpModuleTopBarScriptLoaded) return;
  window.__erpModuleTopBarScriptLoaded = true;

  var routes = @json($topBarRoutes);
  var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')
    || (document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value);

  function url(tpl, id) {
    return String(tpl).replace('__ID__', String(id)).replace('__CID__', String(id)).replace('__OID__', String(id));
  }

  window.refreshRiderTopAccordion = function() {
    if (!routes.accordion) return;
    fetch(routes.accordion, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
    })
      .then(function(r) { return r.text(); })
      .then(function(html) {
        var container = document.getElementById('riderTopAccordionContainer');
        if (container) container.innerHTML = html;
      });
  };

  function initTopCategoryColumnSelect2() {
    if (!(window.jQuery && jQuery.fn && jQuery.fn.select2)) return;
    var $col = jQuery('#addRiderTopCategoryColumn');
    var $modal = jQuery('#addRiderTopCategoryModal');
    if (!$col.length || !$modal.length) return;
    if ($col.hasClass('select2-hidden-accessible')) $col.select2('destroy');
    $col.select2({
      width: '100%',
      dropdownParent: $modal,
      placeholder: $col.data('placeholder') || 'Select column',
      allowClear: true
    });
  }

  var riderTopAvailableValues = [];

  function createRiderTopOptionRow(initialValue) {
    var rowsWrap = document.getElementById('addRiderTopOptionRows');
    if (!rowsWrap) return;
    var row = document.createElement('div');
    row.className = 'd-flex align-items-center gap-2';
    var input = document.createElement('select');
    input.className = 'form-select rider-top-option-row-input';
    input.appendChild(new Option('Select value', ''));
    riderTopAvailableValues.forEach(function(v) {
      input.appendChild(new Option(v, v));
    });
    if (initialValue) input.value = initialValue;
    var removeBtn = document.createElement('button');
    removeBtn.type = 'button';
    removeBtn.className = 'btn btn-sm btn-outline-danger';
    removeBtn.textContent = 'Remove';
    removeBtn.addEventListener('click', function() { row.remove(); });
    row.appendChild(input);
    row.appendChild(removeBtn);
    rowsWrap.appendChild(row);
  }

  function setRiderTopSelectionMode(mode) {
    var addBtn = document.getElementById('addRiderTopOptionRowBtn');
    var rowsWrap = document.getElementById('addRiderTopOptionRows');
    if (!rowsWrap) return;
    var isMultiple = mode === 'multiple';
    if (addBtn) addBtn.disabled = !isMultiple;
    if (!isMultiple) {
      var rows = rowsWrap.querySelectorAll('.rider-top-option-row-input');
      for (var i = 1; i < rows.length; i++) {
        var rowEl = rows[i].closest('.d-flex');
        if (rowEl) rowEl.remove();
      }
    }
  }

  function setRiderTopOptionSuggestions(values) {
    riderTopAvailableValues = Array.isArray(values) ? values : [];
    var rowsWrap = document.getElementById('addRiderTopOptionRows');
    if (!rowsWrap) return;
    rowsWrap.querySelectorAll('.rider-top-option-row-input').forEach(function(selectEl) {
      var currentValue = selectEl.value || '';
      selectEl.innerHTML = '';
      selectEl.appendChild(new Option('Select value', ''));
      riderTopAvailableValues.forEach(function(v) {
        selectEl.appendChild(new Option(v, v));
      });
      if (currentValue && riderTopAvailableValues.indexOf(currentValue) !== -1) {
        selectEl.value = currentValue;
      }
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    initTopCategoryColumnSelect2();
    if (window.jQuery) {
      jQuery('#addRiderTopCategoryModal').on('shown.bs.modal', initTopCategoryColumnSelect2);
    }

    var formAddRiderTopCategory = document.getElementById('formAddRiderTopCategory');
    if (formAddRiderTopCategory) {
      formAddRiderTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('addRiderTopCategorySubmitBtn');
        if (btn) btn.disabled = true;
        fetch(routes.store_category, {
          method: 'POST',
          body: new FormData(form),
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data.success) {
              bootstrap.Modal.getInstance(document.getElementById('addRiderTopCategoryModal'))?.hide();
              form.reset();
              if (window.jQuery && jQuery('#addRiderTopCategoryColumn').hasClass('select2-hidden-accessible')) {
                jQuery('#addRiderTopCategoryColumn').val(null).trigger('change');
              }
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Saved', text: data.message || 'Category added.' });
            } else if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not save.' });
          })
          .catch(function() {
            if (btn) btn.disabled = false;
            if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save.' });
          });
      });
    }

    document.addEventListener('click', function(e) {
      var addOptionBtn = e.target.closest('.btn-add-rider-top-option');
      if (addOptionBtn) {
        document.getElementById('addRiderTopOptionCategoryId').value = addOptionBtn.getAttribute('data-category-id') || '';
        document.getElementById('addRiderTopOptionCategoryName').textContent = addOptionBtn.getAttribute('data-category-name') || '-';
        document.getElementById('addRiderTopOptionColumnName').textContent = '-';
        var singleMode = document.getElementById('riderTopOptionModeSingle');
        if (singleMode) singleMode.checked = true;
        var rowsWrap = document.getElementById('addRiderTopOptionRows');
        if (rowsWrap) { rowsWrap.innerHTML = ''; createRiderTopOptionRow(''); }
        setRiderTopSelectionMode('single');
        var categoryId = addOptionBtn.getAttribute('data-category-id') || '';
        if (categoryId && routes.field_values) {
          fetch(url(routes.field_values, categoryId), { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (!data.success) { setRiderTopOptionSuggestions([]); return; }
              document.getElementById('addRiderTopOptionColumnName').textContent = data.column || '-';
              var values = Array.isArray(data.values) ? data.values : [];
              setRiderTopOptionSuggestions(values);
              if (values.length && rowsWrap) {
                rowsWrap.innerHTML = '';
                values.forEach(function(v) { createRiderTopOptionRow(v); });
                if (!rowsWrap.querySelector('.rider-top-option-row-input')) {
                  createRiderTopOptionRow('');
                }
              }
            })
            .catch(function() { setRiderTopOptionSuggestions([]); });
        }
      }

      if (e.target.closest('.module-top-visibility-controls') || e.target.closest('.btn-edit-rider-top-category') ||
          e.target.closest('.btn-delete-rider-top-category') || e.target.closest('.btn-edit-rider-top-option') ||
          e.target.closest('.btn-delete-rider-top-option')) {
        e.stopPropagation();
      }

      var editCategoryBtn = e.target.closest('.btn-edit-rider-top-category');
      if (editCategoryBtn) {
        document.getElementById('editRiderTopCategoryId').value = editCategoryBtn.getAttribute('data-category-id') || '';
        document.getElementById('editRiderTopCategoryName').value = editCategoryBtn.getAttribute('data-category-name') || '';
        new bootstrap.Modal(document.getElementById('editRiderTopCategoryModal')).show();
      }

      var editOptionBtn = e.target.closest('.btn-edit-rider-top-option');
      if (editOptionBtn) {
        document.getElementById('editRiderTopOptionId').value = editOptionBtn.getAttribute('data-option-id') || '';
        document.getElementById('editRiderTopOptionName').value = editOptionBtn.getAttribute('data-option-name') || '';
        new bootstrap.Modal(document.getElementById('editRiderTopOptionModal')).show();
      }

      var deleteCategoryBtn = e.target.closest('.btn-delete-rider-top-category');
      if (deleteCategoryBtn) {
        var categoryId = deleteCategoryBtn.getAttribute('data-category-id');
        var categoryName = deleteCategoryBtn.getAttribute('data-category-name') || 'this category';
        var doDelete = function() {
          var fd = new FormData();
          fd.append('_method', 'DELETE');
          fetch(url(routes.destroy_category, categoryId), {
            method: 'POST', body: fd,
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (data.success) {
                window.refreshRiderTopAccordion();
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Deleted', text: data.message || 'Category deleted.' });
              } else if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not delete.' });
            });
        };
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'warning', title: 'Delete category?', text: 'This will also remove all options under "' + categoryName + '".', showCancelButton: true, confirmButtonText: 'Delete' })
            .then(function(r) { if (r.isConfirmed) doDelete(); });
        } else if (confirm('Delete category "' + categoryName + '"?')) doDelete();
      }

      var deleteOptionBtn = e.target.closest('.btn-delete-rider-top-option');
      if (deleteOptionBtn) {
        var optionId = deleteOptionBtn.getAttribute('data-option-id');
        var optionName = deleteOptionBtn.getAttribute('data-option-name') || 'this option';
        var doDelete = function() {
          var fd = new FormData();
          fd.append('_method', 'DELETE');
          fetch(url(routes.destroy_option, optionId), {
            method: 'POST', body: fd,
            headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
          })
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (data.success) {
                window.refreshRiderTopAccordion();
                if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Deleted', text: data.message || 'Option deleted.' });
              }
            });
        };
        if (typeof Swal !== 'undefined') {
          Swal.fire({ icon: 'warning', title: 'Delete option?', text: 'Delete "' + optionName + '"?', showCancelButton: true, confirmButtonText: 'Delete' })
            .then(function(r) { if (r.isConfirmed) doDelete(); });
        } else if (confirm('Delete option?')) doDelete();
      }
    });

    var addRiderTopOptionRowBtn = document.getElementById('addRiderTopOptionRowBtn');
    if (addRiderTopOptionRowBtn) {
      addRiderTopOptionRowBtn.addEventListener('click', function() { createRiderTopOptionRow(''); });
    }

    document.addEventListener('change', function(e) {
      var modeInput = e.target.closest('input[name="selection_mode"]');
      if (modeInput) setRiderTopSelectionMode(modeInput.value || 'single');

      var toggle = e.target.closest('.module-top-visibility-toggle');
      if (toggle) {
        var controls = toggle.closest('.module-top-visibility-controls');
        var categoryId = controls ? controls.getAttribute('data-category-id') : null;
        if (!categoryId) return;
        var topBarToggle = controls.querySelector('.module-top-visibility-toggle[data-field="show_in_top_bar"]');
        var viewCardsToggle = controls.querySelector('.module-top-visibility-toggle[data-field="show_in_view_cards"]');
        var fd = new FormData();
        fd.append('show_in_top_bar', topBarToggle && topBarToggle.checked ? '1' : '0');
        fd.append('show_in_view_cards', viewCardsToggle && viewCardsToggle.checked ? '1' : '0');
        fetch(url(routes.update_visibility, categoryId), {
          method: 'POST', body: fd,
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (!data.success && typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not update.' });
          });
      }
    });

    var formAddRiderTopOption = document.getElementById('formAddRiderTopOption');
    if (formAddRiderTopOption) {
      formAddRiderTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('addRiderTopOptionSubmitBtn');
        var rowsWrap = document.getElementById('addRiderTopOptionRows');
        var modeInput = document.querySelector('input[name="selection_mode"]:checked');
        var mode = modeInput ? modeInput.value : 'single';
        if (btn) btn.disabled = true;
        var payload = new FormData(formAddRiderTopOption);
        payload.delete('selected_values[]');
        var items = [];
        if (rowsWrap) {
          items = Array.prototype.slice.call(rowsWrap.querySelectorAll('.rider-top-option-row-input'))
            .map(function(el) { return (el.value || '').trim(); })
            .filter(function(v) { return v.length > 0; });
        }
        if (mode === 'single' && items.length > 1) items = [items[0]];
        items.forEach(function(v) { payload.append('selected_values[]', v); });
        if (items.length === 0) {
          if (btn) btn.disabled = false;
          if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: 'Please add at least one option value.' });
          return;
        }
        fetch(routes.store_option, {
          method: 'POST', body: payload,
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data.success) {
              bootstrap.Modal.getInstance(document.getElementById('addRiderTopOptionModal'))?.hide();
              formAddRiderTopOption.reset();
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Saved', text: data.message || 'Option added.' });
            } else if (typeof Swal !== 'undefined') Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not save.' });
          })
          .catch(function() { if (btn) btn.disabled = false; });
      });
    }

    var formEditRiderTopCategory = document.getElementById('formEditRiderTopCategory');
    if (formEditRiderTopCategory) {
      formEditRiderTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('editRiderTopCategoryId').value;
        var btn = document.getElementById('editRiderTopCategorySubmitBtn');
        if (!id) return;
        if (btn) btn.disabled = true;
        var fd = new FormData(formEditRiderTopCategory);
        fd.append('_method', 'PUT');
        fetch(url(routes.update_category, id), {
          method: 'POST', body: fd,
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data.success) {
              bootstrap.Modal.getInstance(document.getElementById('editRiderTopCategoryModal'))?.hide();
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Updated', text: data.message || 'Category updated.' });
            }
          })
          .catch(function() { if (btn) btn.disabled = false; });
      });
    }

    var formEditRiderTopOption = document.getElementById('formEditRiderTopOption');
    if (formEditRiderTopOption) {
      formEditRiderTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('editRiderTopOptionId').value;
        var btn = document.getElementById('editRiderTopOptionSubmitBtn');
        if (!id) return;
        if (btn) btn.disabled = true;
        var fd = new FormData(formEditRiderTopOption);
        fd.append('_method', 'PUT');
        fetch(url(routes.update_option, id), {
          method: 'POST', body: fd,
          headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data.success) {
              bootstrap.Modal.getInstance(document.getElementById('editRiderTopOptionModal'))?.hide();
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({ icon: 'success', title: 'Updated', text: data.message || 'Option updated.' });
            }
          })
          .catch(function() { if (btn) btn.disabled = false; });
      });
    }
  });
})();
</script>

