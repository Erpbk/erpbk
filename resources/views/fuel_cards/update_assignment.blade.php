{!! Form::model($fuelCard, ['url' => route('fuelCards.update_assignment', $fuelCard->id), 'method' => 'post','id'=>'formajax']) !!}
<div class="card-body">

    <div class="alert alert-info mb-3">
        <h5 class="mb-2">Updated Data:</h5>
        <strong>Card:</strong> {{ $fuelCard->card_number }}<br>
        @if($fuelCard->rider)
            @php
                $fuelCard->rider->loadMissing('bikes');
                $previousBike = $fuelCard->recordedBikeLabel();
                $currentBike = $fuelCard->currentRiderBikeLabel();
            @endphp
            <strong>Rider:</strong> {{ $fuelCard->rider->name }}
        @else
            @php
                $previousBike = $fuelCard->recordedBikeLabel();
                $currentBike = 'No rider assigned';
            @endphp
            <strong>Status:</strong> No rider assigned
        @endif
    </div>

    <div class="row g-2 mb-3">
        <div class="col-md-6">
            <div class="border rounded p-3 h-100 bg-light">
                <div class="text-muted small text-uppercase">Previous assigned bike</div>
                <div class="fw-semibold">{{ $previousBike }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="border rounded p-3 h-100 {{ $previousBike !== $currentBike ? 'border-danger' : '' }}">
                <div class="text-muted small text-uppercase">Current rider bike</div>
                <div class="fw-semibold {{ $previousBike !== $currentBike ? 'text-danger' : '' }}">{{ $currentBike }}</div>
            </div>
        </div>
    </div>

    <div class="alert alert-danger">Rider has been assigned a new bike. Please update bike for this fuelCard on fuel portal</div>
    <span class="alert alert-warning">Upload Screenshot of updated bike assignment from Fuel portal</span>
    <input type="file" name="attachment" class="form-control mt-5" required>

    <div class="action-btn pt-3">
        <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
        {!! Form::submit('Update Assignment', ['class' => 'btn btn-primary']) !!}
    </div>

{!! Form::close() !!}
