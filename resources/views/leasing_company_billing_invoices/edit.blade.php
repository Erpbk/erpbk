{!! Form::model($invoice, ['route' => ['leasingCompanyBillingInvoices.update', $invoice->id], 'method' => 'put', 'id' => 'formajax', 'files' => true]) !!}

<div class="card-body">
    <div class="row">
        @include('leasing_company_billing_invoices.fields')
    </div>
</div>

<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('leasingCompanyBillingInvoices.index') }}" class="btn btn-default"> Cancel </a>
</div>

{!! Form::close() !!}

