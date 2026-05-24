@extends($layout ?? 'layouts.app')
@section('title', 'ERP Settings')

@section('content')
@include('flash::message')
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h4 class="card-title mb-0">ERP Settings</h4>
        <small class="text-muted">Central place for all ERP configuration</small>
      </div>
      <div class="card-body">
        <p class="text-muted mb-0">
          Configure general ERP options and customize sidebar (menu bar) labels from this page.
          Changes are applied to the main ERP menu immediately after saving.
        </p>
      </div>
    </div>
  </div>
</div>

{{-- General ERP Settings (placeholder for future options) --}}
<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h5 class="card-title mb-0">General Settings</h5>
      </div>
      <div class="card-body">
        <p class="text-muted small">General ERP-wide settings will appear here (e.g. date format, currency, defaults).</p>
        <div class="row">
          <div class="col-md-6">
            <label class="form-label">Application name</label>
            <input type="text" class="form-control" name="app_name" value="{{ config('app.name') }}" readonly disabled />
            <small class="text-muted">Controlled by .env / config</small>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection