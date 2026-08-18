@php
  $fieldKey = $item->kind === 'fixed' ? ($item->field_key ?? '') : '';
  $shouldRenderSlot = $isEdit && $fieldKey !== '' && \App\Support\RiderDocumentReplacement::fieldIsUploadSlot($fieldKey);
  $slotDef = null;
  if ($shouldRenderSlot) {
    $meta = \App\Support\RiderDocumentReplacement::definitionForField($fieldKey);
    $slotDef = $meta ? (\App\Support\RiderDocumentReplacement::definitions()[$meta['key']] ?? null) : null;
  }
  $existingDocs = [];
  if (isset($riders) && $riders instanceof \App\Models\Riders) {
    $existingDocs = \App\Support\RiderDocumentReplacement::existingTypesForRider($riders);
  }
@endphp
@if ($shouldRenderSlot && $slotDef && !empty($existingDocs[$slotDef['key']]))
  @php
    $hasExisting = !empty($existingDocs[$slotDef['key']]);
    $isDual = ($slotDef['type'] ?? '') === 'dual';
    $slotHasError = $errors->has('document_files.'.$slotDef['key'])
      || $errors->has('document_files.'.$slotDef['key'].'.front')
      || $errors->has('document_files.'.$slotDef['key'].'.back');
  @endphp
  <div class="rider-document-upload mt-2"
    data-document-key="{{ $slotDef['key'] }}"
    data-has-existing="{{ $hasExisting ? '1' : '0' }}"
    data-document-type="{{ $slotDef['type'] }}"
    @if (! $slotHasError) hidden @endif>
    <div class="alert alert-warning py-2 px-3 mb-2 rider-document-upload-alert">
      <small>{{ \App\Support\RiderDocumentReplacement::CHANGE_MESSAGE }}</small>
    </div>
    @if ($isDual)
      <label class="form-label mb-1">Upload updated {{ $slotDef['front_label'] ?: $slotDef['label'] }}</label>
      <input type="file"
        name="document_files[{{ $slotDef['key'] }}][front]"
        class="form-control form-control-sm rider-document-file @error('document_files.'.$slotDef['key'].'.front') is-invalid @enderror"
        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf">
      @error('document_files.'.$slotDef['key'].'.front')
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
      <label class="form-label mb-1 mt-2">Upload updated {{ $slotDef['back_label'] ?: $slotDef['label'] }}</label>
      <input type="file"
        name="document_files[{{ $slotDef['key'] }}][back]"
        class="form-control form-control-sm rider-document-file @error('document_files.'.$slotDef['key'].'.back') is-invalid @enderror"
        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf">
      @error('document_files.'.$slotDef['key'].'.back')
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
      <div class="invalid-feedback rider-document-upload-error"></div>
    @else
      <label class="form-label mb-1">Upload updated {{ $slotDef['label'] }}</label>
      <input type="file"
        name="document_files[{{ $slotDef['key'] }}]"
        class="form-control form-control-sm rider-document-file @error('document_files.'.$slotDef['key']) is-invalid @enderror"
        accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf">
      @error('document_files.'.$slotDef['key'])
        <div class="invalid-feedback d-block">{{ $message }}</div>
      @enderror
      <div class="invalid-feedback rider-document-upload-error"></div>
    @endif
  </div>
@endif
