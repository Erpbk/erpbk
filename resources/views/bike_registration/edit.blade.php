{!! Form::model($bikeRegistration, ['route' => ['BikeRegistration.update'], 'method' => 'patch']) !!}
<input type="hidden" name="id" value="{{ $bikeRegistration->id }}">
<div class="modal-body">
    <div class="row">
        @include('bike_registration.fields', ['bikeRegistration' => $bikeRegistration, 'registrationStatuses' => $registrationStatuses])
    </div>
</div>
<div class="modal-footer">
    <button type="submit" class="btn btn-primary">Save</button>
</div>
{!! Form::close() !!}
