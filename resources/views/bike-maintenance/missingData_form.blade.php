
{!! Form::model($bike, ['route' => ['bike-maintenance.update', $bike], 'method' => 'patch','id'=>'formajax']) !!}
    @csrf
    <div class="card-body">
        <div class="row">
            <div class="form-group col-md-4">
                {!! Form::label('previous_km', 'Previous KM:',['class' => 'required']) !!}
                {!! Form::number('previous_km', null, ['class' => 'form-control', 'step' => 'any', 'required' => true, 'readonly' => !is_null($bike->previous_km)]) !!}
            </div>
            <div class="form-group col-md-4">
                {!! Form::label('current_km', 'Current KM:',['class' => 'required']) !!}
                {!! Form::number('current_km', null, ['class' => 'form-control', 'step' => 'any', 'required' => true]) !!}
            </div>
            <div class="form-group col-md-4">
                {!! Form::label('maintenance_km', 'Maintenance KM:',['class' => 'required']) !!}
                {!! Form::number('maintenance_km', null, ['class' => 'form-control', 'step' => 'any', 'required' => true]) !!}
            </div>
        </div>
    </div>

    <div class="action-btn pt-3">
        <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}

    </div>

{!! Form::close() !!}
