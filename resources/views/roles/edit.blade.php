@php $rolesRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.roles' : 'roles'; @endphp
            {!! Form::model($roles, ['route' => [$rolesRoute . '.update', $roles->id], 'method' => 'patch','id'=>'formajax', 'class' => 'form-ajax-submit', 'data-reload-table' => '0']) !!}

         
                    @include('roles.fields')
              
            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
              
            </div>

            {!! Form::close() !!}
