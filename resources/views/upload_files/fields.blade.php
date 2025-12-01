<!-- File Name -->
<div class="form-group col-sm-6">
  {!! Form::label('name', 'File Name:', ['class' => 'required']) !!}
  {!! Form::text('name', null, ['class' => 'form-control', 'required']) !!}
</div>

<!-- File Upload -->
<div class="form-group col-sm-6">
  {!! Form::label('file', 'Upload File:', ['class' => 'required']) !!}
  {!! Form::file('file', ['class' => 'form-control', 'required']) !!}
</div>

<!-- Details -->
<div class="form-group col-sm-12">
  {!! Form::label('details', 'Details:') !!}
  {!! Form::textarea('details', null, ['class' => 'form-control']) !!}
</div>
