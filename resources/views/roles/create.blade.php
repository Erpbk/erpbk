
            {!! Form::open(['route' => 'roles.store','id'=>'formajax']) !!}

                    @include('roles.fields')
              

            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
