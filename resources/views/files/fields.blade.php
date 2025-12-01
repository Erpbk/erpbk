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
  <label class=" pl-2">Name</label>
  <input type="text" name="name" class="form-control mb-3" style="height: 40px;" />

</div>

<div class="col-12">
  <label class=" pl-2">Select file</label>
  <input type="file" name="file_name" class="form-control mb-3" style="height: 40px;" />

</div>
<!-- Expiry Date Field -->
{{-- <div class="form-group col-sm-6">
    {!! Form::label('expiry_date', 'Expiry Date:') !!}
    {!! Form::date('expiry_date', null, ['class' => 'form-control','id'=>'expiry_date']) !!}
</div> --}}

