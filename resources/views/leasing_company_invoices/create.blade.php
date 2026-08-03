{!! Form::open(['route' => 'leasingCompanyInvoices.store', 'id' => 'formajax', 'files' => true]) !!}
<input type="hidden" id="reload_page" value="0">
<input type="hidden" id="redirect_url" value="">

<div class="card-body">
    <div class="row">
        @include('leasing_company_invoices.fields')
    </div>
</div>

<div class="card-footer text-end mt-3">
    {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
