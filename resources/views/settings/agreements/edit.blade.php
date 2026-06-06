@extends($layout ?? 'layouts.app')

@section('title', 'Edit Agreement – Settings')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug');
$groupLabel = $groups[$category->group_key]['label'] ?? $category->group_key;
@endphp

<div class="row">
  <div class="col-lg-10 mx-auto">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-0">Edit Agreement</h4>
          <div class="text-muted small mt-1">Group: {{ $groupLabel }}</div>
        </div>
        <div class="d-flex gap-2 flex-wrap">
          <a href="{{ route('settings-panel.agreements.index', ['company_slug' => $companySlug, 'group' => $category->group_key]) }}" class="btn btn-outline-secondary btn-sm">
            Back
          </a>
        </div>
      </div>

      <div class="card-body">
        <form method="POST" action="{{ route('settings-panel.agreements.update-agreement', ['company_slug' => $companySlug, 'category' => $category->id]) }}">
          @csrf
          @method('PUT')

          <div class="mb-3">
            <label class="form-label">Agreement Name</label>
            <input type="text" name="agreement_name" class="form-control" value="{{ old('agreement_name', $category->name) }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Agreement Code</label>
            <input type="text" name="agreement_code" class="form-control" value="{{ old('agreement_code', $category->agreement_code ?? $category->slug) }}" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Description</label>
            <textarea name="description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Contract template <span class="text-danger">*</span></label>
            <p class="text-muted small mb-2">
              Select which sample template is used when generating this contract from the module. Template content is edited under Riders → Contract Templates.
            </p>
            <select name="contract_template_id" class="form-select" required>
              @forelse($category->templates as $tpl)
              <option value="{{ $tpl->id }}"
                {{ (int) old('contract_template_id', $contractTemplateId) === (int) $tpl->id ? 'selected' : '' }}>
                {{ $tpl->template_name }}
                — {{ \App\Models\AgreementTemplate::TYPES[$tpl->template_type] ?? $tpl->template_type }}
              </option>
              @empty
              <option value="" disabled>No sample templates available</option>
              @endforelse
            </select>
            @error('contract_template_id')
            <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
          </div>

          <div class="mb-3">
            <label class="form-label">Assigned Modules</label>
            <div class="row g-2">
              @foreach($modules as $moduleKey => $label)
              <div class="col-md-4">
                <div class="form-check">
                  <input class="form-check-input" type="checkbox" name="assigned_modules[]" value="{{ $moduleKey }}"
                    id="mod_{{ $moduleKey }}"
                    {{ in_array($moduleKey, old('assigned_modules', $category->assigned_modules ?? []), true) ? 'checked' : '' }}>
                  <label class="form-check-label" for="mod_{{ $moduleKey }}">
                    {{ $label }}
                  </label>
                </div>
              </div>
              @endforeach
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox" name="status" value="1" id="statusSwitch"
                  {{ old('status', $category->status) ? 'checked' : '' }}>
                <label class="form-check-label" for="statusSwitch">Agreement enabled</label>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="ti ti-device-floppy me-1"></i> Save Agreement
            </button>
            <a href="{{ route('settings-panel.agreements.index', ['company_slug' => $companySlug, 'group' => $category->group_key]) }}" class="btn btn-outline-secondary">
              Cancel
            </a>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

@endsection