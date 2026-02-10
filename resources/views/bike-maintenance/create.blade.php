{!! Form::open(['route' => ['bikeMaintenance.store'], 'method' => 'post', 'id' => 'formajax', 'files' => true]) !!}
    @csrf
    
   @include('bike-maintenance.fields')

{!! Form::close() !!}
