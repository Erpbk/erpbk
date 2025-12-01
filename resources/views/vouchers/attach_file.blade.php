@if($voucher->attach_file)
<a href="{{ url('storage/vouchers/'.$voucher->attach_file) }}" class="btn btn-default" target="_blank">
  @if(in_array($voucher->attach_file,['jpeg','jpg','png']))
      <i class="fa fa-file-image text-primary"></i>
      @else
      <i class="fa fa-file text-info"></i>
      @endif

  &nbsp;
 View Document
  </a>
@endif

<form action="{{url('voucher/attach_file/'.$id)}}" method="POST" enctype="multipart/form-data" id="formajax">
<div class="row">
    <div class="col-12 mt-3 mb-3">
        <label class="mb-3 pl-2">Upload Document related to the voucher</label>
        <input type="file" name="attach_file" class="form-control mb-3" style="height: 40px;" />

    </div>
</div>
<button type="submit" name="submit" class="btn btn-primary" style="width: 100%;">Upload</button>

</form>
