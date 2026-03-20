{!! Form::model($role, ['route' => ['admin.roles.update', $role->id], 'method' => 'patch', 'id' => 'formajax']) !!}
    @include('admin.roles.fields')
    <div class="action-btn mt-3">
        {!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
    </div>
{!! Form::close() !!}
