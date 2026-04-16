{!! Form::model($user, ['route' => ['admin.users.update', $user->id], 'method' => 'patch', 'id' => 'formajax']) !!}
    @include('admin.users.fields')
{!! Form::close() !!}

