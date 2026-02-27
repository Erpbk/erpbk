@php $permissionsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.permissions' : 'permissions'; @endphp
            {!! Form::model($permissions, ['route' => [$permissionsRoute . '.update', $permissions->id], 'method' => 'patch','id'=>'formajax']) !!}

        
                    @include('permissions.fields')
             

            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
