@php
$flagsUrl = route($settingsRoutePrefix . '.update-custom-field-flags', array_merge($settingsRouteParams, ['id' => $customField->id]));
$isVisible = (bool) ($customField->is_visible ?? true);
@endphp
<td class="align-middle text-center">
  <div class="form-check form-switch d-inline-block mb-0">
    <input type="checkbox"
      class="form-check-input bike-custom-required-toggle"
      data-id="{{ $customField->id }}"
      data-update-url="{{ $flagsUrl }}"
      data-is-visible-current="{{ $isVisible ? 1 : 0 }}"
      {{ ($customField->is_mandatory ?? false) ? 'checked' : '' }}
      title="Require this value when the field is shown on add/edit forms">
  </div>
</td>
<td class="align-middle text-center">
  <div class="form-check form-switch d-inline-block mb-0">
    <input type="checkbox"
      class="form-check-input bike-custom-visibility-toggle"
      data-id="{{ $customField->id }}"
      data-update-url="{{ $flagsUrl }}"
      data-is-mandatory-current="{{ ($customField->is_mandatory ?? false) ? 1 : 0 }}"
      {{ $isVisible ? 'checked' : '' }}
      title="Show this field on add/edit forms when checked">
  </div>
</td>
