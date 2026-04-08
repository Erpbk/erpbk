{!! Form::model($invoice, ['route' => ['employeeInvoices.update', $invoice->id], 'method' => 'patch', 'id' => 'formajax']) !!}
<div class="card-body">
    <div class="row">
        @include('employee_invoices.fields')
    </div>
</div>
<div class="card-footer">
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    <a href="{{ route('employeeInvoices.index') }}" class="btn btn-default">Cancel</a>
</div>
{!! Form::close() !!}

