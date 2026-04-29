{!! Form::open(['route' => 'bikes.store','id'=>'formajax']) !!}

<div class="card-body">
  <input type="hidden" name="created_by" value="{{ Auth::user()->id }}">
  @include('bikes.fields')

</div>

<div class="action-btn pt-3">
  <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
  {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}