<!-- Number Field -->
<div class="form-group col-sm-6">
    {!! Form::label('card_number', 'Number:', ['class' => 'required']) !!}
    {!! Form::text('card_number', null, ['class' => 'form-control']) !!}
</div>

<!-- Company Field -->
<div class="form-group col-sm-6">
    {!! Form::label('fuel_company_id', 'Fuel Company:', ['class' => 'required']) !!}
    {!! Form::select('fuel_company_id', \App\Models\FuelCompany::dropdown(), null, ['class' => 'form-control select2']) !!}
</div>

<!-- Assigned To -->
<div class="form-group col-sm-6">
    {!! Form::label('assigned_to', 'Assigned To:') !!}
    <select name="assigned_to" class="form-control account-select select2">
        <option value="">Select</option>
        @foreach(\App\Models\Bikes::where('status', 1)->whereNotNull('rider_id')->get() as $bike)
        @php
        $bike->load('rider');
        @endphp
        <option value="{{ $bike->rider->id }}"
            {{ old('assigned_to', isset($fuelCard) ? $fuelCard->assigned_to : '') == $bike->rider->id ? 'selected' : '' }}>
            {{ 'Bike: '.$bike->plate.', Rider: '. ($bike->rider?->name ?? 'N/A') }}
        </option>
        @endforeach
    </select>
</div>

<!-- Assign Date Field -->
<div class="form-group col-md-6">
    <label for="assign_date">Assign Date</label>
    <input type="date" name="assign_date" class="form-control">
</div>



<script type="text/javascript">
    $(document).ready(function() {
        $('.select2').select2({
            dropdownParent: $('#formajax'),
            allowClear: true
        });
    });
</script>