@extends($layout ?? 'layouts.app')

@section('title', $pageTitle ?? 'Module Settings')

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <div>
          <h4 class="card-title mb-0">{{ $moduleLabel }} – Settings</h4>
          <p class="text-muted small mb-0 mt-1">Change the name that appears in the main application menu for this module.</p>
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
        </ul>

        <div class="tab-content" id="moduleSettingsTabContent">
          <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
            <p class="text-muted small mb-3">This name appears in the left sidebar menu of the main application.</p>
            <form action="{{ route('settings-panel.module-settings.store-module-label', ['module' => $moduleKey]) }}" method="POST" class="row g-3 align-items-end">
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
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
