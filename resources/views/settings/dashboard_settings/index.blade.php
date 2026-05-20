@extends('layouts.settingsPanelLayout')

@section('title', $pageTitle ?? 'Dashboard Settings')

@section('content')
@include('flash::message')

@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h4 class="card-title mb-0">{{ $pageTitle }}</h4>
        <p class="text-muted small mb-0 mt-2">
          {{ __('Choose up to :count summary cards for your home dashboard. Each card shows active and inactive counts.', ['count' => $maxVisibleCards ?? 8]) }}
        </p>
      </div>
    </div>
  </div>
</div>

<div class="row">
  <div class="col-12">
    <div class="card">
      <div class="card-body">
        <form method="post" action="{{ route('settings-panel.dashboard-settings.cards', ['company_slug' => $companySlug]) }}">
          @csrf
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width: 48px;">{{ __('Show') }}</th>
                  <th>{{ __('Module') }}</th>
                </tr>
              </thead>
              <tbody>
                @foreach($definitions as $key => $def)
                <tr>
                  <td>
                    <div class="form-check mb-0">
                      <input class="form-check-input dash-card-checkbox" type="checkbox" name="cards[]" value="{{ $key }}" id="dash-card-{{ $key }}"
                        {{ isset($selectedSet[$key]) ? 'checked' : '' }}>
                    </div>
                  </td>
                  <td>
                    <label class="form-check-label mb-0 fw-medium" for="dash-card-{{ $key }}">
                      <i class="ti {{ $def['icon'] ?? 'ti-layout-grid' }} me-1 text-primary"></i>
                      {{ $def['label'] ?? $key }}
                    </label>
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <p class="text-muted small">
            {{ __('Select at most :count modules. Leave all unchecked to hide every dashboard card.', ['count' => $maxVisibleCards ?? 8]) }}
            <span id="dash-card-count" class="fw-medium"></span>
          </p>
          <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
        </form>
      </div>
    </div>
  </div>
</div>

@push('page-scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  var maxCards = {{ (int) ($maxVisibleCards ?? 8) }};
  var checkboxes = document.querySelectorAll('.dash-card-checkbox');
  var countEl = document.getElementById('dash-card-count');

  function selectedCount() {
    return document.querySelectorAll('.dash-card-checkbox:checked').length;
  }

  function refreshCount() {
    if (countEl) {
      countEl.textContent = '(' + selectedCount() + ' / ' + maxCards + ')';
    }
    var atLimit = selectedCount() >= maxCards;
    checkboxes.forEach(function (cb) {
      if (!cb.checked) {
        cb.disabled = atLimit;
      }
    });
  }

  checkboxes.forEach(function (cb) {
    cb.addEventListener('change', function () {
      if (selectedCount() > maxCards) {
        cb.checked = false;
      }
      refreshCount();
    });
  });

  refreshCount();
});
</script>
@endpush
@endsection
