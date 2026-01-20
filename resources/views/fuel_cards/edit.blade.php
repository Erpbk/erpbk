

    {!! Form::model($fuelCard, ['route' => ['fuelCards.update', $fuelCard->id], 'method' => 'patch','id'=>'formajax']) !!}

    <div class="card-body">
        <div class="row">
            <div class="form-group col-sm-6">
                {!! Form::label('card_number', 'Number:') !!}
                {!! Form::text('card_number', null, ['class' => 'form-control']) !!}
            </div>

            <!-- Company Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('card_type', 'Card type:') !!}
                {!! Form::text('card_type', null, ['class' => 'form-control']) !!}
            </div>
        </div>
    </div>

    <div class="action-btn">
        <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
        {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
    </div>

    {!! Form::close() !!}

