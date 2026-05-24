{{-- Reusable module display name form (updates main sidebar via ModuleLabelService) --}}
<form action="{{ route($settingsRoutePrefix . '.store-module-label', $settingsRouteParams) }}" method="POST" class="row g-3 align-items-end">
  @csrf
  <div class="col-md-6">
    <label class="form-label">Name in menu</label>
    <input type="text" name="module_label" class="form-control"
      value="{{ old('module_label', $moduleLabel ?? $settingsHeading ?? '') }}"
      placeholder="{{ $defaultLabel ?? $settingsHeading ?? 'Module name' }}" maxlength="100" required>
    <div class="form-text">Updates the main application sidebar and settings panel menu on the next page load.</div>
  </div>
  <div class="col-md-6 text-end">
    <button class="btn btn-primary" type="submit">Save name</button>
  </div>
</form>
