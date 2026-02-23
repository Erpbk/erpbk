@extends($layout ?? 'layouts.app')

@section('title', 'Account Fields – Site Settings')

@push('third_party_stylesheets')
<style>
  .account-fields-table th {
    font-weight: 600;
    white-space: nowrap;
  }

  .account-fields-table .drag-handle {
    cursor: grab;
    color: #697a8d;
  }

  .account-fields-table .drag-handle:active {
    cursor: grabbing;
  }

  .account-fields-table tr.badge-soft-primary {
    background: rgba(105, 108, 255, 0.08);
  }

  #config-options-container .form-group,
  #addFieldConfigFields .form-group,
  #edit-config-options-fields .form-group {
    margin-bottom: 0.75rem;
  }

  #config-options-container label,
  #addFieldConfigFields label,
  #edit-config-options-fields label {
    font-weight: 500;
    font-size: 0.875rem;
  }

  .add-field-form .form-text {
    font-size: 0.8125rem;
  }
</style>
@endpush

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h4 class="card-title mb-0">Account Fields</h4>
          <p class="text-muted small mb-0 mt-1">Fixed fields come from the database and cannot be removed. You can add custom fields and change their display order.</p>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Fixed fields (read-only) --}}
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">Fixed Fields (from database)</h5>
        <p class="text-muted small mb-0 mt-1">These columns exist in the accounts table and cannot be deleted.</p>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover account-fields-table mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Label</th>
                <th>Data type</th>
                <th>Mandatory</th>
                <th class="text-end" style="width: 100px;">Actions</th>
              </tr>
            </thead>
            <tbody>
              @foreach($fixedFields as $idx => $f)
              <tr class="badge-soft-primary">
                <td>{{ $idx + 1 }}</td>
                <td>{{ $f['label'] }}</td>
                <td><span class="badge bg-label-secondary">{{ $f['data_type'] }}</span></td>
                <td>{{ $f['is_mandatory'] ? 'Yes' : 'No' }}</td>
                <td class="text-end"><span class="text-muted small">—</span></td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Custom fields (add / edit / delete / reorder) --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">Custom Fields</h5>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddNewField" data-bs-toggle="modal" data-bs-target="#addNewFieldModal">
          <i class="ti ti-plus me-1"></i> Add New Field
        </button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover account-fields-table mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 36px;"></th>
                <th>#</th>
                <th>Label</th>
                <th>Data type</th>
                <th>Mandatory</th>
                <th class="text-end" style="width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody id="customFieldsTbody">
              @include('settings.account_fields._custom_fields_tbody', ['customFields' => $customFields, 'dataTypes' => $dataTypes])
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Add New Field modal (AJAX, no full page reload) --}}
<div class="modal fade" id="addNewFieldModal" tabindex="-1" aria-labelledby="addNewFieldModalLabel" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title" id="addNewFieldModalLabel">Add New Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddNewField">
        @csrf
        <div class="modal-body pt-0">
          <div class="add-field-form">
            <div class="mb-3">
              <label class="form-label">Label Name <span class="text-danger">*</span></label>
              <input type="text" name="label" class="form-control" placeholder="e.g. Label Name" required>
            </div>
            <div class="mb-3">
              <label class="form-label d-flex align-items-center gap-1">
                Data Type <span class="text-danger">*</span>
                <i class="ti ti-help ti-sm text-muted" data-bs-toggle="tooltip" title="Choose the type of data this field will store."></i>
              </label>
              <select name="data_type" class="form-select" id="addFieldDataType" required>
                <option value="">Select type</option>
                @foreach($dataTypes as $typeKey => $typeMeta)
                <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
                @endforeach
              </select>
              <p class="form-text text-muted mb-0 mt-1">Remaining custom fields: <span id="remainingFieldsCount">{{ max(0, 50 - $customFields->count()) }}</span></p>
            </div>
            <div id="addFieldOptionsContainer" style="display: none;">
              <div class="mb-3" id="addFieldHelpTextWrap">
                <label class="form-label">Help Text</label>
                <input type="text" name="help_text" class="form-control" placeholder="Optional help for users">
                <p class="form-text text-muted small">Enter some text to help users understand the purpose of this custom field.</p>
              </div>
              <div class="mb-3" id="addFieldDataPrivacyWrap">
                <label class="form-label d-flex align-items-center gap-1">
                  Data Privacy
                  <i class="ti ti-help ti-sm text-muted" data-bs-toggle="tooltip" title="Mark if this field contains sensitive data."></i>
                </label>
                <div class="d-flex gap-4">
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_pii" value="1" class="form-check-input" id="addFieldPii">
                    <label class="form-check-label" for="addFieldPii">PII (Personally Identifiable Information)</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_ephi" value="1" class="form-check-input" id="addFieldEphi">
                    <label class="form-check-label" for="addFieldEphi">ePHI (Electronic Protected Health Information)</label>
                  </div>
                </div>
                <p class="form-text text-muted small">Data will be stored without encryption and will be visible to all users.</p>
              </div>
              <div class="mb-3" id="addFieldPreventDupWrap">
                <label class="form-label d-flex align-items-center gap-1">
                  Prevent Duplicate Values
                  <i class="ti ti-help ti-sm text-muted" data-bs-toggle="tooltip" title="When enabled, the same value cannot be used in two records."></i>
                </label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input type="radio" name="prevent_duplicate_values" value="1" class="form-check-input" id="addPreventDupYes">
                    <label class="form-check-label" for="addPreventDupYes">Yes</label>
                  </div>
                  <div class="form-check">
                    <input type="radio" name="prevent_duplicate_values" value="0" class="form-check-input" id="addPreventDupNo" checked>
                    <label class="form-check-label" for="addPreventDupNo">No</label>
                  </div>
                </div>
              </div>
              <div class="mb-3" id="addFieldInputFormatWrap" style="display: none;">
                <label class="form-label d-flex align-items-center gap-1">
                  Input Format
                  <i class="ti ti-help ti-sm text-muted" data-bs-toggle="tooltip" title="Optional format or pattern for this field."></i>
                </label>
                <input type="text" name="input_format" class="form-control" placeholder="e.g. email format">
              </div>
              <div class="mb-3" id="addFieldDefaultValueWrap" style="display: none;">
                <label class="form-label d-flex align-items-center gap-1">
                  Default Value
                  <i class="ti ti-help ti-sm text-muted" data-bs-toggle="tooltip" title="Pre-filled value when creating a new record."></i>
                </label>
                <div class="input-group">
                  <span class="input-group-text d-none" id="addFieldDefaultIcon"><i class="ti ti-mail"></i></span>
                  <input type="text" name="default_value" class="form-control" placeholder="Default value">
                </div>
              </div>
              <div class="mb-3" id="addFieldConfigOptionsWrap" style="display: none;">
                <label class="form-label small text-uppercase text-muted">Configuration options</label>
                <div id="addFieldConfigFields"></div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Is Mandatory</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input type="radio" name="is_mandatory" value="1" class="form-check-input" id="addMandatoryYes">
                  <label class="form-check-label" for="addMandatoryYes">Yes</label>
                </div>
                <div class="form-check">
                  <input type="radio" name="is_mandatory" value="0" class="form-check-input" id="addMandatoryNo" checked>
                  <label class="form-check-label" for="addMandatoryNo">No</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addFieldSubmitBtn">Save Field</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit modal --}}
