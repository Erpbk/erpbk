var legalCaseStatusConfig = document.getElementById('legal-case-status-manager-config');
  if (legalCaseStatusConfig) {
    var legalCaseStatusSortableInstance = null;

    function initLegalCaseStatusSortable() {
      if (typeof Sortable === 'undefined') return;
      var tbody = document.getElementById('legal-case-statuses-tbody');
      if (!tbody || tbody.querySelectorAll('tr[data-id]').length === 0) return;

      if (legalCaseStatusSortableInstance) {
        legalCaseStatusSortableInstance.destroy();
      }

      legalCaseStatusSortableInstance = new Sortable(tbody, {
        handle: '.visa-drag-handle',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function() {
          var order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(row) {
            return row.getAttribute('data-id');
          });

          fetch("{{ route('settings-panel.legal-case-statuses.reorder', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}", {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': '{{ csrf_token() }}',
              'Accept': 'application/json'
            },
            body: JSON.stringify({
              order: order
            })
          }).then(function(response) {
            return response.json();
          }).then(function(data) {
            if (!data.success && typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Could not save order.'
              });
            }
          }).catch(function() {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not save order.'
              });
            }
          });
        }
      });
    }

    function deleteLegalCaseStatusAjax(url, triggerBtn) {
      return fetch(url, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          _method: 'DELETE'
        })
      }).then(function(response) {
        return response.json().then(function(data) {
          return {
            ok: response.ok,
            status: response.status,
            data: data
          };
        }).catch(function() {
          return {
            ok: response.ok,
            status: response.status,
            data: {
              success: false,
              message: 'Invalid server response.'
            }
          };
        });
      }).then(function(result) {
        if (!result.ok || !result.data || result.data.success !== true) {
          throw new Error((result.data && result.data.message) ? result.data.message : 'Delete failed.');
        }
        var row = triggerBtn ? triggerBtn.closest('tr[data-id]') : null;
        if (row) {
          row.remove();
        }
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'success',
            title: 'Deleted',
            text: result.data.message || 'Visa status deleted successfully.',
            timer: 1600,
            showConfirmButton: false
          });
        }
        return result.data;
      });
    }

    function confirmDelete(url, formId, triggerBtn) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Are you sure?',
          text: "You won't be able to revert this!",
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#3085d6',
          cancelButtonColor: '#d33',
          confirmButtonText: 'Yes, delete it!'
        }).then(function(result) {
          if (!result.isConfirmed) return;
          deleteLegalCaseStatusAjax(url, triggerBtn).catch(function(err) {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: (err && err.message) ? err.message : 'Could not delete legal case status.'
            });
          });
        });
        return;
      }
      if (confirm('Are you sure?')) {
        deleteLegalCaseStatusAjax(url, triggerBtn).catch(function() {
          window.location.href = url;
        });
      }
    }

    document.addEventListener('click', function(e) {
      var deleteBtn = e.target.closest('.js-visa-status-delete-btn');
      if (deleteBtn) {
        confirmDelete(
          deleteBtn.getAttribute('data-delete-url') || '',
          deleteBtn.getAttribute('data-delete-form-id') || '',
          deleteBtn
        );
        return;
      }

      var editBtn = e.target.closest('.js-visa-status-edit-btn');
      if (!editBtn) return;

      var editUrlTemplate = legalCaseStatusConfig.getAttribute('data-edit-url-template') || '';
      var form = document.getElementById('editLegalCaseStatusForm');
      if (!form) return;
      form.action = editUrlTemplate.replace('__ID__', String(editBtn.dataset.id || ''));

      document.getElementById('editLegalCaseStatusName').value = editBtn.dataset.name || '';
      document.getElementById('editLegalCaseStatusCode').value = editBtn.dataset.code || '';
      document.getElementById('editLegalCaseStatusCategory').value = editBtn.dataset.category || 'Other';
      document.getElementById('editLegalCaseStatusDisplayOrder').value = editBtn.dataset.displayOrder || '';
      document.getElementById('editLegalCaseStatusDescription').value = editBtn.dataset.description || '';
      document.getElementById('editLegalCaseStatusIsRequired').checked = String(editBtn.dataset.isRequired || '0') === '1';
      document.getElementById('editLegalCaseStatusIsActive').checked = String(editBtn.dataset.isActive || '0') === '1';
    });

    document.addEventListener('DOMContentLoaded', function() {
      initLegalCaseStatusSortable();
      var targetHash = window.location.hash;
      if (targetHash === '#tab-legal-case-status-management' || targetHash === '#tab-legal-case-top') {
        var visaTabBtn = document.querySelector('[data-bs-target="' + targetHash + '"]');
        if (visaTabBtn && typeof bootstrap !== 'undefined' && bootstrap.Tab) {
          bootstrap.Tab.getOrCreateInstance(visaTabBtn).show();
        } else if (visaTabBtn) {
          visaTabBtn.click();
        }
      }

      var topForm = document.getElementById('legalCaseTopAjaxForm');
      var topCount = document.getElementById('legalCaseTopSelectedCount');
      var topToggle = document.getElementById('legalCaseTopEnabled');
      var topList = document.getElementById('legalCaseTopSelectedList');
      var topModalSelect = document.getElementById('legalCaseTopStatusSelect');
      var topAddBtn = document.getElementById('btnAddLegalCaseTopOption');

      function refreshLegalCaseTopCount() {
        if (!topList || !topCount) return;
        var count = topList.querySelectorAll('li[data-selected-id]').length;
        topCount.textContent = String(count);
        var emptyRow = document.getElementById('legalCaseTopNoOptions');
        if (count === 0) {
          if (!emptyRow) {
            var li = document.createElement('li');
            li.className = 'list-group-item px-0 py-2 text-muted';
            li.id = 'legalCaseTopNoOptions';
            li.textContent = 'No options added yet.';
            topList.appendChild(li);
          }
        } else if (emptyRow) {
          emptyRow.remove();
        }
      }

      function saveLegalCaseTopAjax() {
        if (!topForm) return;
        var fd = new FormData(topForm);
        var isChecked = document.getElementById('legalCaseTopEnabled')?.checked;
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
              return {
                ok: r.ok,
                data: d
              };
            });
          })
          .then(function(result) {
            if (topToggle) topToggle.disabled = false;
            if (topModalSelect) topModalSelect.disabled = false;
            if (topAddBtn) topAddBtn.disabled = false;
            if (!result.ok || !result.data || result.data.success !== true) {
              throw new Error((result.data && result.data.message) ? result.data.message : 'Could not save.');
            }
            refreshLegalCaseTopCount();
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
          saveLegalCaseTopAjax();
        });
      }
      if (topList) {
        if (typeof Sortable !== 'undefined') {
          new Sortable(topList, {
            handle: '.legal-case-top-drag-handle',
            draggable: 'li[data-selected-id]',
            animation: 150,
            ghostClass: 'table-warning',
            onEnd: function() {
              refreshLegalCaseTopCount();
              saveLegalCaseTopAjax();
            }
          });
        }
        topList.addEventListener('click', function(e) {
          var removeBtn = e.target.closest('.js-remove-legal-case-top-option');
          if (!removeBtn) return;
          var row = removeBtn.closest('li[data-selected-id]');
          if (row) {
            row.remove();
            refreshLegalCaseTopCount();
            saveLegalCaseTopAjax();
          }
        });
      }

      function initLegalCaseTopModalSelect2() {
        if (!(typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) || !topModalSelect) return;
        var $select = jQuery(topModalSelect);
        if ($select.hasClass('select2-hidden-accessible')) {
          $select.select2('destroy');
        }
        $select.select2({
          dropdownParent: jQuery('#addLegalCaseTopOptionModal'),
          placeholder: 'Select legal case status',
          allowClear: true,
          width: '100%'
        });
      }
      initLegalCaseTopModalSelect2();
      var addVisaExpenseTopModal = document.getElementById('addLegalCaseTopOptionModal');
      if (addVisaExpenseTopModal) {
        addVisaExpenseTopModal.addEventListener('shown.bs.modal', function() {
          initLegalCaseTopModalSelect2();
          if (typeof jQuery !== 'undefined') {
            jQuery(topModalSelect).select2('open');
          }
        });
      }
      if (topAddBtn && topModalSelect && topList) {
        topAddBtn.addEventListener('click', function() {
          var selectedId = parseInt(topModalSelect.value || '0', 10);
          if (!selectedId) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'warning',
                title: 'Select status',
                text: 'Please select a legal case status first.'
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
            '<span class="legal-case-top-drag-handle me-2 text-muted" title="Drag to sort" style="cursor: grab;"><i class="ti ti-grip-vertical"></i></span>' +
            '<i class="ti ti-point-filled me-1 text-muted"></i>' +
            '<span>' + name + '</span>' +
            '<input type="hidden" name="status_ids[]" value="' + selectedId + '">' +
            '</div>' +
            '<div class="d-flex align-items-center gap-1">' +
            '<button type="button" class="btn btn-xs btn-outline-danger js-remove-legal-case-top-option" data-remove-id="' + selectedId + '" title="Remove option"><i class="ti ti-trash"></i></button>' +
            '</div>';
          topList.appendChild(li);
          refreshLegalCaseTopCount();
          if (typeof jQuery !== 'undefined' && jQuery.fn && jQuery.fn.select2) {
            jQuery(topModalSelect).val('').trigger('change');
          } else {
            topModalSelect.value = '';
          }
          var modalEl = document.getElementById('addLegalCaseTopOptionModal');
          if (modalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            var modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();
          }
          saveLegalCaseTopAjax();
        });
      }
      refreshLegalCaseTopCount();
    });

    var legalCaseStatusTabBtn = document.querySelector('[data-bs-target="#tab-legal-case-status-management"]');
    if (legalCaseStatusTabBtn) {
      legalCaseStatusTabBtn.addEventListener('shown.bs.tab', function() {
        setTimeout(initLegalCaseStatusSortable, 50);
      });
    }
  }

  