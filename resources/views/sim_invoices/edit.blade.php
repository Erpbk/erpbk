{!! Form::model($invoice, ['route' => ['simInvoices.update', $invoice->id], 'method' => 'put', 'id' => 'formajax', 'files' => true]) !!}

<div class="card-body">
    <div class="row">
        @include('sim_invoices.fields')
    </div>
</div>

<div class="card-footer text-end mt-3">
    {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
