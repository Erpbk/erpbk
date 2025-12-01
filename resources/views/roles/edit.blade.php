
            {!! Form::model($roles, ['route' => ['roles.update', $roles->id], 'method' => 'patch','id'=>'formajax']) !!}

         
                    @include('roles.fields')
              
            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
              
            </div>

            {!! Form::close() !!}
