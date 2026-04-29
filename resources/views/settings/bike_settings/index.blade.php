@extends('layouts.settingsPanelLayout')

@section('title', 'Bike Settings – Site Settings')

@section('content')
@include('flash::message')

@php
$activeCategoryId = (int) (request()->query('active_category_id', 0));
$showBikeFieldsMainTab = request()->query->has('active_category_id');
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h4 class="card-title mb-0">Bike Settings</h4>
          <p class="text-muted small mb-0 mt-1">
            Configure bike fixed/custom fields and document types. Bike has no "on top" / "view on card" controls.
          </p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="bikeSettingsMainTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showBikeFieldsMainTab ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">
              General
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">
              Categories
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link {{ $showBikeFieldsMainTab ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-bike-fields" type="button" role="tab">
              Bike Fields
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">
              Documents
            </button>
          </li>
        </ul>

        <div class="tab-content">
          {{-- Tab: General --}}
          <div class="tab-pane fade {{ $showBikeFieldsMainTab ? '' : 'show active' }}" id="tab-general" role="tabpanel">
            <form action="{{ route('settings-panel.bike-settings.store-module-label') }}" method="POST" class="row g-3 align-items-end">
              @csrf
              <div class="col-md-6">
                <label class="form-label">Name in menu</label>
                <input type="text" name="module_label" class="form-control"
                  value="{{ old('module_label', $moduleLabel ?? 'Bike Settings') }}"
                  placeholder="Bike Settings" maxlength="100" required>
              </div>
              <div class="col-md-6 text-end">
                <button class="btn btn-primary" type="submit">Save name</button>
              </div>
            </form>
          </div>

          {{-- Tab: Categories --}}
          <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <div class="card mb-4">
              <div class="card-body">
                <form action="{{ route('settings-panel.bike-settings.store-category') }}" method="POST" class="row g-3 align-items-end">
                  @csrf
                  <div class="col-md-8">
                    <label class="form-label">New category label</label>
                    <input type="text" name="label" class="form-control" required maxlength="255" placeholder="e.g. Safety / Location / etc.">
                  </div>
                  <div class="col-md-4 text-end">
                    <button class="btn btn-primary" type="submit">Add category</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th style="width: 35%;">Label</th>
                    <th style="width: 15%;">System</th>
                    <th style="width: 50%;">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($categories as $cat)
                  <tr>
                    <td>{{ $cat->label }}</td>
                    <td>{!! $cat->is_system ? '<span class="badge bg-secondary">Yes</span>' : '<span class="badge bg-light text-dark border">No</span>' !!}</td>
                    <td>
                      @if(!$cat->is_system)
                      <form action="{{ route('settings-panel.bike-settings.update-category', $cat->id) }}" method="POST" class="d-inline-flex gap-2 align-items-center">
                        @csrf
                        @method('PUT')
                        <input type="text" name="label" value="{{ $cat->label }}" required maxlength="255" class="form-control form-control-sm" style="max-width: 260px">
                        <button class="btn btn-sm btn-primary" type="submit"><i class="ti ti-pencil"></i></button>
                      </form>

                      <form action="{{ route('settings-panel.bike-settings.destroy-category', $cat->id) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this category?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-danger" type="submit"><i class="ti ti-trash"></i></button>
                      </form>
                      @else
                      <span class="text-muted">Not editable</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                  @if($categories->isEmpty())
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">No categories configured.</td>
                  </tr>
                  @endif
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab: Bike Fields --}}
          <div class="tab-pane fade {{ $showBikeFieldsMainTab ? 'show active' : '' }}" id="tab-bike-fields" role="tabpanel">
            <div class="d-flex justify-content-end align-items-center mb-3">
              <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addBikeFieldModal">
                <i class="ti ti-plus me-1"></i> Add Custom Field
              </button>
            </div>

            <div class="modal fade" id="addBikeFieldModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                  <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title">Add New Bike Field</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>

                  <form id="formAddBikeField" action="{{ route('settings-panel.bike-settings.store-field') }}" method="POST">
                    @csrf
                    <div class="modal-body pt-0">
                      <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                          <label class="form-label">Label</label>
                          <input type="text" name="label" class="form-control" required maxlength="255">
                        </div>

                        <div class="col-md-3">
                          <label class="form-label">Data Type</label>
                          <select name="data_type" class="form-select" required>
                            @foreach($dataTypes as $typeKey => $spec)
                            <option value="{{ $typeKey }}">{{ $spec['label'] }}</option>
                            @endforeach
                          </select>
                        </div>

                        <div class="col-md-3">
                          <label class="form-label">Category</label>
                          <select name="category_id" class="form-select">
                            <option value="">Unassigned</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                            @endforeach
                          </select>
                        </div>

                        <div class="col-md-2">
                          <div class="form-check mt-4">
                            <input type="hidden" name="is_mandatory" value="0">
                            <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="bikeFieldIsMandatoryModal">
                            <label class="form-check-label" for="bikeFieldIsMandatoryModal">Mandatory</label>
                          </div>
                        </div>

                        <div class="col-md-12">
                          <label class="form-label">Help Text</label>
                          <input type="text" name="help_text" class="form-control" maxlength="1000">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Default Value</label>
                          <input type="text" name="default_value" class="form-control" maxlength="500">
                        </div>

                        <div class="col-md-6">
                          <label class="form-label">Dropdown Options (one per line)</label>
                          <input type="hidden" name="config_options" id="addBikeFieldConfigOptionsHidden" value="">
                          <div id="addBikeFieldOptionsRows" class="d-flex flex-column gap-2"></div>
                          <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="addBikeFieldOptionRowBtn">Add Option</button>
                        </div>
                      </div>
                    </div>

                    <div class="modal-footer">
                      <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                      <button class="btn btn-primary" type="submit">Add custom field</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
              <p class="text-muted small mb-0">
                Fixed fields come from <code>bike_field_category_assignments</code>. Custom fields come from <code>bike_custom_fields</code>.
                Use <b>All Fields</b> to move fields, and category tabs show their current assignments.
              </p>
            </div>

            <ul class="nav nav-tabs nav-tabs-rider-fields mb-3" id="bikeFieldsCategoryTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $activeCategoryId === 0 ? 'active' : '' }}"
                  id="bike-fields-all-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#bike-fields-all-pane"
                  type="button"
                  role="tab">
                  All Fields
                  <span class="badge bg-label-primary ms-1">{{ count($fixedAssignments ?? []) + count($customFields ?? []) }}</span>
                </button>
              </li>

              @foreach($categories as $cat)
              @php
              $fixedCount = count($fixedAssignmentsByCategory[$cat->id] ?? collect());
              $customCount = count(($customFieldsByCategory[$cat->id] ?? collect()));
              $tabActive = $activeCategoryId === (int)$cat->id;
              @endphp
              <li class="nav-item" role="presentation">
                <button class="nav-link {{ $tabActive ? 'active' : '' }}"
                  id="bike-cat-{{ $cat->id }}-tab"
                  data-bs-toggle="tab"
                  data-bs-target="#bike-field-cat-{{ $cat->id }}"
                  type="button"
                  role="tab">
                  {{ $cat->label }}
                  <span class="badge bg-label-info ms-1">{{ $fixedCount + $customCount }}</span>
                </button>
              </li>
              @endforeach
            </ul>

            <div class="tab-content">
              {{-- All Fields --}}
              <div class="tab-pane fade {{ $activeCategoryId === 0 ? 'show active' : '' }}"
                id="bike-fields-all-pane" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover bike-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 60px;">#</th>
                        <th>Field</th>
                        <th>Current category</th>
                        <th class="text-center">Required</th>
                        <th class="text-center">Show in form</th>
                        <th>Move to category</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @php
                      $fixedList = $fixedAssignments ?? collect();
                      $fixedOffset = 0;
                      @endphp
                      @foreach($fixedList as $rowIndex => $row)
                      @php
                      $fieldLabel = $row->display_label ? $row->display_label : \App\Models\BikeCustomField::humanizeFieldKey($row->field_key);
                      $categoryLabel = $row->category?->label ?? '';
                      @endphp
                      <tr>
                        <td class="align-middle">{{ $rowIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $fieldLabel }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                        </td>
                        <td class="align-middle">
                          <span class="badge bg-label-info">{{ $categoryLabel }}</span>
                        </td>
                        <td class="align-middle text-center">{{ ($row->is_required ?? false) ? 'Yes' : 'No' }}</td>
                        <td class="align-middle text-center">{{ ($row->is_visible ?? true) ? 'Yes' : 'No' }}</td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.bike-settings.update-field-assignment') }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <input type="hidden" name="field_key" value="{{ $row->field_key }}">
                            <input type="hidden" name="display_label" value="{{ $row->display_label }}">
                            <input type="hidden" name="is_visible" value="{{ ($row->is_visible ?? true) ? 1 : 0 }}">
                            <input type="hidden" name="is_required" value="{{ ($row->is_required ?? false) ? 1 : 0 }}">
                            <input type="hidden" name="input_type" value="{{ $row->input_type }}">
                            @php
                            $inputOptions = '';
                            if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                            $inputOptions = (string)$row->input_config['options'];
                            }
                            @endphp
                            <input type="hidden" name="input_config_options" value="{{ $inputOptions }}">

                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;" required>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)$row->category_id === (int)$dst->id ? 'selected' : '' }}>
                                {{ $dst->label }}
                              </option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                            $fixedInputOptions = '';
                            if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                              $fixedInputOptions = (string) $row->input_config['options'];
                            }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-fixed-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeFixedFieldModal"
                            data-field-key="{{ $row->field_key }}"
                            data-field-label="{{ $row->display_label }}"
                            data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                            data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                            data-input-type="{{ $row->input_type ?? 'text' }}"
                            data-input-config-options='@json($fixedInputOptions)'
                            data-category-id="{{ $row->category_id ?? '' }}"
                            title="Edit fixed field">
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @endforeach

                      @php $customStart = count($fixedList); @endphp
                      @foreach(($customFields ?? collect()) as $customIndex => $customField)
                      @php
                      $cat = $customField->category;
                      $catLabel = $cat?->label ?? 'Unassigned';
                      $isReq = (bool) ($customField->is_mandatory ?? false);
                      @endphp
                      <tr class="table-light">
                        <td class="align-middle">{{ $customStart + $customIndex + 1 }}</td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $customField->label }}</span>
                          <span class="badge bg-label-secondary ms-1">Custom</span>
                        </td>
                        <td class="align-middle">
                          @if($cat)
                          <span class="badge bg-label-info">{{ $catLabel }}</span>
                          @else
                          <span class="badge bg-label-warning">Unassigned</span>
                          @endif
                        </td>
                        <td class="align-middle text-center">{{ $isReq ? 'Yes' : 'No' }}</td>
                        <td class="align-middle text-center">-</td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.bike-settings.assign-custom-field-category', $customField->id) }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;" required>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)($customField->category_id ?? 0) === (int)$dst->id ? 'selected' : '' }}>
                                {{ $dst->label }}
                              </option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                            $customConfigOptions = '';
                            if (is_array($customField->config ?? null) && isset($customField->config['options'])) {
                              $customConfigOptions = (string) $customField->config['options'];
                            }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-custom-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeCustomFieldModal"
                            data-field-id="{{ $customField->id }}"
                            data-field-label="{{ $customField->label }}"
                            data-help-text="{{ $customField->help_text }}"
                            data-data-type="{{ $customField->data_type }}"
                            data-is-mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                            data-default-value="{{ $customField->default_value }}"
                            data-input-format="{{ $customField->input_format }}"
                            data-config-options='@json($customConfigOptions)'
                            data-update-url="{{ route('settings-panel.bike-settings.update-field', ['id' => $customField->id]) }}"
                            data-category-id="{{ $customField->category_id ?? '' }}"
                            title="Edit custom field">
                            <i class="ti ti-pencil"></i>
                          </button>
                          <form action="{{ route('settings-panel.bike-settings.destroy-field', $customField->id) }}"
                            method="POST"
                            class="d-inline ms-1"
                            onsubmit="return confirm('Delete this bike custom field?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete custom field">
                              <i class="ti ti-trash"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                      @endforeach
                      @if(($fixedList ?? collect())->isEmpty() && ($customFields ?? collect())->isEmpty())
                      <tr>
                          <td colspan="7" class="text-center text-muted py-3">No bike fields configured yet.</td>
                      </tr>
                      @endif
                    </tbody>
                  </table>
                </div>
              </div>

              {{-- Category tabs: fixed + custom --}}
              @foreach($categories as $cat)
              @php
              $fixedRows = $fixedAssignmentsByCategory[$cat->id] ?? collect();
              $customRows = $customFieldsByCategory[$cat->id] ?? collect();
              $isActive = $activeCategoryId === (int)$cat->id;
              @endphp

              <div class="tab-pane fade {{ $isActive ? 'show active' : '' }}"
                id="bike-field-cat-{{ $cat->id }}" role="tabpanel">
                <div class="table-responsive">
                  <table class="table table-hover bike-settings-table mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 60px;"></th>
                        <th>Field</th>
                        <th class="text-center">Required</th>
                        <th class="text-center">Show in form</th>
                        <th>Move to category</th>
                        <th class="text-end">Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach($fixedRows as $rowIndex => $row)
                      @php
                      $fieldLabel = $row->display_label ? $row->display_label : \App\Models\BikeCustomField::humanizeFieldKey($row->field_key);
                      @endphp
                      <tr>
                        <td class="align-middle"></td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $fieldLabel }}</span>
                          <span class="text-muted ms-1">({{ $row->field_key }})</span>
                        </td>
                        <td class="align-middle text-center">{{ ($row->is_required ?? false) ? 'Yes' : 'No' }}</td>
                        <td class="align-middle text-center">{{ ($row->is_visible ?? true) ? 'Yes' : 'No' }}</td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.bike-settings.update-field-assignment') }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <input type="hidden" name="field_key" value="{{ $row->field_key }}">
                            <input type="hidden" name="display_label" value="{{ $row->display_label }}">
                            <input type="hidden" name="is_visible" value="{{ ($row->is_visible ?? true) ? 1 : 0 }}">
                            <input type="hidden" name="is_required" value="{{ ($row->is_required ?? false) ? 1 : 0 }}">
                            <input type="hidden" name="input_type" value="{{ $row->input_type }}">
                            @php
                            $inputOptions = '';
                            if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                            $inputOptions = (string)$row->input_config['options'];
                            }
                            @endphp
                            <input type="hidden" name="input_config_options" value="{{ $inputOptions }}">

                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;" required>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)$cat->id === (int)$dst->id ? 'selected' : '' }}>{{ $dst->label }}</option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                            $fixedInputOptions = '';
                            if (is_array($row->input_config ?? null) && isset($row->input_config['options'])) {
                              $fixedInputOptions = (string) $row->input_config['options'];
                            }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-fixed-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeFixedFieldModal"
                            data-field-key="{{ $row->field_key }}"
                            data-field-label="{{ $row->display_label }}"
                            data-is-visible="{{ ($row->is_visible ?? true) ? 1 : 0 }}"
                            data-is-required="{{ ($row->is_required ?? false) ? 1 : 0 }}"
                            data-input-type="{{ $row->input_type ?? 'text' }}"
                            data-input-config-options='@json($fixedInputOptions)'
                            data-category-id="{{ $row->category_id ?? '' }}"
                            title="Edit fixed field">
                            <i class="ti ti-pencil"></i>
                          </button>
                        </td>
                      </tr>
                      @endforeach

                      @foreach($customRows as $customIndex => $customField)
                      <tr class="table-light">
                        <td class="align-middle"></td>
                        <td class="align-middle">
                          <span class="fw-semibold">{{ $customField->label }}</span>
                          <span class="badge bg-label-secondary ms-1">Custom</span>
                        </td>
                        <td class="align-middle text-center">{{ ($customField->is_mandatory ?? false) ? 'Yes' : 'No' }}</td>
                        <td class="align-middle text-center">-</td>
                        <td class="align-middle">
                          <form action="{{ route('settings-panel.bike-settings.assign-custom-field-category', $customField->id) }}" method="POST" class="d-flex gap-2 align-items-center flex-wrap">
                            @csrf
                            <select name="category_id" class="form-select form-select-sm" style="width: 180px;" required>
                              @foreach($categories as $dst)
                              <option value="{{ $dst->id }}" {{ (int)($customField->category_id ?? 0) === (int)$dst->id ? 'selected' : '' }}>
                                {{ $dst->label }}
                              </option>
                              @endforeach
                            </select>
                            <button type="submit" class="btn btn-sm btn-outline-primary">Move</button>
                          </form>
                        </td>
                        <td class="align-middle text-end">
                          @php
                            $customConfigOptions = '';
                            if (is_array($customField->config ?? null) && isset($customField->config['options'])) {
                              $customConfigOptions = (string) $customField->config['options'];
                            }
                          @endphp
                          <button type="button"
                            class="btn btn-sm btn-outline-primary btn-edit-bike-custom-field"
                            data-bs-toggle="modal"
                            data-bs-target="#editBikeCustomFieldModal"
                            data-field-id="{{ $customField->id }}"
                            data-field-label="{{ $customField->label }}"
                            data-help-text="{{ $customField->help_text }}"
                            data-data-type="{{ $customField->data_type }}"
                            data-is-mandatory="{{ $customField->is_mandatory ? 1 : 0 }}"
                            data-default-value="{{ $customField->default_value }}"
                            data-input-format="{{ $customField->input_format }}"
                            data-config-options='@json($customConfigOptions)'
                            data-update-url="{{ route('settings-panel.bike-settings.update-field', ['id' => $customField->id]) }}"
                            data-category-id="{{ $customField->category_id ?? '' }}"
                            title="Edit custom field">
                            <i class="ti ti-pencil"></i>
                          </button>
                          <form action="{{ route('settings-panel.bike-settings.destroy-field', $customField->id) }}"
                            method="POST"
                            class="d-inline ms-1"
                            onsubmit="return confirm('Delete this bike custom field?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete custom field">
                              <i class="ti ti-trash"></i>
                            </button>
                          </form>
                        </td>
                      </tr>
                      @endforeach

                      @if($fixedRows->isEmpty() && $customRows->isEmpty())
                      <tr>
                        <td colspan="6" class="text-center text-muted py-3">No fields in this category.</td>
                      </tr>
                      @endif
                    </tbody>
                  </table>
                </div>
              </div>
              @endforeach
            </div>
          </div>

          {{-- Edit Bike Fixed Field Modal --}}
          <div class="modal fade" id="editBikeFixedFieldModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                  <h5 class="modal-title">Edit bike fixed field</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="formEditBikeFixedField" action="{{ route('settings-panel.bike-settings.update-field-assignment') }}" method="POST">
                  @csrf
                  <div class="modal-body pt-0">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-4">
                        <label class="form-label">Field key</label>
                        <input type="text" name="field_key" id="editBikeFixedFieldKey" class="form-control" readonly>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Display label</label>
                        <input type="text" name="display_label" id="editBikeFixedDisplayLabel" class="form-control">
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="editBikeFixedCategoryId" class="form-select" required>
                          @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-4">
                        <div class="form-check mt-4">
                          <input type="hidden" name="is_visible" value="0">
                          <input type="checkbox" name="is_visible" value="1" id="editBikeFixedIsVisible" class="form-check-input">
                          <label class="form-check-label" for="editBikeFixedIsVisible">Visible</label>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <div class="form-check mt-4">
                          <input type="hidden" name="is_required" value="0">
                          <input type="checkbox" name="is_required" value="1" id="editBikeFixedIsRequired" class="form-check-input">
                          <label class="form-check-label" for="editBikeFixedIsRequired">Required</label>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Input type</label>
                        <select name="input_type" id="editBikeFixedInputType" class="form-select">
                          @foreach(['text','textarea','number','decimal','date','datetime','dropdown','checkbox'] as $t)
                            <option value="{{ $t }}">{{ ucfirst($t) }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">Dropdown options (one per line)</label>
                        <input type="hidden" name="input_config_options" id="editBikeFixedInputConfigOptionsHidden" value="">
                        <div id="editBikeFixedOptionsRows" class="d-flex flex-column gap-2"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editBikeFixedOptionRowBtn">Add Option</button>
                      </div>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save fixed field</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          {{-- Edit Bike Custom Field Modal --}}
          <div class="modal fade" id="editBikeCustomFieldModal" tabindex="-1" data-bs-backdrop="static" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
              <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                  <h5 class="modal-title">Edit bike custom field</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="formEditBikeCustomField" action="#" method="POST">
                  @csrf
                  @method('PUT')

                  <div class="modal-body pt-0">
                    <div class="row g-3 align-items-end">
                      <div class="col-md-6">
                        <label class="form-label">Label</label>
                        <input type="text" name="label" id="editBikeCustomLabel" class="form-control" required maxlength="255">
                      </div>
                      <div class="col-md-6">
                        <label class="form-label">Help text</label>
                        <input type="text" name="help_text" id="editBikeCustomHelpText" class="form-control" maxlength="1000">
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Data type</label>
                        <select name="data_type" id="editBikeCustomDataType" class="form-select" required>
                          @foreach($dataTypes as $typeKey => $spec)
                            <option value="{{ $typeKey }}">{{ $spec['label'] }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-4">
                        <div class="form-check mt-4">
                          <input type="hidden" name="is_mandatory" value="0">
                          <input type="checkbox" name="is_mandatory" value="1" id="editBikeCustomIsMandatory" class="form-check-input">
                          <label class="form-check-label" for="editBikeCustomIsMandatory">Mandatory</label>
                        </div>
                      </div>

                      <div class="col-md-4">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="editBikeCustomCategoryId" class="form-select">
                          <option value="">Unassigned</option>
                          @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                          @endforeach
                        </select>
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Default value</label>
                        <input type="text" name="default_value" id="editBikeCustomDefaultValue" class="form-control" maxlength="500">
                      </div>

                      <div class="col-md-6">
                        <label class="form-label">Input format</label>
                        <input type="text" name="input_format" id="editBikeCustomInputFormat" class="form-control" maxlength="100">
                      </div>

                      <div class="col-md-12">
                        <label class="form-label">Config options (dropdown: one per line)</label>
                        <input type="hidden" name="config_options" id="editBikeCustomConfigOptionsHidden" value="">
                        <div id="editBikeCustomOptionsRows" class="d-flex flex-column gap-2"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="editBikeCustomOptionRowBtn">Add Option</button>
                      </div>
                    </div>
                  </div>

                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save custom field</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <script>
            function bikeSafeJsonParse(value, fallback) {
              try {
                if (value === undefined || value === null) return fallback;
                return JSON.parse(value);
              } catch (e) {
                return fallback;
              }
            }

            function bikeParseOptionLines(raw) {
              return String(raw || '')
                .split(/\r?\n/)
                .map(function (s) {
                  return s.trim();
                })
                .filter(function (s) {
                  return s.length > 0;
                });
            }

            function bikeSyncOptionsToHidden(container, hiddenInput) {
              if (!container || !hiddenInput) return;
              const items = Array.prototype.slice.call(container.querySelectorAll('input[type="text"]'))
                .map(function (el) {
                  return (el.value || '').trim();
                })
                .filter(function (v) {
                  return v.length > 0;
                });
              hiddenInput.value = items.join('\n');
            }

            function bikeCreateOptionRow(container, hiddenInput, initialValue) {
              const row = document.createElement('div');
              row.className = 'd-flex align-items-center gap-2';

              const rowInput = document.createElement('input');
              rowInput.type = 'text';
              rowInput.className = 'form-control';
              rowInput.placeholder = 'Option value';
              rowInput.value = initialValue || '';
              rowInput.addEventListener('input', function () {
                bikeSyncOptionsToHidden(container, hiddenInput);
              });

              const removeBtn = document.createElement('button');
              removeBtn.type = 'button';
              removeBtn.className = 'btn btn-sm btn-outline-danger';
              removeBtn.textContent = 'Remove';
              removeBtn.addEventListener('click', function () {
                row.remove();
                bikeSyncOptionsToHidden(container, hiddenInput);
              });

              row.appendChild(rowInput);
              row.appendChild(removeBtn);
              container.appendChild(row);
              bikeSyncOptionsToHidden(container, hiddenInput);
            }

            function bikeRenderOptionRows(container, hiddenInput, rawOptions) {
              if (!container || !hiddenInput) return;
              container.innerHTML = '';
              const items = bikeParseOptionLines(rawOptions);
              if (!items.length) {
                bikeCreateOptionRow(container, hiddenInput, '');
                return;
              }
              items.forEach(function (item) {
                bikeCreateOptionRow(container, hiddenInput, item);
              });
            }

            function bikeInitOptionRowButtons() {
              // Add modal (new custom field)
              const addBtn = document.getElementById('addBikeFieldOptionRowBtn');
              const rows = document.getElementById('addBikeFieldOptionsRows');
              const hidden = document.getElementById('addBikeFieldConfigOptionsHidden');
              if (addBtn && rows && hidden) {
                addBtn.addEventListener('click', function () {
                  bikeCreateOptionRow(rows, hidden, '');
                });
              }

              // Edit fixed field modal
              const editFixedAddBtn = document.getElementById('editBikeFixedOptionRowBtn');
              const editFixedRows = document.getElementById('editBikeFixedOptionsRows');
              const editFixedHidden = document.getElementById('editBikeFixedInputConfigOptionsHidden');
              if (editFixedAddBtn && editFixedRows && editFixedHidden) {
                editFixedAddBtn.addEventListener('click', function () {
                  bikeCreateOptionRow(editFixedRows, editFixedHidden, '');
                });
              }

              // Edit custom field modal
              const editCustomAddBtn = document.getElementById('editBikeCustomOptionRowBtn');
              const editCustomRows = document.getElementById('editBikeCustomOptionsRows');
              const editCustomHidden = document.getElementById('editBikeCustomConfigOptionsHidden');
              if (editCustomAddBtn && editCustomRows && editCustomHidden) {
                editCustomAddBtn.addEventListener('click', function () {
                  bikeCreateOptionRow(editCustomRows, editCustomHidden, '');
                });
              }
            }

            bikeInitOptionRowButtons();

            document.addEventListener('show.bs.modal', function (e) {
              const modalId = e.target && e.target.id ? e.target.id : null;
              const btn = e.relatedTarget;
              if (!modalId || !btn) return;

              // Fixed field edit
              if (modalId === 'editBikeFixedFieldModal') {
                const fieldKey = btn.dataset.fieldKey || '';
                document.getElementById('editBikeFixedFieldKey').value = fieldKey;
                document.getElementById('editBikeFixedDisplayLabel').value = btn.dataset.fieldLabel || '';

                const categoryId = btn.dataset.categoryId || '';
                const catSelect = document.getElementById('editBikeFixedCategoryId');
                if (categoryId && catSelect.querySelector('option[value="' + categoryId + '"]')) {
                  catSelect.value = categoryId;
                }

                document.getElementById('editBikeFixedIsVisible').checked = String(btn.dataset.isVisible) === '1';
                document.getElementById('editBikeFixedIsRequired').checked = String(btn.dataset.isRequired) === '1';
                document.getElementById('editBikeFixedInputType').value = btn.dataset.inputType || 'text';

                const configOptionsRaw = btn.dataset.inputConfigOptions;
                const configOptions = bikeSafeJsonParse(configOptionsRaw, '');
                bikeRenderOptionRows(
                  document.getElementById('editBikeFixedOptionsRows'),
                  document.getElementById('editBikeFixedInputConfigOptionsHidden'),
                  configOptions || ''
                );
              }

              // Custom field edit
              if (modalId === 'editBikeCustomFieldModal') {
                const updateUrl = btn.dataset.updateUrl || '#';
                const form = document.getElementById('formEditBikeCustomField');
                if (form && updateUrl && updateUrl !== '#') {
                  form.action = updateUrl;
                }

                document.getElementById('editBikeCustomLabel').value = btn.dataset.fieldLabel || '';
                document.getElementById('editBikeCustomHelpText').value = btn.dataset.helpText || '';
                document.getElementById('editBikeCustomDataType').value = btn.dataset.dataType || '';
                document.getElementById('editBikeCustomIsMandatory').checked = String(btn.dataset.isMandatory) === '1';

                const categoryId = btn.dataset.categoryId || '';
                const catSelect = document.getElementById('editBikeCustomCategoryId');
                if (categoryId !== '' && catSelect.querySelector('option[value="' + categoryId + '"]')) {
                  catSelect.value = categoryId;
                } else {
                  catSelect.value = '';
                }

                document.getElementById('editBikeCustomDefaultValue').value = btn.dataset.defaultValue || '';
                document.getElementById('editBikeCustomInputFormat').value = btn.dataset.inputFormat || '';

                const configOptionsRaw = btn.dataset.configOptions;
                const configOptions = bikeSafeJsonParse(configOptionsRaw, '');
                bikeRenderOptionRows(
                  document.getElementById('editBikeCustomOptionsRows'),
                  document.getElementById('editBikeCustomConfigOptionsHidden'),
                  configOptions || ''
                );
              }
            });
          </script>

          {{-- Tab: Documents --}}
          <div class="tab-pane fade" id="tab-docs" role="tabpanel">
            <div class="card mb-4">
              <div class="card-body">
                <form action="{{ route('settings-panel.bike-settings.store-document-type') }}" method="POST" class="row g-3 align-items-end">
                  @csrf
                  <div class="col-md-3">
                    <label class="form-label">Key</label>
                    <input type="text" name="key" class="form-control" required maxlength="80" placeholder="e.g. mulkiya">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Type</label>
                    <select name="type" class="form-select" required>
                      <option value="single">Single</option>
                      <option value="dual">Dual</option>
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-control" maxlength="255" placeholder="Optional">
                  </div>
                  <div class="col-md-3 text-end">
                    <button type="submit" class="btn btn-primary">Add</button>
                  </div>

                  <div class="col-md-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" min="0" value="0">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Front label (dual)</label>
                    <input type="text" name="front_label" class="form-control" maxlength="255">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Back label (dual)</label>
                    <input type="text" name="back_label" class="form-control" maxlength="255">
                  </div>
                </form>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th>Key</th>
                    <th>Type</th>
                    <th>Labels</th>
                    <th style="width: 160px;">Save</th>
                    <th style="width: 120px;">Delete</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($documentTypes as $doc)
                  <tr>
                    <form action="{{ route('settings-panel.bike-settings.update-document-type', $doc->id) }}" method="POST">
                      @csrf
                      @method('PUT')
                      <td>
                        <input type="text" name="key" value="{{ $doc->key }}" class="form-control form-control-sm" required>
                      </td>
                      <td>
                        <select name="type" class="form-select form-select-sm">
                          <option value="single" {{ $doc->type === 'single' ? 'selected' : '' }}>Single</option>
                          <option value="dual" {{ $doc->type === 'dual' ? 'selected' : '' }}>Dual</option>
                        </select>
                      </td>
                      <td>
                        <input type="text" name="label" value="{{ $doc->label }}" class="form-control form-control-sm mb-2" placeholder="Label">
                        <input type="text" name="front_label" value="{{ $doc->front_label }}" class="form-control form-control-sm mb-2" placeholder="Front label">
                        <input type="text" name="back_label" value="{{ $doc->back_label }}" class="form-control form-control-sm" placeholder="Back label">
                      </td>
                      <td class="text-end">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                      </td>
                    </form>
                    <td>
                      <form action="{{ route('settings-panel.bike-settings.destroy-document-type', $doc->id) }}" method="POST" onsubmit="return confirm('Delete this document type?')" class="d-inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-danger">Del</button>
                      </form>
                    </td>
                  </tr>
                  @endforeach
                  @if(($documentTypes ?? collect())->isEmpty())
                  <tr>
                    <td colspan="5" class="text-center text-muted py-3">No document types configured.</td>
                  </tr>
                  @endif
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

@endsection