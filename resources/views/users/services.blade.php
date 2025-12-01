<script src="{{ asset('js/modal_custom.js') }}"></script>
{!! Form::open(['route' => ['user_services', $id],'id'=>'formajax2']) !!}
<div class="row mt-3">
    <h4>Add More</h4>
    <!-- Country Field -->
    <div class="form-group col-sm-4">
       {!! Form::label('inquiry_country', 'Inquiry Country:') !!}
       {!! Form::select('inquiry_country', $countries, IConstants::COUNTRY, ['class' => 'form-control form-select select2','id'=>'inquiry_country']) !!}
   </div>
   <!-- Service Id Field -->
   <div class="form-group col-sm-4">
       {!! Form::label('service_id', 'Service:') !!}
       <select name="service_id[]" id="service_id" class="form-control form-select select2" multiple>    
           {!! Common::getServices(IConstants::COUNTRY) !!}       
       </select>
   </div>
   <div class="form-group col-sm-4">
       {!! Form::label('notes', 'Notes:') !!}
       {!! Form::text('notes', null, ['class' => 'form-control ']) !!}
   </div>
   </div>

    <br>

    {!! Form::submit('Save', ['class' => 'btn btn-primary mb-4','style'=>'float:right;']) !!}

{!! Form::close() !!}
<table class="table table-striped table-condensed table-bordered">
<tr>
    <th>Country</th>
    <th>Services</th>
    <th>Note</th>
    <th>Action</th>
</tr>
@foreach($user_services as $service)
<tr>
    <td>{{$service->inquiry_country}}</td>
    <td>{!! Common::userServices($service->service_id) !!}
    </td>
    <td>{{$service->notes}}</td>
    <td>
        {!! Form::open(['route' => ['user_services',[$id,'del'=>$service->id]], 'method' => 'delete','class'=> 'formajax2']) !!}
        {!! Form::button('<i class="fa fa-trash"></i>', [
            'type' => 'submit',
            'class' => 'btn text-danger',
            'onclick' => 'return confirm("Are you sure, want to delete this service?")'
    
        ]) !!}
        {!! Form::close() !!}
    </td>
</tr>
@endforeach
</table>



