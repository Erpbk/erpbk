@php
  $isDual = ($docType->type ?? '') === 'dual';
  $title = \App\Support\RiderDocumentChecklist::cleanDocumentLabel(
    $docType->label ?: '',
    $isDual ? ($docType->front_label ?: $docType->key) : ($docType->key ?: '')
  );
  $accept = '.jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf';
  $existingAny = (bool) ($existingAny ?? false);
  $fieldValue = static function ($values, $key) {
    if (!is_array($values) || !array_key_exists($key, $values)) {
      return '';
    }
    $v = $values[$key];
    if ($v === null || $v === '' || (is_string($v) && strtolower(trim($v)) === 'null')) {
      return '';
    }
    return $v;
  };
@endphp

{!! Form::open([
  'url' => route('files.rider-document-upload.store', ['rider' => $riders->id, 'documentType' => $docType->id]),
  'method' => 'POST',
  'id' => 'formajax',
  'enctype' => 'multipart/form-data',
  'class' => 'rider-doc-upload-form',
]) !!}

<div class="rider-doc-upload">
  <div class="rider-doc-upload-intro mb-3">
    <div class="d-flex align-items-start gap-2">
      <span class="rider-doc-upload-badge">
        <i class="ti {{ $isDual ? 'ti-copy' : 'ti-file' }}"></i>
      </span>
      <div>
        <div class="fw-semibold">{{ $title }}</div>
        <div class="text-muted small">
          @if($isDual)
            Dual document — upload Front and Back first, then enter details once below.
          @else
            Single document — upload the file first, then complete the fields below.
          @endif
        </div>
      </div>
    </div>
  </div>

  @if($isDual)
    @php
      $sharedFields = $shared['fields'] ?? collect();
      $sharedValues = $shared['values'] ?? [];
    @endphp

    <div class="rider-doc-sides-row">
      @foreach(['front' => $front, 'back' => $back] as $sideKey => $side)
        @php
          $sideLabel = $sideKey === 'front'
            ? ($docType->front_label ?: ($side['subtype']->label ?? 'Front'))
            : ($docType->back_label ?: ($side['subtype']->label ?? 'Back'));
          $existing = $side['file'] ?? null;
          $existingUrl = $existing
            ? storage_url($existing->type . '/' . $existing->type_id . '/' . $existing->file_name)
            : null;
          $needsFile = !$existing;
        @endphp
        <div class="card rider-doc-side-card">
          <div class="card-header py-2 px-3 d-flex align-items-center justify-content-between gap-2">
            <div class="fw-semibold text-truncate">
              <i class="ti ti-rectangle me-1 text-primary"></i>{{ $sideLabel }}
            </div>
            @if($existing)
              <span class="badge bg-label-success flex-shrink-0">Uploaded</span>
            @else
              <span class="badge bg-label-secondary flex-shrink-0">Required</span>
            @endif
          </div>
          <div class="card-body">
            @include('partials.universal_document_upload', [
              'name' => 'sides['.$sideKey.'][file]',
              'id' => 'rider_doc_'.$docType->id.'_'.$sideKey.'_file',
              'label' => $sideLabel . ' file',
              'required' => $needsFile,
              'accept' => $accept,
              'showHint' => true,
              'compact' => false,
              'variant' => 'dropzone',
              'existingUrl' => $existingUrl,
              'existingName' => $existing->name ?? ($existing->file_name ?? null),
              'existingIsPdf' => $existing ? str_ends_with(strtolower((string) $existing->file_type), 'pdf') : false,
            ])
            @if($existing && $existingUrl)
              <div class="small mt-2">
                <a href="{{ $existingUrl }}" target="_blank" rel="noopener">View current file</a>
                <span class="text-muted"> — leave empty to keep it</span>
              </div>
            @endif
          </div>
        </div>
      @endforeach
    </div>

    @if($sharedFields->isNotEmpty())
      <div class="card rider-doc-side-card mb-3">
        <div class="card-header py-2 px-3">
          <div class="fw-semibold">
            <i class="ti ti-forms me-1 text-primary"></i>Document details
          </div>
        </div>
        <div class="card-body">
          <div class="row g-3">
            @foreach($sharedFields as $field)
              @include('files._rider_document_field', [
                'field' => $field,
                'val' => $fieldValue($sharedValues, $field->key),
                'fname' => 'field_values['.$field->key.']',
              ])
            @endforeach
          </div>
        </div>
      </div>
    @endif
  @else
    @php
      $side = $main ?? ['file' => null, 'fields' => collect(), 'values' => []];
      $existing = $side['file'] ?? null;
      $existingUrl = $existing
        ? storage_url($existing->type . '/' . $existing->type_id . '/' . $existing->file_name)
        : null;
      $values = $side['values'] ?? [];
      $fields = $side['fields'] ?? collect();
      $needsFile = !$existing;
    @endphp
    <div class="card rider-doc-side-card mb-3">
      <div class="card-body">
        @include('partials.universal_document_upload', [
          'name' => 'sides[main][file]',
          'id' => 'rider_doc_'.$docType->id.'_main_file',
          'label' => 'Document file',
          'required' => $needsFile,
          'accept' => $accept,
          'showHint' => true,
          'compact' => false,
          'variant' => 'dropzone',
          'existingUrl' => $existingUrl,
          'existingName' => $existing->name ?? ($existing->file_name ?? null),
          'existingIsPdf' => $existing ? str_ends_with(strtolower((string) $existing->file_type), 'pdf') : false,
        ])
        @if($existing && $existingUrl)
          <div class="small mt-2 mb-3">
            <a href="{{ $existingUrl }}" target="_blank" rel="noopener">View current file</a>
            <span class="text-muted"> — leave empty to keep it</span>
          </div>
        @endif

        @if($fields->isNotEmpty())
          <div class="row g-3 mt-1">
            @foreach($fields as $field)
              @include('files._rider_document_field', [
                'field' => $field,
                'val' => $fieldValue($values, $field->key),
                'fname' => 'sides[main][field_values]['.$field->key.']',
              ])
            @endforeach
          </div>
        @endif
      </div>
    </div>
  @endif
</div>

<div class="action-btn pt-2 d-flex justify-content-end gap-2">
  <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
  {!! Form::submit($existingAny ? 'Update Document' : 'Save Document', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}

<style>
  .rider-doc-upload-badge {
    width: 2.25rem;
    height: 2.25rem;
    border-radius: 0.6rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: rgba(30, 75, 142, 0.08);
    color: #1e4b8e;
    flex-shrink: 0;
  }
  .rider-doc-sides-row {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    align-items: stretch;
    gap: 1rem;
    margin-bottom: 0.75rem;
  }
  .rider-doc-sides-row .rider-doc-side-card {
    flex: 1 1 0;
    min-width: 0;
    margin-bottom: 0;
  }
  .rider-doc-side-card {
    border: 1px solid #e9ecef;
    border-radius: 0.75rem;
    overflow: hidden;
  }
  .rider-doc-side-card .card-header {
    background: #f8fafc;
    border-bottom: 1px solid #eef2f7;
  }
  .rider-doc-upload .form-label.required:after {
    content: " *";
    color: #dc3545;
  }
  @media (max-width: 767.98px) {
    .rider-doc-sides-row {
      flex-direction: column;
      flex-wrap: wrap;
    }
  }
</style>
