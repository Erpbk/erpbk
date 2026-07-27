@extends('layouts.settingsPanelLayout')

@section('title', 'Employee Settings – Site Settings')

@push('third_party_stylesheets')
<style>
  .employee-settings-table th {
    font-weight: 600;
    white-space: nowrap;
  }

  .employee-settings-table .drag-handle {
    cursor: grab;
    color: #697a8d;
  }

  .employee-settings-table .drag-handle:active {
    cursor: grabbing;
  }

  .employee-settings-table tr.badge-soft-primary {
    background: rgba(105, 108, 255, 0.08);
  }

  #employeeConfigOptionsContainer .form-group,
  #addEmployeeFieldConfigFields .form-group,
  #edit-employee-config-options-fields .form-group {
    margin-bottom: 0.75rem;
  }

  #employeeConfigOptionsContainer label,
  #addEmployeeFieldConfigFields label,
  #edit-employee-config-options-fields label {
    font-weight: 500;
    font-size: 0.875rem;
  }

  .add-employee-field-form .form-text {
    font-size: 0.8125rem;
  }

  .employee-fields-sortable-tbody .drag-handle {
    cursor: grab;
  }

  .employee-fields-sortable-tbody .drag-handle:active {
    cursor: grabbing;
  }

  .nav-tabs-employee-fields .nav-link {
    padding: 0.5rem 0.75rem;
  }

  /* Keep Select2 dropdown above Bootstrap modal/backdrop. */
  .select2-container--open {
    z-index: 2000 !important;
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
          <h4 class="card-title mb-0">Employee Settings</h4>
          <p class="text-muted small mb-0 mt-1">
            Manage employee categories (add, edit, reorder). Fixed employee fields and custom fields are grouped by category; open the Employee Fields tab and use each category sub-tab to manage and reorder fields.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>
{{-- Main content: tabs General | Categories | Employee Fields --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs nav-tabs-employee-settings mb-3" id="employeeSettingsMainTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-categories-btn" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">Categories</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-employee-fields-btn" data-bs-toggle="tab" data-bs-target="#tab-employee-fields" type="button" role="tab">Employee Fields</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-employee-top-btn" data-bs-toggle="tab" data-bs-target="#tab-employee-top" type="button" role="tab">Employee Top</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-employee-status-btn" data-bs-toggle="tab" data-bs-target="#tab-employee-status" type="button" role="tab">Employee Status</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-employee-documents-btn" data-bs-toggle="tab" data-bs-target="#tab-employee-documents" type="button" role="tab">Employee Documents</button>
          </li>

        </ul>

        <div class="tab-content" id="employeeSettingsTabContent">
          {{-- Tab 1: General (module name in menu) --}}
          <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            @include('settings.partials._module_general_label_form', [
              'settingsRoutePrefix' => 'settings-panel.employee-settings',
              'settingsRouteParams' => ['company_slug' => request()->route('company_slug') ?? session('company_slug')],
              'moduleMenuKey' => 'employees',
              'moduleLabel' => $moduleLabel ?? null,
              'defaultLabel' => 'Employees',
            ])
            @include('settings.partials._module_menu_icon_form', [
              'settingsRoutePrefix' => 'settings-panel.employee-settings',
              'settingsRouteParams' => ['company_slug' => request()->route('company_slug') ?? session('company_slug')],
              'moduleMenuKey' => 'employees',
              'defaultLabel' => 'Employees',
            ])
          </div>

          {{-- Tab 2: Categories --}}
          <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Add, edit, reorder employee categories. Custom categories can be deleted if they have no custom fields.</p>
              <button type="button" class="btn btn-primary btn-sm" id="btnAddEmployeeCategory" data-bs-toggle="modal" data-bs-target="#addEmployeeCategoryModal">
                <i class="ti ti-plus me-1"></i> Add Category
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover employee-settings-table mb-0">
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
                <tbody id="employeeCategoriesTbody">
                  @include('settings.employee_settings._categories_tbody', ['categories' => $categories])
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab 3: Employee Documents (dynamic document types for employee files page) --}}
          <div class="tab-pane fade" id="tab-employee-documents" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Define document types required for employees. Single = one file per type (e.g. Profile Photo). Dual = front and back page (e.g. Passport first/second). Key is used to match uploaded file names.</p>
              <button type="button" class="btn btn-primary btn-sm" id="btnAddEmployeeDocumentType" data-bs-toggle="modal" data-bs-target="#addEmployeeDocumentTypeModal">
                <i class="ti ti-plus me-1"></i> Add Document Type
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover employee-settings-table mb-0">
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
                <tbody id="employeeDocumentTypesTbody" class="employee-document-types-sortable-tbody">
                  @include('settings.employee_settings._document_types_tbody', ['documentTypes' => $documentTypes])
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab 5: Employee Top --}}
          <div class="tab-pane fade" id="tab-employee-top" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Create a Employee Top category first, then add multiple options under each category.</p>
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addRiderTopCategoryModal">
                <i class="ti ti-plus me-1"></i> Add Category
              </button>
            </div>
            <div id="riderTopAccordionContainer">
              @include('settings.employee_settings._employee_top_accordion', ['employeeTopCategories' => $employeeTopCategories])
            </div>
          </div>

          {{-- Tab 6: Employee Status --}}
          <div class="tab-pane fade" id="tab-employee-status" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Manage employee statuses in one place. Changes here stay synced with employee records (`status`).</p>
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addEmployeeStatusModal">
                <i class="ti ti-plus me-1"></i> Add Status
              </button>
            </div>
            <div class="table-responsive">
              <table class="table table-hover employee-settings-table mb-0">
                <thead class="table-light">
                  <tr>
                    <th>#</th>
                    <th>Status</th>
                    <th class="text-center">Top Bar</th>
                    <th class="text-center">View Card</th>
                    <th class="text-end" style="width: 120px;">Actions</th>
                  </tr>
                </thead>
                <tbody id="employeeStatusTbody">
                  @include('settings.employee_settings._employee_status_rows', ['employeeStatusOptions' => $employeeStatusOptions ?? collect()])
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab 4: Employee Fields (all-fields + category tabs) --}}
          <div class="tab-pane fade" id="tab-employee-fields" role="tabpanel">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">Use the static <b>All Fields</b> tab to assign fields. Category tabs show assigned fields category-wise.</p>
              <button type="button" class="btn btn-primary btn-sm" id="btnAddCustomFieldFromTop" data-bs-toggle="modal" data-bs-target="#addEmployeeFieldModal">
                <i class="ti ti-plus me-1"></i> Add Custom Field
              </button>
            </div>
            <ul class="nav nav-tabs nav-tabs-employee-fields mb-3" id="employeeFieldsCategoryTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="employee-fields-all-tab" data-bs-toggle="tab" data-bs-target="#employee-fields-all-pane" type="button" role="tab">
                  All Fields
                  <span class="badge bg-label-primary ms-1">{{ count($allFixedFieldsForStatic ?? []) }}</span>
                </button>
              </li>
              @foreach($fieldsByCategory as $idx => $group)
              @php
              $categoryCustomFields = ($customFieldsByCategory ?? collect())->get($group->category->id, collect());
              @endphp
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="employee-cat-{{ $group->category->id }}-tab" data-bs-toggle="tab" data-bs-target="#employee-field-cat-{{ $group->category->id }}" type="button" role="tab">
                  {{ $group->category->label }}
                  <span class="badge bg-label-info ms-1 employee-cat-badge-custom">{{ count($group->fields) + $categoryCustomFields->count() }}</span>
                </button>
              </li>
              @endforeach
            </ul>

            <div class="tab-content" id="employeeFieldsCategoryTabContent">
              <div class="tab-pane fade show active" id="employee-fields-all-pane" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover employee-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Field</th>
                        <th>Current category</th>
                        <th>Move to category</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="employee-fields-tbody-all">
                      @forelse(($allFixedFieldsForStatic ?? []) as $rowIndex => $row)
                      <tr data-field-key="{{ $row->field_key }}" data-field-label="{{ $row->label }}" data-category-id="{{ $row->category_id ?? '' }}" data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}" data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}" data-input-type="{{ $row->input_type ?? 'text' }}" data-input-config='@json($row->input_config ?? [])' class="{{ !($row->is_visible ?? true) ? 'table-secondary' : '' }}">
                        <td class="align-middle">{{ $rowIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="employee-fixed-field-label d-inline-block align-middle" data-field-key="{{ $row->field_key }}" title="Click to edit name">{{ $row->label }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                        </td>
                        <td class="align-middle">
                          @if(!empty($row->category_label))
                          <span class="badge bg-label-info">{{ $row->category_label }}</span>
                          @else
                          <span class="badge bg-label-warning">Unassigned</span>
                          @endif
                        </td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.employee-settings.update-field-assignment') }}" method="POST" class="d-flex justify-content-center employee-field-assignment-form">
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
                          <button type="button" class="btn btn-sm btn-outline-primary btn-edit-employee-fixed-field"
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
                        <td colspan="5" class="text-center text-muted py-3">No employee fields found.</td>
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
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.employee-settings.assign-custom-field-category', ['id' => $customField->id]) }}" method="POST" class="d-flex justify-content-center">
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
                          <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-employee-field"
                              data-id="{{ $customField->id }}"
                              data-label="{{ $customField->label }}"
                              data-help_text="{{ $customField->help_text }}"
                              data-data_type="{{ $customField->data_type }}"
                              data-is_mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                              data-is_visible="{{ ($customField->is_visible ?? true) ? 1 : 0 }}"
                              data-prevent_duplicate_values="{{ $customField->prevent_duplicate_values ? 1 : 0 }}"
                              data-default_value="{{ $customField->default_value }}"
                              data-input_format="{{ $customField->input_format }}"
                              data-config='@json($customField->config)'
                              data-category_id="{{ $customField->category_id ?? '' }}"
                              data-update-url="{{ route('settings-panel.employee-settings.update-field', ['id' => $customField->id]) }}"
                              data-bs-toggle="modal" data-bs-target="#editEmployeeFieldModal">
                              <i class="ti ti-pencil"></i>
                            </button>
                            <form method="POST"
                              class="d-inline employee-destroy-field-form"
                              action="{{ route('settings-panel.employee-settings.destroy-field', ['id' => $customField->id]) }}"
                              data-category-id="{{ $customField->category_id ?? '' }}">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-outline-danger btn-destroy-employee-field">
                                <i class="ti ti-trash"></i>
                              </button>
                            </form>
                          </div>
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
              <div class="tab-pane fade" id="employee-field-cat-{{ $group->category->id }}" role="tabpanel" data-category-id="{{ $group->category->id }}">
                <div class="table-responsive">
                  <table class="table table-hover employee-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 36px;"></th>
                        <th>#</th>
                        <th>Field</th>
                        <th>Move to category</th>
                        <th class="text-end" style="width: 120px;">Actions</th>
                      </tr>
                    </thead>
                    <tbody id="employee-fields-tbody-{{ $group->category->id }}" class="employee-fields-sortable-tbody">
                      @foreach($group->fields as $rowIndex => $row)
                      <tr data-field-key="{{ $row->field_key }}" data-field-label="{{ $row->label }}" data-category-id="{{ $group->category->id }}" data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}" data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}" data-input-type="{{ $row->input_type ?? 'text' }}" data-input-config='@json($row->input_config ?? [])' class="{{ !($row->is_visible ?? true) ? 'table-secondary' : '' }}">
                        <td class="align-middle"><span class="drag-handle cursor-grab"><i class="ti ti-grip-vertical"></i></span></td>
                        <td class="align-middle employee-field-index">{{ $rowIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="employee-fixed-field-label d-inline-block align-middle" data-field-key="{{ $row->field_key }}" title="Click to edit name">{{ $row->label }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                        </td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.employee-settings.update-field-assignment') }}" method="POST" class="d-flex justify-content-center employee-field-assignment-form">
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
                          <button type="button" class="btn btn-sm btn-outline-primary btn-edit-employee-fixed-field"
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
                      @if($fixedCount === 0 && $categoryCustomFields->isEmpty())
                      <tr>
                        <td colspan="5" class="text-center text-muted py-3">No fields in this category.</td>
                      </tr>
                      @endif
                    </tbody>
                    <tbody id="employee-custom-fields-tbody-{{ $group->category->id }}" class="employee-custom-fields-sortable-tbody" data-category-id="{{ $group->category->id }}">
                      @foreach($categoryCustomFields as $customIndex => $customField)
                      <tr class="table-light" data-id="{{ $customField->id }}">
                        <td class="align-middle"><span class="drag-handle cursor-grab"><i class="ti ti-grip-vertical"></i></span></td>
                        <td class="align-middle employee-custom-field-index">{{ $fixedCount + $customIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $customField->label }}</span>
                          <span class="badge bg-label-secondary ms-1">Custom</span>
                        </td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.employee-settings.assign-custom-field-category', ['id' => $customField->id]) }}" method="POST" class="d-flex justify-content-center">
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
                          <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary btn-edit-employee-field"
                              data-id="{{ $customField->id }}"
                              data-label="{{ $customField->label }}"
                              data-help_text="{{ $customField->help_text }}"
                              data-data_type="{{ $customField->data_type }}"
                              data-is_mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                              data-is_visible="{{ ($customField->is_visible ?? true) ? 1 : 0 }}"
                              data-prevent_duplicate_values="{{ $customField->prevent_duplicate_values ? 1 : 0 }}"
                              data-default_value="{{ $customField->default_value }}"
                              data-input_format="{{ $customField->input_format }}"
                              data-config='@json($customField->config)'
                              data-category_id="{{ $group->category->id }}"
                              data-update-url="{{ route('settings-panel.employee-settings.update-field', ['id' => $customField->id]) }}"
                              data-bs-toggle="modal" data-bs-target="#editEmployeeFieldModal">
                              <i class="ti ti-pencil"></i>
                            </button>
                            <form method="POST"
                              class="d-inline employee-destroy-field-form"
                              action="{{ route('settings-panel.employee-settings.destroy-field', ['id' => $customField->id]) }}"
                              data-category-id="{{ $group->category->id }}">
                              @csrf
                              @method('DELETE')
                              <button type="submit" class="btn btn-sm btn-outline-danger btn-destroy-employee-field">
                                <i class="ti ti-trash"></i>
                              </button>
                            </form>
                          </div>
                        </td>
                      </tr>
                      @endforeach
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
<div class="modal fade" id="addEmployeeFieldModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add New Rider Field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddEmployeeField" action="{{ route('settings-panel.employee-settings.store-field') }}" method="POST">
        @csrf
        <div class="modal-body pt-0">
          <div class="add-employee-field-form">
            <div class="mb-3">
              <label class="form-label">Label Name <span class="text-danger">*</span></label>
              <input type="text" name="label" class="form-control" placeholder="e.g. Emergency Contact" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Category</label>
              @php $otherEmployeeCategory = ($categories ?? collect())->firstWhere('slug', 'other'); @endphp
              <input type="text" class="form-control" value="{{ $otherEmployeeCategory?->label ?? 'Other' }}" disabled>
              <input type="hidden" name="category_id" value="{{ $otherEmployeeCategory?->id ?? '' }}">
              <p class="form-text mb-0">New custom fields are added under <b>{{ $otherEmployeeCategory?->label ?? 'Other' }}</b>. You can move them to another category from the Employee Fields tab.</p>
            </div>
            <div class="mb-3">
              <label class="form-label d-flex align-items-center gap-1">
                Data Type <span class="text-danger">*</span>
              </label>
              <select name="data_type" class="form-select" id="addEmployeeFieldDataType" required>
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
            <div id="addEmployeeFieldOptionsContainer" style="display: none;">
              <div class="mb-3" id="addEmployeeFieldHelpTextWrap">
                <label class="form-label">Help Text</label>
                <input type="text" name="help_text" class="form-control" placeholder="Optional help for users">
              </div>
              <div class="mb-3" id="addEmployeeFieldDataPrivacyWrap">
                <label class="form-label">Data Privacy</label>
                <div class="d-flex gap-4">
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_pii" value="1" class="form-check-input" id="addEmployeeFieldPii">
                    <label class="form-check-label" for="addEmployeeFieldPii">PII</label>
                  </div>
                  <div class="form-check">
                    <input type="checkbox" name="data_privacy_ephi" value="1" class="form-check-input" id="addEmployeeFieldEphi">
                    <label class="form-check-label" for="addEmployeeFieldEphi">ePHI</label>
                  </div>
                </div>
              </div>
              <div class="mb-3" id="addEmployeeFieldPreventDupWrap">
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
              <div class="mb-3" id="addEmployeeFieldDefaultValueWrap">
                <label class="form-label">Default Value</label>
                <input type="text" name="default_value" class="form-control" placeholder="Default value">
              </div>
              <div class="mb-3" id="addEmployeeFieldInputFormatWrap" style="display: none;">
                <label class="form-label">Input Format</label>
                <input type="text" name="input_format" class="form-control" placeholder="e.g. email format">
              </div>
              <div class="mb-3" id="addEmployeeFieldConfigOptionsWrap" style="display: none;">
                <label class="form-label small text-uppercase text-muted">Configuration options</label>
                <div id="addEmployeeFieldConfigFields"></div>
              </div>
            </div>
            <input type="hidden" name="is_mandatory" value="0">
            <input type="hidden" name="is_visible" value="1">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addEmployeeFieldSubmitBtn">Save Field</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Rider Category modal --}}
<div class="modal fade" id="addEmployeeCategoryModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddEmployeeCategory" action="{{ route('settings-panel.employee-settings.store-category') }}" method="POST">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" class="form-control" placeholder="e.g. Documents" required maxlength="255">
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary" id="addEmployeeCategorySubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Add Employee Status modal --}}
<div class="modal fade" id="addEmployeeStatusModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Employee Status</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('settings-panel.employee-settings.store-employee-status') }}" method="POST">
        @csrf
        <div class="modal-body pt-0">
          <div class="mb-3">
            <label class="form-label" for="newRiderStatusName">Status Name <span class="text-danger">*</span></label>
            <input type="text" name="name" id="newRiderStatusName" class="form-control" placeholder="e.g. Absconder" required maxlength="255">
          </div>
          <div class="mb-3">
            <input type="hidden" name="show_in_top_bar" value="0">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="show_in_top_bar" id="newRiderStatusTopBar" value="1" checked>
              <label class="form-check-label" for="newRiderStatusTopBar">Show in Top Bar</label>
            </div>
          </div>
          <div class="mb-0">
            <input type="hidden" name="show_in_view_cards" value="0">
            <div class="form-check form-switch">
              <input class="form-check-input" type="checkbox" name="show_in_view_cards" id="newRiderStatusViewCard" value="1" checked>
              <label class="form-check-label" for="newRiderStatusViewCard">Show in View Card</label>
            </div>
          </div>
        </div>
        <div class="modal-footer border-0 pt-0">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Add Status</button>
        </div>
      </form>
    </div>
  </div>
</div>

@include('settings.employee_settings._edit_employee_status_modal')

{{-- Edit Rider Category modal --}}
<div class="modal fade" id="editEmployeeCategoryModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Category</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditEmployeeCategory">
        <input type="hidden" name="id" id="editEmployeeCategoryId">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" id="editEmployeeCategoryLabel" class="form-control" required maxlength="255">
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
            <input type="hidden" id="editRiderFixedFieldVisibleHidden" value="1">
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
              <div id="edit-employee-fixed-config-options-fields"></div>
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

@php
  use App\Support\ModuleTopBarRoutes;
  $topBarRoutes = ModuleTopBarRoutes::resolve('employees');
@endphp
@include('settings.partials.top_bar.modals', [
  'topBarTabLabel' => 'Employee Top',
  'topBarColumnField' => 'employee_column',
  'topBarColumnLabel' => 'Employee Column',
  'topBarSelectableColumns' => $employeeTopSelectableColumns ?? [],
])
@include('settings.partials.top_bar.scripts', ['topBarRoutes' => $topBarRoutes])
{{-- Add Rider Document Type modal --}}
<div class="modal fade" id="addEmployeeDocumentTypeModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Add Document Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formAddEmployeeDocumentType">
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
          <button type="submit" class="btn btn-primary" id="addEmployeeDocumentTypeSubmitBtn">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- Edit Rider Document Type modal --}}
<div class="modal fade" id="editEmployeeDocumentTypeModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit Document Type</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditEmployeeDocumentType">
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
<div class="modal fade" id="editEmployeeFieldModal" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header border-0 pb-0">
        <h5 class="modal-title">Edit employee custom field</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form id="formEditEmployeeField" method="POST" action="">
        <div class="modal-body pt-0">
          <input type="hidden" name="id" id="editEmployeeFieldId">
          <input type="hidden" id="editEmployeeFieldPreviousCategoryId" value="">
          @csrf
          @method('PUT')
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label">Label Name <span class="text-danger">*</span></label>
              <input type="text" name="label" id="editEmployeeFieldLabel" class="form-control" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Category <span class="text-danger">*</span></label>
              <select name="category_id" id="editEmployeeFieldCategory" class="form-select" required>
                <option value="">Select category</option>
                @foreach($categories as $cat)
                <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Data Type <span class="text-danger">*</span></label>
              <select name="data_type" id="editEmployeeFieldDataType" class="form-select" required>
                @foreach($dataTypes as $typeKey => $typeMeta)
                <option value="{{ $typeKey }}">{{ $typeMeta['label'] }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Help Text</label>
              <input type="text" name="help_text" id="editEmployeeFieldHelpText" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Default Value</label>
              <input type="text" name="default_value" id="editEmployeeFieldDefaultValue" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Input Format</label>
              <input type="text" name="input_format" id="editEmployeeFieldInputFormat" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Data Privacy</label>
              <div class="d-flex gap-4 mt-2">
                <div class="form-check">
                  <input type="checkbox" name="data_privacy_pii" value="1" class="form-check-input" id="editEmployeeFieldPii">
                  <label class="form-check-label" for="editEmployeeFieldPii">PII</label>
                </div>
                <div class="form-check">
                  <input type="checkbox" name="data_privacy_ephi" value="1" class="form-check-input" id="editEmployeeFieldEphi">
                  <label class="form-check-label" for="editEmployeeFieldEphi">ePHI</label>
                </div>
              </div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Prevent Duplicate Values</label>
              <div class="d-flex gap-3 mt-2">
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
            <input type="hidden" name="is_mandatory" value="0">
            <input type="hidden" name="is_visible" value="1">
            <div class="col-12" id="editRiderConfigOptionsWrap" style="display: none;">
              <label class="form-label small text-uppercase text-muted">Configuration options</label>
              <div id="edit-employee-config-options-fields"></div>
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

<input type="hidden" id="editEmployeeFieldConfigJson" value="{}">
<input type="hidden" id="employeeDataTypesMetaJson" value='@json($dataTypes)'>
@endsection
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
  (function() {
    'use strict';

    const dataTypesMeta = JSON.parse((document.getElementById('employeeDataTypesMetaJson') && document.getElementById('employeeDataTypesMetaJson').value) || '{}');

    function getEmployeeCsrf() {
      return (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) ||
        (document.querySelector('.employee-field-assignment-form input[name="_token"]') && document.querySelector('.employee-field-assignment-form input[name="_token"]').value) ||
        (document.querySelector('#formEditEmployeeStatus input[name="_token"]') && document.querySelector('#formEditEmployeeStatus input[name="_token"]').value) ||
        (document.querySelector('input[name="_token"]') && document.querySelector('input[name="_token"]').value) ||
        '';
    }

    var csrf = getEmployeeCsrf();

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
    const addTypeSelect = document.getElementById('addEmployeeFieldDataType');
    const addOptionsContainer = document.getElementById('addEmployeeFieldOptionsContainer');
    const addConfigContainer = document.getElementById('addEmployeeFieldConfigFields');
    const addConfigWrap = document.getElementById('addEmployeeFieldConfigOptionsWrap');
    const addInputFormatWrap = document.getElementById('addEmployeeFieldInputFormatWrap');

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
    const editTypeSelect = document.getElementById('editEmployeeFieldDataType');
    const editConfigContainer = document.getElementById('edit-employee-config-options-fields');
    const editConfigWrap = document.getElementById('editRiderConfigOptionsWrap');

    if (editTypeSelect) {
      editTypeSelect.addEventListener('change', function() {
        const typeKey = this.value;
        if (!typeKey) {
          editConfigWrap.style.display = 'none';
          editConfigContainer.innerHTML = '';
          return;
        }

        const fieldConfigInput = document.getElementById('editEmployeeFieldConfigJson');
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

    window.refreshEmployeeCustomFieldsCategory = function(categoryId) {
      var tbody = document.getElementById('employee-custom-fields-tbody-' + categoryId);
      if (!tbody) return;
      var tableBodyCategoryUrlTemplate = "{{ route('settings-panel.employee-settings.table-body-category', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'categoryId' => '__ID__']) }}";
      var url = tableBodyCategoryUrlTemplate.replace('__ID__', String(categoryId));
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
          var badge = document.querySelector('.employee-cat-badge-custom[data-category-id="' + categoryId + '"]');
          if (badge) {
            var rows = tbody.querySelectorAll('tr[data-id]');
            badge.textContent = rows.length;
          }
          if (typeof initEmployeeCustomFieldsSortables === 'function') initEmployeeCustomFieldsSortables();
        });
    };

    window.refreshRiderCategoriesTable = function() {
      fetch("{{ route('settings-panel.employee-settings.categories-table-body') }}", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(resp) {
          return resp.text();
        })
        .then(function(html) {
          const tbody = document.getElementById('employeeCategoriesTbody');
          if (tbody) tbody.innerHTML = html;
          if (typeof initRiderCategoriesSortable === 'function') initRiderCategoriesSortable();
        });
    };

    document.getElementById('formEditEmployeeCategory').addEventListener('submit', function(e) {
      e.preventDefault();
      var form = this;
      var id = form.querySelector('#editEmployeeCategoryId').value;
      var fd = new FormData(form);
      fd.set('_method', 'PUT');
      var updateCategoryUrlTemplate = "{{ route('settings-panel.employee-settings.update-category', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'id' => '__ID__']) }}";
      fetch(updateCategoryUrlTemplate.replace('__ID__', String(id)), {
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
              var m = bootstrap.Modal.getInstance(document.getElementById('editEmployeeCategoryModal'));
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
      var editFixedFieldBtn = e.target.closest('.btn-edit-employee-fixed-field');
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
      var typeInput = document.getElementById('editRiderFixedFieldType');
      var fixedConfigWrap = document.getElementById('editRiderFixedConfigOptionsWrap');
      var fixedConfigContainer = document.getElementById('edit-employee-fixed-config-options-fields');

      if (keyInput) keyInput.value = fieldKey;
      if (keyTextInput) keyTextInput.value = fieldKey;
      if (labelInput) labelInput.value = fieldLabel;
      if (categoryInput) categoryInput.value = categoryId;
      var visibleHiddenEl = document.getElementById('editRiderFixedFieldVisibleHidden');
      if (visibleHiddenEl) visibleHiddenEl.value = String(isVisible) === '1' ? '1' : '0';
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
      var editCustomFieldBtn = e.target.closest('.btn-edit-employee-field');
      if (!editCustomFieldBtn) return;

      var editForm = document.getElementById('formEditEmployeeField');
      if (editForm && editCustomFieldBtn.dataset.updateUrl) {
        editForm.action = editCustomFieldBtn.dataset.updateUrl;
      }
      var idInput = document.getElementById('editEmployeeFieldId');
      if (idInput) idInput.value = editCustomFieldBtn.dataset.id || '';
      var prevCatInput = document.getElementById('editEmployeeFieldPreviousCategoryId');
      if (prevCatInput) prevCatInput.value = editCustomFieldBtn.dataset.category_id || '';
      var labelInput = document.getElementById('editEmployeeFieldLabel');
      if (labelInput) labelInput.value = editCustomFieldBtn.dataset.label || '';
      var categoryInput = document.getElementById('editEmployeeFieldCategory');
      if (categoryInput) categoryInput.value = editCustomFieldBtn.dataset.category_id || '';
      var typeInput = document.getElementById('editEmployeeFieldDataType');
      if (typeInput) typeInput.value = editCustomFieldBtn.dataset.data_type || 'text';
      var helpTextInput = document.getElementById('editEmployeeFieldHelpText');
      if (helpTextInput) helpTextInput.value = editCustomFieldBtn.dataset.help_text || '';
      var defaultValueInput = document.getElementById('editEmployeeFieldDefaultValue');
      if (defaultValueInput) defaultValueInput.value = editCustomFieldBtn.dataset.default_value || '';
      var inputFormatInput = document.getElementById('editEmployeeFieldInputFormat');
      if (inputFormatInput) inputFormatInput.value = editCustomFieldBtn.dataset.input_format || '';
      var dupYes = document.getElementById('editRiderPreventDupYes');
      var dupNo = document.getElementById('editRiderPreventDupNo');
      if (dupYes && dupNo) {
        var preventDup = String(editCustomFieldBtn.dataset.prevent_duplicate_values || '0') === '1';
        dupYes.checked = preventDup;
        dupNo.checked = !preventDup;
      }
      var configInput = document.getElementById('editEmployeeFieldConfigJson');
      if (configInput) configInput.value = editCustomFieldBtn.dataset.config || '{}';
      if (typeInput && typeof typeInput.dispatchEvent === 'function') {
        typeInput.dispatchEvent(new Event('change'));
      }
    });

    var formEditRiderFixedField = document.getElementById('formEditRiderFixedField');
    if (formEditRiderFixedField) {
      var fixedTypeSelect = document.getElementById('editRiderFixedFieldType');
      var fixedConfigWrap = document.getElementById('editRiderFixedConfigOptionsWrap');
      var fixedConfigContainer = document.getElementById('edit-employee-fixed-config-options-fields');
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
        var isVisible = document.getElementById('editRiderFixedFieldVisibleHidden') ? parseInt(document.getElementById('editRiderFixedFieldVisibleHidden').value, 10) : 1;
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

        fetch("{{ route('settings-panel.employee-settings.update-field-assignment') }}", {
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

            return fetch('{{ route("settings-panel.employee-settings.update-field-assignment-visibility") }}', {
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
            location.reload();
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
        document.getElementById('editEmployeeCategoryId').value = editBtn.dataset.id || '';
        document.getElementById('editEmployeeCategoryLabel').value = editBtn.dataset.label || '';
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
          new bootstrap.Modal(document.getElementById('editEmployeeCategoryModal')).show();
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
          new bootstrap.Modal(document.getElementById('editEmployeeDocumentTypeModal')).show();
        }
      }
    });

    // Employee Documents: refresh table body
    window.refreshEmployeeDocumentTypesTable = function() {
      fetch("{{ route('settings-panel.employee-settings.document-types-table-body') }}", {
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        })
        .then(function(r) {
          return r.text();
        })
        .then(function(html) {
          var tbody = document.getElementById('employeeDocumentTypesTbody');
          if (tbody) tbody.innerHTML = html;
          if (typeof initEmployeeDocumentTypesSortable === 'function') initEmployeeDocumentTypesSortable();
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
    var formAddEmployeeDocumentType = document.getElementById('formAddEmployeeDocumentType');
    if (formAddEmployeeDocumentType) {
      formAddEmployeeDocumentType.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        fd.set('is_active', form.querySelector('#addDocTypeActive').checked ? '1' : '0');
        var btn = document.getElementById('addEmployeeDocumentTypeSubmitBtn');
        if (btn) btn.disabled = true;
        fetch("{{ route('settings-panel.employee-settings.store-document-type') }}", {
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
                var m = bootstrap.Modal.getInstance(document.getElementById('addEmployeeDocumentTypeModal'));
                if (m) m.hide();
              }
              form.reset();
              document.getElementById('addDocTypeSingleWrap').style.display = 'block';
              document.getElementById('addDocTypeDualWrap').style.display = 'none';
              document.getElementById('addDocTypeActive').checked = true;
              window.refreshEmployeeDocumentTypesTable();
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
    var formEditEmployeeDocumentType = document.getElementById('formEditEmployeeDocumentType');
    if (formEditEmployeeDocumentType) {
      formEditEmployeeDocumentType.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var id = document.getElementById('editDocTypeId').value;
        var fd = new FormData(form);
        fd.set('_method', 'PUT');
        fd.set('is_active', form.querySelector('#editDocTypeActive').checked ? '1' : '0');
        var updateDocumentUrlTemplate = "{{ route('settings-panel.employee-settings.update-document-type', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'id' => '__ID__']) }}";
        fetch(updateDocumentUrlTemplate.replace('__ID__', String(id)), {
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
                var m = bootstrap.Modal.getInstance(document.getElementById('editEmployeeDocumentTypeModal'));
                if (m) m.hide();
              }
              window.refreshEmployeeDocumentTypesTable();
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
            window.refreshEmployeeDocumentTypesTable();
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

    // Sortable for employee document types
    function initEmployeeDocumentTypesSortable() {
      var tbody = document.getElementById('employeeDocumentTypesTbody');
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
          fetch("{{ route('settings-panel.employee-settings.reorder-document-types') }}", {
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
    if (document.getElementById('employeeDocumentTypesTbody')) initEmployeeDocumentTypesSortable();

    // Sortable for employee categories (manual reorder)
    var employeeCategoriesSortable = null;

    function initRiderCategoriesSortable() {
      var tbody = document.getElementById('employeeCategoriesTbody');
      if (!tbody || typeof Sortable === 'undefined') return;
      if (employeeCategoriesSortable) employeeCategoriesSortable.destroy();
      var rows = tbody.querySelectorAll('tr[data-id]');
      if (rows.length < 1) return;
      employeeCategoriesSortable = new Sortable(tbody, {
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
          fetch("{{ route('settings-panel.employee-settings.reorder-categories') }}", {
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
    if (document.getElementById('employeeCategoriesTbody')) initRiderCategoriesSortable();

    // Open tab from URL ?tab=employee-fields
    (function() {
      var params = new URLSearchParams(window.location.search);
      var tab = params.get('tab');
      if (tab === 'employee-fields' && document.getElementById('tab-employee-fields-btn')) {
        var tabEl = new bootstrap.Tab(document.getElementById('tab-employee-fields-btn'));
        tabEl.show();
      } else if (tab === 'employee-status' && document.getElementById('tab-employee-status-btn')) {
        var statusTabEl = new bootstrap.Tab(document.getElementById('tab-employee-status-btn'));
        statusTabEl.show();
      }
    })();

    // Add custom field form: submit via AJAX and refresh the category tbody
    var formAddEmployeeField = document.getElementById('formAddEmployeeField');
    if (formAddEmployeeField) {
      formAddEmployeeField.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var fd = new FormData(form);
        var categoryId = form.querySelector('select[name="category_id"]') && form.querySelector('select[name="category_id"]').value;
        var submitBtn = document.getElementById('addEmployeeFieldSubmitBtn');
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('addEmployeeFieldModal'));
                if (modal) modal.hide();
              }
              form.reset();
              if (categoryId) {
                window.refreshEmployeeCustomFieldsCategory(categoryId);
              } else {
                window.location.href = "{{ route('settings-panel.employee-settings.index') }}?tab=employee-fields";
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
    var formEditEmployeeField = document.getElementById('formEditEmployeeField');
    if (formEditEmployeeField) {
      formEditEmployeeField.addEventListener('submit', function(e) {
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
                var modal = bootstrap.Modal.getInstance(document.getElementById('editEmployeeFieldModal'));
                if (modal) modal.hide();
              }
              var prevCatId = form.querySelector('#editEmployeeFieldPreviousCategoryId') && form.querySelector('#editEmployeeFieldPreviousCategoryId').value;
              if (prevCatId) window.refreshEmployeeCustomFieldsCategory(prevCatId);
              if (categoryId) window.refreshEmployeeCustomFieldsCategory(categoryId);
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

    // Move fixed field to another category
    document.addEventListener('submit', function(e) {
      var form = e.target.closest('.employee-field-assignment-form');
      if (!form) return;
      e.preventDefault();
      var categorySelect = form.querySelector('select[name="category_id"]');
      var categoryId = categorySelect && categorySelect.value;
      if (!categoryId) {
        if (typeof Swal !== 'undefined') Swal.fire({
          icon: 'warning',
          title: 'Select a category',
          text: 'Choose a category before clicking Move.'
        });
        return;
      }
      var moveBtn = form.querySelector('button[type="submit"]');
      if (moveBtn) moveBtn.disabled = true;
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
          return r.json().then(function(data) {
            return r.ok ? data : Promise.reject(data);
          });
        })
        .then(function(data) {
          window.location.href = "{{ route('settings-panel.employee-settings.index') }}?tab=employee-fields";
        })
        .catch(function(err) {
          if (moveBtn) moveBtn.disabled = false;
          var msg = (err && err.message) ? err.message : 'Could not move field to the selected category.';
          if (err && err.errors) {
            var firstKey = Object.keys(err.errors)[0];
            if (firstKey && err.errors[firstKey] && err.errors[firstKey][0]) {
              msg = err.errors[firstKey][0];
            }
          }
          if (typeof Swal !== 'undefined') Swal.fire({
            icon: 'error',
            title: 'Error',
            text: msg
          });
        });
    });

    // Delete custom field: AJAX and refresh that category tbody
    document.addEventListener('submit', function(e) {
      var form = e.target.closest('.employee-destroy-field-form');
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
          if (data.success && categoryId) {
            window.refreshEmployeeCustomFieldsCategory(categoryId);
          } else if (data.success) {
            window.location.href = "{{ route('settings-panel.employee-settings.index') }}?tab=employee-fields";
            return;
          }
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

    // Inline edit fixed employee field display name (click label to edit)
    document.addEventListener('click', function(e) {
      var labelEl = e.target.closest('.employee-fixed-field-label');
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
        fetch("{{ route('settings-panel.employee-settings.update-field-assignment-label') }}", {
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
    var employeeCustomFieldSortables = [];

    function initEmployeeCustomFieldsSortables() {
      employeeCustomFieldSortables.forEach(function(s) {
        if (s) s.destroy();
      });
      employeeCustomFieldSortables = [];
      document.querySelectorAll('.employee-custom-fields-sortable-tbody').forEach(function(tbody) {
        var rows = tbody.querySelectorAll('tr[data-id]');
        if (rows.length < 1) return;
        var categoryId = tbody.getAttribute('data-category-id');
        if (!categoryId || typeof Sortable === 'undefined') return;
        var sortable = new Sortable(tbody, {
          handle: '.drag-handle',
          draggable: 'tr[data-id]',
          filter: 'input,select,textarea,button,a,label,.form-check-input,.form-check-label',
          preventOnFilter: false,
          animation: 150,
          ghostClass: 'table-warning',
          chosenClass: 'table-active',
          forceFallback: true,
          fallbackOnBody: true,
          fallbackTolerance: 3,
          onEnd: function() {
            var order = Array.from(tbody.querySelectorAll('tr[data-id]'))
              .map(function(tr) {
                return parseInt(tr.getAttribute('data-id'), 10);
              })
              .filter(function(id) {
                return Number.isFinite(id) && id > 0;
              });
            if (!order.length) return;
            fetch("{{ route('settings-panel.employee-settings.reorder-fields') }}", {
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
                  tbody.querySelectorAll('tr[data-id] .employee-custom-field-index').forEach(function(td, i) {
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
        employeeCustomFieldSortables.push(sortable);
      });
    }

    // Drag-and-drop reorder for Employee Fields (per category tbody)
    var employeeFieldSortables = [];

    function initEmployeeFieldSortables() {
      employeeFieldSortables.forEach(function(s) {
        if (s) s.destroy();
      });
      employeeFieldSortables = [];
      document.querySelectorAll('.employee-fields-sortable-tbody').forEach(function(tbody) {
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
            fetch("{{ route('settings-panel.employee-settings.reorder-field-assignments') }}", {
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
                  tbody.querySelectorAll('tr[data-field-key] .employee-field-index').forEach(function(td) {
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
        employeeFieldSortables.push(sortable);
      });
    }
    // Required/Visible toggles removed; field visibility/required now managed in Role Permissions.

    function getRiderStatusCsrf() {
      return getEmployeeCsrf();
    }

    function applyRiderStatusRowData(row, data) {
      if (!row || !data) return;
      row.setAttribute('data-status-name', data.name || '');
      var nameEl = row.querySelector('.employee-status-name');
      if (nameEl) nameEl.textContent = data.name || '';
      var editBtn = row.querySelector('.btn-edit-employee-status');
      if (editBtn) {
        editBtn.setAttribute('data-name', data.name || '');
        editBtn.setAttribute('data-show-in-top-bar', data.show_in_top_bar ? '1' : '0');
        editBtn.setAttribute('data-show-in-view-cards', data.show_in_view_cards ? '1' : '0');
      }
      var topToggle = row.querySelector('.employee-status-top-bar-toggle');
      var viewToggle = row.querySelector('.employee-status-view-card-toggle');
      if (topToggle) topToggle.checked = !!data.show_in_top_bar;
      if (viewToggle) viewToggle.checked = !!data.show_in_view_cards;
    }

    function saveRiderStatusRow(row, updateUrl) {
      var csrf = getRiderStatusCsrf();
      if (!row || !updateUrl || !csrf) return Promise.reject();

      var topToggle = row.querySelector('.employee-status-top-bar-toggle');
      var viewToggle = row.querySelector('.employee-status-view-card-toggle');
      var body = new URLSearchParams();
      body.append('_token', csrf);
      body.append('_method', 'PUT');
      body.append('name', row.getAttribute('data-status-name') || '');
      body.append('show_in_top_bar', topToggle && topToggle.checked ? '1' : '0');
      body.append('show_in_view_cards', viewToggle && viewToggle.checked ? '1' : '0');

      return fetch(updateUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-CSRF-TOKEN': csrf,
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: body.toString()
        })
        .then(function(r) {
          return r.json().then(function(data) {
            return r.ok ? data : Promise.reject(data);
          });
        })
        .then(function(data) {
          if (data && data.success) {
            applyRiderStatusRowData(row, data);
          }
          return data;
        });
    }

    document.addEventListener('change', function(e) {
      var toggle = e.target.closest('.employee-status-top-bar-toggle, .employee-status-view-card-toggle');
      if (!toggle) return;
      var row = toggle.closest('tr[data-id]');
      var updateUrl = toggle.getAttribute('data-update-url');
      if (!row || !updateUrl) return;

      saveRiderStatusRow(row, updateUrl).catch(function(err) {
        toggle.checked = !toggle.checked;
        if (typeof Swal !== 'undefined') {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: (err && err.message) ? err.message : 'Could not update status.'
          });
        }
      });
    });

    document.addEventListener('click', function(e) {
      var editBtn = e.target.closest('.btn-edit-employee-status');
      if (!editBtn) return;
      document.getElementById('editEmployeeStatusId').value = editBtn.getAttribute('data-id') || '';
      document.getElementById('editEmployeeStatusName').value = editBtn.getAttribute('data-name') || '';
      document.getElementById('editEmployeeStatusTopBar').checked = editBtn.getAttribute('data-show-in-top-bar') === '1';
      document.getElementById('editEmployeeStatusViewCard').checked = editBtn.getAttribute('data-show-in-view-cards') === '1';
    });

    var formEditEmployeeStatus = document.getElementById('formEditEmployeeStatus');
    if (formEditEmployeeStatus) {
      formEditEmployeeStatus.addEventListener('submit', function(e) {
        e.preventDefault();
        var form = this;
        var id = document.getElementById('editEmployeeStatusId').value;
        var csrf = getRiderStatusCsrf();
        if (!id || !csrf) return;

        var fd = new FormData(form);
        fd.set('_method', 'PUT');
        fd.set('show_in_top_bar', document.getElementById('editEmployeeStatusTopBar').checked ? '1' : '0');
        fd.set('show_in_view_cards', document.getElementById('editEmployeeStatusViewCard').checked ? '1' : '0');

        var updateStatusUrlTemplate = "{{ route('settings-panel.employee-settings.update-employee-status', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'id' => '__ID__']) }}";
        fetch(updateStatusUrlTemplate.replace('__ID__', String(id)), {
            method: 'POST',
            body: fd,
            headers: {
              'X-CSRF-TOKEN': csrf,
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(function(r) {
            return r.json().then(function(data) {
              return r.ok ? data : Promise.reject(data);
            });
          })
          .then(function(data) {
            if (data && data.success) {
              var row = document.querySelector('#employeeStatusTbody tr[data-id="' + id + '"]');
              if (row) applyRiderStatusRowData(row, data);
              if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                var m = bootstrap.Modal.getInstance(document.getElementById('editEmployeeStatusModal'));
                if (m) m.hide();
              }
              if (typeof Swal !== 'undefined') {
                Swal.fire({
                  icon: 'success',
                  title: 'Updated',
                  text: data.message || 'Employee status updated.',
                  timer: 2000,
                  showConfirmButton: false
                });
              }
            }
          })
          .catch(function(err) {
            if (typeof Swal !== 'undefined') {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: (err && err.message) ? err.message : 'Could not update status.'
              });
            }
          });
      });
    }

    document.getElementById('tab-employee-fields-btn') && document.getElementById('tab-employee-fields-btn').addEventListener('shown.bs.tab', function() {
      setTimeout(initEmployeeFieldSortables, 50);
      setTimeout(initEmployeeCustomFieldsSortables, 80);
    });
    document.querySelectorAll('#employeeFieldsCategoryTabs [data-bs-toggle="tab"]').forEach(function(tabBtn) {
      tabBtn.addEventListener('shown.bs.tab', function() {
        setTimeout(initEmployeeFieldSortables, 40);
        setTimeout(initEmployeeCustomFieldsSortables, 70);
      });
    });
    if (document.getElementById('tab-employee-fields').classList.contains('show')) {
      setTimeout(initEmployeeFieldSortables, 100);
      setTimeout(initEmployeeCustomFieldsSortables, 150);
    }
  })();
</script>

@endsection
