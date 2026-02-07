<<<<<<< Updated upstream
{!! Form::model($invoice, ['route' => ['leasingCompanyInvoices.update', $invoice->id], 'method' => 'put', 'id' => 'formajax', 'files' => true]) !!}
=======
{!! Form::model($invoice, ['route' => ['leasingCompanyInvoices.update', $invoice->id], 'method' => 'put', 'id' => 'formajax']) !!}
>>>>>>> Stashed changes

<div class="card-body">
    <div class="row">
        @include('leasing_company_invoices.fields')
    </div>
</div>

<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('leasingCompanyInvoices.index') }}" class="btn btn-default"> Cancel </a>
</div>

{!! Form::close() !!}
