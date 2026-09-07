@php
$licenseRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.license-statuses' : 'license-statuses';
$categories = $categories ?? collect();
$selectedCategoryId = (int) old('license_category_id', $selectedCategoryId ?? ($LicenseStatus->license_category_id ?? 0));
$cancelUrl = route($licenseRoute . '.index') . ($selectedCategoryId ? ('?category_id=' . $selectedCategoryId) : '');
@endphp
@if($selectedCategoryId)
<input type="hidden" name="return_to" value="{{ $cancelUrl }}">
@endif

<div class="form-group col-sm-12">
    <label for="license_category_id" class="required">License Category:</label>
    <select name="license_category_id" id="license_category_id" class="form-control @error('license_category_id') is-invalid @enderror" required>
        <option value="">Select license category</option>
        @forelse($categories as $category)
        <option value="{{ $category->id }}" {{ (int) $selectedCategoryId === (int) $category->id ? 'selected' : '' }}>
            {{ $category->name }}{{ $category->is_default ? ' (Default)' : '' }}
        </option>
        @empty
        <option value="" disabled>Create a license category first</option>
        @endforelse
    </select>
    <small class="text-muted">Statuses belong to one license category. The same status name may be used in a different category.</small>
    @error('license_category_id')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="name" class="required">Name:</label>
    <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $LicenseStatus->name ?? '') }}" required>
    @error('name')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="code">Code:</label>
    <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" value="{{ old('code', $LicenseStatus->code ?? '') }}" maxlength="20">
    @error('code')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="category">Type:</label>
    <select name="category" id="category" class="form-control @error('category') is-invalid @enderror">
        <option value="Document" {{ old('category', $LicenseStatus->category ?? '') == 'Document' ? 'selected' : '' }}>Document</option>
        <option value="Permit" {{ old('category', $LicenseStatus->category ?? '') == 'Permit' ? 'selected' : '' }}>Permit</option>
        <option value="License" {{ old('category', $LicenseStatus->category ?? '') == 'License' ? 'selected' : '' }}>License</option>
        <option value="Insurance" {{ old('category', $LicenseStatus->category ?? '') == 'Insurance' ? 'selected' : '' }}>Insurance</option>
        <option value="Other" {{ old('category', $LicenseStatus->category ?? 'Other') == 'Other' ? 'selected' : '' }}>Other</option>
    </select>
    @error('category')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="default_fee">Default Fee:</label>
    <input type="number" name="default_fee" id="default_fee" class="form-control @error('default_fee') is-invalid @enderror" value="{{ old('default_fee', $LicenseStatus->default_fee ?? '0.00') }}" min="0" step="0.01">
    @error('default_fee')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-6">
    <label for="display_order">Display Order:</label>
    <input type="number" name="display_order" id="display_order" class="form-control @error('display_order') is-invalid @enderror" value="{{ old('display_order', $LicenseStatus->display_order ?? '') }}" min="1">
    @error('display_order')
    <span class="invalid-feedback" role="alert">
        <strong>{{ $message }}</strong>
    </span>
    @enderror
</div>

<div class="form-group col-sm-12">
    <label for="description">Description:</label>
    <textarea name="description" id="description" class="form-control @error('description') is-invalid @enderror" rows="3">{{ old('description', $LicenseStatus->description ?? '') }}</textarea>
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
                <input type="checkbox" name="is_active" id="is_active" class="form-check-input" value="1" {{ old('is_active', $LicenseStatus->is_active ?? true) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_active">Active</label>
            </div>
        </div>
        <div class="col-sm-6">
            <div class="form-check">
                <input type="checkbox" name="is_required" id="is_required" class="form-check-input" value="1" {{ old('is_required', $LicenseStatus->is_required ?? false) ? 'checked' : '' }}>
                <label class="form-check-label" for="is_required">Required</label>
            </div>
        </div>
    </div>
</div>

@php $licenseRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.license-statuses' : 'license-statuses'; @endphp
<div class="form-group col-sm-12">
    <button type="submit" class="btn btn-primary">Save</button>
    <a href="{{ $cancelUrl }}" class="btn btn-default">Cancel</a>
</div>
