<div class="row">

    <div class="form-group col-sm-6">
        {!! Form::label('depreciation_method', 'Depreciation Method:') !!}
        {!! Form::select('depreciation_method', $depreciationMethods, $depreciationMethodValue ?? 'straight_line', ['class' => 'form-control', 'id' => 'depreciation_method']) !!}
    </div>

    <div class="form-group col-sm-6">
        {!! Form::label('depreciation_frequency', 'Depreciation Posting:') !!}
        {!! Form::select('depreciation_frequency', $depreciationFrequencies ?? \App\Models\AssetCategory::depreciationFrequencies(), $depreciationFrequencyValue ?? 'monthly', ['class' => 'form-control', 'id' => 'depreciation_frequency']) !!}
        <small class="text-muted">How often depreciation is posted to the ledger.</small>
    </div>

    <div class="form-group col-sm-6">
        {!! Form::label('useful_life_months', 'Useful Life (Months):') !!}
        {!! Form::number('useful_life_months', $usefulLifeMonthsValue ?? 60, ['class' => 'form-control', 'min' => 1, 'max' => 600, 'id' => 'useful_life_months']) !!}
        <small class="text-muted">e.g. 60 months = 5 years. Yearly posting creates one entry per 12 months.</small>
    </div>

    @if($showSalvagePercent ?? true)
    <div class="form-group col-sm-6">
        {!! Form::label('salvage_value_percent', 'Default Salvage Value (%):') !!}
        {!! Form::number('salvage_value_percent', $salvageValuePercentValue ?? 0, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'max' => 100, 'id' => 'salvage_value_percent']) !!}
        <small class="text-muted">Applied as % of acquisition cost when creating assets.</small>
    </div>
    @endif

    @if($showSalvageValue ?? false)
    <div class="form-group col-sm-6">
        {!! Form::label('salvage_value', 'Salvage Value:') !!}
        {!! Form::number('salvage_value', $salvageValueAmount ?? 0, ['class' => 'form-control', 'step' => '0.01', 'min' => 0, 'id' => 'salvage_value']) !!}
    </div>
    @endif
</div>
