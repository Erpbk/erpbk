@if($fines->attachment_path)
<a href="{{ asset('storage/' . $fines->attachment_path) }}" class="btn btn-default" target="_blank">
  @if(in_array($fines->attachment_path,['jpeg','jpg','png']))
      <i class="fa fa-file-image text-primary"></i>
      @else
      <i class="fa fa-file text-info"></i>
      @endif

  &nbsp;
 View Document
  </a>
@endif

<form action="{{url('rtaFines/attach_file/'.$id)}}" method="POST" enctype="multipart/form-data" id="formajax">
	<div class="row">
	    <div class="col-12 mt-3 mb-3">
	        @include('partials.universal_document_upload', [
	          'name' => 'attachment_path',
	          'label' => 'Upload Document related to the voucher',
	          'required' => false,
	          'inputClass' => 'form-control',
	          'showHint' => true,
	        ])

	    </div>
	</div>
	<button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Upload</button>
</form>
