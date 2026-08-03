@php
  $__companySlug = $company_slug ?? \App\Support\CompanyRouteContext::slug();
  $uploadRouteParams = array_filter([
    'id' => $id,
    'company_slug' => $__companySlug,
  ]);
  $existingFile = $voucher->attach_file ? basename((string) $voucher->attach_file) : null;
@endphp
@if($existingFile)
<a href="{{ url('storage/vouchers/'.$existingFile) }}" class="btn btn-default" target="_blank">
  @if(in_array(strtolower(pathinfo($existingFile, PATHINFO_EXTENSION)), ['jpeg','jpg','png','gif','webp'], true))
      <i class="fa fa-file-image text-primary"></i>
      @else
      <i class="fa fa-file text-info"></i>
      @endif

  &nbsp;
 View Document
  </a>
@endif

<form action="{{ route('voucher.fileupload', $uploadRouteParams) }}" method="POST" enctype="multipart/form-data" id="formajax">
@csrf
<input type="hidden" name="company_slug" value="{{ $__companySlug }}">
<div class="row">
    <div class="col-12 mt-3 mb-3">
        <label class="mb-3 pl-2">Upload Document related to the voucher</label>
        <input type="file" name="attach_file" class="form-control mb-3" style="height: 40px;" required />

    </div>
</div>
<button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Upload</button>

</form>
