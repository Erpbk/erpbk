{!! Form::open(['route' => 'fixed-assets.store', 'id' => 'formajax', 'class' => 'fixed-asset-form']) !!}

<div class="card-body">
    <div class="row">
        <div class="form-group col-sm-3">
            {!! Form::label('asset_category_id', 'Asset Category:') !!}
            <select name="asset_category_id" id="asset_category_id" class="form-control select2" required>
                <option value="">Select category</option>
                @foreach($categories as $category)
                    @php $acc = $category->assetAccount; @endphp
                    <option value="{{ $category->id }}"
                        data-method="{{ $category->depreciation_method }}"
                        data-frequency="{{ $category->depreciation_frequency }}"
                        data-life="{{ $category->useful_life_months }}"
                        data-salvage-percent="{{ $category->salvage_value_percent }}"
                        data-asset-account-id="{{ $category->asset_account_id }}"
                        data-asset-account-name="{{ $acc?->name ?? $category->name }}">
                        {{ $category->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="form-group col-sm-3">
            {!! Form::label('name', 'Asset Name:') !!}
            {!! Form::text('name', null, ['class' => 'form-control', 'required' => true]) !!}
        </div>
        <div class="form-group col-sm-3">
            {!! Form::label('asset_code', 'Asset Code:') !!}
            {!! Form::text('asset_code', null, ['class' => 'form-control']) !!}
            <small class="text-muted">Leave blank to auto-generate.</small>
        </div>
        <div class="form-group col-sm-3">
            {!! Form::label('serial_number', 'Serial Number:') !!}
            {!! Form::text('serial_number', null, ['class' => 'form-control']) !!}
        </div>
        <div class="form-group col-sm-3">
            {!! Form::label('branch_id', 'Branch:') !!}
            {!! Form::select('branch_id', auth()->user()->branchDropdown(true), null, ['class' => 'form-control select2', 'required' => true, 'id' => 'branch_id']) !!}
        </div>
        <div class="form-group col-sm-3">
            {!! Form::label('acquisition_date', 'Acquisition Date:') !!}
            {!! Form::date('acquisition_date', date('Y-m-d'), ['class' => 'form-control', 'required' => true, 'id' => 'acquisition_date']) !!}
            <small class="text-muted" id="acquisition_date_help">Date the asset was purchased.</small>
        </div>
        <div class="form-group col-sm-3">
            {!! Form::label('in_service_date', 'In-Service Date:') !!}
            {!! Form::date('in_service_date', date('Y-m-d'), ['class' => 'form-control', 'required' => true, 'id' => 'in_service_date']) !!}
            <small class="text-muted" id="in_service_date_help">When asset usage started. Depreciation schedule is based on this date.</small>
        </div>

        <div class="form-group col-sm-3">
            {!! Form::label('acquisition_cost', 'Acquisition Cost:') !!}
            {!! Form::number('acquisition_cost', null, ['class' => 'form-control', 'step' => '0.01', 'min' => '0.01', 'required' => true, 'id' => 'acquisition_cost']) !!}
        </div>
        <div class="form-group col-sm-5 my-3 gap-5 mx-4">
            <div class="row border border-gray-300 rounded p-2">
                @include('fixed_assets.partials.acquisition_fields')
                @include('fixed_assets.partials.past_depreciation_fields')
            </div>
        </div>
        <div class="form-group col-sm-6 my-3">
            <div class="row border border-gray-300 rounded p-2">
                @include('fixed_assets.partials.status_acquisition_fields', [
                        'creditAccounts' => $creditAccounts,
                        'statusValue' => 'draft',
                        'acquisitionPostingValue' => 'already_posted',
                    ])
            </div>
        </div>

        <div class="form-group col-sm-12">
            {!! Form::label('description', 'Description:') !!}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>

        <div class="col-12"><hr><h6 class="text-danger">Depreciation Settings</h6></div>

        @include('fixed_assets.partials.depreciation_fields', [
            'depreciationMethods' => $depreciationMethods,
            'depreciationFrequencies' => $depreciationFrequencies,
            'showSalvagePercent' => false,
            'showSalvageValue' => true,
            'salvageValueAmount' => 0,
        ])

        <div class="form-group col-sm-12">
            {!! Form::label('notes', 'Notes:') !!}
            {!! Form::textarea('notes', null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}

<script src="{{ asset('js/fixed_asset_form.js') }}"></script>
<script>
$(function () {
    var defaultsTemplate = @json(route('fixed-assets.category-defaults', ['categoryId' => '__ID__']));

    initFixedAssetForm({
        lastMonthStart: @json(\App\Models\FixedAsset::lastMonthStartDate()->toDateString()),
        getCategoryDefaultsUrl: function () {
            var id = $('#asset_category_id').val() || '0';
            return defaultsTemplate.replace('__ID__', id);
        }
    });
});
</script>
