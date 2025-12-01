
            {!! Form::open(['route' => 'riderInvoices.store','id'=>'formajax']) !!}

            <div class="card-body">

                <div class="row">
                    @include('rider_invoices.fields')
                </div>

            </div>

            <div class="card-footer">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('riderInvoices.index') }}" class="btn btn-default"> Cancel </a>
            </div>

            {!! Form::close() !!}
