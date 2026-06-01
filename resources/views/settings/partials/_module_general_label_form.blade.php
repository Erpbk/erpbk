{{-- Main sidebar menu label(s). Dropdown modules: parent heading + each submenu item. --}}
@php
$moduleMenuKey = $moduleMenuKey ?? ($moduleKey ?? '');
$menuDropdownContext = $moduleMenuKey !== ''
  ? \App\Support\MenuDropdownRegistry::contextForModuleKey($moduleMenuKey)
  : null;
@endphp
<form action="{{ route($settingsRoutePrefix . '.store-module-label', $settingsRouteParams) }}" method="POST">
  @csrf
  @if($menuDropdownContext)
  <p class="text-muted small mb-3">
    Customize the main dropdown heading and each submenu item in the application sidebar. Each label is saved independently.
  </p>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <label class="form-label fw-semibold">Main dropdown heading</label>
      <input type="text" name="module_label" class="form-control"
        value="{{ old('module_label', $menuDropdownContext['parent_label']) }}"
        placeholder="{{ $menuDropdownContext['parent_default'] }}" maxlength="100" required>
      <div class="form-text">Shown on the collapsed parent menu item (e.g. “{{ $menuDropdownContext['parent_default'] }}”).</div>
    </div>
  </div>
  <div class="row g-3">
    @foreach($menuDropdownContext['children'] as $child)
    <div class="col-md-6">
      <label class="form-label fw-semibold">Submenu label</label>
      <input type="text" name="submenu_labels[{{ $child['key'] }}]" class="form-control"
        value="{{ old('submenu_labels.'.$child['key'], $child['label']) }}"
        placeholder="{{ $child['default'] }}" maxlength="100"
        data-menu-label-key="{{ $child['key'] }}">
      <div class="form-text">Key: <code>{{ $child['key'] }}</code> — default: “{{ $child['default'] }}”</div>
    </div>
    @endforeach
  </div>
  <div class="text-end mt-3">
    <button class="btn btn-primary" type="submit">Save menu labels</button>
  </div>
  @else
  <div class="row g-3 align-items-end">
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
  </div>
  @endif
</form>
