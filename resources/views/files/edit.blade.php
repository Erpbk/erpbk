{!! Form::model($files, ['route' => ['files.update', $files->id], 'method' => 'patch', 'id' => 'formajax', 'enctype' => 'multipart/form-data']) !!}

<div class="row">
    @include('files.fields')
</div>

<div class="action-btn pt-3">
    {!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
