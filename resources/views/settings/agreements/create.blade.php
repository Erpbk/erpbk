@extends($layout ?? 'layouts.app')

@section('title', 'Create Agreement')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug');
$groupLabel = $groups[$groupKey]['label'] ?? $groupKey;
@endphp

<div class="row">
  <div class="col-lg-10 mx-auto">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">Create Agreement</h4>
          <div class="text-muted small mt-1">Group: {{ $groupLabel }}</div>
        </div>
        <a href="{{ route('documents.agreements.index', ['company_slug' => $companySlug, 'group' => $groupKey]) }}" class="btn btn-outline-secondary btn-sm">
          Back
        </a>
      </div>

      <div class="card-body">
        <form method="POST" action="{{ route('documents.agreements.store-agreement', ['company_slug' => $companySlug]) }}">
          @csrf
          <input type="hidden" name="group_key" value="{{ $groupKey }}">

          <div class="mb-3">
            <label class="form-label">Agreement Name</label>
            <input type="text" name="agreement_name" class="form-control" value="{{ old('agreement_name') }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Agreement Code</label>
            <input type="text" name="agreement_code" class="form-control" value="{{ old('agreement_code') }}" required placeholder="e.g. RIDER_CONTRACT">
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description') }}</textarea>
          </div>

          <p class="alert alert-info py-2 small">
            After creating this agreement, open <strong>Edit</strong> to choose which sample contract template (Corporate or Premium) will be used by assigned modules.
          </p>

          <div class="mb-3">
            <label class="form-label">Assigned Modules <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">Choose which modules can use this agreement.</p>
            <div class="row g-2">
              @foreach($modules as $moduleKey => $label)
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="assigned_modules[]" value="{{ $moduleKey }}"
                    id="mod_{{ $moduleKey }}"
                    {{ in_array($moduleKey, old('assigned_modules', []), true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="mod_{{ $moduleKey }}">
                    {{ $label }}
                  </label>
                </div>
              </div>
              @endforeach
            </div>
            @error('assigned_modules')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
            @error('assigned_modules.*')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch"
                  {{ old('status', true) ? 'checked' : '' }}>
                <label class="form-check-label" for="statusSwitch">Agreement enabled</label>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-device-floppy me-1"></i> Create Agreement
            </button>
            <a href="{{ route('documents.agreements.index', ['company_slug' => $companySlug, 'group' => $groupKey]) }}" class="btn btn-outline-secondary">
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection