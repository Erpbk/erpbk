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

  .rider-top-visibility-controls {
    background: #f8f9fb;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    padding: 0.35rem 0.6rem;
  }

  .rider-top-visibility-controls .form-check {
    min-height: auto;
    margin-bottom: 0;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
  }

  .rider-top-visibility-controls .form-check-input {
    width: 2rem;
    height: 1.1rem;
    margin: 0;
    cursor: pointer;
  }

  .rider-top-visibility-controls .form-check-label {
    font-size: 0.78rem;
    font-weight: 500;
    color: #5f6b7a;
    margin-top: 0;
    cursor: pointer;
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

{{-- Main content: tabs General | Categories | Rider Fields --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-rider-settings mb-3" id="riderSettingsMainTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-categories-btn" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">Categories</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-rider-fields-btn" data-bs-toggle="tab" data-bs-target="#tab-rider-fields" type="button" role="tab">Rider Fields</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-rider-top-btn" data-bs-toggle="tab" data-bs-target="#tab-rider-top" type="button" role="tab">Rider Top</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-rider-documents-btn" data-bs-toggle="tab" data-bs-target="#tab-rider-documents" type="button" role="tab">Rider Documents</button>
          </li>

        </ul>

        <div class="tab-content" id="riderSettingsTabContent">
          {{-- Tab 1: General (module name in menu) --}}
          <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <p class="text-muted small mb-3">This name appears in the settings panel sidebar. Change it to match your terminology.</p>
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

          {{-- Tab 2: Categories --}}
          <div class="tab-pane fade" id="tab-categories" role="tabpanel">
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

          {{-- Tab 3: Rider Documents (dynamic document types for rider files page) --}}
          <div class="tab-pane fade" id="tab-rider-documents" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Define document types required for riders. Single = one file per type (e.g. Profile Photo). Dual = front and back page (e.g. Passport first/second). Key is used to match uploaded file names.</p>
              <button type="button" class="btn btn-primary btn-sm" id="btnAddRiderDocumentType" data-bs-toggle="modal" data-bs-target="#addRiderDocumentTypeModal">
                <i class="ti ti-plus me-1"></i> Add Document Type
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover rider-settings-table mb-0">
                <thead class="table-light">
                  <tr>
                    <th style="width: 36px;"></th>
                    <th>#</th>
                    <th>Key</th>
                    <th>Type</th>
                    <th>Label(s)</th>
                    <th>Status</th>
                    <th class="text-end" style="width: 140px;">Actions</th>
                  </tr>
                </thead>
                <tbody id="riderDocumentTypesTbody" class="rider-document-types-sortable-tbody">
                  @include('settings.rider_settings._document_types_tbody', ['documentTypes' => $documentTypes])
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab 5: Rider Top --}}
          <div class="tab-pane fade" id="tab-rider-top" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Create a Rider Top category first, then add multiple options under each category.</p>
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRiderTopCategoryModal">
                <i class="ti ti-plus me-1"></i> Add Category
              </button>
            </div>
            <div id="riderTopAccordionContainer">
              @include('settings.rider_settings._rider_top_accordion', ['riderTopCategories' => $riderTopCategories])
            </div>
          </div>

          {{-- Tab 4: Rider Fields (all-fields + category tabs) --}}
          <div class="tab-pane fade" id="tab-rider-fields" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Use the static <b>All Fields</b> tab to assign fields. Category tabs show assigned fields category-wise.</p>
              <button type="button" class="btn btn-primary btn-sm" id="btnAddCustomFieldFromTop" data-bs-toggle="modal" data-bs-target="#addRiderFieldModal">
                <i class="ti ti-plus me-1"></i> Add Custom Field
              </button>
            </div>
            <ul class="nav nav-tabs nav-tabs-rider-fields mb-3" id="riderFieldsCategoryTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="rider-fields-all-tab" data-bs-toggle="tab" data-bs-target="#rider-fields-all-pane" type="button" role="tab">
                  All Fields
                  <span class="badge bg-label-primary ms-1">{{ count($allFixedFieldsForStatic ?? []) }}</span>
                </button>
              </li>
              @foreach($fieldsByCategory as $idx => $group)
              @php
                $categoryCustomFields = ($customFieldsByCategory ?? collect())->get($group->category->id, collect());
              @endphp
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="rider-cat-{{ $group->category->id }}-tab" data-bs-toggle="tab" data-bs-target="#rider-field-cat-{{ $group->category->id }}" type="button" role="tab">
                  {{ $group->category->label }}
                  <span class="badge bg-label-info ms-1 rider-cat-badge-custom">{{ count($group->fields) + $categoryCustomFields->count() }}</span>
                </button>
              </li>
              @endforeach
            </ul>

            <div class="tab-content" id="riderFieldsCategoryTabContent">
              <div class="tab-pane fade show active" id="rider-fields-all-pane" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover rider-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Field</th>
                        <th>Current category</th>
                        <th class="text-center">Required</th>
                        <th class="text-center">Show in rider form</th>
                        <th>Move to category</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="rider-fields-tbody-all">
                      @forelse(($allFixedFieldsForStatic ?? []) as $rowIndex => $row)
                      <tr data-field-key="{{ $row->field_key }}" data-field-label="{{ $row->label }}" data-category-id="{{ $row->category_id ?? '' }}" data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}" data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}" data-input-type="{{ $row->input_type ?? 'text' }}" data-input-config='@json($row->input_config ?? [])' class="{{ !($row->is_visible ?? true) ? 'table-secondary' : '' }}">
                        <td class="align-middle">{{ $rowIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="rider-fixed-field-label d-inline-block align-middle" data-field-key="{{ $row->field_key }}" title="Click to edit name">{{ $row->label }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                        </td>
                        <td class="align-middle">
                          @if(!empty($row->category_label))
                          <span class="badge bg-label-info">{{ $row->category_label }}</span>
                          @else
                          <span class="badge bg-label-warning">Unassigned</span>
                          @endif
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox" class="form-check-input rider-field-required-toggle" id="req-all-{{ $row->field_key }}" data-field-key="{{ $row->field_key }}" {{ ($row->is_required ?? false) ? 'checked' : '' }} title="Require this field in rider add/edit forms">
                            <label class="form-check-label visually-hidden" for="req-all-{{ $row->field_key }}">Mark as required</label>
                          </div>
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox" class="form-check-input rider-field-visibility-toggle" id="vis-all-{{ $row->field_key }}" data-field-key="{{ $row->field_key }}" {{ ($row->is_visible ?? true) ? 'checked' : '' }} title="Hide from Rider Add/Edit/View when unchecked">
                            <label class="form-check-label visually-hidden" for="vis-all-{{ $row->field_key }}">Show in rider form</label>
                          </div>
                        </td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.rider-settings.update-field-assignment') }}" method="POST" class="d-flex justify-content-center rider-field-assignment-form">
                            @csrf
                            <input type="hidden" name="field_key" value="{{ $row->field_key }}">
                            <select name="category_id" class="form-select form-select-sm" style="width: auto; min-width: 160px;">
                              <option value="">Select category</option>
                              @foreach($categories as $c)
                              <option value="{{ $c->id }}" {{ (int)($row->category_id ?? 0) === (int)$c->id ? 'selected' : '' }}>{{ $c->label }}</option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-1">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          <button type="button" class="btn btn-sm btn-outline-primary btn-edit-rider-fixed-field"
                            data-field-key="{{ $row->field_key }}"
                            data-field-label="{{ $row->label }}"
                            data-category-id="{{ $row->category_id ?? '' }}"
                            data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                            data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                            data-input-type="{{ $row->input_type ?? 'text' }}"
                            data-input-config='@json($row->input_config ?? [])'>
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @empty
                      <tr>
                        <td colspan="7" class="text-center text-muted py-3">No rider fields found.</td>
                      </tr>
                      @endforelse
                      @foreach(($customFields ?? collect()) as $customIndex => $customField)
                      <tr class="table-light">
                        <td class="align-middle">{{ count($allFixedFieldsForStatic ?? []) + $customIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $customField->label }}</span>
                          <span class="badge bg-label-secondary ms-1">Custom</span>
                        </td>
                        <td class="align-middle">
                          @if(!empty($customField->category?->label))
                          <span class="badge bg-label-info">{{ $customField->category->label }}</span>
                          @else
                          <span class="badge bg-label-warning">Unassigned</span>
                          @endif
                        </td>
                        <td class="align-middle text-center">{{ $customField->is_mandatory ? 'Yes' : 'No' }}</td>
                        <td class="align-middle text-center">-</td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.rider-settings.assign-custom-field-category', ['id' => $customField->id]) }}" method="POST" class="d-flex justify-content-center">
                            @csrf
                            <select name="category_id" class="form-select form-select-sm" style="width: auto; min-width: 160px;" required>
                              <option value="">Select category</option>
                              @foreach($categories as $c)
                              <option value="{{ $c->id }}" {{ (int)($customField->category_id ?? 0) === (int)$c->id ? 'selected' : '' }}>{{ $c->label }}</option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-1">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          <button type="button" class="btn btn-sm btn-outline-primary btn-edit-rider-field"
                            data-id="{{ $customField->id }}"
                            data-label="{{ $customField->label }}"
                            data-help_text="{{ $customField->help_text }}"
                            data-data_type="{{ $customField->data_type }}"
                            data-is_mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                            data-prevent_duplicate_values="{{ $customField->prevent_duplicate_values ? 1 : 0 }}"
                            data-default_value="{{ $customField->default_value }}"
                            data-input_format="{{ $customField->input_format }}"
                            data-config='@json($customField->config)'
                            data-category_id="{{ $customField->category_id ?? '' }}"
                            data-update-url="{{ route('settings-panel.rider-settings.update-field', ['id' => $customField->id]) }}"
                            data-bs-toggle="modal" data-bs-target="#editRiderFieldModal">
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>

              @foreach($fieldsByCategory as $idx => $group)
              @php
                $categoryCustomFields = ($customFieldsByCategory ?? collect())->get($group->category->id, collect());
                $fixedCount = count($group->fields);
              @endphp
              <div class="tab-pane fade" id="rider-field-cat-{{ $group->category->id }}" role="tabpanel" data-category-id="{{ $group->category->id }}">
                <div class="table-responsive">
                  <table class="table table-hover rider-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 36px;"></th>
                        <th>#</th>
                        <th>Field</th>
                        <th class="text-center">Required</th>
                        <th class="text-center">Show in rider form</th>
                        <th>Move to category</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="rider-fields-tbody-{{ $group->category->id }}" class="rider-fields-sortable-tbody">
                      @foreach($group->fields as $rowIndex => $row)
                      <tr data-field-key="{{ $row->field_key }}" data-field-label="{{ $row->label }}" data-category-id="{{ $group->category->id }}" data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}" data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}" data-input-type="{{ $row->input_type ?? 'text' }}" data-input-config='@json($row->input_config ?? [])' class="{{ !($row->is_visible ?? true) ? 'table-secondary' : '' }}">
                        <td class="align-middle"><span class="drag-handle cursor-grab"><i class="ti ti-grip-vertical"></i></span></td>
                        <td class="align-middle rider-field-index">{{ $rowIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="rider-fixed-field-label d-inline-block align-middle" data-field-key="{{ $row->field_key }}" title="Click to edit name">{{ $row->label }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox" class="form-check-input rider-field-required-toggle" id="req-{{ $group->category->id }}-{{ $row->field_key }}" data-field-key="{{ $row->field_key }}" {{ ($row->is_required ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label visually-hidden" for="req-{{ $group->category->id }}-{{ $row->field_key }}">Mark as required</label>
                          </div>
                        </td>
                        <td class="align-middle text-center">
                          <div class="form-check form-switch d-inline-block mb-0">
                            <input type="checkbox" class="form-check-input rider-field-visibility-toggle" id="vis-{{ $group->category->id }}-{{ $row->field_key }}" data-field-key="{{ $row->field_key }}" {{ ($row->is_visible ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label visually-hidden" for="vis-{{ $group->category->id }}-{{ $row->field_key }}">Show in rider form</label>
                          </div>
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
                        <td class="align-middle text-end">
                          <button type="button" class="btn btn-sm btn-outline-primary btn-edit-rider-fixed-field"
                            data-field-key="{{ $row->field_key }}"
                            data-field-label="{{ $row->label }}"
                            data-category-id="{{ $group->category->id }}"
                            data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                            data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                            data-input-type="{{ $row->input_type ?? 'text' }}"
                            data-input-config='@json($row->input_config ?? [])'>
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @endforeach
                      @foreach($categoryCustomFields as $customIndex => $customField)
                      <tr class="table-light" data-id="{{ $customField->id }}">
                        <td class="align-middle"><span class="text-muted">-</span></td>
                        <td class="align-middle rider-custom-field-index">{{ $fixedCount + $customIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $customField->label }}</span>
                          <span class="badge bg-label-secondary ms-1">Custom</span>
                        </td>
                        <td class="align-middle text-center">{{ $customField->is_mandatory ? 'Yes' : 'No' }}</td>
                        <td class="align-middle text-center">-</td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.rider-settings.assign-custom-field-category', ['id' => $customField->id]) }}" method="POST" class="d-flex justify-content-center">
                            @csrf
                            <select name="category_id" class="form-select form-select-sm" style="width: auto; min-width: 160px;" required>
                              @foreach($categories as $c)
                              <option value="{{ $c->id }}" {{ (int)$group->category->id === (int)$c->id ? 'selected' : '' }}>{{ $c->label }}</option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary ms-1">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          <button type="button" class="btn btn-sm btn-outline-primary btn-edit-rider-field"
                            data-id="{{ $customField->id }}"
                            data-label="{{ $customField->label }}"
                            data-help_text="{{ $customField->help_text }}"
                            data-data_type="{{ $customField->data_type }}"
                            data-is_mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                            data-prevent_duplicate_values="{{ $customField->prevent_duplicate_values ? 1 : 0 }}"
                            data-default_value="{{ $customField->default_value }}"
                            data-input_format="{{ $customField->input_format }}"
                            data-config='@json($customField->config)'
                            data-category_id="{{ $group->category->id }}"
                            data-update-url="{{ route('settings-panel.rider-settings.update-field', ['id' => $customField->id]) }}"
                            data-bs-toggle="modal" data-bs-target="#editRiderFieldModal">
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @endforeach
                      @if($fixedCount === 0 && $categoryCustomFields->isEmpty())
                      <tr>
                        <td colspan="7" class="text-center text-muted py-3">No fields in this category.</td>
                      </tr>
                      @endif
                    </tbody>
                  </table>
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
              <label class="form-label">Category</label>
              <input type="text" class="form-control" value="Unassigned (set from Rider Fields tab after creation)" disabled>
              <input type="hidden" name="category_id" value="">
              <p class="form-text mb-0">New custom fields are created as unassigned and can be moved category-wise from the Rider Fields tab.</p>
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
      <form id="formAddRiderCategory" action="{{ route('settings-panel.rider-settings.store-category') }}" method="POST">
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

{{-- Edit Fixed Rider Field modal --}}
<div class="modal fade" id="editRiderFixedFieldModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Fixed Rider Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderFixedField">
        @csrf
        <input type="hidden" name="field_key" id="editRiderFixedFieldKey">
        <div class="modal-body pt-0">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Field Key</label>
              <input type="text" id="editRiderFixedFieldKeyText" class="form-control" readonly>
            </div>
            <div class="col-md-6">
              <label class="form-label">Show in rider form</label>
              <div class="form-check form-switch mt-2">
                <input type="checkbox" class="form-check-input" id="editRiderFixedFieldVisible" checked>
                <label class="form-check-label" for="editRiderFixedFieldVisible">Visible in add/edit/view</label>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Display Label</label>
              <input type="text" name="display_label" id="editRiderFixedFieldLabel" class="form-control" maxlength="255" placeholder="Enter display label">
            </div>
            <div class="col-md-6">
              <label class="form-label">Field Type</label>
              <select name="input_type" id="editRiderFixedFieldType" class="form-select" required>
                @foreach($dataTypes as $typeKey => $typeMeta)
                <option value="{{ $typeKey }}">{{ $typeMeta['label'] ?? ucfirst($typeKey) }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-12" id="editRiderFixedConfigOptionsWrap" style="display:none;">
              <label class="form-label small text-uppercase text-muted">Type configuration</label>
              <div id="edit-rider-fixed-config-options-fields"></div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category</label>
              <select name="category_id" id="editRiderFixedFieldCategoryId" class="form-select" required>
                @foreach($categories as $c)
                <option value="{{ $c->id }}">{{ $c->label }}</option>
                @endforeach
              </select>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editRiderFixedFieldSubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Rider Top Category modal --}}
<div class="modal fade" id="addRiderTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Rider Top Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddRiderTopCategory">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Rider Dropdown Column <span class="text-danger">*</span></label>
            <select name="rider_column" class="form-select" required>
              <option value="">Select dropdown column</option>
              @foreach(($riderTopSelectableColumns ?? []) as $columnKey => $columnLabel)
              <option value="{{ $columnKey }}">{{ $columnLabel }} ({{ $columnKey }})</option>
              @endforeach
            </select>
            <div class="form-text">Only rider dropdown columns are available here; foreign-key and externally linked columns are excluded.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addRiderTopCategorySubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Rider Top Option modal --}}
<div class="modal fade" id="addRiderTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Rider Top Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddRiderTopOption">
        @csrf
        <input type="hidden" name="category_id" id="addRiderTopOptionCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-2 text-muted small">
            Category: <strong id="addRiderTopOptionCategoryName">-</strong>
          </div>
          <div class="mb-2 text-muted small">
            Source Column: <strong id="addRiderTopOptionColumnName">-</strong>
          </div>
          <div class="mb-3">
            <label class="form-label d-block">Selection Mode</label>
            <div class="d-flex gap-3">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="riderTopOptionModeSingle" value="single" checked>
                <label class="form-check-label" for="riderTopOptionModeSingle">Single Select</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="selection_mode" id="riderTopOptionModeMultiple" value="multiple">
                <label class="form-check-label" for="riderTopOptionModeMultiple">Multiple Select</label>
              </div>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label">Add Option Value(s) <span class="text-danger">*</span></label>
            <div id="addRiderTopOptionRows" class="d-flex flex-column gap-2"></div>
            <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addRiderTopOptionRowBtn">Add Option</button>
            <div class="form-text">Values are loaded from the selected category column in the rider table. You can add one or more items.</div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addRiderTopOptionSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Rider Top Category modal --}}
<div class="modal fade" id="editRiderTopCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Rider Top Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderTopCategory">
        @csrf
        <input type="hidden" name="id" id="editRiderTopCategoryId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Category Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editRiderTopCategoryName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editRiderTopCategorySubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Rider Top Option modal --}}
<div class="modal fade" id="editRiderTopOptionModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Rider Top Option</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderTopOption">
        @csrf
        <input type="hidden" name="id" id="editRiderTopOptionId">
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Option Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="editRiderTopOptionName" class="form-control" maxlength="255" required>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="editRiderTopOptionSubmitBtn">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Rider Document Type modal --}}
<div class="modal fade" id="addRiderDocumentTypeModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Document Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddRiderDocumentType">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Key <span class="text-danger">*</span></label>
            <input type="text" name="key" id="addDocTypeKey" class="form-control" placeholder="e.g. photo, passport" pattern="[a-z0-9_]+" maxlength="80" required>
            <div class="form-text">Lowercase letters, numbers, underscores. Used to match uploaded file names.</div>
          </div>
          <div class="mb-3">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" id="addDocTypeType" class="form-select" required>
              <option value="single">Single (one file)</option>
              <option value="dual">Dual (front + back page)</option>
            </select>
          </div>
          <div class="mb-3" id="addDocTypeSingleWrap">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" id="addDocTypeLabel" class="form-control" placeholder="e.g. Profile Photo" maxlength="255">
          </div>
          <div id="addDocTypeDualWrap" style="display: none;">
            <div class="mb-3">
              <label class="form-label">Front / First page label <span class="text-danger">*</span></label>
              <input type="text" name="front_label" id="addDocTypeFrontLabel" class="form-control" placeholder="e.g. Passport ( First Page )" maxlength="255">
            </div>
            <div class="mb-3">
              <label class="form-label">Back / Second page label <span class="text-danger">*</span></label>
              <input type="text" name="back_label" id="addDocTypeBackLabel" class="form-control" placeholder="e.g. Passport ( Second Page )" maxlength="255">
            </div>
          </div>
          <div class="mb-0">
            <div class="form-check">
              <input type="checkbox" name="is_active" id="addDocTypeActive" class="form-check-input" value="1" checked>
              <label class="form-check-label" for="addDocTypeActive">Active</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addRiderDocumentTypeSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Rider Document Type modal --}}
<div class="modal fade" id="editRiderDocumentTypeModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Document Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderDocumentType">
        <input type="hidden" name="id" id="editDocTypeId">
        @csrf
        @method('PUT')
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Key <span class="text-danger">*</span></label>
            <input type="text" name="key" id="editDocTypeKey" class="form-control" pattern="[a-z0-9_]+" maxlength="80" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Type <span class="text-danger">*</span></label>
            <select name="type" id="editDocTypeType" class="form-select" required>
              <option value="single">Single (one file)</option>
              <option value="dual">Dual (front + back page)</option>
            </select>
          </div>
          <div class="mb-3" id="editDocTypeSingleWrap">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" id="editDocTypeLabel" class="form-control" maxlength="255">
          </div>
          <div id="editDocTypeDualWrap" style="display: none;">
            <div class="mb-3">
              <label class="form-label">Front / First page label <span class="text-danger">*</span></label>
              <input type="text" name="front_label" id="editDocTypeFrontLabel" class="form-control" maxlength="255">
            </div>
            <div class="mb-3">
              <label class="form-label">Back / Second page label <span class="text-danger">*</span></label>
              <input type="text" name="back_label" id="editDocTypeBackLabel" class="form-control" maxlength="255">
            </div>
          </div>
          <div class="mb-0">
            <div class="form-check">
              <input type="checkbox" name="is_active" id="editDocTypeActive" class="form-check-input" value="1">
              <label class="form-check-label" for="editDocTypeActive">Active</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Rider Field modal --}}
<div class="modal fade" id="editRiderFieldModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit rider custom field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditRiderField" method="POST" action="">
        <div class="modal-body pt-0">
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
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary">Update</button>
        </div>
      </form>
    </div>
  </div>
</div>

<input type="hidden" id="editRiderFieldConfigJson" value="{}">
<input type="hidden" id="riderDataTypesMetaJson" value='@json($dataTypes)'>
@endsection
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
  (function() {
    'use strict';

    let dataTypesMeta = {};
    const dataTypesMetaEl = document.getElementById('riderDataTypesMetaJson');
    if (dataTypesMetaEl && dataTypesMetaEl.value) {
      try {
        dataTypesMeta = JSON.parse(dataTypesMetaEl.value);
      } catch (e) {
        dataTypesMeta = {};
      }
    }

    function buildConfigFields(container, typeKey, existingConfig) {
      container.innerHTML = '';
      const typeMeta = dataTypesMeta[typeKey] || null;
      if (!typeMeta || !typeMeta.config || !typeMeta.config.length) {
        return;
      }

      function parseOptionLines(rawValue) {
        return String(rawValue || '')
          .split(/\r?\n/)
          .map(function(line) {
            return line.trim();
          })
          .filter(function(line) {
            return line.length > 0;
          });
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

          if (cfg.key === 'options') {
            const help = document.createElement('div');
            help.className = 'form-text';
            help.textContent = 'Each option is added as a separate item.';

            // Keep backend payload compatible: store options as newline-separated string.
            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = name;
            hiddenInput.value = Array.isArray(value) ? value.join('\n') : String(value || '');

            const optionsWrap = document.createElement('div');
            optionsWrap.className = 'mt-2';

            const list = document.createElement('div');
            list.className = 'd-flex flex-column gap-2';

            const addBtn = document.createElement('button');
            addBtn.type = 'button';
            addBtn.className = 'btn btn-sm btn-outline-primary mt-2';
            addBtn.textContent = 'Add Option';

            var syncHiddenOptions = function() {
              const items = Array.prototype.slice.call(list.querySelectorAll('input[type="text"]'))
                .map(function(el) {
                  return (el.value || '').trim();
                })
                .filter(function(v) {
                  return v.length > 0;
                });
              hiddenInput.value = items.join('\n');
            };

            const createOptionRow = function(initialValue) {
              const row = document.createElement('div');
              row.className = 'd-flex align-items-center gap-2';

              const rowInput = document.createElement('input');
              rowInput.type = 'text';
              rowInput.className = 'form-control';
              rowInput.placeholder = 'Option value';
              rowInput.value = initialValue || '';

              const removeBtn = document.createElement('button');
              removeBtn.type = 'button';
              removeBtn.className = 'btn btn-sm btn-outline-danger';
              removeBtn.textContent = 'Remove';

              removeBtn.addEventListener('click', function() {
                row.remove();
                syncHiddenOptions();
              });
              rowInput.addEventListener('input', syncHiddenOptions);

              row.appendChild(rowInput);
              row.appendChild(removeBtn);
              list.appendChild(row);
            };

            const existingItems = parseOptionLines(hiddenInput.value);
            if (existingItems.length) {
              existingItems.forEach(function(item) {
                createOptionRow(item);
              });
            } else {
              createOptionRow('');
            }

            addBtn.addEventListener('click', function() {
              createOptionRow('');
              syncHiddenOptions();
            });

            group.appendChild(label);
            group.appendChild(hiddenInput);
            group.appendChild(help);
            optionsWrap.appendChild(list);
            optionsWrap.appendChild(addBtn);
            group.appendChild(optionsWrap);
            container.appendChild(group);
            syncHiddenOptions();
            return;
          }
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
          if (typeof initRiderCategoriesSortable === 'function') initRiderCategoriesSortable();
        });
    };

    window.refreshRiderTopAccordion = function() {
      fetch("{{ route('settings-panel.rider-settings.rider-top-accordion-body') }}", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(resp) {
          return resp.text();
        })
        .then(function(html) {
          var container = document.getElementById('riderTopAccordionContainer');
          if (container) container.innerHTML = html;
        });
    };

    var baseUrl = "{{ url('') }}";
    var csrf = document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content') || document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value;

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
      if (initialValue) {
        input.value = initialValue;
      }

      var removeBtn = document.createElement('button');
      removeBtn.type = 'button';
      removeBtn.className = 'btn btn-sm btn-outline-danger';
      removeBtn.textContent = 'Remove';
      removeBtn.addEventListener('click', function() {
        row.remove();
      });

      row.appendChild(input);
      row.appendChild(removeBtn);
      rowsWrap.appendChild(row);
    }

    function setRiderTopSelectionMode(mode) {
      var addBtn = document.getElementById('addRiderTopOptionRowBtn');
      var rowsWrap = document.getElementById('addRiderTopOptionRows');
      if (!rowsWrap) return;
      var rows = rowsWrap.querySelectorAll('.rider-top-option-row-input');
      var isMultiple = mode === 'multiple';
      if (addBtn) addBtn.disabled = !isMultiple;
      if (!isMultiple && rows.length > 1) {
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

    var formAddCat = document.getElementById('formAddRiderCategory');
    if (formAddCat) {
      formAddCat.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('addRiderCategorySubmitBtn');
        var fd = new FormData(form);
        if (btn) btn.disabled = true;
        fetch(form.action || "{{ route('settings-panel.rider-settings.store-category') }}", {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(r) {
            if (!r.ok) {
              return r.json().then(function(d) {
                return {
                  _httpError: true,
                  status: r.status,
                  data: d
                };
              }).catch(function() {
                return {
                  _httpError: true,
                  status: r.status
                };
              });
            }
            return r.json();
          })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data._httpError) {
              var msg = (data.data && data.data.message) || 'Server error ('.concat(data.status, ').');
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg
              });
              return;
            }
            if (data.success) {
              if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('addRiderCategoryModal'));
                if (modal) modal.hide();
              }
              form.reset();
              if (typeof window.refreshRiderCategoriesTable === 'function') window.refreshRiderCategoriesTable();
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
          .catch(function(err) {
            if (btn) btn.disabled = false;
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Could not save. Check your connection or try again.'
            });
          });
      });
    }

    var formAddRiderTopCategory = document.getElementById('formAddRiderTopCategory');
    if (formAddRiderTopCategory) {
      formAddRiderTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('addRiderTopCategorySubmitBtn');
        if (btn) btn.disabled = true;
        fetch("{{ route('settings-panel.rider-settings.store-rider-top-category') }}", {
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
            if (btn) btn.disabled = false;
            if (data.success) {
              if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var modal = bootstrap.Modal.getInstance(document.getElementById('addRiderTopCategoryModal'));
                if (modal) modal.hide();
              }
              form.reset();
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || 'Category added.'
              });
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not save.'
            });
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
    }

    document.addEventListener('click', function(e) {
      var addOptionBtn = e.target.closest('.btn-add-rider-top-option');
      if (!addOptionBtn) return;
      var categoryIdInput = document.getElementById('addRiderTopOptionCategoryId');
      var categoryNameEl = document.getElementById('addRiderTopOptionCategoryName');
      var columnNameEl = document.getElementById('addRiderTopOptionColumnName');
      var rowsWrap = document.getElementById('addRiderTopOptionRows');
      var singleModeInput = document.getElementById('riderTopOptionModeSingle');
      if (categoryIdInput) categoryIdInput.value = addOptionBtn.getAttribute('data-category-id') || '';
      if (categoryNameEl) categoryNameEl.textContent = addOptionBtn.getAttribute('data-category-name') || '-';
      if (columnNameEl) columnNameEl.textContent = '-';
      if (singleModeInput) singleModeInput.checked = true;
      if (rowsWrap) {
        rowsWrap.innerHTML = '';
        createRiderTopOptionRow('');
      }
      setRiderTopSelectionMode('single');

      var categoryId = addOptionBtn.getAttribute('data-category-id') || '';
      if (!categoryId) return;
      const fieldValuesUrlTemplate = "{{ route('settings-panel.rider-settings.rider-top-category-field-values', ['id' => '__CID__']) }}";
      const fieldValuesUrl = fieldValuesUrlTemplate.replace('__CID__', categoryId);
      fetch(fieldValuesUrl, {
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
          }
        })
        .then(function(r) {
          return r.json();
        })
        .then(function(data) {
          if (!data.success) {
            setRiderTopOptionSuggestions([]);
            if (columnNameEl) columnNameEl.textContent = '-';
            return;
          }
          if (columnNameEl) columnNameEl.textContent = data.column || '-';
          var values = Array.isArray(data.values) ? data.values : [];
          setRiderTopOptionSuggestions(values);
          if (values.length && rowsWrap) {
            var firstInput = rowsWrap.querySelector('.rider-top-option-row-input');
            if (firstInput && !firstInput.value) {
              firstInput.value = values[0];
            }
          }
        })
        .catch(function() {
          setRiderTopOptionSuggestions([]);
          if (columnNameEl) columnNameEl.textContent = '-';
        });
    });

    var addRiderTopOptionRowBtn = document.getElementById('addRiderTopOptionRowBtn');
    if (addRiderTopOptionRowBtn) {
      addRiderTopOptionRowBtn.addEventListener('click', function() {
        createRiderTopOptionRow('');
      });
    }

    document.addEventListener('change', function(e) {
      var modeInput = e.target.closest('input[name="selection_mode"]');
      if (!modeInput) return;
      setRiderTopSelectionMode(modeInput.value || 'single');
    });

    document.addEventListener('click', function(e) {
      if (
        e.target.closest('.rider-top-visibility-controls') ||
        e.target.closest('.btn-edit-rider-top-category') ||
        e.target.closest('.btn-delete-rider-top-category') ||
        e.target.closest('.btn-edit-rider-top-option') ||
        e.target.closest('.btn-delete-rider-top-option')
      ) {
        e.stopPropagation();
      }
    });

    document.addEventListener('click', function(e) {
      var editCategoryBtn = e.target.closest('.btn-edit-rider-top-category');
      if (!editCategoryBtn) return;
      var idInput = document.getElementById('editRiderTopCategoryId');
      var nameInput = document.getElementById('editRiderTopCategoryName');
      if (idInput) idInput.value = editCategoryBtn.getAttribute('data-category-id') || '';
      if (nameInput) nameInput.value = editCategoryBtn.getAttribute('data-category-name') || '';
      if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = new bootstrap.Modal(document.getElementById('editRiderTopCategoryModal'));
        modal.show();
      }
    });

    document.addEventListener('click', function(e) {
      var editOptionBtn = e.target.closest('.btn-edit-rider-top-option');
      if (!editOptionBtn) return;
      var idInput = document.getElementById('editRiderTopOptionId');
      var nameInput = document.getElementById('editRiderTopOptionName');
      if (idInput) idInput.value = editOptionBtn.getAttribute('data-option-id') || '';
      if (nameInput) nameInput.value = editOptionBtn.getAttribute('data-option-name') || '';
      if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = new bootstrap.Modal(document.getElementById('editRiderTopOptionModal'));
        modal.show();
      }
    });

    document.addEventListener('click', function(e) {
      var deleteCategoryBtn = e.target.closest('.btn-delete-rider-top-category');
      if (!deleteCategoryBtn) return;
      var categoryId = deleteCategoryBtn.getAttribute('data-category-id');
      var categoryName = deleteCategoryBtn.getAttribute('data-category-name') || 'this category';
      if (!categoryId) return;

      var doDelete = function() {
        var fd = new FormData();
        fd.append('_method', 'DELETE');
        const deleteUrlTemplate = "{{ route('settings-panel.rider-settings.destroy-rider-top-category', ['id' => '__CID__']) }}";
        const deleteUrl = deleteUrlTemplate.replace('__CID__', categoryId);
        fetch(deleteUrl, {
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
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Deleted',
                text: data.message || 'Category deleted.'
              });
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not delete category.'
            });
          })
          .catch(function() {
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Could not delete category.'
            });
          });
      };

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: 'Delete category?',
          text: 'This will also remove all options under "' + categoryName + '".',
          showCancelButton: true,
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel'
        }).then(function(result) {
          if (result.isConfirmed) doDelete();
        });
      } else if (confirm('Delete category "' + categoryName + '"?')) {
        doDelete();
      }
    });

    document.addEventListener('click', function(e) {
      var deleteOptionBtn = e.target.closest('.btn-delete-rider-top-option');
      if (!deleteOptionBtn) return;
      var optionId = deleteOptionBtn.getAttribute('data-option-id');
      var optionName = deleteOptionBtn.getAttribute('data-option-name') || 'this option';
      if (!optionId) return;

      var doDelete = function() {
        var fd = new FormData();
        fd.append('_method', 'DELETE');
        const deleteUrlTemplate = "{{ route('settings-panel.rider-settings.destroy-rider-top-option', ['id' => '__OID__']) }}";
        const deleteUrl = deleteUrlTemplate.replace('__OID__', optionId);
        fetch(deleteUrl, {
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
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Deleted',
                text: data.message || 'Option deleted.'
              });
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not delete option.'
            });
          })
          .catch(function() {
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Could not delete option.'
            });
          });
      };

      if (typeof Swal !== 'undefined') {
        Swal.fire({
          icon: 'warning',
          title: 'Delete option?',
          text: 'Delete "' + optionName + '"?',
          showCancelButton: true,
          confirmButtonText: 'Delete',
          cancelButtonText: 'Cancel'
        }).then(function(result) {
          if (result.isConfirmed) doDelete();
        });
      } else if (confirm('Delete option "' + optionName + '"?')) {
        doDelete();
      }
    });

    document.addEventListener('change', function(e) {
      var toggle = e.target.closest('.rider-top-visibility-toggle');
      if (!toggle) return;

      var controls = toggle.closest('.rider-top-visibility-controls');
      var categoryId = controls ? controls.getAttribute('data-category-id') : null;
      if (!categoryId) return;

      var topBarToggle = controls.querySelector('.rider-top-visibility-toggle[data-field="show_in_top_bar"]');
      var viewCardsToggle = controls.querySelector('.rider-top-visibility-toggle[data-field="show_in_view_cards"]');
      var topBarValue = topBarToggle ? (topBarToggle.checked ? 1 : 0) : 0;
      var viewCardsValue = viewCardsToggle ? (viewCardsToggle.checked ? 1 : 0) : 0;

      var fd = new FormData();
      fd.append('show_in_top_bar', String(topBarValue));
      fd.append('show_in_view_cards', String(viewCardsValue));

      const updateVisibilityUrlTemplate = "{{ route('settings-panel.rider-settings.update-rider-top-category-visibility', ['id' => '__CID__']) }}";
      const updateVisibilityUrl = updateVisibilityUrlTemplate.replace('__CID__', categoryId);
      fetch(updateVisibilityUrl, {
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
              text: data.message || 'Could not update display options.'
            });
          }
        })
        .catch(function() {
          if (typeof Swal !== 'undefined') Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not update display options.'
          });
        });
    });

    var formAddRiderTopOption = document.getElementById('formAddRiderTopOption');
    if (formAddRiderTopOption) {
      formAddRiderTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var btn = document.getElementById('addRiderTopOptionSubmitBtn');
        var rowsWrap = document.getElementById('addRiderTopOptionRows');
        var modeInput = document.querySelector('input[name="selection_mode"]:checked');
        var mode = modeInput ? modeInput.value : 'single';
        if (btn) btn.disabled = true;
        var payload = new FormData(form);
        payload.delete('selected_values[]');
        var items = [];
        if (rowsWrap) {
          items = Array.prototype.slice.call(rowsWrap.querySelectorAll('.rider-top-option-row-input'))
            .map(function(el) {
              return (el.value || '').trim();
            })
            .filter(function(v) {
              return v.length > 0;
            });
        }
        if (mode === 'single' && items.length > 1) {
          items = [items[0]];
        }
        items.forEach(function(v) {
          payload.append('selected_values[]', v);
        });
        if (items.length === 0) {
          if (btn) btn.disabled = false;
          if (typeof Swal !== 'undefined') Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Please add at least one option value.'
          });
          return;
        }
        fetch("{{ route('settings-panel.rider-settings.store-rider-top-option') }}", {
            method: 'POST',
            body: payload,
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('addRiderTopOptionModal'));
                if (modal) modal.hide();
              }
              form.reset();
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || 'Option added.'
              });
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not save.'
            });
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
    }

    var formEditRiderTopCategory = document.getElementById('formEditRiderTopCategory');
    if (formEditRiderTopCategory) {
      formEditRiderTopCategory.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var id = document.getElementById('editRiderTopCategoryId') && document.getElementById('editRiderTopCategoryId').value;
        var btn = document.getElementById('editRiderTopCategorySubmitBtn');
        if (!id) return;
        if (btn) btn.disabled = true;

        var fd = new FormData(form);
        fd.append('_method', 'PUT');
        const updateUrlTemplate = "{{ route('settings-panel.rider-settings.update-rider-top-category', ['id' => '__CID__']) }}";
        const updateUrl = updateUrlTemplate.replace('__CID__', id);
        fetch(updateUrl, {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('editRiderTopCategoryModal'));
                if (modal) modal.hide();
              }
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: data.message || 'Category updated.'
              });
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not update category.'
            });
          })
          .catch(function() {
            if (btn) btn.disabled = false;
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Could not update category.'
            });
          });
      });
    }

    var formEditRiderTopOption = document.getElementById('formEditRiderTopOption');
    if (formEditRiderTopOption) {
      formEditRiderTopOption.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var id = document.getElementById('editRiderTopOptionId') && document.getElementById('editRiderTopOptionId').value;
        var btn = document.getElementById('editRiderTopOptionSubmitBtn');
        if (!id) return;
        if (btn) btn.disabled = true;

        var fd = new FormData(form);
        fd.append('_method', 'PUT');
        const updateUrlTemplate = "{{ route('settings-panel.rider-settings.update-rider-top-option', ['id' => '__OID__']) }}";
        const updateUrl = updateUrlTemplate.replace('__OID__', id);
        fetch(updateUrl, {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('editRiderTopOptionModal'));
                if (modal) modal.hide();
              }
              window.refreshRiderTopAccordion();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: data.message || 'Option updated.'
              });
            } else if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not update option.'
            });
          })
          .catch(function() {
            if (btn) btn.disabled = false;
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'Could not update option.'
            });
          });
      });
    }

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
      var editFixedFieldBtn = e.target.closest('.btn-edit-rider-fixed-field');
      if (!editFixedFieldBtn) return;

      var fieldKey = editFixedFieldBtn.getAttribute('data-field-key') || '';
      var fieldLabel = editFixedFieldBtn.getAttribute('data-field-label') || '';
      var categoryId = editFixedFieldBtn.getAttribute('data-category-id') || '';
      var isVisible = editFixedFieldBtn.getAttribute('data-is-visible') || '1';
      var inputType = editFixedFieldBtn.getAttribute('data-input-type') || 'text';
      var inputConfigRaw = editFixedFieldBtn.getAttribute('data-input-config') || '{}';
      var inputConfig = {};
      try {
        inputConfig = JSON.parse(inputConfigRaw);
      } catch (e) {
        inputConfig = {};
      }

      var keyInput = document.getElementById('editRiderFixedFieldKey');
      var keyTextInput = document.getElementById('editRiderFixedFieldKeyText');
      var labelInput = document.getElementById('editRiderFixedFieldLabel');
      var categoryInput = document.getElementById('editRiderFixedFieldCategoryId');
      var visibleInput = document.getElementById('editRiderFixedFieldVisible');
      var typeInput = document.getElementById('editRiderFixedFieldType');
      var fixedConfigWrap = document.getElementById('editRiderFixedConfigOptionsWrap');
      var fixedConfigContainer = document.getElementById('edit-rider-fixed-config-options-fields');

      if (keyInput) keyInput.value = fieldKey;
      if (keyTextInput) keyTextInput.value = fieldKey;
      if (labelInput) labelInput.value = fieldLabel;
      if (categoryInput) categoryInput.value = categoryId;
      if (visibleInput) visibleInput.checked = String(isVisible) === '1';
      if (typeInput) typeInput.value = inputType;
      if (fixedConfigWrap && fixedConfigContainer) {
        const typeMeta = dataTypesMeta[inputType] || {};
        const hasConfig = typeMeta.config && typeMeta.config.length;
        fixedConfigWrap.style.display = hasConfig ? 'block' : 'none';
        buildConfigFields(fixedConfigContainer, inputType, inputConfig);
      }

      if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        var modal = new bootstrap.Modal(document.getElementById('editRiderFixedFieldModal'));
        modal.show();
      }
    });

    document.addEventListener('click', function(e) {
      var editCustomFieldBtn = e.target.closest('.btn-edit-rider-field');
      if (!editCustomFieldBtn) return;

      var editForm = document.getElementById('formEditRiderField');
      if (editForm && editCustomFieldBtn.dataset.updateUrl) {
        editForm.action = editCustomFieldBtn.dataset.updateUrl;
      }
      var idInput = document.getElementById('editRiderFieldId');
      if (idInput) idInput.value = editCustomFieldBtn.dataset.id || '';
      var prevCatInput = document.getElementById('editRiderFieldPreviousCategoryId');
      if (prevCatInput) prevCatInput.value = editCustomFieldBtn.dataset.category_id || '';
      var labelInput = document.getElementById('editRiderFieldLabel');
      if (labelInput) labelInput.value = editCustomFieldBtn.dataset.label || '';
      var categoryInput = document.getElementById('editRiderFieldCategory');
      if (categoryInput) categoryInput.value = editCustomFieldBtn.dataset.category_id || '';
      var typeInput = document.getElementById('editRiderFieldDataType');
      if (typeInput) typeInput.value = editCustomFieldBtn.dataset.data_type || 'text';
      var helpTextInput = document.getElementById('editRiderFieldHelpText');
      if (helpTextInput) helpTextInput.value = editCustomFieldBtn.dataset.help_text || '';
      var defaultValueInput = document.getElementById('editRiderFieldDefaultValue');
      if (defaultValueInput) defaultValueInput.value = editCustomFieldBtn.dataset.default_value || '';
      var inputFormatInput = document.getElementById('editRiderFieldInputFormat');
      if (inputFormatInput) inputFormatInput.value = editCustomFieldBtn.dataset.input_format || '';
      var mandatoryYes = document.getElementById('editRiderMandatoryYes');
      var mandatoryNo = document.getElementById('editRiderMandatoryNo');
      if (mandatoryYes && mandatoryNo) {
        var isMandatory = String(editCustomFieldBtn.dataset.is_mandatory || '0') === '1';
        mandatoryYes.checked = isMandatory;
        mandatoryNo.checked = !isMandatory;
      }
      var dupYes = document.getElementById('editRiderPreventDupYes');
      var dupNo = document.getElementById('editRiderPreventDupNo');
      if (dupYes && dupNo) {
        var preventDup = String(editCustomFieldBtn.dataset.prevent_duplicate_values || '0') === '1';
        dupYes.checked = preventDup;
        dupNo.checked = !preventDup;
      }
      var configInput = document.getElementById('editRiderFieldConfigJson');
      if (configInput) configInput.value = editCustomFieldBtn.dataset.config || '{}';
      if (typeInput && typeof typeInput.dispatchEvent === 'function') {
        typeInput.dispatchEvent(new Event('change'));
      }
    });

    var formEditRiderFixedField = document.getElementById('formEditRiderFixedField');
    if (formEditRiderFixedField) {
      var fixedTypeSelect = document.getElementById('editRiderFixedFieldType');
      var fixedConfigWrap = document.getElementById('editRiderFixedConfigOptionsWrap');
      var fixedConfigContainer = document.getElementById('edit-rider-fixed-config-options-fields');
      if (fixedTypeSelect) {
        fixedTypeSelect.addEventListener('change', function() {
          var typeKey = this.value || '';
          const typeMeta = dataTypesMeta[typeKey] || {};
          const hasConfig = typeMeta.config && typeMeta.config.length;
          if (fixedConfigWrap) fixedConfigWrap.style.display = hasConfig ? 'block' : 'none';
          if (fixedConfigContainer) buildConfigFields(fixedConfigContainer, typeKey, {});
        });
      }

      formEditRiderFixedField.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var submitBtn = document.getElementById('editRiderFixedFieldSubmitBtn');
        var fieldKey = document.getElementById('editRiderFixedFieldKey') && document.getElementById('editRiderFixedFieldKey').value;
        var isVisible = document.getElementById('editRiderFixedFieldVisible') && document.getElementById('editRiderFixedFieldVisible').checked ? 1 : 0;
        if (!fieldKey) return;
        if (submitBtn) submitBtn.disabled = true;

        var assignmentFd = new FormData();
        assignmentFd.append('field_key', fieldKey);
        assignmentFd.append('display_label', (document.getElementById('editRiderFixedFieldLabel') && document.getElementById('editRiderFixedFieldLabel').value) || '');
        assignmentFd.append('category_id', (document.getElementById('editRiderFixedFieldCategoryId') && document.getElementById('editRiderFixedFieldCategoryId').value) || '');
        assignmentFd.append('input_type', (document.getElementById('editRiderFixedFieldType') && document.getElementById('editRiderFixedFieldType').value) || 'text');
        if (fixedConfigContainer) {
          fixedConfigContainer.querySelectorAll('input[name^="config["], textarea[name^="config["], select[name^="config["]').forEach(function(el) {
            if (el.type === 'checkbox') {
              assignmentFd.append(el.name, el.checked ? '1' : '0');
            } else {
              assignmentFd.append(el.name, el.value || '');
            }
          });
        }

        fetch("{{ route('settings-panel.rider-settings.update-field-assignment') }}", {
            method: 'POST',
            body: assignmentFd,
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
            if (!data.success) throw new Error(data.message || 'Could not update field assignment.');

            return fetch('{{ route("settings-panel.rider-settings.update-field-assignment-visibility") }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({
                field_key: fieldKey,
                is_visible: isVisible
              })
            }).then(function(r) {
              return r.json();
            });
          })
          .then(function(data) {
            if (submitBtn) submitBtn.disabled = false;
            if (!data.success) {
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'error',
                title: 'Error',
                text: data.message || 'Could not update visibility.'
              });
              return;
            }

            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
              var modal = bootstrap.Modal.getInstance(document.getElementById('editRiderFixedFieldModal'));
              if (modal) modal.hide();
            }
            if (typeof window.refreshRiderFieldsTableBody === 'function') {
              window.refreshRiderFieldsTableBody();
            } else {
              location.reload();
            }
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'success',
              title: 'Updated',
              text: 'Field settings updated.'
            });
          })
          .catch(function(err) {
            if (submitBtn) submitBtn.disabled = false;
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: (err && err.message) ? err.message : 'Could not update field settings.'
            });
          });
      });
    }

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

      var editDocBtn = e.target.closest('.btn-edit-document-type');
      if (editDocBtn) {
        e.preventDefault();
        var singleWrap = document.getElementById('editDocTypeSingleWrap');
        var dualWrap = document.getElementById('editDocTypeDualWrap');
        document.getElementById('editDocTypeId').value = editDocBtn.dataset.id || '';
        document.getElementById('editDocTypeKey').value = editDocBtn.dataset.key || '';
        document.getElementById('editDocTypeType').value = editDocBtn.dataset.type || 'single';
        document.getElementById('editDocTypeLabel').value = editDocBtn.dataset.label || '';
        document.getElementById('editDocTypeFrontLabel').value = editDocBtn.dataset.frontLabel || '';
        document.getElementById('editDocTypeBackLabel').value = editDocBtn.dataset.backLabel || '';
        document.getElementById('editDocTypeActive').checked = editDocBtn.dataset.active === '1';
        if (singleWrap) singleWrap.style.display = (editDocBtn.dataset.type === 'dual') ? 'none' : 'block';
        if (dualWrap) dualWrap.style.display = (editDocBtn.dataset.type === 'dual') ? 'block' : 'none';
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          new bootstrap.Modal(document.getElementById('editRiderDocumentTypeModal')).show();
        }
      }
    });

    // Rider Documents: refresh table body
    window.refreshRiderDocumentTypesTable = function() {
      fetch("{{ route('settings-panel.rider-settings.document-types-table-body') }}", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(r) {
          return r.text();
        })
        .then(function(html) {
          var tbody = document.getElementById('riderDocumentTypesTbody');
          if (tbody) tbody.innerHTML = html;
          if (typeof initRiderDocumentTypesSortable === 'function') initRiderDocumentTypesSortable();
        });
    };

    // Add document type: type toggle
    var addDocTypeType = document.getElementById('addDocTypeType');
    if (addDocTypeType) {
      addDocTypeType.addEventListener('change', function() {
        var isDual = this.value === 'dual';
        document.getElementById('addDocTypeSingleWrap').style.display = isDual ? 'none' : 'block';
        document.getElementById('addDocTypeDualWrap').style.display = isDual ? 'block' : 'none';
      });
    }
    var editDocTypeType = document.getElementById('editDocTypeType');
    if (editDocTypeType) {
      editDocTypeType.addEventListener('change', function() {
        var isDual = this.value === 'dual';
        document.getElementById('editDocTypeSingleWrap').style.display = isDual ? 'none' : 'block';
        document.getElementById('editDocTypeDualWrap').style.display = isDual ? 'block' : 'none';
      });
    }

    // Add document type form
    var formAddRiderDocumentType = document.getElementById('formAddRiderDocumentType');
    if (formAddRiderDocumentType) {
      formAddRiderDocumentType.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        fd.set('is_active', form.querySelector('#addDocTypeActive').checked ? '1' : '0');
        var btn = document.getElementById('addRiderDocumentTypeSubmitBtn');
        if (btn) btn.disabled = true;
        fetch("{{ route('settings-panel.rider-settings.store-document-type') }}", {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(r) {
            if (!r.ok) return r.json().then(function(d) {
              return {
                _httpError: true,
                status: r.status,
                data: d
              };
            }).catch(function() {
              return {
                _httpError: true,
                status: r.status
              };
            });
            return r.json();
          })
          .then(function(data) {
            if (btn) btn.disabled = false;
            if (data._httpError) {
              var msg = (data.data && data.data.message) || 'Server error.';
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'error',
                title: 'Error',
                text: msg
              });
              return;
            }
            if (data.success) {
              if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var m = bootstrap.Modal.getInstance(document.getElementById('addRiderDocumentTypeModal'));
                if (m) m.hide();
              }
              form.reset();
              document.getElementById('addDocTypeSingleWrap').style.display = 'block';
              document.getElementById('addDocTypeDualWrap').style.display = 'none';
              document.getElementById('addDocTypeActive').checked = true;
              window.refreshRiderDocumentTypesTable();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Saved',
                text: data.message || 'Document type added.'
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
    }

    // Edit document type form
    var formEditRiderDocumentType = document.getElementById('formEditRiderDocumentType');
    if (formEditRiderDocumentType) {
      formEditRiderDocumentType.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var id = document.getElementById('editDocTypeId').value;
        var fd = new FormData(form);
        fd.set('_method', 'PUT');
        fd.set('is_active', form.querySelector('#editDocTypeActive').checked ? '1' : '0');
        fetch(baseUrl + '/settings-panel/rider-settings/documents/' + id, {
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
                var m = bootstrap.Modal.getInstance(document.getElementById('editRiderDocumentTypeModal'));
                if (m) m.hide();
              }
              window.refreshRiderDocumentTypesTable();
              if (typeof Swal !== 'undefined') Swal.fire({
                icon: 'success',
                title: 'Updated',
                text: data.message || 'Document type updated.'
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

    // Delete document type
    document.addEventListener('submit', function(e) {
      var form = e.target.closest('.btn-delete-document-type');
      if (!form || form.tagName !== 'FORM') return;
      e.preventDefault();
      var msg = form.getAttribute('data-confirm') || 'Delete this document type?';
      if (!confirm(msg)) return;
      var action = form.getAttribute('action');
      var fd = new FormData(form);
      fetch(action, {
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
            window.refreshRiderDocumentTypesTable();
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'success',
              title: 'Deleted',
              text: data.message || 'Document type deleted.'
            });
          } else {
            if (typeof Swal !== 'undefined') Swal.fire({
              icon: 'error',
              title: 'Error',
              text: data.message || 'Could not delete.'
            });
          }
        })
        .catch(function() {
          if (typeof Swal !== 'undefined') Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Could not delete.'
          });
        });
    });

    // Sortable for rider document types
    function initRiderDocumentTypesSortable() {
      var tbody = document.getElementById('riderDocumentTypesTbody');
      if (!tbody || !window.Sortable) return;
      if (tbody._sortable) return;
      tbody._sortable = window.Sortable.create(tbody, {
        handle: '.drag-handle',
        animation: 150,
        onEnd: function() {
          var order = [];
          tbody.querySelectorAll('tr[data-id]').forEach(function(tr) {
            order.push(parseInt(tr.getAttribute('data-id'), 10));
          });
          var fd = new FormData();
          fd.append('_token', csrf);
          order.forEach(function(id) {
            fd.append('order[]', id);
          });
          fetch("{{ route('settings-panel.rider-settings.reorder-document-types') }}", {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          }).then(function() {});
        }
      });
    }
    if (document.getElementById('riderDocumentTypesTbody')) initRiderDocumentTypesSortable();

    // Sortable for rider categories (manual reorder)
    var riderCategoriesSortable = null;
    function initRiderCategoriesSortable() {
      var tbody = document.getElementById('riderCategoriesTbody');
      if (!tbody || typeof Sortable === 'undefined') return;
      if (riderCategoriesSortable) riderCategoriesSortable.destroy();
      var rows = tbody.querySelectorAll('tr[data-id]');
      if (rows.length < 1) return;
      riderCategoriesSortable = new Sortable(tbody, {
        handle: '.drag-handle',
        draggable: 'tr[data-id]',
        animation: 150,
        ghostClass: 'table-warning',
        forceFallback: true,
        fallbackOnBody: true,
        fallbackTolerance: 3,
        onEnd: function() {
          var order = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(tr) {
            return parseInt(tr.getAttribute('data-id'), 10);
          });
          fetch("{{ route('settings-panel.rider-settings.reorder-categories') }}", {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
              },
              body: JSON.stringify({
                order: order
              })
            })
            .then(function(r) {
              return r.json().catch(function() {
                return {
                  success: false
                };
              });
            })
            .then(function(data) {
              if (data.success) {
                tbody.querySelectorAll('tr[data-id] td:nth-child(2)').forEach(function(td, i) {
                  td.textContent = i + 1;
                });
                if (typeof Swal !== 'undefined') Swal.fire({
                  toast: true,
                  position: 'top-end',
                  icon: 'success',
                  title: 'Category order saved.',
                  showConfirmButton: false,
                  timer: 2000
                });
              } else if (typeof Swal !== 'undefined') Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: data.message || 'Could not save category order.',
                showConfirmButton: false,
                timer: 3000
              });
            })
            .catch(function() {
              if (typeof Swal !== 'undefined') Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'error',
                title: 'Could not save category order.',
                showConfirmButton: false,
                timer: 3000
              });
            });
        }
      });
    }
    if (document.getElementById('riderCategoriesTbody')) initRiderCategoriesSortable();

    // Open tab from URL ?tab=rider-fields
    (function() {
      var params = new URLSearchParams(window.location.search);
      var tab = params.get('tab');
      if (tab === 'rider-fields' && document.getElementById('tab-rider-fields-btn')) {
        var tabEl = new bootstrap.Tab(document.getElementById('tab-rider-fields-btn'));
        tabEl.show();
      }
    })();

    // New custom fields always start as unassigned.

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
              if (categoryId) {
                window.refreshRiderCustomFieldsCategory(categoryId);
              } else {
                window.location.href = "{{ route('settings-panel.rider-settings.index') }}?tab=rider-fields";
                return;
              }
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
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
              field_key: fieldKey,
              display_label: newLabel
            })
          })
          .then(function(r) {
            return r.json();
          })
          .then(function(data) {
            if (data.success && data.label !== undefined) {
              labelEl.textContent = data.label;
              delete labelEl.dataset.pendingLabel;
              if (typeof Swal !== 'undefined') Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: 'Name updated.',
                showConfirmButton: false,
                timer: 1500
              });
            }
          })
          .catch(function() {
            if (labelEl.dataset.pendingLabel) labelEl.textContent = labelEl.dataset.pendingLabel;
          });
      }

      input.addEventListener('blur', saveAndRevert);
      input.addEventListener('keydown', function(ev) {
        if (ev.key === 'Enter') {
          ev.preventDefault();
          input.blur();
        }
        if (ev.key === 'Escape') {
          input.value = currentText;
          input.blur();
        }
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
          draggable: 'tr[data-id]',
          animation: 150,
          ghostClass: 'table-warning',
          forceFallback: true,
          fallbackOnBody: true,
          fallbackTolerance: 3,
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
              .then(function(r) {
                return r.json().catch(function() {
                  return {
                    success: false
                  };
                });
              })
              .then(function(data) {
                if (data.success) {
                  tbody.querySelectorAll('tr[data-id] .rider-custom-field-index').forEach(function(td, i) {
                    td.textContent = i + 1;
                  });
                  if (typeof Swal !== 'undefined') Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Order saved.',
                    showConfirmButton: false,
                    timer: 2000
                  });
                } else if (typeof Swal !== 'undefined') Swal.fire({
                  toast: true,
                  position: 'top-end',
                  icon: 'error',
                  title: data.message || 'Could not save order.',
                  showConfirmButton: false,
                  timer: 3000
                });
              })
              .catch(function() {
                if (typeof Swal !== 'undefined') Swal.fire({
                  toast: true,
                  position: 'top-end',
                  icon: 'error',
                  title: 'Could not save order.',
                  showConfirmButton: false,
                  timer: 3000
                });
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
          draggable: 'tr[data-field-key]',
          filter: 'input,select,textarea,button,a,label,.form-check-input,.form-check-label',
          preventOnFilter: false,
          animation: 150,
          ghostClass: 'table-warning',
          chosenClass: 'table-active',
          forceFallback: true,
          fallbackOnBody: true,
          fallbackTolerance: 3,
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
              .then(function(r) {
                return r.json().catch(function() {
                  return {
                    success: false
                  };
                });
              })
              .then(function(data) {
                if (data.success) {
                  var idx = 1;
                  tbody.querySelectorAll('tr[data-field-key] .rider-field-index').forEach(function(td) {
                    td.textContent = idx++;
                  });
                  if (typeof Swal !== 'undefined') Swal.fire({
                    toast: true,
                    position: 'top-end',
                    icon: 'success',
                    title: 'Order saved.',
                    showConfirmButton: false,
                    timer: 2000
                  });
                } else if (typeof Swal !== 'undefined') Swal.fire({
                  toast: true,
                  position: 'top-end',
                  icon: 'error',
                  title: (data && data.message) || 'Could not save order.',
                  showConfirmButton: false,
                  timer: 3000
                });
              })
              .catch(function() {
                if (typeof Swal !== 'undefined') Swal.fire({
                  toast: true,
                  position: 'top-end',
                  icon: 'error',
                  title: 'Could not save order.',
                  showConfirmButton: false,
                  timer: 3000
                });
              });
          }
        });
        riderFieldSortables.push(sortable);
      });
    }
    document.addEventListener('change', function(e) {
      var toggle = e.target.closest('.rider-field-visibility-toggle');
      if (!toggle) return;

      var fieldKey = toggle.getAttribute('data-field-key');
      var isVisible = toggle.checked ? 1 : 0;
      var row = toggle.closest('tr');

      console.log('Toggle clicked for field:', fieldKey, 'New visibility:', isVisible);

      var csrf = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || (document.querySelector('.rider-field-assignment-form input[name="_token"]') && document.querySelector('.rider-field-assignment-form input[name="_token"]').value);

      if (!csrf) {
        console.error('CSRF token not found');
        if (typeof Swal !== 'undefined') Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'CSRF token missing.',
          showConfirmButton: false,
          timer: 3000
        });
        toggle.checked = !toggle.checked;
        return;
      }

      console.log('CSRF token found, sending request...');

      var formBody = new URLSearchParams();
      formBody.append('_token', csrf);
      formBody.append('field_key', fieldKey);
      formBody.append('is_visible', String(isVisible));

      fetch('{{ route("settings-panel.rider-settings.update-field-assignment-visibility") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formBody.toString()
        })
        .then(function(r) {
          console.log('Response status:', r.status, r.statusText);
          return r.json().then(function(data) {
            console.log('Response data:', data);
            return r.ok ? data : Promise.reject(data);
          });
        })
        .then(function(data) {
          console.log('Success:', data);
          if (row) row.classList.toggle('table-secondary', !data.is_visible);
          if (typeof Swal !== 'undefined') Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: data.message || 'Saved.',
            showConfirmButton: false,
            timer: 2000
          });
        })
        .catch(function(err) {
          console.error('Error:', err);
          toggle.checked = !toggle.checked;
          if (row) row.classList.toggle('table-secondary', !toggle.checked);

          var errorMsg = 'Could not update.';
          if (err && err.message) {
            errorMsg = err.message;
          } else if (err && typeof err === 'object') {
            errorMsg = JSON.stringify(err);
          }

          if (typeof Swal !== 'undefined') Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: errorMsg,
            showConfirmButton: false,
            timer: 5000
          });
        });
    });

    document.addEventListener('change', function(e) {
      var toggle = e.target.closest('.rider-field-required-toggle');
      if (!toggle) return;

      var fieldKey = toggle.getAttribute('data-field-key');
      var isRequired = toggle.checked ? 1 : 0;
      var csrf = (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || (document.querySelector('.rider-field-assignment-form input[name="_token"]') && document.querySelector('.rider-field-assignment-form input[name="_token"]').value);

      if (!csrf) {
        if (typeof Swal !== 'undefined') Swal.fire({
          toast: true,
          position: 'top-end',
          icon: 'error',
          title: 'CSRF token missing.',
          showConfirmButton: false,
          timer: 3000
        });
        toggle.checked = !toggle.checked;
        return;
      }

      var formBody = new URLSearchParams();
      formBody.append('_token', csrf);
      formBody.append('field_key', fieldKey);
      formBody.append('is_required', String(isRequired));

      fetch('{{ route("settings-panel.rider-settings.update-field-assignment-required") }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: formBody.toString()
        })
        .then(function(r) {
          return r.json().then(function(data) {
            return r.ok ? data : Promise.reject(data);
          });
        })
        .then(function(data) {
          if (typeof Swal !== 'undefined') Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'success',
            title: data.message || 'Saved.',
            showConfirmButton: false,
            timer: 2000
          });
        })
        .catch(function(err) {
          toggle.checked = !toggle.checked;

          var errorMsg = 'Could not update.';
          if (err && err.message) {
            errorMsg = err.message;
          } else if (err && typeof err === 'object') {
            errorMsg = JSON.stringify(err);
          }

          if (typeof Swal !== 'undefined') Swal.fire({
            toast: true,
            position: 'top-end',
            icon: 'error',
            title: errorMsg,
            showConfirmButton: false,
            timer: 5000
          });
        });
    });

    document.getElementById('tab-rider-fields-btn') && document.getElementById('tab-rider-fields-btn').addEventListener('shown.bs.tab', function() {
      setTimeout(initRiderFieldSortables, 50);
      setTimeout(initRiderCustomFieldsSortables, 80);
    });
    document.querySelectorAll('#riderFieldsCategoryTabs [data-bs-toggle="tab"]').forEach(function(tabBtn) {
      tabBtn.addEventListener('shown.bs.tab', function() {
        setTimeout(initRiderFieldSortables, 40);
        setTimeout(initRiderCustomFieldsSortables, 70);
      });
    });
    if (document.getElementById('tab-rider-fields').classList.contains('show')) {
      setTimeout(initRiderFieldSortables, 100);
      setTimeout(initRiderCustomFieldsSortables, 150);
    }
  })();
</script>

@endsection