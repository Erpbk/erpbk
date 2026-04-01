
{!! Form::model($invoice, ['route' => ['customer_invoices.update', $invoice->id], 'method' => 'patch', 'id' => 'formajax', 'files' => true]) !!}
    @csrf
   @include('customer_invoices.fields')

{!! Form::close() !!}