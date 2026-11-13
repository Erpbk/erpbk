@php
  $name = $name ?? 'year';
  $id = $id ?? $name;
  $value = (string) ($value ?? '');
  $placeholder = $placeholder ?? 'Select year';
@endphp
<div class="vat-year-wrap">
  <input type="text"
         class="form-control vat-year-picker"
         id="{{ $id }}"
         name="{{ $name }}"
         value="{{ $value }}"
         placeholder="{{ $placeholder }}"
         autocomplete="off"
         readonly
         inputmode="numeric">
  <div class="vat-year-panel" hidden>
    <div class="vat-year-nav">
      <button type="button" class="vat-year-nav-btn vat-year-prev" aria-label="Previous years">&lsaquo;</button>
      <span class="vat-year-range"></span>
      <button type="button" class="vat-year-nav-btn vat-year-next" aria-label="Next years">&rsaquo;</button>
    </div>
    <div class="vat-year-grid"></div>
    <div class="vat-year-footer">
      <button type="button" class="vat-year-clear">Clear</button>
    </div>
  </div>
</div>
