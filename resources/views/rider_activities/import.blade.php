@php
$successMessage = session('success');
$errorMessage = session('error');
$importSummary = session('activities_import_summary');
$validationErrors = $errors ? $errors->all() : [];
$formAction = $formAction ?? route('rider.activities_import');
$errorsRoute = $errorsRoute ?? route('rider.activities_import_errors', ['type' => 'noon']);
$customers = $customers ?? collect();
$configuredCustomerIds = $configuredCustomerIds ?? [];
$defaultCustomerId = $defaultCustomerId ?? \App\Services\RiderActivities\RiderActivityImportMappingService::DEFAULT_CUSTOMER_ID;
$importSettingsUrl = $importSettingsUrl ?? null;
$selectedCustomerId = (int) old('customer_id', $defaultCustomerId);
@endphp

<form
  action="{{ $formAction }}"
  method="POST"
  enctype="multipart/form-data"
  id="rider-activities-import-form">
  @csrf
  <div class="row">
    <div class="col-12">
      @if(!empty($importTypeLabel))
      <h5 class="text-primary mb-3">{{ $importTypeLabel }}</h5>
      @endif
      @if(!empty($sampleDownloadUrl))
      <a href="{{ $sampleDownloadUrl }}" class="text-success w-100" download="{{ $sampleDownloadLabel ?? 'Rider Activities Sample' }}">
        <i class="fa fa-file-download text-success"></i> &nbsp; {{ $sampleDownloadLabel ?? 'Download Sample File' }}
      </a>
      @endif
      <p class="text-muted mt-2">
        <small>Select the project, then upload the Excel file. Column mappings are loaded from import settings for that project.</small>
      </p>
      @if($importSettingsUrl)
      <p class="mb-0">
        <a href="{{ $importSettingsUrl }}" target="_blank" class="text-primary small">
          <i class="fa fa-cog"></i> Configure import mappings per project
        </a>
      </p>
      @endif
    </div>
    <div class="row">
      <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
          <a href="{{ url('sample/noon_activity_sample.xlsx') }}" class="text-success w-100" download="Noon Activities Sample">
            <i class="fa fa-file-download text-success"></i> &nbsp; Download Sample File
          </a>
        </div>
      </div>
    </div>
    <div class="col-12 mt-3 mb-3">
      <label class="mb-2 pl-2">Project</label>
      <select name="customer_id" class="form-control mb-3" required>
        @forelse($customers as $customer)
        @php
        $isReady = in_array((int) $customer->id, $configuredCustomerIds, true)
          || (int) $customer->id === (int) $defaultCustomerId;
        @endphp
        <option value="{{ $customer->id }}" @selected((int) $customer->id === $selectedCustomerId) @disabled(!$isReady)>
          {{ $customer->name }}@if((int) $customer->id === (int) $defaultCustomerId) (Noon — default)@endif@if(!$isReady) — configure in settings @endif
        </option>
        @empty
        <option value="{{ $defaultCustomerId }}" selected>Default Project (ID: {{ $defaultCustomerId }})</option>
        @endforelse
      </select>
    </div>
    <div class="col-12 mt-3 mb-3">
      @include('rider_activities.partials.file_preview', [
        'previewPrefix' => 'rider-act-import',
        'previewUrl' => $previewUrl ?? route('rider.activities_import_preview'),
        'fieldLabels' => $fieldLabels ?? \App\Services\RiderActivities\RiderActivityImportMappingService::fieldLabels(),
        'previewConfigs' => $previewConfigs ?? [],
      ])
    </div>
  </div>
  <button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Start Import</button>
  <a href="{{ $errorsRoute }}" class="btn btn-info" style="width: 100%; margin-top: 10px;">Check Last Import Errors</a>
</form>

@include('rider_activities.partials.import_ajax_script', [
  'formId' => 'rider-activities-import-form',
  'errorsRoute' => $errorsRoute,
  'reloadOnSuccess' => true,
])
