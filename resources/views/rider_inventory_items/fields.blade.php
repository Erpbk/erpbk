<div class="form-group col-sm-6">
    <label for="name" class="required">Item Name:</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $item->name ?? '') }}" required>
    @error('name')
    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="item_price" class="required">Item Price:</label>
    <input type="number" name="item_price" id="item_price" class="form-control @error('item_price') is-invalid @enderror" value="{{ old('item_price', $item->item_price ?? '0.00') }}" min="0" step="0.01" required>
    @error('item_price')
    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="display_order">Display Order:</label>
    <input type="number" name="display_order" id="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', $item->display_order ?? '') }}" min="1">
    @error('display_order')
    <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <div class="form-check mt-4">
        <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $item->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">Active</label>
    </div>
</div>

@php $itemRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.rider-inventory-items' : 'rider-inventory-items'; @endphp
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ route($itemRoute . '.index') }}" class="btn btn-default">Cancel</a>
</div>
