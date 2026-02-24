@extends($layout ?? 'layouts.app')

@section('title', 'Voucher Settings – Site Settings')

@push('third_party_stylesheets')
<style>
  .voucher-settings-table th { font-weight: 600; white-space: nowrap; }
  .voucher-settings-table .drag-handle { cursor: grab; color: #697a8d; }
  .voucher-settings-table .drag-handle:active { cursor: grabbing; }
  .voucher-settings-table tr.badge-soft-primary { background: rgba(105, 108, 255, 0.08); }
  #config-options-container .form-group, #addFieldConfigFields .form-group, #edit-config-options-fields .form-group { margin-bottom: 0.75rem; }
  #config-options-container label, #addFieldConfigFields label, #edit-config-options-fields label { font-weight: 500; font-size: 0.875rem; }
  .add-field-form .form-text { font-size: 0.8125rem; }
</style>
@endpush

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h4 class="card-title mb-0">Voucher Settings</h4>
          <p class="text-muted small mb-0 mt-1">Manage voucher types (shown when creating a voucher) and custom fields for vouchers.</p>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Voucher Types --}}
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">Voucher Types</h5>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddVoucherType" data-bs-toggle="modal" data-bs-target="#addVoucherTypeModal">
          <i class="ti ti-plus me-1"></i> Add Voucher Type
        </button>
      </div>
      <div class="card-body p-0">
        <div class="table-responsive">
          <table class="table table-hover voucher-settings-table mb-0">
            <thead class="table-light">
              <tr>
                <th>#</th>
                <th>Code</th>
                <th>Label</th>
                <th>Status</th>
                <th class="text-end" style="width: 120px;">Actions</th>
              </tr>
            </thead>
            <tbody id="voucherTypesTbody">
              @include('settings.voucher_settings._voucher_types_tbody', ['voucherTypes' => $voucherTypes])
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Voucher Custom Fields --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <h5 class="card-title mb-0">Voucher Custom Fields</h5>
        <button type="button" class="btn btn-primary btn-sm" id="btnAddNewVoucherField" data-bs-toggle="modal" data-bs-target="#addVoucherFieldModal">
          <i class="ti ti-plus me-1"></i> Add New Field
        </button>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-hover voucher-settings-table mb-0">
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
            <tbody id="voucherCustomFieldsTbody">
              @include('settings.voucher_settings._voucher_custom_fields_tbody', ['customFields' => $customFields, 'dataTypes' => $dataTypes])
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Add Voucher Type modal --}}
<div class="modal fade" id="addVoucherTypeModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Voucher Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddVoucherType">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Code <span class="text-danger">*</span></label>
            <input type="text" name="code" class="form-control" placeholder="e.g. JV" required maxlength="20">
            <p class="form-text text-muted small">Short code used in the system (e.g. JV for Journal).</p>
          </div>
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" class="form-control" placeholder="e.g. Journal Voucher" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addVoucherTypeSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Voucher Type modal --}}
<div class="modal fade" id="editVoucherTypeModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Voucher Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditVoucherType">
        <input type="hidden" name="id" id="editVoucherTypeId">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Code <span class="text-danger">*</span></label>
            <input type="text" name="code" id="editVoucherTypeCode" class="form-control" required maxlength="20">
          </div>
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" id="editVoucherTypeLabel" class="form-control" required>
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="is_active" value="1" class="form-check-input" id="editVoucherTypeActive">
              <label class="form-check-label" for="editVoucherTypeActive">Active (show when creating voucher)</label>
            </div>
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

