<script>
(function() {
  var csrf = '{{ csrf_token() }}';

  window.refreshBikeTopAccordion = function() {
    fetch("{{ route('settings-panel.bike-settings.bike-top-accordion-body') }}", {
        headers: {
          'Accept': 'text/html',
          'X-Requested-With': 'XMLHttpRequest'
        }
      })
      .then(function(r) {
        return r.text();
      })
      .then(function(html) {
        var container = document.getElementById('bikeTopAccordionContainer');
        if (container) container.innerHTML = html;
      })
      .catch(function() {});
  };

  document.addEventListener('DOMContentLoaded', function() {
    var $topCategoryColumn = typeof jQuery !== 'undefined' ? jQuery('#addBikeTopCategoryColumn') : null;
    var $modal = typeof jQuery !== 'undefined' ? jQuery('#addBikeTopCategoryModal') : null;
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

    var bikeTopAvailableValues = [];
    var bikeTopChoices = null;

    function bikeTopSelectValueExists(val) {
      if (val === null || val === undefined || val === '') return false;
      var s = String(val);
      if (bikeTopChoices && bikeTopChoices.length) {
        return bikeTopChoices.some(function(c) {
          return String(c.value) === s;
        });
      }
      return bikeTopAvailableValues.indexOf(s) !== -1;
    }

    function fillBikeTopOptionSelect(selectEl) {
      selectEl.innerHTML = '<option value="">Select value</option>';
      if (bikeTopChoices && bikeTopChoices.length) {
        bikeTopChoices.forEach(function(c) {
          var opt = document.createElement('option');
          opt.value = c.value;
          opt.textContent = c.label || c.value;
          selectEl.appendChild(opt);
        });
      } else {
        bikeTopAvailableValues.forEach(function(v) {
          var opt = document.createElement('option');
          opt.value = v;
          opt.textContent = v;
          selectEl.appendChild(opt);
        });
      }
    }

    function createBikeTopOptionRow(initialValue) {
      var rowsWrap = document.getElementById('addBikeTopOptionRows');
      if (!rowsWrap) return;
      var wrap = document.createElement('div');
      var input = document.createElement('select');
      input.className = 'form-select bike-top-option-row-input';
      fillBikeTopOptionSelect(input);
      if (initialValue && bikeTopSelectValueExists(initialValue)) {
        input.value = String(initialValue);
      }
      wrap.appendChild(input);
      rowsWrap.appendChild(wrap);
    }

    function setBikeTopSelectionMode(mode) {
      var addBtn = document.getElementById('addBikeTopOptionRowBtn');
      var rowsWrap = document.getElementById('addBikeTopOptionRows');
      if (!rowsWrap) return;
      var rows = rowsWrap.querySelectorAll('.bike-top-option-row-input');
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

    function setBikeTopOptionSuggestions(payload) {
      if (payload && typeof payload === 'object' && !Array.isArray(payload)) {
        bikeTopChoices = Array.isArray(payload.choices) && payload.choices.length ? payload.choices : null;
        bikeTopAvailableValues = Array.isArray(payload.values) ? payload.values : [];
      } else {
        bikeTopChoices = null;
        bikeTopAvailableValues = Array.isArray(payload) ? payload : [];
      }
      var rowsWrap = document.getElementById('addBikeTopOptionRows');
      if (!rowsWrap) return;
      rowsWrap.querySelectorAll('.bike-top-option-row-input').forEach(function(selectEl) {
        var current = selectEl.value;
        fillBikeTopOptionSelect(selectEl);
        if (current && bikeTopSelectValueExists(current)) {
          selectEl.value = String(current);
        }
      });
    }

    var formAddBikeTopCategory = document.getElementById('formAddBikeTopCategory');
    if (formAddBikeTopCategory) {
      formAddBikeTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('addBikeTopCategorySubmitBtn');
        if (btn) btn.disabled = true;
        fetch("{{ route('settings-panel.bike-settings.store-bike-top-category') }}", {
            method: 'POST',
            body: new FormData(formAddBikeTopCategory),
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
              var modalEl = document.getElementById('addBikeTopCategoryModal');
              if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                bootstrap.Modal.getInstance(modalEl).hide();
              }
              formAddBikeTopCategory.reset();
              window.refreshBikeTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || '',
                timer: 1400,
                showConfirmButton: false
              });
              window.location.reload();
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
      var addOptionBtn = e.target.closest('.btn-add-bike-top-option');
      if (addOptionBtn) {
        var categoryIdInput = document.getElementById('addBikeTopOptionCategoryId');
        var categoryNameEl = document.getElementById('addBikeTopOptionCategoryName');
        var columnNameEl = document.getElementById('addBikeTopOptionColumnName');
        var rowsWrap = document.getElementById('addBikeTopOptionRows');
        var singleModeInput = document.getElementById('bikeTopOptionModeSingle');
        if (categoryIdInput) categoryIdInput.value = addOptionBtn.getAttribute('data-category-id') || '';
        if (categoryNameEl) categoryNameEl.textContent = addOptionBtn.getAttribute('data-category-name') || '-';
        if (columnNameEl) columnNameEl.textContent = '…';
        if (rowsWrap) {
          rowsWrap.innerHTML = '';
          createBikeTopOptionRow('');
        }
        setBikeTopSelectionMode('single');
        if (singleModeInput) singleModeInput.checked = true;

        var cid = addOptionBtn.getAttribute('data-category-id');
        var fieldValuesUrlTemplate = "{{ route('settings-panel.bike-settings.bike-top-category-field-values', ['id' => '__CID__']) }}";
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
                setBikeTopOptionSuggestions({
                  values: [],
                  choices: null
                });
                return;
              }
              if (columnNameEl) columnNameEl.textContent = data.column || '-';
              setBikeTopOptionSuggestions({
                values: data.values || [],
                choices: data.choices || null
              });
              var firstInput = rowsWrap ? rowsWrap.querySelector('.bike-top-option-row-input') : null;
              if (firstInput && data.values && data.values.length) {
                firstInput.value = String(data.values[0]);
              }
            })
            .catch(function() {
              setBikeTopOptionSuggestions({
                values: [],
                choices: null
              });
            });
        }
      }

      if (e.target.closest('#bikeTopAccordion') ||
        e.target.closest('.bike-top-visibility-controls') ||
        e.target.closest('.btn-edit-bike-top-category') ||
        e.target.closest('.btn-delete-bike-top-category') ||
        e.target.closest('.btn-edit-bike-top-option') ||
        e.target.closest('.btn-delete-bike-top-option')) {
        /* handled below */
      }

      var editCategoryBtn = e.target.closest('.btn-edit-bike-top-category');
      if (editCategoryBtn) {
        var idInput = document.getElementById('editBikeTopCategoryId');
        var nameInput = document.getElementById('editBikeTopCategoryName');
        if (idInput) idInput.value = editCategoryBtn.getAttribute('data-category-id') || '';
        if (nameInput) nameInput.value = editCategoryBtn.getAttribute('data-category-name') || '';
        var modal = new bootstrap.Modal(document.getElementById('editBikeTopCategoryModal'));
        modal.show();
      }

      var editOptionBtn = e.target.closest('.btn-edit-bike-top-option');
      if (editOptionBtn) {
        var idInput = document.getElementById('editBikeTopOptionId');
        var nameInput = document.getElementById('editBikeTopOptionName');
        if (idInput) idInput.value = editOptionBtn.getAttribute('data-option-id') || '';
        if (nameInput) nameInput.value = editOptionBtn.getAttribute('data-option-name') || '';
        var modal = new bootstrap.Modal(document.getElementById('editBikeTopOptionModal'));
        modal.show();
      }

      var deleteCategoryBtn = e.target.closest('.btn-delete-bike-top-category');
      if (deleteCategoryBtn) {
        var cid = deleteCategoryBtn.getAttribute('data-category-id');
        var cname = deleteCategoryBtn.getAttribute('data-category-name') || '';
        var deleteUrlTemplate = "{{ route('settings-panel.bike-settings.destroy-bike-top-category', ['id' => '__CID__']) }}";
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
                window.refreshBikeTopAccordion();
                if (typeof Swal !== 'undefined') Swal.fire({
                  icon: 'success',
                  title: 'Deleted',
                  text: data.message || ''
                });
                window.location.reload();
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

      var deleteOptionBtn = e.target.closest('.btn-delete-bike-top-option');
      if (deleteOptionBtn) {
        var oid = deleteOptionBtn.getAttribute('data-option-id');
        var oname = deleteOptionBtn.getAttribute('data-option-name') || '';
        var olabel = deleteOptionBtn.getAttribute('data-option-label') || oname;
        var deleteUrlTemplate = "{{ route('settings-panel.bike-settings.destroy-bike-top-option', ['id' => '__OID__']) }}";
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
                window.refreshBikeTopAccordion();
                if (typeof Swal !== 'undefined') Swal.fire({
                  icon: 'success',
                  title: 'Deleted',
                  text: data.message || ''
                });
                window.location.reload();
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
      var toggle = e.target.closest('.bike-top-visibility-toggle');
      if (!toggle) return;
      var controls = toggle.closest('.bike-top-visibility-controls');
      var categoryId = controls ? controls.getAttribute('data-category-id') : null;
      if (!categoryId) return;
      var topBarToggle = controls.querySelector('.bike-top-visibility-toggle[data-field="show_in_top_bar"]');
      var viewCardsToggle = controls.querySelector('.bike-top-visibility-toggle[data-field="show_in_view_cards"]');
      var topBarValue = topBarToggle ? (topBarToggle.checked ? 1 : 0) : 0;
      var viewCardsValue = viewCardsToggle ? (viewCardsToggle.checked ? 1 : 0) : 0;
      var fd = new FormData();
      fd.append('show_in_top_bar', String(topBarValue));
      fd.append('show_in_view_cards', String(viewCardsValue));
      var updateVisibilityUrlTemplate = "{{ route('settings-panel.bike-settings.update-bike-top-category-visibility', ['id' => '__CID__']) }}";
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

    var formEditBikeTopCategory = document.getElementById('formEditBikeTopCategory');
    if (formEditBikeTopCategory) {
      formEditBikeTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('editBikeTopCategoryId').value;
        var urlTemplate = "{{ route('settings-panel.bike-settings.update-bike-top-category', ['id' => '__CID__']) }}";
        var fd = new FormData(formEditBikeTopCategory);
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
              bootstrap.Modal.getInstance(document.getElementById('editBikeTopCategoryModal')).hide();
              window.refreshBikeTopAccordion();
              window.location.reload();
            }
          });
      });
    }

    var formEditBikeTopOption = document.getElementById('formEditBikeTopOption');
    if (formEditBikeTopOption) {
      formEditBikeTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var id = document.getElementById('editBikeTopOptionId').value;
        var urlTemplate = "{{ route('settings-panel.bike-settings.update-bike-top-option', ['id' => '__OID__']) }}";
        var fd = new FormData(formEditBikeTopOption);
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
              bootstrap.Modal.getInstance(document.getElementById('editBikeTopOptionModal')).hide();
              window.refreshBikeTopAccordion();
              window.location.reload();
            }
          });
      });
    }

    var formAddBikeTopOption = document.getElementById('formAddBikeTopOption');
    if (formAddBikeTopOption) {
      formAddBikeTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var btn = document.getElementById('addBikeTopOptionSubmitBtn');
        var rowsWrap = document.getElementById('addBikeTopOptionRows');
        var modeInput = document.querySelector('input[name="selection_mode"]:checked');
        var mode = modeInput ? modeInput.value : 'single';
        if (btn) btn.disabled = true;
        var payload = new FormData(formAddBikeTopOption);
        payload.delete('selected_values[]');
        var items = [];
        if (rowsWrap) {
          items = Array.prototype.slice.call(rowsWrap.querySelectorAll('.bike-top-option-row-input'))
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
        fetch("{{ route('settings-panel.bike-settings.store-bike-top-option') }}", {
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
              bootstrap.Modal.getInstance(document.getElementById('addBikeTopOptionModal')).hide();
              window.refreshBikeTopAccordion();
              window.location.reload();
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

    var addBikeTopOptionRowBtn = document.getElementById('addBikeTopOptionRowBtn');
    if (addBikeTopOptionRowBtn) {
      addBikeTopOptionRowBtn.addEventListener('click', function() {
        createBikeTopOptionRow('');
      });
    }

    document.querySelectorAll('input[name="selection_mode"]').forEach(function(radio) {
      radio.addEventListener('change', function() {
        setBikeTopSelectionMode(this.value || 'single');
      });
    });

    var bikeTopUserPrefsForm = document.getElementById('bikeTopUserPrefsForm');
    if (bikeTopUserPrefsForm) {
      bikeTopUserPrefsForm.addEventListener('submit', function(e) {
        e.preventDefault();
        var boxes = bikeTopUserPrefsForm.querySelectorAll('.bike-top-user-pref-option');
        var total = boxes.length;
        var checked = Array.prototype.filter.call(boxes, function(b) {
          return b.checked;
        });
        var fd = new FormData();
        fd.append('_token', csrf);
        if (checked.length === total) {
          /* show all — omit ids */
        } else {
          checked.forEach(function(b) {
            fd.append('visible_option_ids[]', b.value);
          });
        }
        fetch("{{ route('settings-panel.bike-settings.save-bike-top-user-preferences') }}", {
            method: 'POST',
            body: fd,
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(r) {
            return r.json();
          })
          .then(function(data) {
            if (data.success && typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || '',
                timer: 1400,
                showConfirmButton: false
              });
            }
          });
      });
    }

    var bikeTopUserPrefsResetBtn = document.getElementById('bikeTopUserPrefsResetBtn');
    if (bikeTopUserPrefsResetBtn) {
      bikeTopUserPrefsResetBtn.addEventListener('click', function() {
        var fd = new FormData();
        fd.append('_token', csrf);
        fetch("{{ route('settings-panel.bike-settings.save-bike-top-user-preferences') }}", {
            method: 'POST',
            body: fd,
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(r) {
            return r.json();
          })
          .then(function(data) {
            if (data.success) {
              window.location.reload();
            }
          });
      });
    }

    var targetHash = window.location.hash;
    if (targetHash === '#tab-vehicle-top') {
      var btn = document.querySelector('[data-bs-target="#tab-vehicle-top"]');
      if (btn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
        bootstrap.Tab.getOrCreateInstance(btn).show();
      } else if (btn) btn.click();
    }
  });
})();
</script>
