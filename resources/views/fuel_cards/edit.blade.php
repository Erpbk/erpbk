

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

            <!-- Branch Field -->
            <div class="form-group col-sm-6">
                {!! Form::label('branch_id', 'Branch:',['class'=>'required']) !!}
                {!! Form::select('branch_id', App\Models\Branch::dropdown(),null, ['class' => 'form-select select2', 'required']) !!}
            </div>
        </div>
    </div>

    <div class="action-btn">
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

