
            {!! Form::open(['route' => 'customers.store','id'=>'formajax']) !!}

            <div class="card-body">

                <div class="row">
                    @include('customers.fields')
                </div>

            </div>

            <div class="action-btn pt-3">
              <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}

            </div>

            {!! Form::close() !!}
