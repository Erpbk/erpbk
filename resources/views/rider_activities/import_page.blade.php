@extends('layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', 'Import Rider Activities')

@push('third_party_stylesheets')
<style>
  .imp-page-header {
    margin-bottom: 1.5rem;
  }

  .imp-back-btn {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #4b5563;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    transition: background .15s, color .15s;
  }

  .imp-back-btn:hover {
    background: #f1f5f9;
    color: #111827;
    text-decoration: none;
  }

  .imp-title {
    font-size: 1.35rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
  }

  .imp-subtitle {
    color: #6b7280;
    font-size: .875rem;
    margin: .2rem 0 0;
  }

  .imp-card {
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(15, 23, 42, .06);
    padding: 2rem;
    max-width: 860px;
    margin: 0 auto;
  }

  .imp-section-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .06em;
    color: #94a3b8;
    margin-bottom: .5rem;
  }

  .imp-info-banner {
    background: #eff6ff;
    color: #1e40af;
    border-radius: 8px;
    padding: .7rem 1rem;
    font-size: .85rem;
    display: flex;
    align-items: flex-start;
    gap: .6rem;
  }

  .imp-divider {
    border: 0;
    border-top: 1px solid #f1f5f9;
    margin: 1.5rem 0;
  }

  .imp-actions {
    display: flex;
    gap: .75rem;
    margin-top: 1.5rem;
  }

  .imp-actions .btn {
    flex: 1;
  }
</style>
@endpush

@section('content')
@php
$successMessage = session('success');
$errorMessage = session('error');
$importSummary = session('activities_import_summary');
$validationErrors = $errors ? $errors->all() : [];
$defaultCustomerId = $defaultCustomerId ?? \App\Services\RiderActivities\RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID;
$selectedCustomerId = (int) old('customer_id', $defaultCustomerId);
$errorsRoute = route('rider.activities_import_errors', ['type' => 'noon']);
@endphp

<div class="container-fluid">
  {{-- Page header --}}
  <div class="imp-page-header d-flex align-items-center gap-3">
    <a href="{{ route('riderActivities.index') }}" class="imp-back-btn" title="Back to Rider Activities">
      <i class="ti ti-arrow-left" style="font-size:1.1rem;"></i>
    </a>
    <div>
      <h1 class="imp-title">
        <i class="ti ti-activity" style="color:#2563eb;margin-right:.3rem;"></i>
        Import Rider Activities
      </h1>
      <p class="imp-subtitle">Upload an Excel or CSV file to bulk-import rider activity records.</p>
    </div>
    @if($importSettingsUrl)
    <div class="ml-auto">
      <a href="{{ $importSettingsUrl }}" class="btn btn-outline-secondary btn-sm" target="_blank">
        <i class="ti ti-settings"></i> Configure Mappings
      </a>
    </div>
    @endif
  </div>

  <div class="imp-card">
    {{-- Info banner --}}
    <div class="imp-info-banner mb-4">
      <i class="ti ti-info-circle" style="font-size:1.1rem;flex-shrink:0;margin-top:.05rem;"></i>
      <span>
        Select the project, then upload the Excel file.
        Column mappings are loaded from import settings for that project.
        <a href="{{ url('sample/noon_activity_sample.xlsx') }}" class="font-weight-bold ml-1" download="Noon Activities Sample">
          <i class="ti ti-download" style="font-size:.85rem;"></i> Download sample file
        </a>
      </span>
    </div>

    <form
      action="{{ route('rider.activities_import_page') }}"
      method="POST"
      enctype="multipart/form-data"
      id="rider-activities-import-page-form">
      @csrf

      {{-- Project select --}}
      <div class="mb-4">
        <label class="imp-section-label">Project</label>
        <select name="customer_id" class="form-control" required>
          @forelse($customers as $customer)
          @php
          $isReady = in_array((int) $customer->id, $configuredCustomerIds, true)
          || (int) $customer->id === (int) $defaultCustomerId;
          @endphp
          <option value="{{ $customer->id }}"
            @selected((int) $customer->id === $selectedCustomerId)
            @disabled(!$isReady)>
            {{ $customer->name }}{{ (int) $customer->id === (int) $defaultCustomerId ? ' (Noon — default)' : '' }}{{ !$isReady ? ' — configure in settings' : '' }}
          </option>
          @empty
          <option value="{{ $defaultCustomerId }}" selected>Default Project (ID: {{ $defaultCustomerId }})</option>
          @endforelse
        </select>
      </div>

      <hr class="imp-divider">

      {{-- File upload + preview --}}
      <div class="mb-3">
        <label class="imp-section-label">File</label>
        @include('rider_activities.partials.file_preview', [
        'previewPrefix' => 'rider-act-import-pg',
        'previewUrl' => $previewUrl ?? route('rider.activities_import_preview'),
        'fieldLabels' => $fieldLabels ?? \App\Services\RiderActivities\RiderActivityImportMappingService::fieldLabels(),
        'previewConfigs' => $previewConfigs ?? [],
        ])
      </div>

      <div class="imp-actions">
        <button type="submit" class="btn btn-primary">
          <i class="ti ti-upload"></i> Start Import
        </button>
        <a href="{{ $errorsRoute }}" class="btn btn-outline-info">
          <i class="ti ti-alert-triangle"></i> Last Import Errors
        </a>
      </div>
    </form>
  </div>
</div>

@include('rider_activities.partials.import_ajax_script', [
  'formId' => 'rider-activities-import-page-form',
  'errorsRoute' => $errorsRoute,
  'reloadOnSuccess' => true,
])
@endsection
