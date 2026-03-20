{!! Form::open(['route' => 'admin.roles.store', 'id' => 'formajax']) !!}
    @include('admin.roles.fields')
    <div class="action-btn mt-3">
        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    </div>
{!! Form::close() !!}
