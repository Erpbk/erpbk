{!! Form::model($invoice, ['route' => 'customer_invoices.store', 'method' => 'post', 'id' => 'formajax', 'files' => true]) !!}
    @csrf
    
   @include('customer_invoices.fields')

{!! Form::close() !!}
