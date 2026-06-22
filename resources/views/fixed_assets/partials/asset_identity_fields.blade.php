<div id="asset-name-field-wrap" class="form-group col-sm-3">
    {!! Form::label('name', 'Asset Name:') !!}
    {!! Form::text('name', $nameValue ?? null, ['class' => 'form-control', 'id' => 'asset_name', 'maxlength' => 255]) !!}
</div>

<div id="asset-bike-field-wrap" class="form-group col-sm-3" style="display: none;">
    {!! Form::label('bike_id', 'Vehicle:') !!}
    <select name="bike_id" id="bike_id" class="form-control select2">
        <option value="">Select vehicle</option>
        @foreach($availableBikes ?? [] as $bike)
            <option value="{{ $bike->id }}"
                data-label="{{ $bike->emiratesPlateLabel() }}"
                data-chassis-number="{{ $bike->chassis_number ?? '' }}"
                data-branch-id="{{ $bike->branch_id ?? '' }}"
                @selected(($bikeIdValue ?? null) == $bike->id)>
                {{ $bike->emiratesPlateLabel() }}
            </option>
        @endforeach
    </select>
    <input type="hidden" id="asset_name_hidden" value="{{ $nameValue ?? '' }}" disabled>
    <small class="text-muted">Asset name is set from the vehicle emirates-plate.</small>
</div>
