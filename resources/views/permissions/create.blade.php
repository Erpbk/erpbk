
            {!! Form::open(['route' => 'permissions.store','id' => 'formajax']) !!}

          
                    @include('permissions.fields')
             

            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
