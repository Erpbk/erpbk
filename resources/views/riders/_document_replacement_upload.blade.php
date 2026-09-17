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
$accept = '.jpg,.jpeg,.png,.pdf,.doc,.docx,image/jpeg,image/png,application/pdf';
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
  @include('partials.universal_document_upload', [
    'name' => 'document_files['.$slotDef['key'].'][front]',
    'label' => 'Upload updated '.($slotDef['front_label'] ?: $slotDef['label']),
    'required' => false,
    'accept' => $accept,
    'inputClass' => 'form-control form-control-sm rider-document-file',
    'labelClass' => 'form-label mb-1',
    'errorKey' => 'document_files.'.$slotDef['key'].'.front',
    'showHint' => false,
    'compact' => true,
  ])
  @include('partials.universal_document_upload', [
    'name' => 'document_files['.$slotDef['key'].'][back]',
    'label' => 'Upload updated '.($slotDef['back_label'] ?: $slotDef['label']),
    'required' => false,
    'accept' => $accept,
    'inputClass' => 'form-control form-control-sm rider-document-file',
    'labelClass' => 'form-label mb-1 mt-2',
    'errorKey' => 'document_files.'.$slotDef['key'].'.back',
    'showHint' => true,
    'compact' => true,
  ])
  @else
  @include('partials.universal_document_upload', [
    'name' => 'document_files['.$slotDef['key'].']',
    'label' => 'Upload updated '.$slotDef['label'],
    'required' => false,
    'accept' => $accept,
    'inputClass' => 'form-control form-control-sm rider-document-file',
    'labelClass' => 'form-label mb-1',
    'errorKey' => 'document_files.'.$slotDef['key'],
    'showHint' => true,
    'compact' => true,
  ])
  @endif
</div>
@endif
