{!! Form::open(['route' => 'employeeInvoices.store', 'id' => 'formajax']) !!}
<div class="card-body">
    <div class="row">
        @include('employee_invoices.fields')
    </div>
</div>
<div class="card-footer text-end mt-3">
    {!! Form::submit('Save Invoice', ['class' => 'btn btn-primary']) !!}
</div>
{!! Form::close() !!}

