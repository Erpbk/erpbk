    <div class="content px-3">

        @include('adminlte-templates::common.errors')


            {!! Form::open(['route' => 'users.store','id'=>'formajax']) !!}


                <div class="row">
                    @include('users.fields')
                </div>

                <br>
                <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}

            {!! Form::close() !!}

        </div>
