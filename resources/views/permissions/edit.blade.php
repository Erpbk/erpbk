
            {!! Form::model($permissions, ['route' => ['permissions.update', $permissions->id], 'method' => 'patch','id'=>'formajax']) !!}

        
                    @include('permissions.fields')
             

            <div class="action-btn">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
