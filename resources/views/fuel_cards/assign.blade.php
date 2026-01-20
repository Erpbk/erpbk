
{!! Form::model($fuelCard, ['url' => route('fuelCards.assign', $fuelCard->id), 'method' => 'post','id'=>'formajax']) !!}

<div class="card-body">
    <div class="row">
        <!-- Number Field -->
        <div class="form-group col-sm-6">
            {!! Form::label('card number', 'Card Number:') !!}
            {!! Form::text('number', $fuelCard->card_number, ['class' => 'form-control', 'readonly' => true]) !!}
        </div>

        <!-- Rider Field -->
        <div class="form-group col-sm-6">
            {!! Form::label('assigned_to', 'Assign To:') !!}
            {!! Form::select('assigned_to', \App\Models\Riders::dropdown(), null, ['class' => 'form-select select2']) !!}
        </div>
        
        <div class="form-group col-md-6">
            <label for="assign_date">Assign Date</label>
            <input type="date" name="assign_date" class="form-control">
        </div>
    </div>
    <div class="row mt-3">
        <div class="col-md-8">
            <textarea class="form-control" placeholder="Note....." name="note"></textarea>
        </div>
    </div>
</div>

<div class="action-btn pt-3">
    <button type="button" class="btn btn-default"  data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}

</div>

{!! Form::close() !!}

<script type="text/javascript">

$(document).ready(function () {
    $('.select2').select2({
        dropdownParent: $('#formajax'),
        allowClear: true
    });
});
</script>



