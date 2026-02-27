@php $rolesRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.roles' : 'roles'; @endphp
            {!! Form::open(['route' => $rolesRoute . '.store','id'=>'formajax']) !!}

                    @include('roles.fields')
              

            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
