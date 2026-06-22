{!! Form::model($category, ['route' => ['asset-categories.update', $category->id], 'method' => 'PUT', 'id' => 'formajax']) !!}

<div class="card-body">
    <div class="row">
        <div class="form-group col-sm-6">
            {!! Form::label('name', 'Category Name:') !!}
            @if($category->isSystemLocked())
                <input type="text" class="form-control" value="{{ $category->name }}" readonly disabled>
                <input type="hidden" name="name" value="{{ $category->name }}">
                <small class="text-muted">System category name cannot be changed.</small>
            @else
                {!! Form::text('name', null, ['class' => 'form-control', 'required' => true, 'maxlength' => 255]) !!}
            @endif
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
            'depreciationMethodValue' => $category->depreciation_method,
            'depreciationFrequencyValue' => $category->depreciation_frequency,
            'usefulLifeMonthsValue' => $category->useful_life_months,
            'salvageValuePercentValue' => $category->salvage_value_percent,
            'showSalvagePercent' => true,
            'showSalvageValue' => false,
        ])

        <div class="form-group col-sm-3 mt-2">
            <label>Active</label>
            <div class="status-toggle-container">
                <input type="hidden" name="is_active" value="0"/>
                <label class="toggle-switch">
                    <input type="checkbox" name="is_active" value="1" @if($category->is_active) checked @endif/>
                    <span class="toggle-slider"></span>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="action-btn">
    <button type="button" class="btn btn-default" data-bs-dismiss="modal">Cancel</button>
    {!! Form::submit('Update', ['class' => 'btn btn-primary']) !!}
</div>

{!! Form::close() !!}
