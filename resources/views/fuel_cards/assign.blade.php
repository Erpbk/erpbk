{!! Form::model($fuelCard, ['url' => route('fuelCards.assign', $fuelCard->id), 'method' => 'post','id'=>'formajax']) !!}

<div class="card-body">
    <div class="alert alert-warning d-flex align-items-start gap-2 mb-3">
        <i class="ti ti-alert-triangle mt-1"></i>
        <div>
            After assigning this card here, make sure it is also assigned to the rider’s <strong>correct bike</strong> on the external fuel portal.
            <div id="assign-bike-hint" class="fw-semibold mt-1" style="display: none;"></div>
        </div>
    </div>

    <div class="row">
        <!-- Number Field -->
        <div class="form-group col-sm-6">
            {!! Form::label('card number', 'Card Number:') !!}
            {!! Form::text('number', $fuelCard->card_number, ['class' => 'form-control', 'readonly' => true]) !!}
        </div>

        <!-- Rider Field -->
        <div class="form-group col-sm-6">
            {!! Form::label('assigned_to', 'Assign To:', ['class' => 'required']) !!}
            <select name="assigned_to" class="form-control account-select select2">
                <option value="">Select</option>
                @foreach($availableRiders as $rider)
                @php
                    $riderBike = \App\Models\FuelCards::formatBikeLabel($rider->bikes, $rider->bikes ? null : 'No bike assigned');
                @endphp
                <option value="{{ $rider->id }}"
                    data-bike="{{ $riderBike }}"
                    {{ old('assigned_to') == $rider->id ? 'selected' : '' }}>
                    {{ 'Rider: '. ($rider->name ?? 'N/A') }}
                </option>
                @endforeach
            </select>
            @if($availableRiders->isEmpty())
            <small class="text-muted">Every active rider already holds a fuel card.</small>
            @endif
        </div>

        <div class="form-group col-md-6">
            <label for="assign_date" class="required">Assign Date</label>
            <input type="date" name="assign_date" class="form-control" value="{{ old('assign_date', now()->format('Y-m-d')) }}">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-8">
            <textarea class="form-control" placeholder="Note....." name="note"></textarea>
        </div>
    </div>
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}

</div>

{!! Form::close() !!}

<script type="text/javascript">
    $(document).ready(function() {
        var $riderSelect = $('.select2');
        $riderSelect.select2({
            dropdownParent: $('#formajax'),
            allowClear: true
        });

        function updateAssignBikeHint() {
            var $selected = $riderSelect.find('option:selected');
            var bike = $selected.data('bike');
            var $hint = $('#assign-bike-hint');
            if ($riderSelect.val() && bike) {
                $hint.text("This rider’s current bike: " + bike).show();
            } else {
                $hint.hide().text('');
            }
        }

        $riderSelect.on('change', updateAssignBikeHint);
        updateAssignBikeHint();
    });
</script>
