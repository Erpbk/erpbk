@if (!empty($riderDocumentFrontend['definitions']))
<div class="modal fade" id="riderDocumentReplaceModal" tabindex="-1" aria-labelledby="riderDocumentReplaceModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="riderDocumentReplaceModalLabel">Upload required documents</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="riderDocumentReplaceForm" enctype="multipart/form-data">
        <div class="modal-body">
          <div class="alert alert-warning py-2 px-3 mb-3" id="riderDocumentReplaceMessage"></div>
          <div id="riderDocumentReplaceFields"></div>
          <div class="invalid-feedback d-block" id="riderDocumentReplaceFormError" style="display: none;"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="riderDocumentReplaceSubmit">Upload &amp; Save</button>
        </div>
      </form>
    </div>
  </div>
</div>
<script>
  window.__riderDocumentReplacement = @json($riderDocumentFrontend);
  (function () {
    var cfg = window.__riderDocumentReplacement;
    if (!cfg || !cfg.fields) {
      return;
    }

    var ACCEPT = '.jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf';
    var activeKey = null;
    var savedOk = false;
    var reverting = false;
    var submitting = false;
    var modalEl = null;
    var modalInstance = null;

    function csrfToken() {
      var meta = document.querySelector('meta[name="csrf-token"]');
      return meta ? meta.getAttribute('content') : '';
    }

    function definitionByKey(key) {
      var list = cfg.definitions || [];
      for (var i = 0; i < list.length; i++) {
        if (list[i].key === key) {
          return list[i];
        }
      }
      return null;
    }

    function inputsForKey(key) {
      return document.querySelectorAll('[data-rider-document-key="' + key + '"]');
    }

    function originalValue(input) {
      if (input.dataset.riderDocumentOriginal === undefined) {
        input.dataset.riderDocumentOriginal = input.value || '';
      }
      return input.dataset.riderDocumentOriginal;
    }

    function normalizeForCompare(input, raw) {
      var value = (raw || '').trim();
      var role = input.getAttribute('data-rider-document-role');
      if (role === 'expiry') {
        if (value.indexOf('0000-00-00') === 0) {
          return '';
        }
        return value.slice(0, 10);
      }
      return value;
    }

    function valuesDiffer(input) {
      return normalizeForCompare(input, originalValue(input)) !== normalizeForCompare(input, input.value);
    }

    function keyIsDirty(key) {
      var dirty = false;
      inputsForKey(key).forEach(function (el) {
        if (valuesDiffer(el)) {
          dirty = true;
        }
      });
      return dirty;
    }

    function firstDirtyKey(form) {
      var seen = {};
      var scope = form || document;
      var dirty = null;
      scope.querySelectorAll('[data-rider-document-key]').forEach(function (el) {
        if (dirty) {
          return;
        }
        var key = el.getAttribute('data-rider-document-key');
        if (!key || seen[key]) {
          return;
        }
        seen[key] = true;
        if (keyIsDirty(key)) {
          dirty = key;
        }
      });
      return dirty;
    }

    function revertKey(key) {
      reverting = true;
      inputsForKey(key).forEach(function (el) {
        el.value = originalValue(el);
        el.classList.remove('is-invalid');
      });
      reverting = false;
    }

    function commitOriginals(key, saved) {
      inputsForKey(key).forEach(function (el) {
        var name = el.getAttribute('name');
        if (saved && Object.prototype.hasOwnProperty.call(saved, name)) {
          var next = saved[name];
          el.value = next == null ? '' : String(next);
          el.dataset.riderDocumentOriginal = el.value || '';
          return;
        }
        el.dataset.riderDocumentOriginal = el.value || '';
      });
    }

    function replaceBadge(expiryField, html) {
      if (!expiryField) {
        return;
      }
      var input = document.querySelector('[name="' + expiryField + '"]');
      if (!input) {
        return;
      }
      var group = input.closest('.form-group, .col-md-3, .col-3') || input.parentElement;
      if (!group) {
        return;
      }
      var existing = group.querySelectorAll('.rider-doc-expiry-badge');
      if (!html) {
        existing.forEach(function (el) { el.remove(); });
        return;
      }
      var wrap = document.createElement('span');
      wrap.innerHTML = String(html).trim();
      var node = wrap.firstElementChild;
      if (!node) {
        return;
      }
      if (existing.length) {
        existing[0].replaceWith(node);
        for (var i = 1; i < existing.length; i++) {
          existing[i].remove();
        }
        return;
      }
      var label = group.querySelector('label');
      var holder = document.createElement('span');
      holder.className = 'ms-1';
      holder.appendChild(node);
      if (label) {
        label.insertAdjacentElement('afterend', holder);
      } else {
        input.insertAdjacentElement('beforebegin', holder);
      }
    }

    function toast(message, type) {
      if (typeof toastr !== 'undefined') {
        if (type === 'success') {
          toastr.success(message);
        } else {
          toastr.error(message);
        }
        return;
      }
      alert(message);
    }

    function escapeHtml(value) {
      return String(value == null ? '' : value)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
    }

    function fieldLabel(def, side) {
      if (side === 'front') {
        return def.front_label || ((def.label || 'Document') + ' First Page');
      }
      if (side === 'back') {
        return def.back_label || ((def.label || 'Document') + ' Second Page');
      }
      return def.single_label || def.label || 'Document';
    }

    function fileInputHtml(name, label) {
      return ''
        + '<div class="mb-3">'
        +   '<label class="form-label required fw-bold">' + escapeHtml(label) + '</label>'
        +   '<input type="file" name="' + name + '" class="form-control rider-document-file" accept="' + ACCEPT + '" required>'
        +   '<div class="invalid-feedback rider-document-upload-error"></div>'
        +   '<div class="form-text">JPG, PNG, PDF, DOC or DOCX. Max 20 MB.</div>'
        + '</div>';
    }

    function ensureModal() {
      modalEl = document.getElementById('riderDocumentReplaceModal');
      if (!modalEl) {
        return null;
      }
      if (window.bootstrap && bootstrap.Modal) {
        modalInstance = bootstrap.Modal.getOrCreateInstance(modalEl, {
          backdrop: 'static',
          keyboard: false
        });
      }
      return modalEl;
    }

    function showModal() {
      ensureModal();
      if (modalInstance) {
        modalInstance.show();
        return;
      }
      if (modalEl) {
        modalEl.classList.add('show');
        modalEl.style.display = 'block';
        modalEl.removeAttribute('aria-hidden');
      }
    }

    function hideModal() {
      if (modalInstance) {
        modalInstance.hide();
        return;
      }
      if (modalEl) {
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
      }
    }

    function clearFileErrors() {
      var box = document.getElementById('riderDocumentReplaceFormError');
      if (box) {
        box.textContent = '';
        box.style.display = 'none';
      }
      document.querySelectorAll('#riderDocumentReplaceFields .rider-document-file').forEach(function (file) {
        file.classList.remove('is-invalid');
        var err = file.parentElement ? file.parentElement.querySelector('.rider-document-upload-error') : null;
        if (err) {
          err.textContent = '';
        }
      });
    }

    function showFileErrors(errors) {
      var first = '';
      Object.keys(errors || {}).forEach(function (key) {
        var messages = errors[key] || [];
        var text = messages[0] || '';
        if (!first && text) {
          first = text;
        }
        var name = key.indexOf('.') === -1
          ? key
          : key.split('.')[0] + key.split('.').slice(1).map(function (part) {
            return '[' + part + ']';
          }).join('');
        var el = document.querySelector('#riderDocumentReplaceFields [name="' + name + '"]');
        if (!el) {
          return;
        }
        el.classList.add('is-invalid');
        var err = el.parentElement ? el.parentElement.querySelector('.rider-document-upload-error') : null;
        if (err) {
          err.textContent = text;
          err.style.display = 'block';
        }
      });
      if (first) {
        var box = document.getElementById('riderDocumentReplaceFormError');
        if (box) {
          box.textContent = first;
          box.style.display = 'block';
        }
      }
    }

    function populateModal(key) {
      var def = definitionByKey(key);
      if (!def) {
        return false;
      }
      var title = document.getElementById('riderDocumentReplaceModalLabel');
      var message = document.getElementById('riderDocumentReplaceMessage');
      var fields = document.getElementById('riderDocumentReplaceFields');
      if (title) {
        title.textContent = 'Upload required documents — ' + (def.label || 'Document');
      }
      if (message) {
        message.textContent = cfg.message || 'Please upload the required document(s) before the change can be saved.';
      }
      if (fields) {
        if (def.type === 'dual') {
          fields.innerHTML = fileInputHtml('document_files[' + key + '][front]', fieldLabel(def, 'front'))
            + fileInputHtml('document_files[' + key + '][back]', fieldLabel(def, 'back'));
        } else {
          fields.innerHTML = fileInputHtml('document_files[' + key + ']', fieldLabel(def));
        }
      }
      clearFileErrors();
      return true;
    }

    function openForKey(key) {
      if (!key || reverting || submitting) {
        return false;
      }
      if (activeKey === key) {
        showModal();
        return true;
      }
      if (activeKey && activeKey !== key) {
        return false;
      }
      if (!cfg.replaceUrl) {
        toast('Document upload is not available.', 'error');
        return false;
      }
      if (!populateModal(key)) {
        return false;
      }
      savedOk = false;
      activeKey = key;
      showModal();
      return true;
    }

    function currentTypeValues(key) {
      var values = {};
      inputsForKey(key).forEach(function (el) {
        var name = el.getAttribute('name');
        if (name) {
          values[name] = el.value;
        }
      });
      return values;
    }

    function filesArePresent(def) {
      var missing = false;
      document.querySelectorAll('#riderDocumentReplaceFields .rider-document-file').forEach(function (file) {
        if (!(file.files && file.files.length)) {
          missing = true;
          file.classList.add('is-invalid');
          var err = file.parentElement ? file.parentElement.querySelector('.rider-document-upload-error') : null;
          if (err) {
            err.textContent = 'This document is required.';
            err.style.display = 'block';
          }
        }
      });
      if (missing && def && def.type === 'dual') {
        toast('Please upload both ' + fieldLabel(def, 'front') + ' and ' + fieldLabel(def, 'back') + '.', 'error');
      } else if (missing) {
        toast(cfg.message, 'error');
      }
      return !missing;
    }

    function setSubmitting(on) {
      submitting = !!on;
      var btn = document.getElementById('riderDocumentReplaceSubmit');
      if (!btn) {
        return;
      }
      btn.disabled = submitting;
      btn.innerHTML = submitting
        ? '<i class="fa fa-spinner fa-spin me-2"></i>Saving...'
        : 'Upload & Save';
    }

    function submitModal(e) {
      if (e) {
        e.preventDefault();
      }
      var key = activeKey;
      var def = definitionByKey(key);
      if (!key || !def || submitting) {
        return;
      }
      clearFileErrors();
      if (!filesArePresent(def)) {
        return;
      }

      var fd = new FormData();
      fd.append('_token', csrfToken());
      fd.append('document_key', key);
      var values = currentTypeValues(key);
      Object.keys(values).forEach(function (name) {
        fd.append(name, values[name] == null ? '' : values[name]);
      });
      document.querySelectorAll('#riderDocumentReplaceFields .rider-document-file').forEach(function (file) {
        if (file.files && file.files[0]) {
          fd.append(file.name, file.files[0]);
        }
      });

      setSubmitting(true);
      fetch(cfg.replaceUrl, {
        method: 'POST',
        body: fd,
        headers: {
          'X-CSRF-TOKEN': csrfToken(),
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        },
        credentials: 'same-origin'
      }).then(function (response) {
        return response.json().then(function (payload) {
          return { ok: response.ok, status: response.status, payload: payload || {} };
        }).catch(function () {
          return { ok: response.ok, status: response.status, payload: {} };
        });
      }).then(function (result) {
        var payload = result.payload || {};
        if (!result.ok || payload.success === false) {
          if (payload.errors) {
            showFileErrors(payload.errors);
          }
          toast(payload.message || cfg.message, 'error');
          return;
        }
        savedOk = true;
        if (!cfg.existing) {
          cfg.existing = {};
        }
        cfg.existing[key] = true;
        commitOriginals(key, payload.saved || {});
        replaceBadge(payload.expiry_field, payload.badge_html || '');
        toast(payload.message || 'Document information updated successfully.', 'success');
        hideModal();
      }).catch(function () {
        toast('The document could not be saved. Please try again.', 'error');
      }).finally(function () {
        setSubmitting(false);
      });
    }

    function onModalHidden() {
      var key = activeKey;
      activeKey = null;
      if (!savedOk && key) {
        revertKey(key);
      }
      savedOk = false;
      var form = document.getElementById('riderDocumentReplaceForm');
      if (form) {
        form.reset();
      }
    }

    function bind(input) {
      originalValue(input);
      input.addEventListener('change', function () {
        if (reverting) {
          return;
        }
        var key = input.getAttribute('data-rider-document-key');
        if (!key) {
          return;
        }
        if (!keyIsDirty(key)) {
          return;
        }
        openForKey(key);
      });
    }

    function tagKnownFields() {
      Object.keys(cfg.fields).forEach(function (name) {
        document.querySelectorAll('[name="' + name + '"]').forEach(function (el) {
          el.setAttribute('data-rider-document-key', cfg.fields[name].key);
          el.setAttribute('data-rider-document-role', cfg.fields[name].role);
          bind(el);
        });
      });
    }

    window.riderDocumentReplacementValidate = function (form) {
      var key = firstDirtyKey(form);
      if (!key) {
        return true;
      }
      toast(cfg.message, 'error');
      openForKey(key);
      return false;
    };

    window.riderDocumentReplacementShowErrors = function (errors) {
      var opened = false;
      Object.keys(errors || {}).forEach(function (key) {
        if (key.indexOf('document_files.') !== 0) {
          return;
        }
        var parts = key.split('.');
        if (parts[1] && !opened) {
          opened = true;
          openForKey(parts[1]);
        }
      });
      if (opened) {
        showFileErrors(errors);
      }
    };

    ensureModal();
    var form = document.getElementById('riderDocumentReplaceForm');
    if (form) {
      form.addEventListener('submit', submitModal);
    }
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', onModalHidden);
    }

    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', tagKnownFields);
    } else {
      tagKnownFields();
    }
  })();
</script>
@endif
