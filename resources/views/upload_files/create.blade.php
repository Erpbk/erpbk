{!! Form::open(['route' => 'upload_files.store', 'files' => true, 'id' => 'formajax']) !!}
<div class="card-body">
  <div class="row">
    @include('upload_files.fields')
  </div>
</div>
<div class="action-btn pt-3">
  <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
  {!! Form::submit('Upload', ['class' => 'btn btn-primary']) !!}
</div>
{!! Form::close() !!}