{{-- Add Voucher Field modal (same structure as account fields) --}}
<div class="modal fade" id="addVoucherFieldModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add New Voucher Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddVoucherField">
        @csrf
        <div class="modal-body pt-0">
          <div class="add-field-form">
            <div class="mb-3">
              <label class="form-label">Label Name <span class="text-danger">*</span></label>
              <input type="text" name="label" class="form-control" placeholder="e.g. Label Name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Data Type <span class="text-danger">*</span></label>
              <select name="data_type" class="form-select" id="addVoucherFieldDataType" required>
                <option value="">Select type</option>
                @foreach($dataTypes as $typeKey => $typeMeta)
                <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
                @endforeach
              </select>
              <p class="form-text text-muted mb-0 mt-1">Remaining custom fields: <span id="remainingVoucherFieldsCount">{{ max(0, 50 - $customFields->count()) }}</span></p>
            </div>
            <div id="addVoucherFieldOptionsContainer" style="display: none;">
              <div class="mb-3" id="addVoucherFieldHelpTextWrap">
                <label class="form-label">Help Text</label>
                <input type="text" name="help_text" class="form-control" placeholder="Optional help for users">
              </div>
              <div class="mb-3" id="addVoucherFieldDataPrivacyWrap">
                <label class="form-label">Data Privacy</label>
                <div class="d-flex gap-4">
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_pii" value="1" class="form-check-input" id="addVoucherFieldPii">
                    <label class="form-check-label" for="addVoucherFieldPii">PII</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_ephi" value="1" class="form-check-input" id="addVoucherFieldEphi">
                    <label class="form-check-label" for="addVoucherFieldEphi">ePHI</label>
                  </div>
                </div>
              </div>
              <div class="mb-3" id="addVoucherFieldPreventDupWrap">
                <label class="form-label">Prevent Duplicate Values</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input type="radio" name="prevent_duplicate_values" value="1" class="form-check-input" id="addVoucherPreventDupYes">
                    <label class="form-check-label" for="addVoucherPreventDupYes">Yes</label>
                  </div>
                  <div class="form-check">
                    <input type="radio" name="prevent_duplicate_values" value="0" class="form-check-input" id="addVoucherPreventDupNo" checked>
                    <label class="form-check-label" for="addVoucherPreventDupNo">No</label>
                  </div>
                </div>
              </div>
              <div class="mb-3" id="addVoucherFieldDefaultValueWrap">
                <label class="form-label">Default Value</label>
                <input type="text" name="default_value" class="form-control" placeholder="Default value">
              </div>
              <div class="mb-3" id="addVoucherFieldConfigOptionsWrap" style="display: none;">
                <label class="form-label small text-uppercase text-muted">Configuration options</label>
                <div id="addVoucherFieldConfigFields"></div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Is Mandatory</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input type="radio" name="is_mandatory" value="1" class="form-check-input" id="addVoucherMandatoryYes">
                  <label class="form-check-label" for="addVoucherMandatoryYes">Yes</label>
                </div>
                <div class="form-check">
                  <input type="radio" name="is_mandatory" value="0" class="form-check-input" id="addVoucherMandatoryNo" checked>
                  <label class="form-check-label" for="addVoucherMandatoryNo">No</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addVoucherFieldSubmitBtn">Save Field</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Voucher Field modal --}}
