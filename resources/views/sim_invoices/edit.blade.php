{!! Form::model($invoice, ['route' => ['simInvoices.update', $invoice->id], 'method' => 'put', 'id' => 'formajax', 'files' => true]) !!}

<div class="card-body">
    <div class="row">
        @include('sim_invoices.fields')
    </div>
</div>

<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('simInvoices.index') }}" class="btn btn-default"> Cancel </a>
</div>

{!! Form::close() !!}
