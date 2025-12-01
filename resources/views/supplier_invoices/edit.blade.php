

         {!! Form::model($invoice, ['route' => ['supplierInvoices.update', $invoice->id], 'method' => 'patch', 'id' => 'formajax']) !!}


            <div class="card-body">
                <div class="row">
                    @include('supplier_invoices.fields')
                </div>
            </div>

            <div class="card-footer">
                {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
                <a href="{{ route('supplier_invoices.index') }}" class="btn btn-default"> Cancel </a>
            </div>

            {!! Form::close() !!}

