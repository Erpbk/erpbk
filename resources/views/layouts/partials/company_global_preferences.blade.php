@php
  $prefs = $globalPreferences ?? \App\Support\CompanyGlobalPreferences::all();
  $fontFamily = $prefs['font_family'] ?? 'Public Sans';
  $fontStack = \App\Support\CompanyGlobalPreferences::fontCssStack(null, $fontFamily);
  $googleQuery = \App\Support\CompanyGlobalPreferences::googleFontQuery(null, $fontFamily);
  // Always apply for tenant users; defaults are a no-op (Public Sans).
  $applyPrefs = !empty($applyCompanyTheme)
    || \App\Support\CompanyContext::id() !== null
    || (auth()->check() && !empty(auth()->user()->company_id));
  $appGlobalPreferencesJs = [
    'font_family' => $fontFamily,
    'date_format' => $prefs['date_format'] ?? 'd-m-Y',
    'decimal_places' => (int) ($prefs['decimal_places'] ?? 2),
    'thousand_separator' => \App\Support\CompanyGlobalPreferences::thousandSeparator(),
    'decimal_separator' => $prefs['decimal_separator'] ?? '.',
    'currency_position' => $prefs['currency_position'] ?? 'before',
    'font_stack' => $fontStack,
  ];
@endphp
@if($applyPrefs)
@if($googleQuery)
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ $googleQuery }}&display=swap" data-global-pref-font>
@endif
{{--
  IMPORTANT: font stack must use {!! !!} — {{ }} escapes quotes to &quot; and breaks CSS.
  Vuexy maps UI text through --bs-font-sans-serif (not only --bs-body-font-family).
--}}
<style id="company-global-preferences">
  :root,
  .light-style,
  .dark-style,
  html {
    --bs-font-sans-serif: {!! $fontStack !!};
    --bs-body-font-family: {!! $fontStack !!};
    --company-font-family: {!! $fontStack !!};
  }

  html,
  body {
    font-family: {!! $fontStack !!} !important;
  }

  body .layout-wrapper,
  body .layout-container,
  body .layout-menu,
  body .layout-navbar,
  body .layout-page,
  body .content-wrapper,
  body .card,
  body .modal,
  body .dropdown-menu,
  body .form-control,
  body .form-select,
  body .btn:not(.btn-icon),
  body table,
  body .datatables-basic,
  body .swal2-popup,
  body .menu-inner,
  body .navbar-nav,
  body .select2-container,
  body .select2-results,
  body .select2-selection {
    font-family: {!! $fontStack !!} !important;
  }

  body .ti,
  body [class^="ti-"],
  body [class*=" ti-"],
  body .tf-icons,
  body .menu-icon.tf-icons {
    font-family: "tabler-icons" !important;
  }
</style>
<script>
  window.appGlobalPreferences = @json($appGlobalPreferencesJs);
  (function () {
    var stack = window.appGlobalPreferences && window.appGlobalPreferences.font_stack;
    if (!stack) return;
    var root = document.documentElement;
    root.style.setProperty('--bs-font-sans-serif', stack);
    root.style.setProperty('--bs-body-font-family', stack);
    root.style.setProperty('--company-font-family', stack);
    if (document.body) {
      document.body.style.fontFamily = stack;
    } else {
      document.addEventListener('DOMContentLoaded', function () {
        if (document.body) document.body.style.fontFamily = stack;
      });
    }
  })();
</script>
@endif
