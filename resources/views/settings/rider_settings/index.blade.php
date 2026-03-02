@extends($layout ?? 'layouts.app')

@section('title', 'Rider Settings – Site Settings')

@push('third_party_stylesheets')
<style>
  .rider-settings-table th {
    font-weight: 600;
    white-space: nowrap;
  }

  .rider-settings-table .drag-handle {
    cursor: grab;
    color: #697a8d;
  }

  .rider-settings-table .drag-handle:active {
    cursor: grabbing;
  }

  .rider-settings-table tr.badge-soft-primary {
    background: rgba(105, 108, 255, 0.08);
  }

  #riderConfigOptionsContainer .form-group,
  #addRiderFieldConfigFields .form-group,
  #edit-rider-config-options-fields .form-group {
    margin-bottom: 0.75rem;
  }

  #riderConfigOptionsContainer label,
  #addRiderFieldConfigFields label,
  #edit-rider-config-options-fields label {
    font-weight: 500;
    font-size: 0.875rem;
  }

  .add-rider-field-form .form-text {
    font-size: 0.8125rem;
  }

  .rider-fields-sortable-tbody .drag-handle {
    cursor: grab;
  }

  .rider-fields-sortable-tbody .drag-handle:active {
    cursor: grabbing;
  }

  .nav-tabs-rider-fields .nav-link {
    padding: 0.5rem 0.75rem;
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
          <h4 class="card-title mb-0">Rider Settings</h4>
          <p class="text-muted small mb-0 mt-1">
            Manage rider categories (add, edit, reorder). Fixed rider fields and custom fields are grouped by category; open the Rider Fields tab and use each category sub-tab to manage and reorder fields.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Module display name (rename this module in the settings panel menu) --}}
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">Module display name</h5>
        <p class="text-muted small mb-0 mt-1">This name appears in the settings panel sidebar. Change it to match your terminology.</p>
      </div>
      <div class="card-body">
        <form action="{{ route('settings-panel.rider-settings.store-module-label') }}" method="POST" class="row g-3 align-items-end">
          @csrf
          <div class="col-md-6">
            <label class="form-label">Name in menu</label>
            <input type="text" name="module_label" class="form-control" value="{{ old('module_label', $moduleLabel ?? 'Rider Settings') }}" placeholder="Rider Settings" maxlength="100" required>
          </div>
          <div class="col-md-6">
            <button type="submit" class="btn btn-primary">Save name</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

