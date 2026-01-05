<!-- Type Field -->
<input type="hidden" name="type" value="{{request('type')}}"/>
<input type="hidden" name="type_id" value="{{request('type_id')}}"/>

{{-- <!-- Type Id Field -->
<div class="form-group col-sm-6">
    {!! Form::label('type_id', 'Type Id:') !!}
    {!! Form::number('type_id', null, ['class' => 'form-control', 'required']) !!}
</div> --}}

<!-- File Name Field -->
<div class="col-12">
  <input type="hidden" name="name"  value="0"/>
</div>

@php
  $suggest = request('suggested_name') ?? false ;
@endphp
@if($suggest)
  <div class="col-12">
    <input type="hidden" name="suggested_name"  value="{{ $suggest }}"/>
    <label class=" pl-2">File Name Will Be stored As: <strong class="text-danger">{{ $suggest }}</strong></label>
  </div>
@else
  <div class="col-12">
    <label class=" pl-2">Suggest File Name<small class="text-muted"> (Optional)</small></label>
    <input type="text" name="suggested_name" class="form-control" style="height: 40px;" nullable/>
    <small class="text-muted mb-3">This Will be Saved Instead of File Name</small>
  </div>
@endif

<div class="col-12">
  <label class=" pl-2">Select file</label>
  <input type="file" name="file_name" class="form-control mb-3" style="height: 40px;" />

</div>
<!-- Expiry Date Field -->
{{-- <div class="form-group col-sm-6">
    {!! Form::label('expiry_date', 'Expiry Date:') !!}
    {!! Form::date('expiry_date', null, ['class' => 'form-control','id'=>'expiry_date']) !!}
</div> --}}

