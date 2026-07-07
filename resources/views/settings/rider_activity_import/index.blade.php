@extends('layouts.settingsPanelLayout')

@section('title', 'Activity Import Settings')

@section('content')
@include('flash::message')
@php
$companySlug = request()->route('company_slug') ?? session('company_slug');
$importType = $importType ?? \App\Services\RiderActivities\RiderActivityImportMappingService::TYPE_RIDER;
$importTypeLabels = $importTypeLabels ?? \App\Services\RiderActivities\RiderActivityImportMappingService::importTypeLabels();
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header">
        <h4 class="card-title mb-0">Activity Import Settings</h4>
        <p class="text-muted small mb-0 mt-1">
          Configure Excel column mappings per project for rider activities and live activities.
          Customer ID {{ $defaultCustomerId }} uses the built-in Noon format unless you save custom mappings here.
        </p>
      </div>
      <div class="card-body">
        <ul class="nav nav-tabs mb-4">
          @foreach($importTypeLabels as $typeKey => $typeLabel)
          <li class="nav-item">
            <a
              class="nav-link @if($importType === $typeKey) active @endif"
              href="{{ route('settings-panel.rider-activity-import-settings.index', ['company_slug' => $companySlug, 'import_type' => $typeKey, 'customer_id' => $selectedCustomerId]) }}">
              {{ $typeLabel }}
            </a>
          </li>
          @endforeach
        </ul>

        <form method="GET" action="{{ route('settings-panel.rider-activity-import-settings.index', ['company_slug' => $companySlug]) }}" class="row g-3 align-items-end mb-4">
          <input type="hidden" name="import_type" value="{{ $importType }}">
          <div class="col-md-6">
            <label class="form-label" for="customer_id">Project</label>
            <select name="customer_id" id="customer_id" class="form-select" onchange="this.form.submit()">
              @foreach($customers as $customer)
              <option value="{{ $customer->id }}" @selected((int) $customer->id === (int) $selectedCustomerId)>
                {{ $customer->name }} (ID: {{ $customer->id }})
              </option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            @if((int) $selectedCustomerId === (int) $defaultCustomerId)
            <span class="badge bg-label-success">Built-in import format active</span>
            @elseif(in_array((int) $selectedCustomerId, $configuredCustomerIds, true))
            <span class="badge bg-label-primary">Custom mappings configured</span>
            @else
            <span class="badge bg-label-warning">Not configured — import disabled until mappings are saved</span>
            @endif
          </div>
        </form>

        <form method="POST" action="{{ route('settings-panel.rider-activity-import-settings.store', ['company_slug' => $companySlug]) }}">
          @csrf
          <input type="hidden" name="customer_id" value="{{ $selectedCustomerId }}">
          <input type="hidden" name="import_type" value="{{ $importType }}">

          <div class="row g-3 mb-4">
            <div class="col-md-4">
              <label class="form-label" for="header_rows_to_skip">Header rows to skip</label>
              <input type="number" min="0" max="20" class="form-control" id="header_rows_to_skip" name="header_rows_to_skip" value="{{ old('header_rows_to_skip', $headerRowsToSkip) }}" required>
              <small class="text-muted">Number of top rows to ignore before data (Noon default: 2).</small>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="is_active" id="is_active" value="1" @checked(old('is_active', $storedSetting?->is_active ?? true))>
                <label class="form-check-label" for="is_active">Import enabled for this project</label>
              </div>
            </div>
          </div>

          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead>
                <tr>
                  <th>System Field</th>
                  <th style="width: 180px;">Excel Column Index</th>
                  <th style="width: 140px;">Excel Column</th>
                  <th>Noon Default</th>
                  <th>Required</th>
                </tr>
              </thead>
              <tbody>
                @foreach($fieldLabels as $fieldKey => $fieldLabel)
                @php
                $currentValue = old('column_mappings.' . $fieldKey, $columnMappings[$fieldKey] ?? $defaultMappings[$fieldKey]);
                $columnLetter = '';
                if (is_numeric($currentValue)) {
                $index = (int) $currentValue;
                $columnLetter = $index < 26 ? chr(65 + $index) : 'Col ' . ($index + 1);
                }
                @endphp
                <tr>
                  <td>{{ $fieldLabel }}</td>
                  <td>
                    <input
                      type="number"
                      min="0"
                      class="form-control form-control-sm column-index-input"
                      name="column_mappings[{{ $fieldKey }}]"
                      value="{{ $currentValue }}"
                      data-field="{{ $fieldKey }}"
                      @if(!empty($requiredFields[$fieldKey])) required @endif>
                  </td>
                  <td class="column-letter text-muted" data-field="{{ $fieldKey }}">{{ $columnLetter }}</td>
                  <td>{{ $defaultMappings[$fieldKey] ?? '—' }}</td>
                  <td>
                    @if(!empty($requiredFields[$fieldKey]))
                    <span class="badge bg-label-danger">Yes</span>
                    @else
                    <span class="badge bg-label-secondary">No</span>
                    @endif
                  </td>
                </tr>
                @endforeach
              </tbody>
            </table>
          </div>

          <p class="text-muted small mb-3">
            Column index is zero-based (A = 0, B = 1, C = 2, …). Match each system field to the column position in your project's Excel export.
          </p>

          <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary">Save Import Settings</button>
            <button type="button" class="btn btn-outline-secondary" id="reset-defaults-btn">Reset to Noon Defaults</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    const defaultMappings = @json($defaultMappings);
    const defaultHeaderRows = {{ (int) \App\Services\RiderActivities\RiderActivityImportMappingService::defaultHeaderRowsToSkip() }};

    const indexToLetter = (index) => {
      const value = parseInt(index, 10);
      if (Number.isNaN(value) || value < 0) {
        return '';
      }
      if (value < 26) {
        return String.fromCharCode(65 + value);
      }
      return 'Col ' + (value + 1);
    };

    const updateColumnLetters = () => {
      document.querySelectorAll('.column-index-input').forEach((input) => {
        const field = input.dataset.field;
        const letterCell = document.querySelector('.column-letter[data-field="' + field + '"]');
        if (letterCell) {
          letterCell.textContent = indexToLetter(input.value);
        }
      });
    };

    document.querySelectorAll('.column-index-input').forEach((input) => {
      input.addEventListener('input', updateColumnLetters);
    });

    document.getElementById('reset-defaults-btn')?.addEventListener('click', function() {
      document.getElementById('header_rows_to_skip').value = defaultHeaderRows;
      Object.keys(defaultMappings).forEach((field) => {
        const input = document.querySelector('.column-index-input[data-field="' + field + '"]');
        if (input) {
          input.value = defaultMappings[field];
        }
      });
      updateColumnLetters();
    });

    updateColumnLetters();
  });
</script>
@endsection
