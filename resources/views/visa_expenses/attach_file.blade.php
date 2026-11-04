@php
  $__companySlug = $company_slug ?? \App\Support\CompanyRouteContext::slug();
  $existingPath = $voucher->attach_file ? ltrim((string) $voucher->attach_file, '/') : null;
  $existingFile = $existingPath ? basename($existingPath) : null;
  $existingUrl = $existingPath
    ? (str_contains($existingPath, '/') ? url('storage/' . $existingPath) : url('storage/vouchers/' . $existingPath))
    : null;
@endphp
@if($existingUrl)
<a href="{{ $existingUrl }}" class="btn btn-default" target="_blank">
  @if(in_array(strtolower(pathinfo($existingFile, PATHINFO_EXTENSION)), ['jpeg','jpg','png','gif','webp'], true))
      <i class="fa fa-file-image text-primary"></i>
  @else
      <i class="fa fa-file text-info"></i>
  @endif
  &nbsp;
  View Document
</a>
@endif

<form action="{{ route('VisaExpense.fileupload', ['company_slug' => $__companySlug, 'id' => $id]) }}" method="POST" enctype="multipart/form-data" id="formajax">
@csrf
<input type="hidden" name="company_slug" value="{{ $__companySlug }}">
<div class="row">
    <div class="col-12 mt-3 mb-3">
        @include('partials.universal_document_upload', [
          'name' => 'attach_file',
          'label' => 'Upload Document related to this visa expense payment',
          'required' => true,
          'inputClass' => 'form-control',
          'showHint' => true,
        ])
    </div>
</div>
<button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Upload</button>
</form>
