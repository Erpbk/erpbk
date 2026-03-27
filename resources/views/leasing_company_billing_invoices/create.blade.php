{!! Form::open(['route' => 'leasingCompanyBillingInvoices.store', 'id' => 'formajax', 'files' => true]) !!}
<input type="hidden" id="reload_page" value="0">
<input type="hidden" id="redirect_url" value="">

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

