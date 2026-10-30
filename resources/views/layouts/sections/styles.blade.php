<!-- BEGIN: Theme CSS-->
<!-- Fonts (company font loaded in company_global_preferences; Public Sans as fallback) -->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@php
  $__prefFont = ($globalPreferences['font_family'] ?? null) ?: ($appFontFamily ?? 'Public Sans');
  $__prefGoogle = \App\Support\CompanyGlobalPreferences::googleFontQuery(null, $__prefFont);
@endphp
@if($__prefGoogle)
<link href="https://fonts.googleapis.com/css2?family={{ $__prefGoogle }}&display=swap" rel="stylesheet">
@elseif($__prefFont === 'Public Sans' || $__prefFont === 'system-ui')
<link href="https://fonts.googleapis.com/css2?family=Public+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;1,300;1,400;1,500;1,600;1,700&display=swap" rel="stylesheet">
@endif

<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/tabler-icons.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/fontawesome.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/fonts/flag-icons.css') }}" />
<!-- Core CSS -->
<link rel="stylesheet" href="{{ asset('assets/vendor/css' .$configData['rtlSupport'] .'/core.css') }}" class="{{ $configData['hasCustomizer'] ? 'template-customizer-core-css' : '' }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/css' .$configData['rtlSupport'] .'/' .$configData['theme'].'.css') }}" class="{{ $configData['hasCustomizer'] ? 'template-customizer-theme-css' : '' }}" />
<link rel="stylesheet" href="{{ asset('assets/css/demo.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/node-waves/node-waves.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/typeahead-js/typeahead.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/toastr/toastr.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/spinkit/spinkit.css') }}" />
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/select2/select2.css') }}" />
<link rel="stylesheet" href="{{ asset('css/custom.css') }}?v=20261030-amber" />
<link rel="stylesheet" href="{{ asset('css/fixed-footer.css') }}?v=1.3" />
@include('layouts.partials.company_brand_theme')
{{-- Font prefs are included after Vite in commonMaster so they win the cascade --}}

<!-- Vendor Styles -->
@yield('vendor-style')


<!-- Page Styles -->
@yield('page-style')
@stack('page-styles')
@stack('third_party_stylesheets')