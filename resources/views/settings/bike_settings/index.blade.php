@extends('layouts.settingsPanelLayout')

@section('title', 'Bike Settings – Site Settings')

@section('content')
@include('flash::message')

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h4 class="card-title mb-0">Bike Settings</h4>
          <p class="text-muted small mb-0 mt-1">
            Configure bike fixed/custom fields and documents (no "on top" / "view on card" for bikes).
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
        <ul class="nav nav-tabs mb-3" id="bikeSettingsTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-categories-btn" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">Categories</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-fields-btn" data-bs-toggle="tab" data-bs-target="#tab-fields" type="button" role="tab">Bike Fields</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-custom-fields-btn" data-bs-toggle="tab" data-bs-target="#tab-custom-fields" type="button" role="tab">Custom Fields</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-docs-btn" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">Documents</button>
          </li>
        </ul>

        <div class="tab-content">
          {{-- Tab: General --}}
          <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <form action="{{ route('settings-panel.bike-settings.store-module-label') }}" method="POST" class="row g-3 align-items-end">
              @csrf
              <div class="col-md-6">
                <label class="form-label">Name in menu</label>
                <input type="text" name="module_label" class="form-control" value="{{ old('module_label', $moduleLabel ?? 'Bike Settings') }}" placeholder="Bike Settings" maxlength="100" required>
              </div>
              <div class="col-md-6 text-end">
                <button type="submit" class="btn btn-primary">Save name</button>
              </div>
            </form>
          </div>

          {{-- Tab: Categories --}}
          <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <h5 class="mb-3">Categories</h5>

            <div class="card mb-4">
              <div class="card-body">
                <form action="{{ route('settings-panel.bike-settings.store-category') }}" method="POST" class="row g-3 align-items-end">
                  @csrf
                  <div class="col-md-6">
                    <label class="form-label">New category label</label>
                    <input type="text" name="label" class="form-control" required maxlength="255" placeholder="e.g. Safety / Location / etc.">
                  </div>
                  <div class="col-md-6 text-end">
                    <button type="submit" class="btn btn-primary">Add category</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="table-responsive">
              <table class="table table-bordered align-middle">
                <thead>
                  <tr>
                    <th style="width: 30%">Label</th>
                    <th style="width: 20%">System</th>
                    <th style="width: 50%">Actions</th>
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

                          <form action="{{ route('settings-panel.bike-settings.destroy-category', $cat->id) }}" method="POST" class="d-inline-flex ms-2" onsubmit="return confirm('Delete this category?')">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-danger" type="submit">Delete</button>
                          </form>
                        @else
                          <span class="text-muted">Editable fields disabled.</span>
                        @endif
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>

          {{-- Tab: Bike Fields (fixed field assignments) --}}
          <div class="tab-pane fade" id="tab-fields" role="tabpanel">
            <h5 class="mb-3">Fixed field assignments</h5>
            <p class="text-muted small mb-3">
              Control which bike table columns appear in the Bike form, and whether they are required.
            </p>

            @foreach($categories as $cat)
              <div class="card mb-4">
                <div class="card-header">
                  <b>{{ $cat->label }}</b>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-bordered align-middle m-0">
                      <thead>
                        <tr>
                          <th>Field Key</th>
                          <th>Display Label</th>
                          <th>Visible</th>
                          <th>Required</th>
                          <th>Input Type</th>
                          <th>Dropdown Options (if needed)</th>
                          <th>Move To Category</th>
                          <th style="width: 1%">Save</th>
                        </tr>
                      </thead>
                      <tbody>
                        @php $rows = $fixedAssignmentsByCategory[$cat->id] ?? collect(); @endphp
                        @foreach($rows as $assignment)
                          <tr>
                            <form action="{{ route('settings-panel.bike-settings.update-field-assignment') }}" method="POST">
                              @csrf
                              <td class="align-middle">
                                {{ $assignment->field_key }}
                                <input type="hidden" name="field_key" value="{{ $assignment->field_key }}">
                              </td>

                              <td class="align-middle">
                                <input type="text" name="display_label" value="{{ $assignment->display_label }}" maxlength="255" class="form-control form-control-sm">
                              </td>

                              <td class="align-middle">
                                <input type="hidden" name="is_visible" value="0">
                                <input type="checkbox" name="is_visible" value="1" class="form-check-input" {{ $assignment->is_visible ? 'checked' : '' }}>
                              </td>

                              <td class="align-middle">
                                <input type="hidden" name="is_required" value="0">
                                <input type="checkbox" name="is_required" value="1" class="form-check-input" {{ $assignment->is_required ? 'checked' : '' }}>
                              </td>

                              <td class="align-middle">
                                <select name="input_type" class="form-select form-select-sm">
                                  @foreach(['text','textarea','number','decimal','date','datetime','dropdown','checkbox'] as $type)
                                    <option value="{{ $type }}" {{ ($assignment->input_type ?? '') === $type ? 'selected' : '' }}>
                                      {{ ucfirst($type) }}
                                    </option>
                                  @endforeach
                                </select>
                              </td>

                              <td class="align-middle">
                                @php
                                  $options = '';
                                  if (is_array($assignment->input_config ?? null) && isset($assignment->input_config['options'])) {
                                      $options = $assignment->input_config['options'];
                                  }
                                @endphp
                                <textarea name="input_config_options" rows="2" class="form-control form-control-sm" placeholder="One option per line">{{ $options }}</textarea>
                              </td>

                              <td class="align-middle">
                                <select name="category_id" class="form-select form-select-sm">
                                  @foreach($categories as $dst)
                                    <option value="{{ $dst->id }}" {{ $assignment->category_id == $dst->id ? 'selected' : '' }}>
                                      {{ $dst->label }}
                                    </option>
                                  @endforeach
                                </select>
                              </td>

                              <td class="align-middle text-center">
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                              </td>
                            </form>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            @endforeach
          </div>

          {{-- Tab: Custom Fields --}}
          <div class="tab-pane fade" id="tab-custom-fields" role="tabpanel">
            <h5 class="mb-3">Custom fields</h5>

            <div class="card mb-4">
              <div class="card-body">
                <form action="{{ route('settings-panel.bike-settings.store-field') }}" method="POST" class="row g-3 align-items-end">
                  @csrf
                  <div class="col-md-4">
                    <label class="form-label">Label</label>
                    <input type="text" name="label" class="form-control" required maxlength="255">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Data Type</label>
                    <select name="data_type" class="form-select" required>
                      @foreach($dataTypes as $typeKey => $spec)
                        <option value="{{ $typeKey }}">{{ $spec['label'] }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Category</label>
                    <select name="category_id" class="form-select">
                      <option value="">Unassigned</option>
                      @foreach($categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->label }}</option>
                      @endforeach
                    </select>
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Help Text</label>
                    <input type="text" name="help_text" class="form-control" maxlength="1000">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Default Value</label>
                    <input type="text" name="default_value" class="form-control" maxlength="500">
                  </div>
                  <div class="col-md-3">
                    <div class="form-check mt-4">
                      <input type="hidden" name="is_mandatory" value="0">
                      <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" id="bikeFieldIsMandatory">
                      <label class="form-check-label" for="bikeFieldIsMandatory">Mandatory</label>
                    </div>
                  </div>

                  <div class="col-md-12">
                    <label class="form-label">Dropdown Options (only for dropdown)</label>
                    <textarea name="config_options" rows="3" class="form-control" placeholder="One option per line"></textarea>
                  </div>

                  <div class="col-md-12 text-end">
                    <button class="btn btn-primary" type="submit">Add custom field</button>
                  </div>
                </form>
              </div>
            </div>

            {{-- Assigned custom fields --}}
            @foreach($categories as $cat)
              <div class="card mb-4">
                <div class="card-header">
                  <b>{{ $cat->label }}</b>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-bordered m-0 align-middle">
                      <thead>
                        <tr>
                          <th>Label</th>
                          <th>Type</th>
                          <th>Mandatory</th>
                          <th>Options / Config</th>
                          <th>Help Text</th>
                          <th style="width: 1%">Save</th>
                          <th style="width: 1%">Delete</th>
                        </tr>
                      </thead>
                      <tbody>
                        @php $rows = $customFieldsByCategory[$cat->id] ?? collect(); @endphp
                        @foreach($rows as $field)
                          <tr>
                            <form action="{{ route('settings-panel.bike-settings.update-field', $field->id) }}" method="POST">
                              @csrf
                              @method('PUT')

                              <td>
                                <input type="text" name="label" value="{{ $field->label }}" required maxlength="255" class="form-control form-control-sm">
                                <input type="hidden" name="category_id" value="{{ $cat->id }}">
                              </td>
                              <td>
                                <select name="data_type" class="form-select form-select-sm" required>
                                  @foreach($dataTypes as $typeKey => $spec)
                                    <option value="{{ $typeKey }}" {{ $field->data_type === $typeKey ? 'selected' : '' }}>
                                      {{ $spec['label'] }}
                                    </option>
                                  @endforeach
                                </select>
                              </td>
                              <td>
                                <input type="hidden" name="is_mandatory" value="0">
                                <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" {{ $field->is_mandatory ? 'checked' : '' }}>
                              </td>
                              <td>
                                @php
                                  $options = '';
                                  if (is_array($field->config ?? null) && isset($field->config['options'])) {
                                      $options = $field->config['options'];
                                  }
                                @endphp
                                <textarea name="config_options" rows="2" class="form-control form-control-sm">{{ $options }}</textarea>
                              </td>
                              <td>
                                <input type="text" name="help_text" value="{{ $field->help_text }}" maxlength="1000" class="form-control form-control-sm">
                              </td>
                              <td class="text-center">
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                              </td>
                            </form>

                            <td class="text-center">
                              <form action="{{ route('settings-panel.bike-settings.destroy-field', $field->id) }}" method="POST" onsubmit="return confirm('Delete this custom field?')" >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Del</button>
                              </form>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            @endforeach

            {{-- Unassigned custom fields --}}
            @if($unassignedCustomFields->isNotEmpty())
              <div class="card mb-4">
                <div class="card-header">
                  <b>Unassigned</b>
                </div>
                <div class="card-body p-0">
                  <div class="table-responsive">
                    <table class="table table-bordered m-0 align-middle">
                      <thead>
                        <tr>
                          <th>Label</th>
                          <th>Type</th>
                          <th>Mandatory</th>
                          <th>Options / Config</th>
                          <th>Help Text</th>
                          <th style="width: 1%">Save</th>
                          <th style="width: 1%">Delete</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach($unassignedCustomFields as $field)
                          <tr>
                            <form action="{{ route('settings-panel.bike-settings.update-field', $field->id) }}" method="POST">
                              @csrf
                              @method('PUT')
                              <td>
                                <input type="text" name="label" value="{{ $field->label }}" required maxlength="255" class="form-control form-control-sm">
                                <input type="hidden" name="category_id" value="">
                              </td>
                              <td>
                                <select name="data_type" class="form-select form-select-sm" required>
                                  @foreach($dataTypes as $typeKey => $spec)
                                    <option value="{{ $typeKey }}" {{ $field->data_type === $typeKey ? 'selected' : '' }}>
                                      {{ $spec['label'] }}
                                    </option>
                                  @endforeach
                                </select>
                              </td>
                              <td>
                                <input type="hidden" name="is_mandatory" value="0">
                                <input type="checkbox" name="is_mandatory" value="1" class="form-check-input" {{ $field->is_mandatory ? 'checked' : '' }}>
                              </td>
                              <td>
                                @php
                                  $options = '';
                                  if (is_array($field->config ?? null) && isset($field->config['options'])) {
                                      $options = $field->config['options'];
                                  }
                                @endphp
                                <textarea name="config_options" rows="2" class="form-control form-control-sm">{{ $options }}</textarea>
                              </td>
                              <td>
                                <input type="text" name="help_text" value="{{ $field->help_text }}" maxlength="1000" class="form-control form-control-sm">
                              </td>
                              <td class="text-center">
                                <button type="submit" class="btn btn-sm btn-primary">Save</button>
                              </td>
                            </form>

                            <td class="text-center">
                              <form action="{{ route('settings-panel.bike-settings.destroy-field', $field->id) }}" method="POST" onsubmit="return confirm('Delete this custom field?')" >
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger">Del</button>
                              </form>
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            @endif
          </div>

          {{-- Tab: Documents --}}
          <div class="tab-pane fade" id="tab-docs" role="tabpanel">
            <h5 class="mb-3">Document types</h5>

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
                  <div class="col-md-3">
                    <label class="form-label">Display Order</label>
                    <input type="number" name="display_order" class="form-control" min="0" value="0">
                  </div>

                  <div class="col-md-6">
                    <label class="form-label">Front Label (dual)</label>
                    <input type="text" name="front_label" class="form-control" maxlength="255">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Back Label (dual)</label>
                    <input type="text" name="back_label" class="form-control" maxlength="255">
                  </div>

                  <div class="col-md-12 text-end">
                    <button type="submit" class="btn btn-primary">Add document type</button>
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
                    <th>Display Order</th>
                    <th style="width: 1%">Save</th>
                    <th style="width: 1%">Delete</th>
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
                        <td>
                          <input type="number" name="display_order" value="{{ $doc->display_order }}" min="0" class="form-control form-control-sm">
                        </td>
                        <td class="text-center">
                          <button type="submit" class="btn btn-sm btn-primary">Save</button>
                        </td>
                      </form>
                      <td class="text-center">
                        <form action="{{ route('settings-panel.bike-settings.destroy-document-type', $doc->id) }}" method="POST" onsubmit="return confirm('Delete this document type?')">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="btn btn-sm btn-danger">Del</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
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

