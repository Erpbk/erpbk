<div id="asset-name-field-wrap" class="form-group col-sm-3">
    {!! Form::label('name', 'Asset Name:') !!}
    {!! Form::text('name', $nameValue ?? null, ['class' => 'form-control', 'id' => 'asset_name', 'maxlength' => 255]) !!}
</div>

<div id="asset-bike-field-wrap" class="form-group col-sm-3" style="display: none;">
    {!! Form::label('bike_id', 'Bike:') !!}
    <select name="bike_id" id="bike_id" class="form-control select2">
        <option value="">Select bike</option>
        @foreach($availableBikes ?? [] as $bikeId => $bikeLabel)
            <option value="{{ $bikeId }}"
                data-label="{{ $bikeLabel }}"
                @selected(($bikeIdValue ?? null) == $bikeId)>
                {{ $bikeLabel }}
            </option>
        @endforeach
    </select>
    <input type="hidden" id="asset_name_hidden" value="{{ $nameValue ?? '' }}" disabled>
    <small class="text-muted">Asset name is set from the bike emirates-plate.</small>
</div>
