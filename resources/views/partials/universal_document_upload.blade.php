{{--
  UniversalDocumentUpload — reusable document upload with scan support.

  Desktop/laptop: Upload File | Document Scanner
  Mobile/tablet:  Upload File | Scan with Camera

  Usage:
    @include('partials.universal_document_upload', [
      'name' => 'file_name',
      'label' => 'Document',
      'required' => true,
      'accept' => '.jpg,.jpeg,.png,.pdf,...',  // optional
      'inputClass' => 'form-control',           // optional
      'errorKey' => 'file_name',                // optional
      'showHint' => true,                       // optional
      'compact' => false,                       // optional
      'multiple' => false,                      // optional — native multi-file (rare)
      'variant' => 'default',                   // optional — 'default' | 'dropzone'
      'uploadLabel' => 'Upload File',           // optional
      'existingUrl' => null,                    // optional — show existing file in dropzone
      'existingName' => null,                   // optional
      'existingIsPdf' => false,                 // optional
    ])
--}}
@include('partials.document_file_with_scan', get_defined_vars())
