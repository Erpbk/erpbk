{!! Form::model($fuelCard, ['url' => route('fuelCards.update_assignment', $fuelCard->id), 'method' => 'post','id'=>'formajax']) !!}
<div class="card-body">
    
    {{-- Display Current Fuel Card Info --}}
    <div class="alert alert-info">
        <h5>Updated Data:</h5>
        <strong>Card:</strong> {{ $fuelCard->card_number }}<br>
        @if($fuelCard->rider)
            @php
                $fuelCard->rider->load('bikes');
            @endphp
            <strong>Rider:</strong> {{ $fuelCard->rider->name }}<br>
            <strong>Bike:</strong> {{ $fuelCard->rider->bikes ? $fuelCard->rider->bikes->emirates .'-'. $fuelCard->rider->bikes->plate : 'No bike assigned' }}
        @else
            <strong>Status:</strong> No rider assigned
        @endif
    </div>
    
    <div class="alert alert-danger">Rider has been assigned a new bike. Please update bike for this fuelCard on fuel portal</div>
    <span class="alert alert-warning">Upload Screenshot of updated bike assignment from Fuel portal</span>
    <input type="file" name="attachment" class="form-control mt-5" required>
    
    <div class="action-btn pt-3">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
        {!! Form::submit('Update Assignment', ['class' => 'btn btn-primary']) !!}
    </div>

{!! Form::close() !!}



