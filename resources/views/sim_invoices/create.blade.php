{!! Form::open(['route' => 'simInvoices.store', 'id' => 'formajax', 'files' => true]) !!}
<input type="hidden" id="reload_page" value="0">
<input type="hidden" id="redirect_url" value="">

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
