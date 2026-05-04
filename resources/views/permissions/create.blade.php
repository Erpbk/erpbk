@php $permissionsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.permissions' : 'permissions'; @endphp
            {!! Form::open(['route' => $permissionsRoute . '.store','id' => 'formajax', 'class' => 'form-ajax-submit', 'data-reload-table' => '0']) !!}

          
                    @include('permissions.fields')
             

            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
