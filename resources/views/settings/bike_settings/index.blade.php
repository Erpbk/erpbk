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
                            <button class="btn btn-sm btn-primary" type="submit">Update</button>
                          </form>

                          <form action="{{ route('settings-panel.bike-settings.destroy-category', $cat->id) }}" method="POST" class="d-inline ms-2" onsubmit="return confirm('Delete this category?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
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
            <div class="card mb-4">
              <div class="card-body">
                <h5 class="mb-3">Add Custom Field</h5>
                <form action="{{ route('settings-panel.bike-settings.store-field') }}" method="POST" class="row g-3 align-items-end">
                  @csrf
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
                      <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="bikeFieldIsMandatory">
                      <label class="form-check-label" for="bikeFieldIsMandatory">Mandatory</label>
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
                    <textarea name="config_options" rows="2" class="form-control" placeholder="Option 1&#10;Option 2"></textarea>
                  </div>

                  <div class="col-md-12 text-end">
                    <button class="btn btn-primary" type="submit">Add custom field</button>
                  </div>
                </form>
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
                        </tr>
                      @endforeach
                      @if(($fixedList ?? collect())->isEmpty() && ($customFields ?? collect())->isEmpty())
                        <tr>
                          <td colspan="6" class="text-center text-muted py-3">No bike fields configured yet.</td>
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
                          </tr>
                        @endforeach

                        @if($fixedRows->isEmpty() && $customRows->isEmpty())
                          <tr>
                            <td colspan="5" class="text-center text-muted py-3">No fields in this category.</td>
                          </tr>
                        @endif
                      </tbody>
                    </table>
                  </div>
                </div>
              @endforeach
            </div>
          </div>

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

