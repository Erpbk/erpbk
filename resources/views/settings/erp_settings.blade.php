@extends('layouts.app')
@section('title', 'ERP Settings')

@section('content')
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
          Changes can be saved once the save functionality is implemented.
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

{{-- Sidebar / Menu bar item names --}}
<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-header">
        <h5 class="card-title mb-0">Sidebar (Menu Bar) Labels</h5>
        <p class="text-muted small mb-0 mt-1">Change the display names of sidebar menu items. These names appear in the left navigation.</p>
      </div>
      <div class="card-body">
        <form action="#" method="post" id="erp-settings-form">
          @csrf
          <div class="row">
            @foreach($menuLabels as $key => $label)
            <div class="col-md-6 col-lg-4 mb-3">
              <label class="form-label text-capitalize">{{ str_replace('_', ' ', $key) }}</label>
              <input type="text" class="form-control form-control-sm" name="menu_labels[{{ $key }}]" value="{{ $label }}" placeholder="{{ $label }}" />
            </div>
            @endforeach
          </div>
          <hr />
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" disabled title="Save functionality to be implemented">Save changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection
