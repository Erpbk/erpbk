{!! Form::open(['route' => 'asset-categories.store', 'id' => 'formajax']) !!}

<div class="card-body">
    <div class="row">
        <div class="form-group col-sm-6">
            {!! Form::label('name', 'Category Name:') !!}
            {!! Form::text('name', null, ['class' => 'form-control', 'required' => true, 'maxlength' => 255]) !!}
        </div>
        <div class="form-group col-sm-6">
            {!! Form::label('code', 'Code:') !!}
            {!! Form::text('code', null, ['class' => 'form-control', 'maxlength' => 50]) !!}
        </div>
        <div class="form-group col-sm-12">
            {!! Form::label('description', 'Description:') !!}
            {!! Form::textarea('description', null, ['class' => 'form-control', 'rows' => 2]) !!}
        </div>

        @include('fixed_assets.partials.depreciation_fields', [
            'depreciationMethods' => $depreciationMethods,
            'depreciationFrequencies' => $depreciationFrequencies,
            'showSalvagePercent' => true,
            'showSalvageValue' => false,
        ])

        <div class="form-group col-sm-3 mt-2">
            <label>Active</label>
            <div class="status-toggle-container">
                <input type="hidden" name="is_active" value="0"/>
                <label class="toggle-switch">
                    <input type="checkbox" name="is_active" value="1" checked/>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>

    <div class="alert alert-info mt-2 mb-0">
        <small>Accounts are created under: Non-Current Assets → Fixed Assets → [category], Non-Current Assets → Accumulated Depreciation → Accumulated Depreciation - [category], and Operating Expenses → Depreciation Expense → Depreciation Expense - [category].</small>
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Save', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
