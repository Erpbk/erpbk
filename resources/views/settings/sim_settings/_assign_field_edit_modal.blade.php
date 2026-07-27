<div class="modal fade" id="editSimAssignFieldModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" id="editSimAssignFieldModalTitle">Edit assign field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <form id="formEditSimAssignField" action="#" method="POST">
        @csrf
        @method('PUT')

        <div class="modal-body pt-0">
          <div id="editAssignBuiltinSection" class="row g-3 align-items-end">
            <div class="col-md-4">
              <label class="form-label">Field key</label>
              <input type="text" id="editAssignFieldKey" class="form-control" readonly>
            </div>
            <div class="col-md-4">
              <label class="form-label">Display label</label>
              <input type="text" name="display_label" id="editAssignDisplayLabel" class="form-control" maxlength="255">
            </div>
            <div class="col-md-4">
              <label class="form-label">Input type</label>
              <select name="input_type" id="editAssignInputType" class="form-select">
                @foreach(['text','textarea','number','decimal','date','datetime','dropdown','checkbox','select'] as $t)
                <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                @endforeach
              </select>
            </div>
            <input type="hidden" name="is_visible" id="editAssignIsVisibleHidden" value="1">
            <input type="hidden" name="is_required" id="editAssignIsRequiredHidden" value="0">
            <div class="col-md-3">
              <div class="form-check mt-4">
                <input type="hidden" name="show_on_active" value="0">
                <input type="checkbox" name="show_on_active" value="1" id="editAssignShowActive" class="form-check-input">
                <label class="form-check-label" for="editAssignShowActive">Assign modal</label>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-check mt-4">
                <input type="hidden" name="show_on_change" value="0">
                <input type="checkbox" name="show_on_change" value="1" id="editAssignShowChange" class="form-check-input">
                <label class="form-check-label" for="editAssignShowChange">Return modal</label>
              </div>
            </div>
            <div class="col-md-12" id="editAssignBuiltinOptionsWrap">
              <label class="form-label">Dropdown options (one per line)</label>
              <input type="hidden" name="input_config_options" id="editAssignBuiltinConfigOptionsHidden" value="">
              <div id="editAssignBuiltinOptionsRows" class="d-flex flex-column gap-2"></div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editAssignBuiltinOptionRowBtn">Add Option</button>
            </div>
          </div>

          <div id="editAssignCustomSection" class="row g-3 align-items-end" style="display: none;">
            <div class="col-md-6">
              <label class="form-label">Label</label>
              <input type="text" name="label" id="editAssignCustomLabel" class="form-control" maxlength="255" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Help text</label>
              <input type="text" name="help_text" id="editAssignCustomHelpText" class="form-control" maxlength="1000">
            </div>
            <div class="col-md-4">
              <label class="form-label">Data type</label>
              <select name="data_type" id="editAssignCustomDataType" class="form-select">
                @foreach($dataTypes as $typeKey => $spec)
                <option value="{{ $typeKey }}">{{ $spec['label'] }}</option>
                @endforeach
              </select>
            </div>
                      <input type="hidden" name="is_mandatory" value="0">
            <div class="col-md-4">
              <label class="form-label d-block">Show on modals</label>
              <div class="form-check">
                <input type="hidden" name="show_on_active" value="0">
                <input type="checkbox" name="show_on_active" value="1" id="editAssignCustomShowActive" class="form-check-input">
                <label class="form-check-label" for="editAssignCustomShowActive">Assign modal</label>
              </div>
              <div class="form-check">
                <input type="hidden" name="show_on_change" value="0">
                <input type="checkbox" name="show_on_change" value="1" id="editAssignCustomShowChange" class="form-check-input">
                <label class="form-check-label" for="editAssignCustomShowChange">Return modal</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Default value</label>
              <input type="text" name="default_value" id="editAssignCustomDefaultValue" class="form-control" maxlength="500">
            </div>
            <div class="col-md-6">
              <label class="form-label">Input format</label>
              <input type="text" name="input_format" id="editAssignCustomInputFormat" class="form-control" maxlength="100">
            </div>
            <input type="hidden" name="is_visible" id="editAssignCustomIsVisibleHidden" value="1">
            <input type="hidden" name="is_required" id="editAssignCustomIsRequiredHidden" value="0">
            <div class="col-md-12" id="editAssignCustomOptionsWrap">
              <label class="form-label">Dropdown options (one per line)</label>
              <input type="hidden" name="config_options" id="editAssignCustomConfigOptionsHidden" value="">
              <div id="editAssignCustomOptionsRows" class="d-flex flex-column gap-2"></div>
              <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editAssignCustomOptionRowBtn">Add Option</button>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editSimAssignFieldSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
