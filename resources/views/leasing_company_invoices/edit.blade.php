{!! Form::model($invoice, ['route' => ['leasingCompanyInvoices.update', $invoice->id], 'method' => 'put', 'id' => 'formajax', 'files' => true]) !!}

<div class="card-body">
    <div class="row">
        @include('leasing_company_invoices.fields')
    </div>
</div>

<div class="card-footer text-end mt-3">
    {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
