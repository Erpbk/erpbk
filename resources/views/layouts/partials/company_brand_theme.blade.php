@if(!empty($applyCompanyTheme) && !empty($companyBrand['primary_color']))
@php
  $themePrimary = $companyBrand['primary_color'] ?? '#2563eb';
  $themeSecondary = $companyBrand['secondary_color'] ?? '#1e3a8a';
  $themePrimaryRgb = $companyBrand['primary_rgb'] ?? '37, 99, 235';
  $themePrimarySoft = $companyBrand['primary_soft'] ?? '#eff6ff';
  $themePrimaryLight = $companyBrand['primary_light'] ?? '#f8fafc';
  $themePrimaryMuted = $companyBrand['primary_muted'] ?? $themePrimarySoft;
  $themeOnPrimary = $companyBrand['text_on_primary'] ?? '#ffffff';
  $themeBorder = $companyBrand['border_color'] ?? $themePrimarySoft;
@endphp
<style id="company-brand-theme">
  /*
   * Beat Vuexy theme-default hardcoded #3c0cab primary accents with company brand colors.
   * Semantic success/info/warning/danger buttons are left unchanged.
   */
  :root,
  [data-bs-theme="light"],
  [data-bs-theme="dark"] {
    --bs-primary: {{ $themePrimary }};
    --bs-primary-rgb: {{ $themePrimaryRgb }};
    --bs-link-color: {{ $themePrimary }};
    --bs-link-hover-color: {{ $themeSecondary }};
    --bs-primary-bg-subtle: {{ $themePrimarySoft }};
    --bs-primary-border-subtle: {{ $themeBorder }};
    --bs-primary-text-emphasis: {{ $themeSecondary }};
    --bs-pagination-active-bg: {{ $themePrimary }};
    --bs-pagination-active-border-color: {{ $themePrimary }};
    --bs-nav-pills-link-active-bg: {{ $themePrimary }};
    --bs-dropdown-link-active-bg: {{ $themePrimary }};
    --bs-progress-bar-bg: {{ $themePrimary }};
    --bs-focus-ring-color: rgba({{ $themePrimaryRgb }}, 0.25);
    --company-brand-primary: {{ $themePrimary }};
    --company-brand-secondary: {{ $themeSecondary }};
    --company-brand-on-primary: {{ $themeOnPrimary }};
    --company-brand-soft: {{ $themePrimarySoft }};
    --company-brand-light: {{ $themePrimaryLight }};
    --company-brand-muted: {{ $themePrimaryMuted }};
    --company-brand-border: {{ $themeBorder }};
  }

  /* —— Text / background utilities —— */
  .text-primary,
  a.text-primary:hover,
  a.text-primary:focus {
    color: {{ $themePrimary }} !important;
  }

  .text-body[href]:hover {
    color: {{ $themePrimary }} !important;
  }

  .bg-primary {
    background-color: {{ $themePrimary }} !important;
  }

  a.bg-primary:hover,
  a.bg-primary:focus {
    background-color: {{ $themeSecondary }} !important;
  }

  .border-primary {
    border-color: {{ $themePrimary }} !important;
  }

  .bg-label-primary,
  .bg-label-hover-primary {
    background-color: {{ $themePrimarySoft }} !important;
    color: {{ $themePrimary }} !important;
  }

  .bg-label-hover-primary:hover {
    background-color: {{ $themePrimary }} !important;
    color: {{ $themeOnPrimary }} !important;
  }

  .bg-gradient-primary {
    background-image: linear-gradient(45deg, {{ $themePrimary }}, {{ $themeSecondary }}) !important;
  }

  /* —— Primary buttons (real background/border — Vuexy ignores --bs-btn-* vars) —— */
  .btn-primary,
  .btn-check:focus + .btn-primary,
  .btn-primary:focus,
  .btn-primary.focus,
  .btn-primary.disabled,
  .btn-primary:disabled {
    color: {{ $themeOnPrimary }} !important;
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
  }

  .btn-primary:hover,
  .btn-check:checked + .btn-primary,
  .btn-check:active + .btn-primary,
  .btn-primary:active,
  .btn-primary.active,
  .btn-primary.show.dropdown-toggle,
  .show > .btn-primary.dropdown-toggle {
    color: {{ $themeOnPrimary }} !important;
    background-color: {{ $themeSecondary }} !important;
    border-color: {{ $themeSecondary }} !important;
  }

  .btn-group .btn-primary,
  .input-group .btn-primary {
    border-right-color: {{ $themePrimary }} !important;
    border-left-color: {{ $themePrimary }} !important;
  }

  .btn-group-vertical .btn-primary {
    border-top-color: {{ $themePrimary }} !important;
    border-bottom-color: {{ $themePrimary }} !important;
  }

  .btn-outline-primary {
    color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
    background-color: transparent !important;
  }

  .btn-outline-primary:hover,
  .btn-check:checked + .btn-outline-primary,
  .btn-check:active + .btn-outline-primary,
  .btn-outline-primary:active,
  .btn-outline-primary.active,
  .btn-outline-primary.dropdown-toggle.show {
    color: {{ $themeOnPrimary }} !important;
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
  }

  .btn-label-primary {
    color: {{ $themePrimary }} !important;
    background: {{ $themePrimarySoft }} !important;
    border-color: transparent !important;
  }

  .btn-label-primary:hover,
  .btn-label-primary:focus,
  .btn-label-primary:active,
  .btn-label-primary.active,
  .btn-label-primary.show.dropdown-toggle,
  .show > .btn-label-primary.dropdown-toggle {
    color: {{ $themeOnPrimary }} !important;
    background: {{ $themePrimary }} !important;
  }

  /* —— Forms —— */
  .form-check-input:checked {
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
  }

  .form-control:focus,
  .form-select:focus,
  .form-check-input:focus,
  .card-search input:focus {
    border-color: {{ $themePrimary }} !important;
    box-shadow: 0 0 0 0.2rem rgba({{ $themePrimaryRgb }}, 0.25) !important;
  }

  /* —— Pagination / nav / progress / dropdown —— */
  .page-item.active .page-link,
  .pagination .page-item.active > .page-link {
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
    color: {{ $themeOnPrimary }} !important;
  }

  .page-link {
    color: {{ $themePrimary }};
  }

  .page-link:hover {
    color: {{ $themeSecondary }};
  }

  .nav-pills .nav-link.active,
  .nav-pills .nav-link.active:hover,
  .nav-pills .nav-link.active:focus {
    background-color: {{ $themePrimary }} !important;
    color: {{ $themeOnPrimary }} !important;
  }

  .nav-tabs .nav-link.active,
  .nav-tabs .nav-item.show .nav-link {
    color: {{ $themePrimary }} !important;
    border-bottom-color: {{ $themePrimary }} !important;
  }

  .progress-bar {
    background-color: {{ $themePrimary }} !important;
  }

  .dropdown-item.active,
  .dropdown-item:active {
    background-color: {{ $themePrimary }} !important;
    color: {{ $themeOnPrimary }} !important;
  }

  .dropdown-notifications-item:not(.mark-as-read) .dropdown-notifications-read span {
    background-color: {{ $themePrimary }} !important;
  }

  /* —— Alerts / list / table primary variants —— */
  .alert-primary {
    background-color: {{ $themePrimarySoft }} !important;
    border-color: {{ $themeBorder }} !important;
    color: {{ $themeSecondary }} !important;
  }

  .alert-primary .alert-link {
    color: {{ $themePrimary }} !important;
  }

  .list-group-item-primary {
    color: {{ $themeSecondary }} !important;
    background-color: {{ $themePrimarySoft }} !important;
  }

  a.list-group-item-primary.active,
  button.list-group-item-primary.active {
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
    color: {{ $themeOnPrimary }} !important;
  }

  .table-primary {
    --bs-table-bg: {{ $themePrimarySoft }};
    --bs-table-color: {{ $themeSecondary }};
    --bs-table-border-color: {{ $themeBorder }};
    color: {{ $themeSecondary }};
    background-color: {{ $themePrimarySoft }};
  }

  /* —— Sidebar / menu —— */
  .bg-menu-theme.menu-vertical .menu-item.active > .menu-link:not(.menu-toggle),
  .bg-menu-theme.menu-horizontal .menu-inner > .menu-item.active > .menu-link.menu-toggle {
    background: linear-gradient(72.47deg, {{ $themePrimary }} 22.16%, {{ $themeSecondary }} 76.47%) !important;
    box-shadow: 0px 2px 6px 0px rgba({{ $themePrimaryRgb }}, 0.35) !important;
    color: #fff !important;
  }

  .bg-menu-theme.menu-horizontal .menu-item.active > .menu-link:not(.menu-toggle) {
    background: {{ $themePrimarySoft }} !important;
    color: {{ $themePrimary }} !important;
  }

  .menu-vertical .menu-item.active > .menu-link:not(.menu-toggle),
  .menu-vertical .menu-item.active > .menu-link.menu-toggle {
    color: {{ $themePrimary }};
  }

  .layout-wrapper:not(.layout-horizontal) .bg-menu-theme .menu-inner > .menu-item.active:before,
  .layout-menu-fixed .menu-vertical .menu-item.active > .menu-link:not(.menu-toggle)::before,
  .menu-vertical .menu-item.active > .menu-link:not(.menu-toggle)::before {
    background: {{ $themePrimary }} !important;
  }

  .bg-menu-theme .menu-inner .menu-item.open > .menu-link.menu-toggle,
  .bg-menu-theme .menu-inner .menu-item.active > .menu-link.menu-toggle {
    color: {{ $themePrimary }} !important;
  }

  /* —— Links —— */
  a:not(.btn):not(.menu-link):not(.dropdown-item):not(.list-group-item):not(.page-link):not(.nav-link) {
    color: {{ $themePrimary }};
  }

  a:not(.btn):not(.menu-link):not(.dropdown-item):not(.list-group-item):not(.page-link):not(.nav-link):hover {
    color: {{ $themeSecondary }};
  }

  /* —— Misc primary borders / switches —— */
  .form-switch .form-check-input:checked {
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
  }

  .slider.slider-primary .slider-handle,
  .noUi-primary .noUi-handle {
    background: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
  }

  .select2-container--default .select2-results__option--highlighted[aria-selected],
  .select2-container--default .select2-results__option--highlighted[aria-selected]:hover {
    background-color: {{ $themePrimary }} !important;
    color: {{ $themeOnPrimary }} !important;
  }

  .select2-container--default.select2-container--focus .select2-selection--single,
  .select2-container--default.select2-container--focus .select2-selection--multiple,
  .select2-container--default.select2-container--open .select2-selection--single,
  .select2-container--default.select2-container--open .select2-selection--multiple {
    border-color: {{ $themePrimary }} !important;
  }

  .flatpickr-day.selected,
  .flatpickr-day.startRange,
  .flatpickr-day.endRange,
  .flatpickr-day.selected:hover {
    background: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
  }

  .swal2-styled.swal2-confirm {
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
  }

  .dt-buttons .btn-primary,
  .dt-button.btn-primary {
    background-color: {{ $themePrimary }} !important;
    border-color: {{ $themePrimary }} !important;
    color: {{ $themeOnPrimary }} !important;
  }

  /* —— Dashboard / card bottom accent borders (Vuexy card-border-shadow-*) —— */
  .card.card-border-shadow-primary::after {
    border-bottom-color: {{ $themePrimarySoft }} !important;
  }

  .card.card-border-shadow-primary:hover::after {
    border-bottom-color: {{ $themePrimary }} !important;
  }

  .card.card-hover-border-primary:hover,
  .card .card-hover-border-primary:hover {
    border-color: {{ $themePrimary }} !important;
  }

  html:not([dir=rtl]) .border-primary,
  html[dir=rtl] .border-primary {
    border-color: {{ $themePrimary }} !important;
  }

  .avatar .avatar-initial.bg-label-primary,
  .avatar-initial.rounded.bg-label-primary {
    background-color: {{ $themePrimarySoft }} !important;
    color: {{ $themePrimary }} !important;
  }

  .avatar .avatar-initial.bg-label-primary i,
  .avatar-initial.rounded.bg-label-primary i {
    color: {{ $themePrimary }} !important;
  }
</style>
@endif
