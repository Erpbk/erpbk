{!! Form::model($fuelCard, ['route' => ['fuelCards.update', $fuelCard->id], 'method' => 'patch','id'=>'formajax']) !!}

<div class="card-body">
    @include('fuel_cards.fields')
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
