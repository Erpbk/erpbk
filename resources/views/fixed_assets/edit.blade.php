{!! Form::model($asset, ['route' => ['fixed-assets.update', $asset->id], 'method' => 'PUT', 'id' => 'formajax', 'class' => 'fixed-asset-form']) !!}

<div class="card-body">
    <div class="row">
        <div class="form-group col-sm-6">
            {!! Form::label('asset_category_id', 'Asset Category:') !!}
            <select name="asset_category_id" id="asset_category_id" class="form-control" required>
                @foreach($categories as $category)
                    @php $acc = $category->assetAccount; @endphp
                    <option value="{{ $category->id }}"
                        @selected($asset->asset_category_id == $category->id)
                        data-method="{{ $category->depreciation_method }}"
                        data-frequency="{{ $category->depreciation_frequency }}"
                        data-life="{{ $category->useful_life_months }}"
                        data-salvage-percent="{{ $category->salvage_value_percent }}"
                        data-asset-account-id="{{ $category->asset_account_id }}"
                        data-asset-account-name="{{ $acc?->name ?? $category->name }}"
                        data-is-vehicles="{{ $category->isVehicles() ? '1' : '0' }}">
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        @include('fixed_assets.partials.asset_identity_fields', [
            'availableBikes' => $availableBikes ?? [],
            'nameValue' => $asset->name,
            'bikeIdValue' => $asset->bike_id,
        ])
        <div class="form-group col-sm-4">
            {!! Form::label('asset_code', 'Asset Code:') !!}
            {!! Form::text('asset_code', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group col-sm-4">
            {!! Form::label('serial_number', 'Serial Number:') !!}
            {!! Form::text('serial_number', null, ['class' => 'form-control', 'id' => 'serial_number']) !!}
        </div>
        <div class="form-group col-sm-4">
            {!! Form::label('branch_id', 'Branch:') !!}
            {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-control select2', 'required' => true, 'id' => 'branch_id']) !!}
        </div>
        <div class="form-group col-sm-4">
            {!! Form::label('acquisition_date', 'Acquisition Date:') !!}
            {!! Form::date('acquisition_date', null, ['class' => 'form-control', 'required' => true, 'id' => 'acquisition_date']) !!}
            <small class="text-muted" id="acquisition_date_help">Date the asset was purchased or recorded.</small>
        </div>
        <div class="form-group col-sm-4">
            {!! Form::label('in_service_date', 'In-Service Date:') !!}
            {!! Form::date('in_service_date', null, ['class' => 'form-control', 'required' => true, 'id' => 'in_service_date']) !!}
            <small class="text-muted">Depreciation schedule is based on this date.</small>
        </div>

        @include('fixed_assets.partials.acquisition_fields', [
            'acquisitionTypeValue' => in_array($asset->acquisition_type, ['already_owned'], true) ? 'opening_balance' : ($asset->acquisition_type ?? 'new_purchase'),
            'openingAccumulatedValue' => $asset->opening_accumulated_depreciation ?? 0,
            'depreciationAsOfDateValue' => optional($asset->depreciation_as_of_date)->format('Y-m-d') ?? optional($asset->acquisition_date)->format('Y-m-d'),
        ])

        @include('fixed_assets.partials.past_depreciation_fields', [
            'pastDepreciationHandlingValue' => $asset->past_depreciation_handling,
        ])

        <div class="form-group col-sm-4">
            {!! Form::label('acquisition_cost', 'Acquisition Cost:') !!}
            {!! Form::number('acquisition_cost', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0.01', 'required' => true, 'id' => 'acquisition_cost']) !!}
        </div>

        @include('fixed_assets.partials.status_acquisition_fields', [
            'isEdit' => true,
            'creditAccounts' => $creditAccounts,
            'statusValue' => $asset->status,
            'acquisitionPostingValue' => old('acquisition_posting', 'already_posted'),
            'showAcquisitionOptions' => !$asset->isOpeningBalance() && ($asset->isDraft() || ($asset->status === 'active' && !$asset->isAcquisitionPosted())),
            'lockStatusField' => $asset->isOpeningBalance(),
            'lockedStatusValue' => $asset->status,
            'assetAccountId' => $asset->asset_account_id,
            'assetAccountName' => $asset->assetAccount?->name ?? $asset->category?->name,
        ])

        <div class="form-group col-sm-12">
            {!! Form::label('description', 'Description:') !!}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>

        <div class="col-12"><hr><h6 class="text-muted">Depreciation Settings</h6></div>

        @include('fixed_assets.partials.depreciation_fields', [
            'depreciationMethods' => $depreciationMethods,
            'depreciationFrequencies' => $depreciationFrequencies,
            'depreciationMethodValue' => $asset->depreciation_method,
            'depreciationFrequencyValue' => $asset->depreciation_frequency ?? 'monthly',
            'usefulLifeMonthsValue' => $asset->useful_life_months,
            'showSalvagePercent' => false,
            'showSalvageValue' => true,
            'salvageValueAmount' => $asset->salvage_value,
        ])

        <div class="form-group col-sm-12">
            {!! Form::label('notes', 'Notes:') !!}
            {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>

        <div class="col-12">
            <div class="alert alert-warning mb-0">
                <small>Changing depreciation settings or in-service date will regenerate pending schedule entries. Posted entries are kept.</small>
            </div>
        </div>
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}

<script src="{{ asset('js/fixed_asset_form.js') }}"></script>
<script>
$(function () {
    var defaultsTemplate = @json(route('fixed-assets.category-defaults', ['categoryId' => '__ID__']));

    initFixedAssetForm({
        isEdit: true,
        lastMonthStart: @json(\App\Models\FixedAsset::lastMonthStartDate()->toDateString()),
        getCategoryDefaultsUrl: function () {
            var id = $('#asset_category_id').val() || '0';
            return defaultsTemplate.replace('__ID__', id);
        }
    });
});
</script>
