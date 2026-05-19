@extends($layout ?? 'layouts.app')

@section('title', $pageTitle ?? 'Module Settings')

@section('content')
@include('flash::message')
@php
$settingsCompanySlug = request()->route('company_slug') ?? session('company_slug');
@endphp
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h4 class="card-title mb-0">{{ $moduleLabel }}</h4>
          <p class="text-muted small mb-0 mt-1">Configure module name, categories, field assignments, custom fields, and document types.</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <ul class="nav nav-tabs mb-3" id="moduleSettingsTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-general-btn" data-bs-toggle="tab" data-bs-target="#tab-general" type="button" role="tab">General</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-categories-btn" data-bs-toggle="tab" data-bs-target="#tab-categories" type="button" role="tab">Categories</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-fixed-btn" data-bs-toggle="tab" data-bs-target="#tab-fixed" type="button" role="tab">Field Assignments</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-custom-btn" data-bs-toggle="tab" data-bs-target="#tab-custom" type="button" role="tab">Custom Fields</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-docs-btn" data-bs-toggle="tab" data-bs-target="#tab-docs" type="button" role="tab">Document Types</button>
          </li>
          @include('settings.partials.top_bar._settings_tab')
        </ul>

        <div class="tab-content" id="moduleSettingsTabContent">
          <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <p class="text-muted small mb-3">This name appears in the left sidebar menu of the main application.</p>
            <form action="{{ route('settings-panel.module-settings.store-module-label', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey]) }}" method="POST" class="row g-3 align-items-end">
              @csrf
              <div class="col-md-6">
                <label class="form-label">Name in menu</label>
                <input type="text" name="module_label" class="form-control" value="{{ old('module_label', $moduleLabel) }}" placeholder="{{ $defaultLabel }}" maxlength="100" required>
              </div>
              <div class="col-md-6">
                <button type="submit" class="btn btn-primary">Save name</button>
              </div>
            </form>
          </div>

          <div class="tab-pane fade" id="tab-categories" role="tabpanel">
            <form action="{{ route('settings-panel.module-settings.store-category', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey]) }}" method="POST" class="row g-3 align-items-end mb-3">
              @csrf
              <div class="col-md-6">
                <label class="form-label">Category label</label>
                <input type="text" name="label" class="form-control" required>
              </div>
              <div class="col-md-6">
                <button type="submit" class="btn btn-primary">Add category</button>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th width="260">Actions</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($categories as $category)
                  <tr>
                    <td>
                      {{ $category->label }}
                      @if($category->slug === \App\Services\Module\ModuleDefaultCategoryService::DEFAULT_SLUG && $category->is_system)
                        <span class="badge bg-label-secondary ms-1">Default</span>
                      @endif
                    </td>
                    <td>
                      <form action="{{ route('settings-panel.module-settings.update-category', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey, 'id' => $category->id]) }}" method="POST" class="d-inline-flex gap-2">
                        @csrf
                        @method('PUT')
                        <input type="text" name="label" class="form-control form-control-sm" value="{{ $category->label }}" required>
                        <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                      </form>
                      @if(!($category->slug === \App\Services\Module\ModuleDefaultCategoryService::DEFAULT_SLUG && $category->is_system))
                      <form action="{{ route('settings-panel.module-settings.destroy-category', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey, 'id' => $category->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this category?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                      </form>
                      @endif
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="2" class="text-muted text-center">No categories added yet.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-fixed" role="tabpanel">
            <form action="{{ route('settings-panel.module-settings.store-field-assignment', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey]) }}" method="POST" class="row g-3 mb-3">
              @csrf
              <div class="col-md-3">
                <label class="form-label">Field key</label>
                <input type="text" name="field_key" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Field label</label>
                <input type="text" name="field_label" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                  @foreach($categories as $category)
                  <option value="{{ $category->id }}" @selected(isset($defaultCategory) && (int) $category->id === (int) $defaultCategory->id)>{{ $category->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-3">
                <label class="form-label">Display label</label>
                <input type="text" name="display_label" class="form-control">
              </div>
              <div class="col-md-3">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" name="is_visible" value="1" checked>
                  <label class="form-check-label">Visible</label>
                </div>
              </div>
              <div class="col-md-3">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" name="is_required" value="1">
                  <label class="form-check-label">Required</label>
                </div>
              </div>
              <div class="col-md-6">
                <button type="submit" class="btn btn-primary mt-3">Save assignment</button>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead>
                  <tr>
                    <th>Field key</th>
                    <th>Field label</th>
                    <th>Category</th>
                    <th>Display label</th>
                    <th>Visible</th>
                    <th>Required</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($fixedAssignments as $row)
                  <tr>
                    <td><code>{{ $row->field_key }}</code></td>
                    <td>{{ $row->field_label }}</td>
                    <td>{{ optional($row->category)->label }}</td>
                    <td>{{ $row->display_label }}</td>
                    <td>{{ $row->is_visible ? 'Yes' : 'No' }}</td>
                    <td>{{ $row->is_required ? 'Yes' : 'No' }}</td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="6" class="text-muted text-center">No field assignments yet.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-custom" role="tabpanel">
            <form action="{{ route('settings-panel.module-settings.store-field', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey]) }}" method="POST" class="row g-3 mb-3">
              @csrf
              <div class="col-md-4">
                <label class="form-label">Label</label>
                <input type="text" name="label" class="form-control" required>
              </div>
              <div class="col-md-4">
                <label class="form-label">Data type</label>
                <select name="data_type" class="form-select" required>
                  @foreach(($dataTypes ?? []) as $key => $def)
                  <option value="{{ $key }}">{{ $def['label'] ?? ucfirst($key) }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-4">
                <label class="form-label">Category</label>
                <select name="category_id" class="form-select">
                  @foreach($categories as $category)
                  <option value="{{ $category->id }}" @selected(isset($defaultCategory) && (int) $category->id === (int) $defaultCategory->id)>{{ $category->label }}</option>
                  @endforeach
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label">Help text</label>
                <input type="text" name="help_text" class="form-control">
              </div>
              <div class="col-md-4">
                <div class="form-check mt-4">
                  <input class="form-check-input" type="checkbox" name="is_mandatory" value="1">
                  <label class="form-check-label">Mandatory</label>
                </div>
              </div>
              <div class="col-md-12">
                <button type="submit" class="btn btn-primary">Add custom field</button>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead>
                  <tr>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Category</th>
                    <th>Mandatory</th>
                    <th width="120">Delete</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($customFields as $field)
                  <tr>
                    <td>{{ $field->label }}</td>
                    <td>{{ $field->data_type }}</td>
                    <td>{{ optional($field->category)->label }}</td>
                    <td>{{ $field->is_mandatory ? 'Yes' : 'No' }}</td>
                    <td>
                      <form action="{{ route('settings-panel.module-settings.destroy-field', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey, 'id' => $field->id]) }}" method="POST" onsubmit="return confirm('Delete this custom field?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="5" class="text-muted text-center">No custom fields added yet.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          <div class="tab-pane fade" id="tab-docs" role="tabpanel">
            <form action="{{ route('settings-panel.module-settings.store-document-type', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey]) }}" method="POST" class="row g-3 mb-3">
              @csrf
              <div class="col-md-3">
                <label class="form-label">Key</label>
                <input type="text" name="key" class="form-control" required>
              </div>
              <div class="col-md-3">
                <label class="form-label">Label</label>
                <input type="text" name="label" class="form-control">
              </div>
              <div class="col-md-2">
                <label class="form-label">Type</label>
                <select name="type" class="form-select">
                  <option value="single">Single</option>
                  <option value="dual">Dual</option>
                </select>
              </div>
              <div class="col-md-2">
                <label class="form-label">Front label</label>
                <input type="text" name="front_label" class="form-control">
              </div>
              <div class="col-md-2">
                <label class="form-label">Back label</label>
                <input type="text" name="back_label" class="form-control">
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-primary">Add document type</button>
              </div>
            </form>

            <div class="table-responsive">
              <table class="table table-sm table-bordered">
                <thead>
                  <tr>
                    <th>Key</th>
                    <th>Label</th>
                    <th>Type</th>
                    <th>Front</th>
                    <th>Back</th>
                    <th width="120">Delete</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($documentTypes as $doc)
                  <tr>
                    <td><code>{{ $doc->key }}</code></td>
                    <td>{{ $doc->label }}</td>
                    <td>{{ $doc->type }}</td>
                    <td>{{ $doc->front_label }}</td>
                    <td>{{ $doc->back_label }}</td>
                    <td>
                      <form action="{{ route('settings-panel.module-settings.destroy-document-type', ['company_slug' => $settingsCompanySlug, 'module' => $moduleKey, 'id' => $doc->id]) }}" method="POST" onsubmit="return confirm('Delete this document type?')">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                      </form>
                    </td>
                  </tr>
                  @empty
                  <tr>
                    <td colspan="6" class="text-muted text-center">No document types yet.</td>
                  </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>

          @include('settings.partials.top_bar._settings_tab_content')
        </div>
      </div>
    </div>
  </div>
</div>
@endsection