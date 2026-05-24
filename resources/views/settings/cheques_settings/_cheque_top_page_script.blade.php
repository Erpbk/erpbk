<script>
(function() {
  var csrf = '{{ csrf_token() }}';

  window.refreshChequeTopAccordion = function() {
    fetch("{{ route('settings-panel.cheques-settings.cheque-top-accordion-body') }}", {
        headers: {
          'Accept': 'text/html',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(r) {
        return r.text();
      })
      .then(function(html) {
        var container = document.getElementById('chequeTopAccordionContainer');
        if (container) container.innerHTML = html;
      })
      .catch(function() {});
  };

  document.addEventListener('DOMContentLoaded', function() {
    var $topCategoryColumn = typeof jQuery !== 'undefined' ? jQuery('#addChequeTopCategoryColumn') : null;
    var $modal = typeof jQuery !== 'undefined' ? jQuery('#addChequeTopCategoryModal') : null;
    if ($modal && $modal.length && typeof jQuery.fn.select2 !== 'undefined') {
      $modal.on('shown.bs.modal', function() {
        if ($topCategoryColumn && $topCategoryColumn.length) {
          if ($topCategoryColumn.hasClass('select2-hidden-accessible')) {
            $topCategoryColumn.select2('destroy');
          }
          $topCategoryColumn.select2({
            dropdownParent: $modal,
            width: '100%',
            placeholder: 'Select column'
          });
        }
      });
    }

    var chequeTopAvailableValues = [];
    var chequeTopChoices = null;

    function chequeTopSelectValueExists(val) {
      if (val === null || val === undefined || val === '') return false;
      var s = String(val);
      if (chequeTopChoices && chequeTopChoices.length) {
        return chequeTopChoices.some(function(c) {
          return String(c.value) === s;
        });
      }
      return chequeTopAvailableValues.indexOf(s) !== -1;
    }

    function fillchequeTopOptionSelect(selectEl) {
      selectEl.innerHTML = '<option value="">Select value</option>';
      if (chequeTopChoices && chequeTopChoices.length) {
        chequeTopChoices.forEach(function(c) {
          var opt = document.createElement('option');
          opt.value = c.value;
          opt.textContent = c.label || c.value;
          selectEl.appendChild(opt);
        });
      } else {
        chequeTopAvailableValues.forEach(function(v) {
          var opt = document.createElement('option');
          opt.value = v;
          opt.textContent = v;
          selectEl.appendChild(opt);
        });
      }
    }

    function createChequeTopOptionRow(initialValue) {
      var rowsWrap = document.getElementById('addChequeTopOptionRows');
      if (!rowsWrap) return;
      var wrap = document.createElement('div');
      var input = document.createElement('select');
      input.className = 'form-select cheque-top-option-row-input';
      fillchequeTopOptionSelect(input);
      if (initialValue && chequeTopSelectValueExists(initialValue)) {
        input.value = String(initialValue);
      }
      wrap.appendChild(input);
      rowsWrap.appendChild(wrap);
    }

    function setChequeTopSelectionMode(mode) {
      var addBtn = document.getElementById('addChequeTopOptionRowBtn');
      var rowsWrap = document.getElementById('addChequeTopOptionRows');
      if (!rowsWrap) return;
      var rows = rowsWrap.querySelectorAll('.cheque-top-option-row-input');
      if (mode === 'single') {
        if (addBtn) addBtn.style.display = 'none';
        rows.forEach(function(el, idx) {
          el.closest('div').style.display = idx === 0 ? '' : 'none';
        });
      } else {
        if (addBtn) addBtn.style.display = '';
        rows.forEach(function(el) {
          el.closest('div').style.display = '';
        });
      }
    }

    function setChequeTopOptionSuggestions(payload) {
      if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
        chequeTopChoices = Array.isArray(payload.choices) && payload.choices.length ? payload.choices : null;
        chequeTopAvailableValues = Array.isArray(payload.values) ? payload.values : [];
      } else {
        chequeTopChoices = null;
        chequeTopAvailableValues = Array.isArray(payload) ? payload : [];
      }
      var rowsWrap = document.getElementById('addChequeTopOptionRows');
      if (!rowsWrap) return;
      rowsWrap.querySelectorAll('.cheque-top-option-row-input').forEach(function(selectEl) {
        var current = selectEl.value;
        fillchequeTopOptionSelect(selectEl);
        if (current && chequeTopSelectValueExists(current)) {
          selectEl.value = String(current);
        }
      });
    }

    var formaddChequeTopCategory = document.getElementById('formaddChequeTopCategory');
    if (formaddChequeTopCategory) {
      formaddChequeTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('addChequeTopCategorySubmitBtn');
        if (btn) btn.disabled = true;
        fetch("{{ route('settings-panel.cheques-settings.store-cheque-top-category') }}", {
            method: 'POST',
            body: new FormData(formaddChequeTopCategory),
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(r) {
            return r.json();
          })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data.success) {
              var modalEl = document.getElementById('addChequeTopCategoryModal');
              if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getInstance(modalEl).hide();
              }
              formaddChequeTopCategory.reset();
              window.refreshChequeTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || '',
                timer: 1400,
                showConfirmButton: false
              });
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not save.'
            });
          })
          .catch(function() {
            if (btn) btn.disabled = false;
          });
      });
    }

    document.addEventListener('click', function(e) {
      var addOptionBtn = e.target.closest('.btn-add-cheque-top-option');
      if (addOptionBtn) {
        var categoryIdInput = document.getElementById('addChequeTopOptionCategoryId');
        var categoryNameEl = document.getElementById('addChequeTopOptionCategoryName');
        var columnNameEl = document.getElementById('addChequeTopOptionColumnName');
        var rowsWrap = document.getElementById('addChequeTopOptionRows');
        var singleModeInput = document.getElementById('chequeTopOptionModeSingle');
        if (categoryIdInput) categoryIdInput.value = addOptionBtn.getAttribute('data-category-id') || '';
        if (categoryNameEl) categoryNameEl.textContent = addOptionBtn.getAttribute('data-category-name') || '-';
        if (columnNameEl) columnNameEl.textContent = '…';
        if (rowsWrap) {
          rowsWrap.innerHTML = '';
          createChequeTopOptionRow('');
        }
        setChequeTopSelectionMode('single');
        if (singleModeInput) singleModeInput.checked = true;

        var cid = addOptionBtn.getAttribute('data-category-id');
        var fieldValuesUrlTemplate = "{{ route('settings-panel.cheques-settings.cheque-top-category-field-values', ['id' => '__CID__']) }}";
        if (cid) {
          fetch(fieldValuesUrlTemplate.replace('__CID__', cid), {
              headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(function(r) {
              return r.json();
            })
            .then(function(data) {
              if (!data.success) {
                setChequeTopOptionSuggestions({
                  values: [],
                  choices: null
                });
                return;
              }
              if (columnNameEl) columnNameEl.textContent = data.column || '-';
              setChequeTopOptionSuggestions({
                values: data.values || [],
                choices: data.choices || null
              });
              var firstInput = rowsWrap ? rowsWrap.querySelector('.cheque-top-option-row-input') : null;
              if (firstInput && data.values && data.values.length) {
                firstInput.value = String(data.values[0]);
              }
            })
            .catch(function() {
              setChequeTopOptionSuggestions({
                values: [],
                choices: null
              });
            });
        }
      }

      if (e.target.closest('#chequeTopAccordion') ||
        e.target.closest('.cheque-top-visibility-controls') ||
        e.target.closest('.btn-edit-cheque-top-category') ||
        e.target.closest('.btn-delete-cheque-top-category') ||
        e.target.closest('.btn-edit-cheque-top-option') ||
        e.target.closest('.btn-delete-cheque-top-option')) {
        /* handled below */
      }

      var editCategoryBtn = e.target.closest('.btn-edit-cheque-top-category');
      if (editCategoryBtn) {
        var idInput = document.getElementById('editChequeTopCategoryId');
        var nameInput = document.getElementById('editChequeTopCategoryName');
        if (idInput) idInput.value = editCategoryBtn.getAttribute('data-category-id') || '';
        if (nameInput) nameInput.value = editCategoryBtn.getAttribute('data-category-name') || '';
        var modal = new bootstrap.Modal(document.getElementById('editChequeTopCategoryModal'));
        modal.show();
      }

      var editOptionBtn = e.target.closest('.btn-edit-cheque-top-option');
      if (editOptionBtn) {
        var idInput = document.getElementById('editChequeTopOptionId');
        var nameInput = document.getElementById('editChequeTopOptionName');
        if (idInput) idInput.value = editOptionBtn.getAttribute('data-option-id') || '';
        if (nameInput) nameInput.value = editOptionBtn.getAttribute('data-option-name') || '';
        var modal = new bootstrap.Modal(document.getElementById('editChequeTopOptionModal'));
        modal.show();
      }

      var deleteCategoryBtn = e.target.closest('.btn-delete-cheque-top-category');
      if (deleteCategoryBtn) {
        var cid = deleteCategoryBtn.getAttribute('data-category-id');
        var cname = deleteCategoryBtn.getAttribute('data-category-name') || '';
        var deleteUrlTemplate = "{{ route('settings-panel.cheques-settings.destroy-cheque-top-category', ['id' => '__CID__']) }}";
        var doDelete = function() {
          fetch(deleteUrlTemplate.replace('__CID__', cid), {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(function(r) {
              return r.json();
            })
            .then(function(data) {
              if (data.success) {
                window.refreshChequeTopAccordion();
                if (typeof Swal !== 'undefined') Swal.fire({
                  icon: 'success',
                  title: 'Deleted',
                  text: data.message || ''
                });
              }
            });
        };
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Delete category?',
            text: 'Delete "' + cname + '"?',
            showCancelButton: true,
            confirmButtonText: 'Delete'
          }).then(function(result) {
            if (result.isConfirmed) doDelete();
          });
        } else if (confirm('Delete "' + cname + '"?')) doDelete();
      }

      var deleteOptionBtn = e.target.closest('.btn-delete-cheque-top-option');
      if (deleteOptionBtn) {
        var oid = deleteOptionBtn.getAttribute('data-option-id');
        var oname = deleteOptionBtn.getAttribute('data-option-name') || '';
        var olabel = deleteOptionBtn.getAttribute('data-option-label') || oname;
        var deleteUrlTemplate = "{{ route('settings-panel.cheques-settings.destroy-cheque-top-option', ['id' => '__OID__']) }}";
        var doDelete = function() {
          fetch(deleteUrlTemplate.replace('__OID__', oid), {
              method: 'DELETE',
              headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(function(r) {
              return r.json();
            })
            .then(function(data) {
              if (data.success) {
                window.refreshChequeTopAccordion();
                if (typeof Swal !== 'undefined') Swal.fire({
                  icon: 'success',
                  title: 'Deleted',
                  text: data.message || ''
                });
              }
            });
        };
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'warning',
            title: 'Delete option?',
            text: 'Delete "' + olabel + '"?',
            showCancelButton: true,
            confirmButtonText: 'Delete'
          }).then(function(result) {
            if (result.isConfirmed) doDelete();
          });
        } else if (confirm('Delete "' + olabel + '"?')) doDelete();
      }
    });

    document.addEventListener('change', function(e) {
      var toggle = e.target.closest('.cheque-top-visibility-toggle');
      if (!toggle) return;
      var controls = toggle.closest('.cheque-top-visibility-controls');
      var categoryId = controls ? controls.getAttribute('data-category-id') : null;
      if (!categoryId) return;
      var topBarToggle = controls.querySelector('.cheque-top-visibility-toggle[data-field="show_in_top_bar"]');
      var viewCardsToggle = controls.querySelector('.cheque-top-visibility-toggle[data-field="show_in_view_cards"]');
      var topBarValue = topBarToggle ? (topBarToggle.checked ? 1 : 0) : 0;
      var viewCardsValue = viewCardsToggle ? (viewCardsToggle.checked ? 1 : 0) : 0;
      var fd = new FormData();
      fd.append('show_in_top_bar', String(topBarValue));
      fd.append('show_in_view_cards', String(viewCardsValue));
      var updateVisibilityUrlTemplate = "{{ route('settings-panel.cheques-settings.update-cheque-top-category-visibility', ['id' => '__CID__']) }}";
      fetch(updateVisibilityUrlTemplate.replace('__CID__', categoryId), {
          method: 'POST',
          body: fd,
          headers: {
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(r) {
          return r.json();
        })
        .then(function(data) {
          if (!data.success && typeof Swal !== 'undefined') {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || ''
            });
          }
        })
        .catch(function() {});
    });

    var formeditChequeTopCategory = document.getElementById('formeditChequeTopCategory');
    if (formeditChequeTopCategory) {
      formeditChequeTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('editChequeTopCategoryId').value;
        var urlTemplate = "{{ route('settings-panel.cheques-settings.update-cheque-top-category', ['id' => '__CID__']) }}";
        var fd = new FormData(formeditChequeTopCategory);
        fd.append('_method', 'PUT');
        fetch(urlTemplate.replace('__CID__', id), {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            }
          })
          .then(function(r) {
            return r.json();
          })
          .then(function(data) {
            if (data.success) {
              bootstrap.Modal.getInstance(document.getElementById('editChequeTopCategoryModal')).hide();
              window.refreshChequeTopAccordion();
            }
          });
      });
    }

    var formeditChequeTopOption = document.getElementById('formeditChequeTopOption');
    if (formeditChequeTopOption) {
      formeditChequeTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('editChequeTopOptionId').value;
        var urlTemplate = "{{ route('settings-panel.cheques-settings.update-cheque-top-option', ['id' => '__OID__']) }}";
        var fd = new FormData(formeditChequeTopOption);
        fd.append('_method', 'PUT');
        fetch(urlTemplate.replace('__OID__', id), {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            }
          })
          .then(function(r) {
            return r.json();
          })
          .then(function(data) {
            if (data.success) {
              bootstrap.Modal.getInstance(document.getElementById('editChequeTopOptionModal')).hide();
              window.refreshChequeTopAccordion();
            }
          });
      });
    }

    var formaddChequeTopOption = document.getElementById('formaddChequeTopOption');
    if (formaddChequeTopOption) {
      formaddChequeTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('addChequeTopOptionSubmitBtn');
        var rowsWrap = document.getElementById('addChequeTopOptionRows');
        var modeInput = document.querySelector('input[name="selection_mode"]:checked');
        var mode = modeInput ? modeInput.value : 'single';
        if (btn) btn.disabled = true;
        var payload = new FormData(formaddChequeTopOption);
        payload.delete('selected_values[]');
        var items = [];
        if (rowsWrap) {
          items = Array.prototype.slice.call(rowsWrap.querySelectorAll('.cheque-top-option-row-input'))
            .map(function(el) {
              return (el.value || '').trim();
            })
            .filter(function(v) {
              return v.length > 0;
            });
        }
        if (mode === 'single' && items.length > 1) items = [items[0]];
        items.forEach(function(v) {
          payload.append('selected_values[]', v);
        });
        fetch("{{ route('settings-panel.cheques-settings.store-cheque-top-option') }}", {
            method: 'POST',
            body: payload,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json'
            }
          })
          .then(function(r) {
            return r.json();
          })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data.success) {
              bootstrap.Modal.getInstance(document.getElementById('addChequeTopOptionModal')).hide();
              window.refreshChequeTopAccordion();
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || ''
            });
          })
          .catch(function() {
            if (btn) btn.disabled = false;
          });
      });
    }

    var addChequeTopOptionRowBtn = document.getElementById('addChequeTopOptionRowBtn');
    if (addChequeTopOptionRowBtn) {
      addChequeTopOptionRowBtn.addEventListener('click', function() {
        createChequeTopOptionRow('');
      });
    }

    document.querySelectorAll('input[name="selection_mode"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        setChequeTopSelectionMode(this.value || 'single');
      });
    });    var targetHash = window.location.hash;
    if (targetHash === '#tab-cheque-top') {
      var btn = document.querySelector('[data-bs-target="#tab-cheque-top"]');
      if (btn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(btn).show();
      } else if (btn) btn.click();
    }
  });
})();
</script>