<div class="modal fade" id="editVoucherFieldModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit voucher custom field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditVoucherField">
        <div class="modal-body">
          <input type="hidden" name="id" id="editVoucherFieldId">
          @csrf
          <div class="mb-3">
            <label class="form-label">Label name <span class="text-danger">*</span></label>
            <input type="text" name="label" id="editVoucherFieldLabel" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Data type <span class="text-danger">*</span></label>
            <select name="data_type" id="editVoucherFieldDataType" class="form-select" required>
              @foreach($dataTypes as $typeKey => $typeMeta)
              <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <div class="form-check">
              <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="editVoucherFieldMandatory">
              <label class="form-check-label" for="editVoucherFieldMandatory">Is mandatory</label>
            </div>
          </div>
          <div id="edit-voucher-config-options-container" style="display: none;">
            <label class="form-label small text-uppercase text-muted">Configuration options</label>
            <div id="edit-voucher-config-options-fields"></div>
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
  var voucherTypesUrl = '{{ route("settings-panel.voucher-settings.types-table-body") }}';
  var voucherFieldsUrl = '{{ route("settings-panel.voucher-settings.fields-table-body") }}';
  var storeTypeUrl = '{{ route("settings-panel.voucher-settings.store-type") }}';
  var storeFieldUrl = '{{ route("settings-panel.voucher-settings.store-field") }}';
  var reorderTypesUrl = '{{ route("settings-panel.voucher-settings.reorder-types") }}';
  var reorderFieldsUrl = '{{ route("settings-panel.voucher-settings.reorder-fields") }}';

  function refreshVoucherTypesTbody() {
    fetch(voucherTypesUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.text(); })
      .then(function(html) {
        var tbody = document.getElementById('voucherTypesTbody');
        if (tbody) tbody.innerHTML = html;
      });
  }

  document.getElementById('formAddVoucherType').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var btn = document.getElementById('addVoucherTypeSubmitBtn');
    var fd = new FormData(form);
    if (btn) { btn.disabled = true; btn.textContent = 'Saving…'; }
    fetch(storeTypeUrl, {
      method: 'POST',
      body: fd,
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('addVoucherTypeModal')).hide();
        refreshVoucherTypesTbody();
        Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, icon: 'success', title: 'Voucher type added.' });
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: (data.message || (data.errors && JSON.stringify(data.errors))) || 'Could not save.' });
      }
    })
    .catch(function() {
      if (btn) { btn.disabled = false; btn.textContent = 'Save'; }
      Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save.' });
    });
  });

  document.getElementById('formEditVoucherType').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var id = form.querySelector('[name="id"]').value;
    var fd = new FormData(form);
    fd.set('_method', 'PUT');
    fd.set('is_active', form.querySelector('#editVoucherTypeActive').checked ? '1' : '0');
    fetch(baseUrl + '/settings-panel/voucher-settings/types/' + id, {
      method: 'POST',
      body: fd,
      headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('editVoucherTypeModal')).hide();
        refreshVoucherTypesTbody();
        Swal.fire({ icon: 'success', title: 'Updated', text: data.message || 'Voucher type updated.' });
      } else {
        Swal.fire({ icon: 'error', title: 'Error', text: (data.message || data.errors) || 'Could not update.' });
      }
    })
    .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Could not update.' }); });
  });

  document.addEventListener('click', function(e) {
    var editBtn = e.target.closest('.edit-voucher-type');
    if (editBtn) {
      e.preventDefault();
      document.getElementById('editVoucherTypeId').value = editBtn.dataset.id;
      document.getElementById('editVoucherTypeCode').value = editBtn.dataset.code;
      document.getElementById('editVoucherTypeLabel').value = editBtn.dataset.label;
      document.getElementById('editVoucherTypeActive').checked = editBtn.dataset.active === '1';
      new bootstrap.Modal(document.getElementById('editVoucherTypeModal')).show();
    }
    var delBtn = e.target.closest('.delete-voucher-type');
    if (delBtn) {
      e.preventDefault();
      var id = delBtn.dataset.id, label = delBtn.dataset.label;
      Swal.fire({ title: 'Delete voucher type?', text: 'Delete "' + label + '"?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete' })
        .then(function(result) {
          if (!result.isConfirmed) return;
          var fd = new FormData();
          fd.set('_method', 'DELETE');
          fd.set('_token', csrf);
          fetch(baseUrl + '/settings-panel/voucher-settings/types/' + id, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (data.success) { refreshVoucherTypesTbody(); Swal.fire({ icon: 'success', title: 'Deleted' }); }
              else Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not delete.' });
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Could not delete.' }); });
        });
    }
  });

  function buildConfigFields(containerId, dataType, existingConfig) {
    existingConfig = existingConfig || {};
    var typeMeta = dataTypes[dataType];
    var container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    if (!typeMeta || !typeMeta.config || !typeMeta.config.length) return;
    typeMeta.config.forEach(function(c) {
      var key = c.key, label = c.label, type = c.type || 'text', defaultVal = c.default, placeholder = c.placeholder || '';
      var val = existingConfig[key] !== undefined && existingConfig[key] !== null ? existingConfig[key] : (defaultVal !== undefined ? defaultVal : '');
      if (type === 'checkbox') val = val ? '1' : '0';
      var wrap = document.createElement('div');
      wrap.className = 'form-group';
      var lbl = document.createElement('label');
      lbl.className = 'form-label';
      lbl.textContent = label;
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
      input.className = 'form-control form-control-sm';
      wrap.appendChild(lbl);
      wrap.appendChild(input);
      container.appendChild(wrap);
    });
  }

  function showAddVoucherFieldOptions() {
    var dataType = document.getElementById('addVoucherFieldDataType').value;
    var opt = document.getElementById('addVoucherFieldOptionsContainer');
    if (!dataType) { if (opt) opt.style.display = 'none'; return; }
    if (opt) opt.style.display = 'block';
    var typeMeta = dataTypes[dataType];
    var configWrap = document.getElementById('addVoucherFieldConfigOptionsWrap');
    if (typeMeta && typeMeta.config && typeMeta.config.length) {
      buildConfigFields('addVoucherFieldConfigFields', dataType, {});
      configWrap.style.display = 'block';
    } else {
      configWrap.style.display = 'none';
    }
  }
  document.getElementById('addVoucherFieldDataType').addEventListener('change', showAddVoucherFieldOptions);

  document.getElementById('addVoucherFieldModal').addEventListener('show.bs.modal', function() {
    document.getElementById('formAddVoucherField').reset();
    document.getElementById('addVoucherFieldConfigFields').innerHTML = '';
    document.getElementById('addVoucherFieldOptionsContainer').style.display = 'none';
    document.getElementById('addVoucherFieldConfigOptionsWrap').style.display = 'none';
    document.getElementById('addVoucherMandatoryNo').checked = true;
    document.getElementById('addVoucherPreventDupNo').checked = true;
    showAddVoucherFieldOptions();
    var count = document.querySelectorAll('#voucherCustomFieldsTbody tr[data-id]').length;
    var rem = document.getElementById('remainingVoucherFieldsCount');
    if (rem) rem.textContent = Math.max(0, 50 - count);
  });

  document.getElementById('formAddVoucherField').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var submitBtn = document.getElementById('addVoucherFieldSubmitBtn');
    var fd = new FormData(form);
    fd.set('is_mandatory', form.querySelector('[name="is_mandatory"]:checked').value === '1' ? '1' : '0');
    fd.set('prevent_duplicate_values', form.querySelector('[name="prevent_duplicate_values"]:checked').value);
    var config = {};
    form.querySelectorAll('[name^="config["]').forEach(function(inp) {
      var m = (inp.getAttribute('name') || '').match(/config\[([^\]]+)\]/);
      if (m) {
        var v = inp.type === 'checkbox' ? (inp.checked ? '1' : '0') : inp.value;
        if (inp.type === 'textarea' && inp.name.indexOf('options') !== -1) v = (v || '').split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
        config[m[1]] = v;
      }
    });
    form.querySelectorAll('[name^="config["]').forEach(function(inp) { fd.delete(inp.name); });
    fd.append('config', JSON.stringify(config));
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Saving…'; }
    fetch(storeFieldUrl, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Save Field'; }
        if (data.success) {
          bootstrap.Modal.getInstance(document.getElementById('addVoucherFieldModal')).hide();
          fetch(voucherFieldsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.text(); })
            .then(function(html) {
              document.getElementById('voucherCustomFieldsTbody').innerHTML = html;
              Swal.fire({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, icon: 'success', title: 'Field saved.' });
              initVoucherFieldsSortable();
            });
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: (data.message || (data.errors && JSON.stringify(data.errors))) || 'Could not save.' });
        }
      })
      .catch(function() {
        if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Save Field'; }
        Swal.fire({ icon: 'error', title: 'Error', text: 'Could not save.' });
      });
  });

  document.getElementById('formEditVoucherField').addEventListener('submit', function(e) {
    e.preventDefault();
    var form = this;
    var id = form.querySelector('[name="id"]').value;
    var fd = new FormData(form);
    fd.set('_method', 'PUT');
    fd.set('is_mandatory', form.querySelector('#editVoucherFieldMandatory').checked ? '1' : '0');
    var config = {};
    form.querySelectorAll('[name^="config["]').forEach(function(inp) {
      var m = (inp.getAttribute('name') || '').match(/config\[([^\]]+)\]/);
      if (m) {
        var v = inp.type === 'checkbox' ? (inp.checked ? '1' : '0') : inp.value;
        if (inp.type === 'textarea' && inp.name.indexOf('options') !== -1) v = (v || '').split('\n').map(function(s) { return s.trim(); }).filter(Boolean);
        config[m[1]] = v;
      }
    });
    form.querySelectorAll('[name^="config["]').forEach(function(inp) { fd.delete(inp.name); });
    fd.append('config', JSON.stringify(config));
    fetch(baseUrl + '/settings-panel/voucher-settings/fields/' + id, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
      .then(function(r) { return r.json(); })
      .then(function(data) {
        if (data.success) {
          Swal.fire({ icon: 'success', title: 'Updated', text: data.message || 'Custom field updated.' });
          window.location.reload();
        } else {
          Swal.fire({ icon: 'error', title: 'Error', text: (data.message || data.errors) || 'Could not update.' });
        }
      })
      .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Could not update.' }); });
  });

  document.addEventListener('click', function(e) {
    var editBtn = e.target.closest('.edit-voucher-field');
    if (editBtn) {
      e.preventDefault();
      var config = {};
      try { config = JSON.parse(editBtn.dataset.config || '{}'); } catch (err) {}
      document.getElementById('editVoucherFieldId').value = editBtn.dataset.id;
      document.getElementById('editVoucherFieldLabel').value = editBtn.dataset.label;
      document.getElementById('editVoucherFieldDataType').value = editBtn.dataset.type;
      document.getElementById('editVoucherFieldMandatory').checked = editBtn.dataset.mandatory === '1';
      buildConfigFields('edit-voucher-config-options-fields', editBtn.dataset.type, config);
      document.getElementById('edit-voucher-config-options-container').style.display = (dataTypes[editBtn.dataset.type] && dataTypes[editBtn.dataset.type].config && dataTypes[editBtn.dataset.type].config.length) ? 'block' : 'none';
      new bootstrap.Modal(document.getElementById('editVoucherFieldModal')).show();
    }
    var delBtn = e.target.closest('.delete-voucher-field');
    if (delBtn) {
      e.preventDefault();
      var id = delBtn.dataset.id, label = delBtn.dataset.label;
      Swal.fire({ title: 'Delete field?', text: 'Delete custom field "' + label + '"?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', confirmButtonText: 'Delete' })
        .then(function(result) {
          if (!result.isConfirmed) return;
          var fd = new FormData();
          fd.set('_method', 'DELETE');
          fd.set('_token', csrf);
          fetch(baseUrl + '/settings-panel/voucher-settings/fields/' + id, { method: 'POST', body: fd, headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function(r) { return r.json(); })
            .then(function(data) {
              if (data.success) {
                var row = document.querySelector('#voucherCustomFieldsTbody tr[data-id="' + id + '"]');
                if (row) row.remove();
                var rows = document.querySelectorAll('#voucherCustomFieldsTbody tr[data-id]');
                if (rows.length === 0) document.getElementById('voucherCustomFieldsTbody').innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">No custom fields yet. Click "Add New Field" to create one.</td></tr>';
                else rows.forEach(function(row, i) { var td = row.cells[1]; if (td) td.textContent = i + 1; });
                Swal.fire({ icon: 'success', title: 'Deleted' });
              } else Swal.fire({ icon: 'error', title: 'Error', text: data.message || 'Could not delete.' });
            })
            .catch(function() { Swal.fire({ icon: 'error', title: 'Error', text: 'Could not delete.' }); });
        });
    }
  });

  var voucherFieldsSortable = null;
  function initVoucherFieldsSortable() {
    var tbody = document.getElementById('voucherCustomFieldsTbody');
    if (voucherFieldsSortable) { voucherFieldsSortable.destroy(); voucherFieldsSortable = null; }
    if (!tbody || !tbody.querySelectorAll('tr[data-id]').length) return;
    voucherFieldsSortable = new Sortable(tbody, {
      handle: '.drag-handle',
      animation: 150,
      ghostClass: 'table-warning',
      onEnd: function() {
        var order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(tr) { return tr.getAttribute('data-id'); });
        fetch(reorderFieldsUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }, body: JSON.stringify({ order: order }) })
          .then(function(r) { return r.json(); })
          .then(function(data) {
            if (data.success) {
              var idx = 1;
              tbody.querySelectorAll('tr[data-id]').forEach(function(row) { var td = row.cells[1]; if (td) td.textContent = idx++; });
            }
          });
      }
    });
  }
  initVoucherFieldsSortable();
})();
</script>
@endpush