{{-- Main content: 3 tabs (Categories | Rider Fields | Custom Fields) --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-rider-settings mb-3" id="riderSettingsMainTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-categories-btn" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">Categories</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-rider-fields-btn" data-bs-toggle="tab" data-bs-target="#tab-rider-fields" type="button" role="tab">Rider Fields</button>
          </li>
        </ul>

        <div class="tab-content" id="riderSettingsTabContent">
          {{-- Tab 1: Categories --}}
          <div class="tab-pane fade show active" id="tab-categories" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Add, edit, reorder rider categories. Custom categories can be deleted if they have no custom fields.</p>
              <button type="button" class="btn btn-primary btn-sm" id="btnAddRiderCategory" data-bs-toggle="modal" data-bs-target="#addRiderCategoryModal">
                <i class="ti ti-plus me-1"></i> Add Category
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover rider-settings-table mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 36px;"></th>
                    <th>#</th>
                    <th>Label</th>
                    <th>Slug</th>
                    <th>Type</th>
                    <th class="text-end" style="width: 160px;">Actions</th>
                  </tr>
                </thead>
                <tbody id="riderCategoriesTbody">
                  @include('settings.rider_settings._categories_tbody', ['categories' => $categories])
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab 2: Rider Fields (sub-tabs per category, drag-and-drop order) --}}
          <div class="tab-pane fade" id="tab-rider-fields" role="tabpanel">
            <p class="text-muted small mb-3">Fields are grouped by category. Drag rows to reorder within each category. Use "Move to category" to assign a field to another category.</p>

            <ul class="nav nav-tabs nav-tabs-rider-fields mb-3" id="riderFieldsCategoryTabs" role="tablist">
              @foreach($fieldsByCategory as $idx => $group)
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $idx === 0 ? 'active' : '' }}" id="rider-cat-{{ $group->category->id }}-tab" data-bs-toggle="tab" data-bs-target="#rider-field-cat-{{ $group->category->id }}" type="button" role="tab">
                  {{ $group->category->label }}
                  <span class="badge bg-label-info ms-1 rider-cat-badge-custom">{{ count($group->fields) }}</span>
                </button>
              </li>
              @endforeach
            </ul>

            <div class="tab-content" id="riderFieldsCategoryTabContent">
              @foreach($fieldsByCategory as $idx => $group)
              <div class="tab-pane fade {{ $idx === 0 ? 'show active' : '' }}" id="rider-field-cat-{{ $group->category->id }}" role="tabpanel" data-category-id="{{ $group->category->id }}">
                <div class="table-responsive">
                  <table class="table table-hover rider-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 36px;"></th>
                        <th>#</th>
                        <th>Field</th>
                        <th>Move to category</th>
                      </tr>
                    </thead>
                    <tbody id="rider-fields-tbody-{{ $group->category->id }}" class="rider-fields-sortable-tbody">
                      @forelse($group->fields as $rowIndex => $row)
                      <tr data-field-key="{{ $row->field_key }}">
                        <td class="align-middle"><span class="drag-handle cursor-grab"><i class="ti ti-grip-vertical"></i></span></td>
                        <td class="align-middle rider-field-index">{{ $rowIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="rider-fixed-field-label d-inline-block align-middle" data-field-key="{{ $row->field_key }}" title="Click to edit name">{{ $row->label }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                        </td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.rider-settings.update-field-assignment') }}" method="POST" class="d-flex justify-content-center rider-field-assignment-form">
                            @csrf
                            <input type="hidden" name="field_key" value="{{ $row->field_key }}">
                            <select name="category_id" class="form-select form-select-sm" style="width: auto; min-width: 160px;">
                              @foreach($categories as $c)
                              <option value="{{ $c->id }}" {{ (int)$group->category->id === (int)$c->id ? 'selected' : '' }}>{{ $c->label }}</option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-1">Move</button>
                          </form>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="4" class="text-center text-muted py-3">No fields in this category. Assign fields from another category or add new assignments.</td>
                      </tr>
                      @endforelse
                    </tbody>
                  </table>
                </div>

                {{-- Custom fields for this category --}}
                <div class="mt-4 pt-3 border-top">
                  <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <h6 class="mb-0">Custom fields</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm btn-add-custom-field-in-category" data-category-id="{{ $group->category->id }}" data-bs-toggle="modal" data-bs-target="#addRiderFieldModal">
                      <i class="ti ti-plus me-1"></i> Add custom field
                    </button>
                  </div>
                  <div class="table-responsive">
                    <table class="table table-hover rider-settings-table mb-0">
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
                      <tbody id="rider-custom-fields-tbody-{{ $group->category->id }}" class="rider-custom-fields-sortable-tbody" data-category-id="{{ $group->category->id }}">
                        @include('settings.rider_settings._custom_fields_rows_category', [
                        'customFields' => $customFieldsByCategory->get($group->category->id, collect()),
                        'dataTypes' => $dataTypes,
                        'categories' => $categories
                        ])
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

{{-- Add Rider Field modal --}}
<div class="modal fade" id="addRiderFieldModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add New Rider Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddRiderField" action="{{ route('settings-panel.rider-settings.store-field') }}" method="POST">
        @csrf
        <div class="modal-body pt-0">
          <div class="add-rider-field-form">
            <div class="mb-3">
              <label class="form-label">Label Name <span class="text-danger">*</span></label>
              <input type="text" name="label" class="form-control" placeholder="e.g. Emergency Contact" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Category <span class="text-danger">*</span></label>
              <select name="category_id" class="form-select" required>
                <option value="">Select category</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label class="form-label d-flex align-items-center gap-1">
                Data Type <span class="text-danger">*</span>
              </label>
              <select name="data_type" class="form-select" id="addRiderFieldDataType" required>
                <option value="">Select type</option>
                @foreach($dataTypes as $typeKey => $typeMeta)
                <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
                @endforeach
              </select>
              <p class="form-text text-muted mb-0 mt-1">
                Remaining custom fields:
                <span id="remainingRiderFieldsCount">{{ max(0, 50 - $customFields->count()) }}</span>
              </p>
            </div>
            <div id="addRiderFieldOptionsContainer" style="display: none;">
              <div class="mb-3" id="addRiderFieldHelpTextWrap">
                <label class="form-label">Help Text</label>
                <input type="text" name="help_text" class="form-control" placeholder="Optional help for users">
              </div>
              <div class="mb-3" id="addRiderFieldDataPrivacyWrap">
                <label class="form-label">Data Privacy</label>
                <div class="d-flex gap-4">
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_pii" value="1" class="form-check-input" id="addRiderFieldPii">
                    <label class="form-check-label" for="addRiderFieldPii">PII</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_ephi" value="1" class="form-check-input" id="addRiderFieldEphi">
                    <label class="form-check-label" for="addRiderFieldEphi">ePHI</label>
                  </div>
                </div>
              </div>
              <div class="mb-3" id="addRiderFieldPreventDupWrap">
                <label class="form-label">Prevent Duplicate Values</label>
                <div class="d-flex gap-3">
                  <div class="form-check">
                    <input type="radio" name="prevent_duplicate_values" value="1" class="form-check-input" id="addRiderPreventDupYes">
                    <label class="form-check-label" for="addRiderPreventDupYes">Yes</label>
                  </div>
                  <div class="form-check">
                    <input type="radio" name="prevent_duplicate_values" value="0" class="form-check-input" id="addRiderPreventDupNo" checked>
                    <label class="form-check-label" for="addRiderPreventDupNo">No</label>
                  </div>
                </div>
              </div>
              <div class="mb-3" id="addRiderFieldDefaultValueWrap">
                <label class="form-label">Default Value</label>
                <input type="text" name="default_value" class="form-control" placeholder="Default value">
              </div>
              <div class="mb-3" id="addRiderFieldInputFormatWrap" style="display: none;">
                <label class="form-label">Input Format</label>
                <input type="text" name="input_format" class="form-control" placeholder="e.g. email format">
              </div>
              <div class="mb-3" id="addRiderFieldConfigOptionsWrap" style="display: none;">
                <label class="form-label small text-uppercase text-muted">Configuration options</label>
                <div id="addRiderFieldConfigFields"></div>
              </div>
            </div>
            <div class="mb-3">
              <label class="form-label">Is Mandatory</label>
              <div class="d-flex gap-3">
                <div class="form-check">
                  <input type="radio" name="is_mandatory" value="1" class="form-check-input" id="addRiderMandatoryYes">
                  <label class="form-check-label" for="addRiderMandatoryYes">Yes</label>
                </div>
                <div class="form-check">
                  <input type="radio" name="is_mandatory" value="0" class="form-check-input" id="addRiderMandatoryNo" checked>
                  <label class="form-check-label" for="addRiderMandatoryNo">No</label>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addRiderFieldSubmitBtn">Save Field</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Rider Category modal --}}
<div class="modal fade" id="addRiderCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddRiderCategory">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" class="form-control" placeholder="e.g. Documents" required maxlength="255">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addRiderCategorySubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Rider Category modal --}}
<div class="modal fade" id="editRiderCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderCategory">
        <input type="hidden" name="id" id="editRiderCategoryId">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" id="editRiderCategoryLabel" class="form-control" required maxlength="255">
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

{{-- Edit Rider Field modal --}}
<div class="modal fade" id="editRiderFieldModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit rider custom field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderField" method="POST" action="">
        <div class="modal-body">
          <input type="hidden" name="id" id="editRiderFieldId">
          <input type="hidden" id="editRiderFieldPreviousCategoryId" value="">
          @csrf
          @method('PUT')
          <div class="mb-3">
            <label class="form-label">Label name <span class="text-danger">*</span></label>
            <input type="text" name="label" id="editRiderFieldLabel" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Category <span class="text-danger">*</span></label>
            <select name="category_id" id="editRiderFieldCategory" class="form-select" required>
              <option value="">Select category</option>
              @foreach($categories as $cat)
              <option value="{{ $cat->id }}">{{ $cat->label }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Data type <span class="text-danger">*</span></label>
            <select name="data_type" id="editRiderFieldDataType" class="form-select" required>
              @foreach($dataTypes as $typeKey => $typeMeta)
              <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Help text</label>
            <input type="text" name="help_text" id="editRiderFieldHelpText" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Default value</label>
            <input type="text" name="default_value" id="editRiderFieldDefaultValue" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Input format</label>
            <input type="text" name="input_format" id="editRiderFieldInputFormat" class="form-control">
          </div>
          <div class="mb-3">
            <label class="form-label">Data Privacy</label>
            <div class="d-flex gap-4">
              <div class="form-check">
                <input type="checkbox" name="data_privacy_pii" value="1" class="form-check-input" id="editRiderFieldPii">
                <label class="form-check-label" for="editRiderFieldPii">PII</label>
              </div>
              <div class="form-check">
                <input type="checkbox" name="data_privacy_ephi" value="1" class="form-check-input" id="editRiderFieldEphi">
                <label class="form-check-label" for="editRiderFieldEphi">ePHI</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Prevent Duplicate Values</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input type="radio" name="prevent_duplicate_values" value="1" class="form-check-input" id="editRiderPreventDupYes">
                <label class="form-check-label" for="editRiderPreventDupYes">Yes</label>
              </div>
              <div class="form-check">
                <input type="radio" name="prevent_duplicate_values" value="0" class="form-check-input" id="editRiderPreventDupNo">
                <label class="form-check-label" for="editRiderPreventDupNo">No</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Is Mandatory</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input type="radio" name="is_mandatory" value="1" class="form-check-input" id="editRiderMandatoryYes">
                <label class="form-check-label" for="editRiderMandatoryYes">Yes</label>
              </div>
              <div class="form-check">
                <input type="radio" name="is_mandatory" value="0" class="form-check-input" id="editRiderMandatoryNo">
                <label class="form-check-label" for="editRiderMandatoryNo">No</label>
              </div>
            </div>
          </div>
          <div class="mb-3" id="editRiderConfigOptionsWrap" style="display: none;">
            <label class="form-label small text-uppercase text-muted">Configuration options</label>
            <div id="edit-rider-config-options-fields"></div>
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

<input type="hidden" id="editRiderFieldConfigJson" value="{}">

@push('page_scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
  (function() {
    'use strict';

    const dataTypesMeta = JSON.parse('@json($dataTypes)');

    function buildConfigFields(container, typeKey, existingConfig) {
      container.innerHTML = '';
      const typeMeta = dataTypesMeta[typeKey] || null;
      if (!typeMeta || !typeMeta.config || !typeMeta.config.length) {
        return;
      }

      typeMeta.config.forEach(function(cfg) {
        const group = document.createElement('div');
        group.className = 'form-group mb-2';

        const label = document.createElement('label');
        label.className = 'form-label';
        label.textContent = cfg.label;

        let input;
        const name = 'config[' + cfg.key + ']';
        const value = existingConfig && typeof existingConfig[cfg.key] !== 'undefined' ?
          existingConfig[cfg.key] :
          (typeof cfg.default !== 'undefined' ? cfg.default : '');

        if (cfg.type === 'textarea') {
          input = document.createElement('textarea');
          input.className = 'form-control';
          input.rows = 3;
          if (cfg.placeholder) {
            input.placeholder = cfg.placeholder;
          }
          input.name = name;
          input.value = value;
        } else if (cfg.type === 'checkbox') {
          input = document.createElement('input');
          input.type = 'checkbox';
          input.className = 'form-check-input';
          input.name = name;
          input.value = 1;
          if (value) {
            input.checked = true;
          }
        } else {
          input = document.createElement('input');
          input.type = cfg.type || 'text';
          input.className = 'form-control';
          input.name = name;
          input.value = value;
          if (cfg.placeholder) {
            input.placeholder = cfg.placeholder;
          }
        }

        group.appendChild(label);
        group.appendChild(input);
        container.appendChild(group);
      });
    }

    // Add field modal dynamic options
    const addTypeSelect = document.getElementById('addRiderFieldDataType');
    const addOptionsContainer = document.getElementById('addRiderFieldOptionsContainer');
    const addConfigContainer = document.getElementById('addRiderFieldConfigFields');
    const addConfigWrap = document.getElementById('addRiderFieldConfigOptionsWrap');
    const addInputFormatWrap = document.getElementById('addRiderFieldInputFormatWrap');

    if (addTypeSelect) {
      addTypeSelect.addEventListener('change', function() {
        const typeKey = this.value;
        if (!typeKey) {
          addOptionsContainer.style.display = 'none';
          addConfigWrap.style.display = 'none';
          addInputFormatWrap.style.display = 'none';
          addConfigContainer.innerHTML = '';
          return;
        }

        addOptionsContainer.style.display = 'block';
        const typeMeta = dataTypesMeta[typeKey] || {};
        const hasConfig = typeMeta.config && typeMeta.config.length;
        addConfigWrap.style.display = hasConfig ? 'block' : 'none';
        addInputFormatWrap.style.display = (typeKey === 'text' || typeKey === 'number' || typeKey === 'decimal' || typeKey === 'email' || typeKey === 'url') ? 'block' : 'none';

        buildConfigFields(addConfigContainer, typeKey, {});
      });
    }

    // Edit field modal dynamic options
    const editTypeSelect = document.getElementById('editRiderFieldDataType');
    const editConfigContainer = document.getElementById('edit-rider-config-options-fields');
    const editConfigWrap = document.getElementById('editRiderConfigOptionsWrap');

    if (editTypeSelect) {
      editTypeSelect.addEventListener('change', function() {
        const typeKey = this.value;
        if (!typeKey) {
          editConfigWrap.style.display = 'none';
          editConfigContainer.innerHTML = '';
          return;
        }

        const fieldConfigInput = document.getElementById('editRiderFieldConfigJson');
        let existingConfig = {};
        if (fieldConfigInput && fieldConfigInput.value) {
          try {
            existingConfig = JSON.parse(fieldConfigInput.value);
          } catch (e) {
            existingConfig = {};
          }
        }

        const typeMeta = dataTypesMeta[typeKey] || {};
        const hasConfig = typeMeta.config && typeMeta.config.length;
        editConfigWrap.style.display = hasConfig ? 'block' : 'none';

        buildConfigFields(editConfigContainer, typeKey, existingConfig);
      });
    }

    window.refreshRiderCustomFieldsCategory = function(categoryId) {
      var tbody = document.getElementById('rider-custom-fields-tbody-' + categoryId);
      if (!tbody) return;
      var url = "{{ url('settings-panel/rider-settings/fields/table-body') }}/" + categoryId;
      fetch(url, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(r) {
          return r.text();
        })
        .then(function(html) {
          tbody.innerHTML = html;
          var badge = document.querySelector('.rider-cat-badge-custom[data-category-id="' + categoryId + '"]');
          if (badge) {
            var rows = tbody.querySelectorAll('tr[data-id]');
            badge.textContent = rows.length;
          }
          if (typeof initRiderCustomFieldsSortables === 'function') initRiderCustomFieldsSortables();
        });
    };

    window.refreshRiderCategoriesTable = function() {
      fetch("{{ route('settings-panel.rider-settings.categories-table-body') }}", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(resp) {
          return resp.text();
        })
        .then(function(html) {
          const tbody = document.getElementById('riderCategoriesTbody');
          if (tbody) tbody.innerHTML = html;
        });
    };

    var baseUrl = "{{ url('') }}";
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content') || document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value;

    document.getElementById('formAddRiderCategory').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var btn = document.getElementById('addRiderCategorySubmitBtn');
      var fd = new FormData(form);
      if (btn) btn.disabled = true;
      fetch("{{ route('settings-panel.rider-settings.store-category') }}", {
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
          if (btn) btn.disabled = false;
          if (data.success) {
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
              var modal = bootstrap.Modal.getInstance(document.getElementById('addRiderCategoryModal'));
              if (modal) modal.hide();
            }
            window.refreshRiderCategoriesTable();
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'success',
              title: 'Saved',
              text: data.message || 'Category added.'
            });
          } else {
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not save.'
            });
          }
        })
        .catch(function() {
          if (btn) btn.disabled = false;
          if (typeof Swal !== 'undefined') Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not save.'
          });
        });
    });

    document.getElementById('formEditRiderCategory').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var id = form.querySelector('#editRiderCategoryId').value;
      var fd = new FormData(form);
      fd.set('_method', 'PUT');
      fetch(baseUrl + '/settings-panel/rider-settings/categories/' + id, {
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
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
              var m = bootstrap.Modal.getInstance(document.getElementById('editRiderCategoryModal'));
              if (m) m.hide();
            }
            window.refreshRiderCategoriesTable();
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'success',
              title: 'Updated',
              text: data.message || 'Category updated.'
            });
          } else {
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not update.'
            });
          }
        })
        .catch(function() {
          if (typeof Swal !== 'undefined') Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not update.'
          });
        });
    });

    document.addEventListener('click', function(e) {
      var editBtn = e.target.closest('.btn-edit-category');
      if (editBtn) {
        e.preventDefault();
        document.getElementById('editRiderCategoryId').value = editBtn.dataset.id || '';
        document.getElementById('editRiderCategoryLabel').value = editBtn.dataset.label || '';
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          new bootstrap.Modal(document.getElementById('editRiderCategoryModal')).show();
        }
      }
    });

    // Open tab from URL ?tab=rider-fields
    (function() {
      var params = new URLSearchParams(window.location.search);
      var tab = params.get('tab');
      if (tab === 'rider-fields' && document.getElementById('tab-rider-fields-btn')) {
        var tabEl = new bootstrap.Tab(document.getElementById('tab-rider-fields-btn'));
        tabEl.show();
      }
    })();

    // When opening Add custom field from a category tab, preselect that category
    document.querySelectorAll('.btn-add-custom-field-in-category').forEach(function(btn) {
      btn.addEventListener('click', function() {
        var catId = this.getAttribute('data-category-id');
        var sel = document.querySelector('#addRiderFieldModal select[name="category_id"]');
        if (sel && catId) sel.value = catId;
      });
    });

    // Add custom field form: submit via AJAX and refresh the category tbody
    var formAddRiderField = document.getElementById('formAddRiderField');
    if (formAddRiderField) {
      formAddRiderField.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        var categoryId = form.querySelector('select[name="category_id"]') && form.querySelector('select[name="category_id"]').value;
        var submitBtn = document.getElementById('addRiderFieldSubmitBtn');
        if (submitBtn) submitBtn.disabled = true;
        fetch(form.action, {
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
            if (submitBtn) submitBtn.disabled = false;
            if (data.success) {
              if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('addRiderFieldModal'));
                if (modal) modal.hide();
              }
              form.reset();
              if (categoryId) window.refreshRiderCustomFieldsCategory(categoryId);
              if (typeof Swal !== 'undefined') Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: data.message || 'Field added.',
                showConfirmButton: false,
                timer: 2000
              });
            } else {
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Could not save.'
              });
            }
          })
          .catch(function() {
            if (submitBtn) submitBtn.disabled = false;
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Could not save.'
            });
          });
      });
    }

    // Edit custom field form: submit via AJAX and refresh the category tbody
    var formEditRiderField = document.getElementById('formEditRiderField');
    if (formEditRiderField) {
      formEditRiderField.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        fd.set('_method', 'PUT');
        var categoryId = form.querySelector('select[name="category_id"]') && form.querySelector('select[name="category_id"]').value;
        fetch(form.action, {
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
              if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('editRiderFieldModal'));
                if (modal) modal.hide();
              }
              var prevCatId = form.querySelector('#editRiderFieldPreviousCategoryId') && form.querySelector('#editRiderFieldPreviousCategoryId').value;
              if (prevCatId) window.refreshRiderCustomFieldsCategory(prevCatId);
              if (categoryId) window.refreshRiderCustomFieldsCategory(categoryId);
              if (typeof Swal !== 'undefined') Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: data.message || 'Updated.',
                showConfirmButton: false,
                timer: 2000
              });
            } else {
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Could not update.'
              });
            }
          })
          .catch(function() {
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Could not update.'
            });
          });
      });
    }

    // Delete custom field: AJAX and refresh that category tbody
    document.addEventListener('submit', function(e) {
      var form = e.target.closest('.rider-destroy-field-form');
      if (!form) return;
      e.preventDefault();
      if (!confirm('Are you sure you want to delete this custom field?')) return;
      var categoryId = form.getAttribute('data-category-id');
      fetch(form.action, {
          method: 'POST',
          body: new FormData(form),
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
          if (data.success && categoryId) window.refreshRiderCustomFieldsCategory(categoryId);
          if (typeof Swal !== 'undefined') Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: data.message || 'Deleted.',
            showConfirmButton: false,
            timer: 2000
          });
        });
    });

    // Inline edit fixed rider field display name (click label to edit)
    document.addEventListener('click', function(e) {
      var labelEl = e.target.closest('.rider-fixed-field-label');
      if (!labelEl || labelEl.querySelector('input')) return;
      var fieldKey = labelEl.getAttribute('data-field-key');
      var currentText = (labelEl.textContent || '').trim();
      var input = document.createElement('input');
      input.type = 'text';
      input.className = 'form-control form-control-sm d-inline-block';
      input.style.width = 'min(200px, 100%)';
      input.value = currentText;
      input.dataset.fieldKey = fieldKey;
      labelEl.textContent = '';
      labelEl.appendChild(input);
      input.focus();
      input.select();

      function saveAndRevert() {
        var newLabel = (input.value || '').trim();
        input.remove();
        labelEl.textContent = labelEl.dataset.pendingLabel !== undefined ? labelEl.dataset.pendingLabel : currentText;
        delete labelEl.dataset.pendingLabel;
        if (newLabel === currentText) return;
        labelEl.dataset.pendingLabel = newLabel;
        fetch("{{ route('settings-panel.rider-settings.update-field-assignment-label') }}", {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
          body: JSON.stringify({ field_key: fieldKey, display_label: newLabel })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
          if (data.success && data.label !== undefined) {
            labelEl.textContent = data.label;
            delete labelEl.dataset.pendingLabel;
            if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Name updated.', showConfirmButton: false, timer: 1500 });
          }
        })
        .catch(function() { if (labelEl.dataset.pendingLabel) labelEl.textContent = labelEl.dataset.pendingLabel; });
      }

      input.addEventListener('blur', saveAndRevert);
      input.addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter') { ev.preventDefault(); input.blur(); }
        if (ev.key === 'Escape') { input.value = currentText; input.blur(); }
      });
    });

    // Sortable for custom fields (per category tbody)
    var riderCustomFieldSortables = [];

    function initRiderCustomFieldsSortables() {
      riderCustomFieldSortables.forEach(function(s) {
        if (s) s.destroy();
      });
      riderCustomFieldSortables = [];
      document.querySelectorAll('.rider-custom-fields-sortable-tbody').forEach(function(tbody) {
        var rows = tbody.querySelectorAll('tr[data-id]');
        if (rows.length < 1) return;
        var categoryId = tbody.getAttribute('data-category-id');
        if (!categoryId || typeof Sortable === 'undefined') return;
        var sortable = new Sortable(tbody, {
          handle: '.drag-handle',
          animation: 150,
          ghostClass: 'table-warning',
          onEnd: function() {
            var order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(tr) {
              return parseInt(tr.getAttribute('data-id'), 10);
            });
            fetch("{{ route('settings-panel.rider-settings.reorder-fields') }}", {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrf,
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                  category_id: parseInt(categoryId, 10),
                  order: order
                })
              })
              .then(function(r) { return r.json().catch(function() { return { success: false }; }); })
              .then(function(data) {
                if (data.success) {
                  tbody.querySelectorAll('tr[data-id] .rider-custom-field-index').forEach(function(td, i) {
                    td.textContent = i + 1;
                  });
                  if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Order saved.', showConfirmButton: false, timer: 2000 });
                } else if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: data.message || 'Could not save order.', showConfirmButton: false, timer: 3000 });
              })
              .catch(function() {
                if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Could not save order.', showConfirmButton: false, timer: 3000 });
              });
          }
        });
        riderCustomFieldSortables.push(sortable);
      });
    }

    // Drag-and-drop reorder for Rider Fields (per category tbody)
    var riderFieldSortables = [];

    function initRiderFieldSortables() {
      riderFieldSortables.forEach(function(s) {
        if (s) s.destroy();
      });
      riderFieldSortables = [];
      document.querySelectorAll('.rider-fields-sortable-tbody').forEach(function(tbody) {
        var rows = tbody.querySelectorAll('tr[data-field-key]');
        if (rows.length < 1) return;
        var pane = tbody.closest('.tab-pane');
        var categoryId = pane && pane.getAttribute('data-category-id');
        if (!categoryId) return;
        if (typeof Sortable === 'undefined') return;
        var sortable = new Sortable(tbody, {
          handle: '.drag-handle',
          animation: 150,
          ghostClass: 'table-warning',
          onEnd: function() {
            var order = Array.from(tbody.querySelectorAll('tr[data-field-key]')).map(function(tr) {
              return tr.getAttribute('data-field-key');
            });
            fetch("{{ route('settings-panel.rider-settings.reorder-field-assignments') }}", {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrf,
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                  category_id: parseInt(categoryId, 10),
                  order: order
                })
              })
              .then(function(r) { return r.json().catch(function() { return { success: false }; }); })
              .then(function(data) {
                if (data.success) {
                  var idx = 1;
                  tbody.querySelectorAll('tr[data-field-key] .rider-field-index').forEach(function(td) {
                    td.textContent = idx++;
                  });
                  if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Order saved.', showConfirmButton: false, timer: 2000 });
                } else if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: (data && data.message) || 'Could not save order.', showConfirmButton: false, timer: 3000 });
              })
              .catch(function() {
                if (typeof Swal !== 'undefined') Swal.fire({ toast: true, position: 'top-end', icon: 'error', title: 'Could not save order.', showConfirmButton: false, timer: 3000 });
              });
          }
        });
        riderFieldSortables.push(sortable);
      });
    }
    document.getElementById('tab-rider-fields-btn') && document.getElementById('tab-rider-fields-btn').addEventListener('shown.bs.tab', function() {
      setTimeout(initRiderFieldSortables, 50);
      setTimeout(initRiderCustomFieldsSortables, 80);
    });
    if (document.getElementById('tab-rider-fields').classList.contains('show')) {
      setTimeout(initRiderFieldSortables, 100);
      setTimeout(initRiderCustomFieldsSortables, 150);
    }
  })();
</script>
@endpush

@endsection