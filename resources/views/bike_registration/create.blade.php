{!! Form::open(['route' => 'BikeRegistration.store','id'=>'formajax']) !!}
<div class="modal-body">
    <div class="row">
        <input type="hidden" name="rider_id" value="{{ $data->id }}">
        @include('bike_registration.fields', ['bikeRegistration' => null, 'registrationStatuses' => $registrationStatuses])
    </div>
</div>
<div class="modal-footer">
    <button type="submit" class="btn btn-primary">Save</button>
</div>
{!! Form::close() !!}
