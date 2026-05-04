

    {!! Form::model($fuelCard, ['route' => ['fuelCards.update', $fuelCard->id], 'method' => 'patch','id'=>'formajax']) !!}

    <div class="card-body">
        <div class="row">
            <div class="form-group col-sm-6">
                {!! Form::label('card_number', 'Number:') !!}
                {!! Form::text('card_number', null, ['class' => 'form-control']) !!}
            </div>

            <div class="form-group col-sm-6">
                {!! Form::label('fuel_company_id', 'Fuel company:') !!}
                {!! Form::select('fuel_company_id', \App\Models\FuelCompany::dropdown(), null, ['class' => 'form-control select2']) !!}
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

