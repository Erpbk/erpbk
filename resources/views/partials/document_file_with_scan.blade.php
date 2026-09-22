@php
  $name = $name ?? 'file_name';
  $id = $id ?? ('doc_scan_' . str_replace(['[', ']', '.'], '_', $name) . '_' . substr(uniqid(), -5));
  $scanId = $id . '_camera';
  $scannerId = $id . '_scanner';
  $label = $label ?? null;
  $required = (bool) ($required ?? false);
  $accept = $accept ?? '.jpg,.jpeg,.png,.pdf,.tif,.tiff,.doc,.docx,image/jpeg,image/png,image/tiff,application/pdf';
  $inputClass = trim('document-scan-file-input ' . ($inputClass ?? ''));
  $labelClass = $labelClass ?? 'form-label';
  $showHint = array_key_exists('showHint', get_defined_vars()) ? (bool) $showHint : true;
  $compact = (bool) ($compact ?? false);
  $multiple = (bool) ($multiple ?? false);
  $variant = $variant ?? 'default'; // default | dropzone
  $isDropzone = $variant === 'dropzone';
  $uploadLabel = $uploadLabel ?? ($isDropzone ? 'Upload' : 'Upload File');
  $existingUrl = $existingUrl ?? null;
  $existingName = $existingName ?? ($existingUrl ? basename(parse_url($existingUrl, PHP_URL_PATH) ?: $existingUrl) : null);
  $existingIsPdf = $existingIsPdf ?? ($existingUrl ? \Illuminate\Support\Str::endsWith(strtolower((string) $existingUrl), '.pdf') : false);
@endphp
<div class="document-scan-field {{ $compact ? 'document-scan-field--compact' : '' }} {{ $isDropzone ? 'document-scan-field--dropzone' : '' }}"
     data-document-scan-root
     data-universal-document-upload
     @if($isDropzone) data-scan-variant="dropzone" @endif>
  @if($label !== null && $label !== '')
    <label class="{{ $labelClass }}{{ $required ? ' required fw-bold' : '' }}" for="{{ $id }}">{{ $label }}</label>
  @endif

  <input
    type="file"
    name="{{ $name }}"
    id="{{ $id }}"
    class="document-scan-native-file {{ $inputClass }}{{ isset($errorKey) && $errors->has($errorKey) ? ' is-invalid' : '' }}"
    accept="{{ $accept }}"
    @if($required) required @endif
    @if($multiple) multiple @endif>

  <input
    type="file"
    id="{{ $scanId }}"
    class="document-scan-camera-input document-scan-native-file"
    accept="image/*"
    capture="environment"
    tabindex="-1"
    aria-hidden="true">

  <input
    type="file"
    id="{{ $scannerId }}"
    class="document-scan-scanner-input document-scan-native-file"
    accept="image/*,application/pdf,.jpg,.jpeg,.png,.tif,.tiff,.pdf"
    multiple
    tabindex="-1"
    aria-hidden="true">

  @if($isDropzone)
    <div class="document-scan-dropzone-wrap">
      <div class="document-scan-dropzone" role="button" tabindex="0" title="Click to upload"
           data-dropzone-for="{{ $id }}"
           data-existing-url="{{ $existingUrl ?? '' }}"
           data-existing-pdf="{{ $existingIsPdf ? '1' : '0' }}">
        <div class="document-scan-dropzone-placeholder">
          <i class="fa fa-image document-scan-dropzone-icon"></i>
          <span class="document-scan-dropzone-text">Click to upload</span>
        </div>
        <img class="document-scan-dropzone-image" alt="Preview" hidden>
        <div class="document-scan-dropzone-pdf" hidden>
          <i class="fa fa-file-pdf-o"></i>
          <span>PDF File</span>
        </div>
      </div>

      <div class="document-scan-dropzone-actions">
        <button type="button" class="btn btn-outline-primary btn-sm document-scan-upload-btn w-100"
          data-file-input="{{ $id }}">
          <i class="ti ti-upload me-1"></i>{{ $uploadLabel }}
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm document-scan-camera-btn document-scan-mobile-only w-100"
          data-file-input="{{ $id }}"
          data-camera-input="{{ $scanId }}"
          data-scanner-input="{{ $scannerId }}"
          data-scan-source="camera">
          <i class="ti ti-camera me-1"></i>Scan with Camera
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm document-scan-scanner-btn document-scan-desktop-only w-100"
          data-file-input="{{ $id }}"
          data-camera-input="{{ $scanId }}"
          data-scanner-input="{{ $scannerId }}"
          data-scan-source="scanner">
          Document Scanner
        </button>
      </div>
    </div>

    <div class="document-scan-selected small text-muted mt-1 text-center"
         data-selected-for="{{ $id }}"
         data-empty-text="{{ $existingName ? ('Current: ' . $existingName) : 'No file selected' }}">
      @if($existingName)
        Current: {{ $existingName }}
      @else
        No file selected
      @endif
    </div>
  @else
    <div class="d-flex flex-wrap gap-2 mb-1">
      <button type="button" class="btn btn-outline-primary btn-sm document-scan-upload-btn"
        data-file-input="{{ $id }}">
        <i class="ti ti-upload me-1"></i>{{ $uploadLabel }}
      </button>
      <button type="button" class="btn btn-outline-secondary btn-sm document-scan-camera-btn document-scan-mobile-only"
        data-file-input="{{ $id }}"
        data-camera-input="{{ $scanId }}"
        data-scanner-input="{{ $scannerId }}"
        data-scan-source="camera">
        <i class="ti ti-camera me-1"></i>Scan with Camera
      </button>
      <button type="button" class="btn btn-outline-secondary btn-sm document-scan-scanner-btn document-scan-desktop-only"
        data-file-input="{{ $id }}"
        data-camera-input="{{ $scanId }}"
        data-scanner-input="{{ $scannerId }}"
        data-scan-source="scanner">
        <i class="ti ti-scanner me-1"></i>Document Scanner
      </button>
    </div>

    <div class="document-scan-selected small text-muted" data-selected-for="{{ $id }}">No file selected</div>
  @endif

  @if($showHint)
    <div class="form-text document-scan-hint-desktop document-scan-desktop-only">Upload a file or scan with a connected scanner. Multiple pages are combined into one PDF.</div>
    <div class="form-text document-scan-hint-mobile document-scan-mobile-only">Upload a file or scan with the camera. Multiple pages are combined into one PDF.</div>
  @endif

  @if(isset($errorKey))
    @error($errorKey)
      <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
  @endif
  <div class="invalid-feedback rider-document-upload-error document-scan-upload-error"></div>
</div>
