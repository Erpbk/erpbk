<<<<<<< Updated upstream
<<<<<<< Updated upstream
<<<<<<< Updated upstream
{!! Form::open(['route' => 'leasingCompanyInvoices.store', 'id' => 'formajax', 'files' => true]) !!}
=======
{!! Form::open(['route' => 'leasingCompanyInvoices.store', 'id' => 'formajax']) !!}
>>>>>>> Stashed changes
=======
{!! Form::open(['route' => 'leasingCompanyInvoices.store', 'id' => 'formajax']) !!}
>>>>>>> Stashed changes
=======
{!! Form::open(['route' => 'leasingCompanyInvoices.store', 'id' => 'formajax']) !!}
>>>>>>> Stashed changes
<input type="hidden" id="reload_page" value="0">
<input type="hidden" id="redirect_url" value="">

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
