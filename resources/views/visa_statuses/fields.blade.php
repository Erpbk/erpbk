@php
$visaRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses';
$categories = $categories ?? collect();
$selectedCategoryIds = collect(old('visa_renewal_category_ids', $selectedCategoryIds ?? []))
    ->map(fn ($id) => (int) $id)
    ->filter()
    ->values()
    ->all();
if ($selectedCategoryIds === [] && !empty($selectedCategoryId)) {
    $selectedCategoryIds = [(int) $selectedCategoryId];
}
$primaryCategoryId = (int) ($selectedCategoryIds[0] ?? 0);
$cancelUrl = route($visaRoute . '.index') . ($primaryCategoryId ? ('?category_id=' . $primaryCategoryId) : '');
@endphp
@if($primaryCategoryId)
<input type="hidden" name="return_to" value="{{ $cancelUrl }}">
@endif

<div class="form-group col-sm-12">
    <label class="required">Show this ticket in these Visa Categories:</label>
    <div class="d-flex flex-wrap gap-2 @error('visa_renewal_category_ids') is-invalid @enderror">
        @forelse($categories as $category)
        <label class="border rounded px-3 py-2 mb-0 d-inline-flex align-items-center gap-2" style="cursor:pointer;">
            <input
                type="checkbox"
                name="visa_renewal_category_ids[]"
                value="{{ $category->id }}"
                {{ in_array((int) $category->id, $selectedCategoryIds, true) ? 'checked' : '' }}>
            <span>{{ $category->name }}{{ $category->is_default ? ' (Default)' : '' }}</span>
        </label>
        @empty
        <span class="text-muted">Create a visa category first</span>
        @endforelse
    </div>
    <small class="text-muted">One ticket = one record. Editing updates it in every selected category.</small>
    @error('visa_renewal_category_ids')
    <span class="invalid-feedback d-block" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
    @error('visa_renewal_category_ids.*')
    <span class="invalid-feedback d-block" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="name" class="required">Name:</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $visaStatus->name ?? '') }}" required>
    @error('name')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="code">Code:</label>
    <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $visaStatus->code ?? '') }}" maxlength="20">
    @error('code')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="category">Type:</label>
    <select name="category" id="category" class="form-control @error('category') is-invalid @enderror">
        <option value="Document" {{ old('category', $visaStatus->category ?? '') == 'Document' ? 'selected' : '' }}>Document</option>
        <option value="Permit" {{ old('category', $visaStatus->category ?? '') == 'Permit' ? 'selected' : '' }}>Permit</option>
        <option value="License" {{ old('category', $visaStatus->category ?? '') == 'License' ? 'selected' : '' }}>License</option>
        <option value="Insurance" {{ old('category', $visaStatus->category ?? '') == 'Insurance' ? 'selected' : '' }}>Insurance</option>
        <option value="Other" {{ old('category', $visaStatus->category ?? 'Other') == 'Other' ? 'selected' : '' }}>Other</option>
    </select>
    @error('category')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="default_fee">Default Fee:</label>
    <input type="number" name="default_fee" id="default_fee" class="form-control @error('default_fee') is-invalid @enderror" value="{{ old('default_fee', $visaStatus->default_fee ?? '0.00') }}" min="0" step="0.01">
    @error('default_fee')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="display_order">Display Order (master):</label>
    <input type="number" name="display_order" id="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', $visaStatus->display_order ?? '') }}" min="1">
    <small class="text-muted">Per-category order is set by drag-and-drop in the category view.</small>
    @error('display_order')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-12">
    <label for="description">Description:</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $visaStatus->description ?? '') }}</textarea>
    @error('description')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-12">
    <div class="row">
        <div class="col-sm-6">
            <div class="form-check">
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $visaStatus->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-check">
                <input type="checkbox" name="is_required" id="is_required" class="form-check-input" value="1" {{ old('is_required', $visaStatus->is_required ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_required">Required</label>
            </div>
        </div>
    </div>
</div>

@php $visaRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.visa-statuses' : 'visa-statuses'; @endphp
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ $cancelUrl }}" class="btn btn-default">Cancel</a>
</div>
