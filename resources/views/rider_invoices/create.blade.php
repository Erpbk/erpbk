
            {!! Form::open(['route' => 'riderInvoices.store','id'=>'formajax']) !!}

            <div class="card-body">

                <div class="row">
                    @include('rider_invoices.fields')
                </div>

            </div>

            <div class="card-footer text-end mt-3">
                {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
            </div>

            {!! Form::close() !!}