(function() {
  function parsePayload(raw) {
    if (!raw) return null;
    try {
      return JSON.parse(raw);
    } catch (e) {
      return null;
    }
  }

  function assignTypeShowsOptions(type) {
    return String(type || '').toLowerCase() === 'dropdown';
  }

  function setSelectValue(selectEl, value) {
    if (!selectEl) return;
    var v = String(value || '').toLowerCase();
    if (!v) v = 'text';
    for (var i = 0; i < selectEl.options.length; i++) {
      if (String(selectEl.options[i].value).toLowerCase() === v) {
        selectEl.value = selectEl.options[i].value;
        return;
      }
    }
    if (v) {
      var opt = document.createElement('option');
      opt.value = v;
      opt.textContent = v.charAt(0).toUpperCase() + v.slice(1);
      selectEl.appendChild(opt);
      selectEl.value = v;
    }
  }

  function toggleAssignBuiltinOptionsWrap() {
    var wrap = document.getElementById('editAssignBuiltinOptionsWrap');
    var typeEl = document.getElementById('editAssignInputType');
    if (!wrap || !typeEl) return;
    wrap.style.display = assignTypeShowsOptions(typeEl.value) ? '' : 'none';
  }

  function toggleAssignCustomOptionsWrap() {
    var wrap = document.getElementById('editAssignCustomOptionsWrap');
    var typeEl = document.getElementById('editAssignCustomDataType');
    if (!wrap || !typeEl) return;
    wrap.style.display = assignTypeShowsOptions(typeEl.value) ? '' : 'none';
  }

  window.SimToggleAssignBuiltinOptions = toggleAssignBuiltinOptionsWrap;
  window.SimToggleAssignCustomOptions = toggleAssignCustomOptionsWrap;

  function setSectionEnabled(section, enabled) {
    if (!section) return;
    section.style.display = enabled ? '' : 'none';
    section.querySelectorAll('input, select, textarea, button').forEach(function(el) {
      if (el.type === 'hidden') return;
      if (enabled) {
        el.disabled = false;
        el.removeAttribute('disabled');
      } else {
        el.disabled = true;
      }
    });
  }

  function renderOptions(rowsEl, hiddenEl, rawOptions) {
    if (typeof SimRenderOptionRows === 'function') {
      SimRenderOptionRows(rowsEl, hiddenEl, rawOptions || '');
      return;
    }
    if (!rowsEl || !hiddenEl) return;
    rowsEl.innerHTML = '';
    var lines = String(rawOptions || '').split(/\r?\n/).map(function(s) { return s.trim(); }).filter(Boolean);
    if (!lines.length) lines = [''];
    lines.forEach(function(line) {
      if (typeof SimCreateOptionRow === 'function') {
        SimCreateOptionRow(rowsEl, hiddenEl, line);
      }
    });
  }

  function populateAssignEditModal(triggerEl) {
    var btn = triggerEl && triggerEl.closest ? triggerEl.closest('.btn-edit-Sim-assign-field') : null;
    if (!btn) return;

    var payload = parsePayload(btn.getAttribute('data-assign-payload'));
    if (!payload) return;

    var form = document.getElementById('formEditSimAssignField');
    if (form && payload.update_url) {
      form.action = payload.update_url;
    }

    var isCustom = payload.kind === 'custom';
    var builtinSec = document.getElementById('editAssignBuiltinSection');
    var customSec = document.getElementById('editAssignCustomSection');
    var titleEl = document.getElementById('editSimAssignFieldModalTitle');
    var submitBtn = document.getElementById('editSimAssignFieldSubmitBtn');

    setSectionEnabled(builtinSec, !isCustom);
    setSectionEnabled(customSec, isCustom);

    if (titleEl) {
      titleEl.textContent = isCustom ? 'Edit assign custom field' : 'Edit assign field';
    }
    if (submitBtn) {
      submitBtn.textContent = isCustom ? 'Save custom field' : 'Save';
    }

    if (!isCustom) {
      var fieldKeyEl = document.getElementById('editAssignFieldKey');
      if (fieldKeyEl) fieldKeyEl.value = payload.field_key || '';
      document.getElementById('editAssignDisplayLabel').value = payload.display_label || '';
      var visHidden = document.getElementById('editAssignIsVisibleHidden');
      if (visHidden) visHidden.value = payload.is_visible ? '1' : '1';
      var reqHidden = document.getElementById('editAssignIsRequiredHidden');
      if (reqHidden) reqHidden.value = payload.is_required ? '1' : '0';
      document.getElementById('editAssignShowActive').checked = !!payload.show_on_active;
      document.getElementById('editAssignShowChange').checked = !!payload.show_on_change;
      setSelectValue(document.getElementById('editAssignInputType'), payload.input_type || 'text');
      toggleAssignBuiltinOptionsWrap();
      renderOptions(
        document.getElementById('editAssignBuiltinOptionsRows'),
        document.getElementById('editAssignBuiltinConfigOptionsHidden'),
        payload.input_config_options || ''
      );
    } else {
      document.getElementById('editAssignCustomLabel').value = payload.label || '';
      document.getElementById('editAssignCustomHelpText').value = payload.help_text || '';
      setSelectValue(document.getElementById('editAssignCustomDataType'), payload.data_type || 'text');
      document.getElementById('editAssignCustomDefaultValue').value = payload.default_value || '';
      document.getElementById('editAssignCustomInputFormat').value = payload.input_format || '';
      var custVisHidden = document.getElementById('editAssignCustomIsVisibleHidden');
      if (custVisHidden) custVisHidden.value = payload.is_visible ? '1' : '1';
      var custReqHidden = document.getElementById('editAssignCustomIsRequiredHidden');
      if (custReqHidden) custReqHidden.value = payload.is_required ? '1' : '0';
      document.getElementById('editAssignCustomShowActive').checked = !!payload.show_on_active;
      document.getElementById('editAssignCustomShowChange').checked = !!payload.show_on_change;
      toggleAssignCustomOptionsWrap();
      renderOptions(
        document.getElementById('editAssignCustomOptionsRows'),
        document.getElementById('editAssignCustomConfigOptionsHidden'),
        payload.config_options || ''
      );
    }
  }

  var editAssignInputType = document.getElementById('editAssignInputType');
  if (editAssignInputType) {
    editAssignInputType.addEventListener('change', toggleAssignBuiltinOptionsWrap);
  }
  var editAssignCustomDataType = document.getElementById('editAssignCustomDataType');
  if (editAssignCustomDataType) {
    editAssignCustomDataType.addEventListener('change', toggleAssignCustomOptionsWrap);
  }

  var editModalEl = document.getElementById('editSimAssignFieldModal');
  if (editModalEl) {
    editModalEl.addEventListener('show.bs.modal', function(e) {
      populateAssignEditModal(e.relatedTarget);
    });
  }

  var editForm = document.getElementById('formEditSimAssignField');
  if (editForm) {
    editForm.addEventListener('submit', function() {
      var customVisible = document.getElementById('editAssignCustomSection') &&
        document.getElementById('editAssignCustomSection').style.display !== 'none';
      if (customVisible) {
        if (typeof SimSyncOptionsToHidden === 'function') {
          SimSyncOptionsToHidden(
            document.getElementById('editAssignCustomOptionsRows'),
            document.getElementById('editAssignCustomConfigOptionsHidden')
          );
        }
      } else if (typeof SimSyncOptionsToHidden === 'function') {
        SimSyncOptionsToHidden(
          document.getElementById('editAssignBuiltinOptionsRows'),
          document.getElementById('editAssignBuiltinConfigOptionsHidden')
        );
      }
    });
  }
})();
</script>
