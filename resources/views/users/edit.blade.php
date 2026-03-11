 @php $usersRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.users' : 'users'; @endphp   

        @include('adminlte-templates::common.errors')



            {!! Form::model($user, ['route' => [$usersRoute.'.update', $user->id], 'method' => 'patch','id'=>'formajax']) !!}

                <div class="row">
                    @include('users.fields')
                </div>
<br>
<button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}

            {!! Form::close() !!}