<div class="modal fade" id="editCustomFieldModal" tabindex="-1" aria-labelledby="editCustomFieldModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editCustomFieldModalLabel">Edit custom field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditCustomField">
        <div class="modal-body">
          <input type="hidden" name="id" id="editFieldId">
          @csrf
          <div class="mb-3">
            <label class="form-label">Label name <span class="text-danger">*</span></label>
            <input type="text" name="label" id="editFieldLabel" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Data type <span class="text-danger">*</span></label>
            <select name="data_type" id="editFieldDataType" class="form-select" required>
              @foreach($dataTypes as $typeKey => $typeMeta)
              <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="editFieldMandatory">
              <label class="form-check-label" for="editFieldMandatory">Is mandatory</label>
            </div>
          </div>
          <div id="edit-config-options-container" style="display: none;">
            <label class="form-label small text-uppercase text-muted">Configuration options</label>
            <div id="edit-config-options-fields"></div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  (function() {
    var dataTypes = @json($dataTypes);
    var baseUrl = '{{ url("/") }}';
    var csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function buildConfigFields(containerId, dataType, existingConfig) {
      existingConfig = existingConfig || {};
      var typeMeta = dataTypes[dataType];
      var container = document.getElementById(containerId);
      if (!container) return;
      container.innerHTML = '';
      if (!typeMeta || !typeMeta.config || !typeMeta.config.length) return;
      typeMeta.config.forEach(function(c) {
        var key = c.key;
        var label = c.label;
        var type = c.type || 'text';
        var defaultVal = c.default;
        var placeholder = c.placeholder || '';
        var val = existingConfig[key] !== undefined && existingConfig[key] !== null ? existingConfig[key] : (defaultVal !== undefined ? defaultVal : '');
        if (type === 'checkbox') val = val ? '1' : '0';
        var wrap = document.createElement('div');
        wrap.className = 'form-group';
        var lbl = document.createElement('label');
        lbl.className = 'form-label';
        lbl.textContent = label;
        lbl.setAttribute('for', 'config_' + key);
        var input;
        if (type === 'textarea') {
          input = document.createElement('textarea');
          input.rows = 3;
          input.placeholder = placeholder;
          input.value = Array.isArray(val) ? val.join('\n') : (val || '');
        } else if (type === 'checkbox') {
          input = document.createElement('input');
          input.type = 'checkbox';
          input.value = '1';
          input.checked = val === '1' || val === true;
          input.name = 'config[' + key + ']';
          wrap.appendChild(lbl);
          wrap.appendChild(document.createElement('br'));
          wrap.appendChild(input);
          container.appendChild(wrap);
          return;
        } else {
          input = document.createElement('input');
          input.type = type;
          input.placeholder = placeholder;
          input.value = val;
        }
        input.name = 'config[' + key + ']';
        input.id = 'config_' + key;
        input.className = 'form-control form-control-sm';
        wrap.appendChild(lbl);
        wrap.appendChild(input);
        container.appendChild(wrap);
      });
    }

    function showConfigForType(isAddForm) {
      var select = document.getElementById(isAddForm ? 'customFieldDataType' : 'editFieldDataType');
      var container = document.getElementById(isAddForm ? 'config-options-container' : 'edit-config-options-container');
      var fieldsContainer = document.getElementById(isAddForm ? 'config-options-fields' : 'edit-config-options-fields');
      var dataType = select ? select.value : '';
      if (!dataType || !dataTypes[dataType] || !dataTypes[dataType].config || !dataTypes[dataType].config.length) {
        container.style.display = 'none';
        return;
      }
      var existing = {};
      if (!isAddForm) {
        var id = document.getElementById('editFieldId');
        if (id && id.value) {
          var row = document.querySelector('#customFieldsTbody tr[data-id="' + id.value + '"]');
          if (row) {
            var cfgBtn = row.querySelector('.edit-custom-field');
            if (cfgBtn && cfgBtn.dataset.config) try {
              existing = JSON.parse(cfgBtn.dataset.config);
            } catch (e) {}
          }
        }
      }
      buildConfigFields(fieldsContainer.id, dataType, existing);
      container.style.display = 'block';
    }

    document.getElementById('editFieldDataType').addEventListener('change', function() {
      showConfigForType(false);
    });

    function showAddFieldDynamicSections() {
      var dataType = document.getElementById('addFieldDataType').value;
      var optionsContainer = document.getElementById('addFieldOptionsContainer');
      if (!dataType) {
        if (optionsContainer) optionsContainer.style.display = 'none';
        return;
      }
      if (optionsContainer) optionsContainer.style.display = 'block';

      var typeMeta = dataTypes[dataType];
      var hasConfig = typeMeta && typeMeta.config && typeMeta.config.length;
      var configWrap = document.getElementById('addFieldConfigOptionsWrap');
      if (hasConfig) {
        buildConfigFields('addFieldConfigFields', dataType, {});
        configWrap.style.display = 'block';
      } else {
        configWrap.style.display = 'none';
      }

      var inputFormatWrap = document.getElementById('addFieldInputFormatWrap');
      var defaultWrap = document.getElementById('addFieldDefaultValueWrap');
      var defaultIcon = document.getElementById('addFieldDefaultIcon');
      var showInputFormat = ['email', 'url', 'text', 'date', 'datetime'].indexOf(dataType) !== -1;
      inputFormatWrap.style.display = showInputFormat ? 'block' : 'none';
      defaultWrap.style.display = 'block';
      if (defaultIcon) {
        defaultIcon.classList.toggle('d-none', dataType !== 'email');
        defaultIcon.classList.toggle('d-inline-flex', dataType === 'email');
      }
    }
    document.getElementById('addFieldDataType').addEventListener('change', showAddFieldDynamicSections);

    document.getElementById('addNewFieldModal').addEventListener('show.bs.modal', function() {
      document.getElementById('formAddNewField').reset();
      document.getElementById('addFieldConfigFields').innerHTML = '';
      document.getElementById('addFieldOptionsContainer').style.display = 'none';
      document.getElementById('addFieldConfigOptionsWrap').style.display = 'none';
      document.getElementById('addFieldInputFormatWrap').style.display = 'none';
      document.getElementById('addFieldDefaultValueWrap').style.display = 'none';
      document.getElementById('addMandatoryNo').checked = true;
      document.getElementById('addPreventDupNo').checked = true;
      showAddFieldDynamicSections();
      var count = document.querySelectorAll('#customFieldsTbody tr[data-id]').length;
      var remaining = document.getElementById('remainingFieldsCount');
      if (remaining) remaining.textContent = Math.max(0, 50 - count);
      if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        [].slice.call(document.querySelectorAll('#addNewFieldModal [data-bs-toggle="tooltip"]')).forEach(function(el) {
          new bootstrap.Tooltip(el);
        });
      }
    });

    document.getElementById('formAddNewField').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var submitBtn = document.getElementById('addFieldSubmitBtn');
      var fd = new FormData(form);
      fd.set('is_mandatory', form.querySelector('[name="is_mandatory"]:checked').value === '1' ? '1' : '0');
      fd.set('prevent_duplicate_values', form.querySelector('[name="prevent_duplicate_values"]:checked').value);
      var config = {};
      form.querySelectorAll('[name^="config["]').forEach(function(inp) {
        var name = inp.getAttribute('name');
        var m = name.match(/config\[([^\]]+)\]/);
        if (m) {
          var v = inp.type === 'checkbox' ? (inp.checked ? '1' : '0') : inp.value;
          if (inp.type === 'textarea' && inp.name.indexOf('options') !== -1) v = v.split('\n').map(function(s) {
            return s.trim();
          }).filter(Boolean);
          config[m[1]] = v;
        }
      });
      form.querySelectorAll('[name^="config["]').forEach(function(inp) {
        fd.delete(inp.name);
      });
      fd.append('config', JSON.stringify(config));
      if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.textContent = 'Saving…';
      }
      fetch('{{ route("settings-panel.account-fields.store") }}', {
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
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Field';
          }
          if (data.success) {
            var modal = bootstrap.Modal.getInstance(document.getElementById('addNewFieldModal'));
            if (modal) modal.hide();
            return fetch('{{ route("settings-panel.account-fields.table-body") }}', {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            });
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: (data.message || (data.errors && JSON.stringify(data.errors))) || 'Could not save.'
            });
          }
        })
        .then(function(r) {
          if (r && r.ok) return r.text();
          return null;
        })
        .then(function(html) {
          if (html) {
            document.getElementById('customFieldsTbody').innerHTML = html;
            var toast = Swal.mixin({
              toast: true,
              position: 'top-end',
              showConfirmButton: false,
              timer: 2500
            });
            toast.fire({
              icon: 'success',
              title: 'Field saved. List updated.'
            });
            initSortableOnTbody();
          }
        })
        .catch(function() {
          if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Save Field';
          }
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not save.'
          });
        });
    });

    document.getElementById('formEditCustomField').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var id = form.querySelector('[name="id"]').value;
      var fd = new FormData(form);
      fd.set('_method', 'PUT');
      fd.set('is_mandatory', form.querySelector('#editFieldMandatory').checked ? '1' : '0');
      var config = {};
      form.querySelectorAll('[name^="config["]').forEach(function(inp) {
        var name = inp.getAttribute('name');
        var m = name.match(/config\[([^\]]+)\]/);
        if (m) {
          var v = inp.type === 'checkbox' ? (inp.checked ? '1' : '0') : inp.value;
          if (inp.type === 'textarea' && inp.name.indexOf('options') !== -1) v = v.split('\n').map(function(s) {
            return s.trim();
          }).filter(Boolean);
          config[m[1]] = v;
        }
      });
      form.querySelectorAll('[name^="config["]').forEach(function(inp) {
        fd.delete(inp.name);
      });
      fd.append('config', JSON.stringify(config));
      fetch(baseUrl + '/settings-panel/account-fields/' + id, {
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
          if (data.success) {
            Swal.fire({
              icon: 'success',
              title: 'Updated',
              text: data.message || 'Custom field updated.'
            });
            window.location.reload();
          } else {
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: (data.message || data.errors) || 'Could not update.'
            });
          }
        })
        .catch(function() {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not update.'
          });
        });
    });

    document.addEventListener('click', function(e) {
      var btn = e.target.closest('.edit-custom-field');
      if (btn) {
        e.preventDefault();
        var id = btn.dataset.id,
          label = btn.dataset.label,
          type = btn.dataset.type,
          mandatory = btn.dataset.mandatory,
          configStr = btn.dataset.config || '{}';
        var config = {};
        try {
          config = JSON.parse(configStr);
        } catch (err) {}
        document.getElementById('editFieldId').value = id;
        document.getElementById('editFieldLabel').value = label;
        document.getElementById('editFieldDataType').value = type;
        document.getElementById('editFieldMandatory').checked = mandatory === '1';
        buildConfigFields('edit-config-options-fields', type, config);
        document.getElementById('edit-config-options-container').style.display = (dataTypes[type] && dataTypes[type].config && dataTypes[type].config.length) ? 'block' : 'none';
        new bootstrap.Modal(document.getElementById('editCustomFieldModal')).show();
      }
      var delBtn = e.target.closest('.delete-custom-field');
      if (delBtn) {
        e.preventDefault();
        var id = delBtn.dataset.id,
          label = delBtn.dataset.label;
        Swal.fire({
            title: 'Delete field?',
            text: 'Delete custom field "' + label + '"?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'Delete'
          })
          .then(function(result) {
            if (!result.isConfirmed) return;
            var fd = new FormData();
            fd.set('_method', 'DELETE');
            fd.set('_token', csrf);
            fetch(baseUrl + '/settings-panel/account-fields/' + id, {
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
                if (data.success) {
                  Swal.fire({
                    icon: 'success',
                    title: 'Deleted'
                  });
                  var row = document.querySelector('#customFieldsTbody tr[data-id="' + id + '"]');
                  if (row) row.remove();
                  var rows = document.querySelectorAll('#customFieldsTbody tr[data-id]');
                  if (rows.length === 0) {
                    document.getElementById('customFieldsTbody').innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No custom fields yet. Click "Add New Field" to create one.</td></tr>';
                  } else {
                    rows.forEach(function(row, i) {
                      var td = row.cells[1];
                      if (td) td.textContent = i + 1;
                    });
                  }
                } else Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: data.message || 'Could not delete.'
                });
              })
              .catch(function() {
                Swal.fire({
                  icon: 'error',
                  title: 'Error',
                  text: 'Could not delete.'
                });
              });
          });
      }
    });

    document.addEventListener('click', function(e) {
      if (!e.target.closest('.delete-custom-field')) return;
      var delBtn = e.target.closest('.delete-custom-field');
      var id = delBtn.dataset.id,
        label = delBtn.dataset.label;
      Swal.fire({
          title: 'Delete field?',
          text: 'Delete custom field "' + label + '"?',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonColor: '#d33',
          confirmButtonText: 'Delete'
        })
        .then(function(result) {
          if (!result.isConfirmed) return;
          var fd = new FormData();
          fd.set('_method', 'DELETE');
          fd.set('_token', csrf);
          fetch(baseUrl + '/settings-panel/account-fields/' + id, {
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
              if (data.success) {
                Swal.fire({
                  icon: 'success',
                  title: 'Deleted'
                });
                var row = document.querySelector('#customFieldsTbody tr[data-id="' + id + '"]');
                if (row) row.remove();
                var rows = document.querySelectorAll('#customFieldsTbody tr[data-id]');
                if (rows.length === 0) {
                  document.getElementById('customFieldsTbody').innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No custom fields yet. Click "Add New Field" to create one.</td></tr>';
                } else {
                  rows.forEach(function(row, i) {
                    var td = row.cells[1];
                    if (td) td.textContent = i + 1;
                  });
                }
              } else Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Could not delete.'
              });
            })
            .catch(function() {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not delete.'
              });
            });
        });
    });

    var sortableInstance = null;

    function initSortableOnTbody() {
      var tbody = document.getElementById('customFieldsTbody');
      if (sortableInstance) {
        sortableInstance.destroy();
        sortableInstance = null;
      }
      if (!tbody || !tbody.querySelectorAll('tr[data-id]').length) return;
      sortableInstance = new Sortable(tbody, {
        handle: '.drag-handle',
        animation: 150,
        ghostClass: 'table-warning',
        onEnd: function() {
          var order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(tr) {
            return tr.getAttribute('data-id');
          });
          fetch('{{ route("settings-panel.account-fields.reorder") }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json'
              },
              body: JSON.stringify({
                order: order
              })
            })
            .then(function(r) {
              return r.json();
            })
            .then(function(data) {
              if (data.success) {
                var toast = Swal.mixin({
                  toast: true,
                  position: 'top-end',
                  showConfirmButton: false,
                  timer: 2000
                });
                toast.fire({
                  icon: 'success',
                  title: 'Order saved.'
                });
                var idx = 1;
                tbody.querySelectorAll('tr[data-id]').forEach(function(row) {
                  var td = row.cells[1];
                  if (td) td.textContent = idx++;
                });
              }
            });
        }
      });
    }
    initSortableOnTbody();
  })();
</script>
@endpush