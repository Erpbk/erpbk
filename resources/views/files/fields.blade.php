<!-- Type Field -->
<input type="hidden" name="type" value="{{ request('type') }}" />
<input type="hidden" name="type_id" value="{{ request('type_id') }}" />

<!-- File Name Field -->
<div class="col-12">
  <input type="hidden" name="name" value="0" />
</div>

@php
$suggest = request('suggested_name') ?? false ;
@endphp
@if($suggest)
<div class="col-12">
  <input type="hidden" name="suggested_name" value="{{ $suggest }}" />
  <label class=" pl-2">File Name Will Be stored As: <strong class="text-danger">{{ $suggest }}</strong></label>
</div>
@else
<div class="col-12">
  <label class=" pl-2">Suggest File Name<small class="text-muted"> (Optional)</small></label>
  <input type="text" name="suggested_name" class="form-control" style="height: 40px;" />
  <small class="text-muted mb-3">This Will be Saved Instead of File Name</small>
</div>
@endif

<div class="col-12">
  <label class=" pl-2">Select file</label>
  <input type="file" name="file_name" class="form-control mb-3" style="height: 40px;" required />
</div>

<div class="col-12">
  <label class="pl-2">Expiry Date <small class="text-muted">(Optional)</small></label>
  <input type="date" name="expiry_date" class="form-control mb-3" style="height: 40px;" value="{{ old('expiry_date') }}" />
</div>
