{!! Form::open(['route' => 'files.store','id'=>'formajax','enctype'=>'multipart/form-data']) !!}


<div class="row">
    @include('files.fields')
</div>


<div class="action-btn pt-3">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}