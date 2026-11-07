@extends($layout ?? 'layouts.app')
@section('title', 'Global Settings')

@section('content')
@include('flash::message')

@php
  $settingsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.global.store' : 'settings.global.store';
  $canEdit = auth()->user()->can('settings_company_setting_edit') && auth()->user()->can('settings_company_setting_create');
@endphp

<div class="row">
  <div class="col-12">
    <div class="card mb-4">
      <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div>
          <h4 class="card-title mb-1">Global Settings</h4>
          <small class="text-muted">Company-wide appearance and formatting preferences</small>
        </div>
      </div>
      <div class="card-body">
        <p class="text-muted mb-0">
          These settings apply across the entire ERP for this company — forms, tables, dashboards, vouchers, invoices, and reports that use the shared formatters.
        </p>
      </div>
    </div>
  </div>
</div>

<form method="post" action="{{ route($settingsRoute, ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}" enctype="multipart/form-data">
  @csrf

  <div class="row">
    <div class="col-lg-8">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">Brand Colors</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label" for="company_primary_color">Primary brand color</label>
              <input
                type="color"
                name="company_primary_color"
                id="company_primary_color"
                class="form-control form-control-color w-100 @error('company_primary_color') is-invalid @enderror"
                value="{{ $brandColors['primary'] ?? '#2563eb' }}"
                @disabled(! $canEdit)
              />
              @error('company_primary_color')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
              <small class="text-muted d-block mt-1">Used across the site (UI, invoices, vouchers, emails) for this company only.</small>
            </div>
            <div class="col-md-4">
              <label class="form-label" for="company_secondary_color">Secondary brand color</label>
              <input
                type="color"
                name="company_secondary_color"
                id="company_secondary_color"
                class="form-control form-control-color w-100 @error('company_secondary_color') is-invalid @enderror"
                value="{{ $brandColors['secondary'] ?? '#1e3a8a' }}"
                @disabled(! $canEdit)
              />
              @error('company_secondary_color')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-4">
              <label class="form-label" for="company_accent_color">Accent text color</label>
              <input
                type="color"
                name="company_accent_color"
                id="company_accent_color"
                class="form-control form-control-color w-100 @error('company_accent_color') is-invalid @enderror"
                value="{{ $brandColors['accent'] ?? '#818cf8' }}"
                @disabled(! $canEdit)
              />
              @error('company_accent_color')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
              <small class="text-muted d-block mt-1">Used for highlight text and badge labels (e.g. document dates).</small>
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-info mb-4">
        <strong>Moved to Company Details:</strong>
        Currency, currency icon, date format and time format are now managed under
        <a href="{{ route((View::shared('settings_panel') ?? false) ? 'settings-panel.company' : 'settings', ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}">Company Details</a>.
        This page keeps theme/brand colors, font family and number formats.
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">Font Family</h5>
        </div>
        <div class="card-body">
          <label class="form-label" for="font_family">UI font</label>
          @php
            $fontFamilies = $prefConfig['font_families'] ?? [];
            $googleFontQueries = collect($fontFamilies)
              ->pluck('google')
              ->filter()
              ->unique()
              ->values()
              ->all();
          @endphp
          @if($googleFontQueries !== [])
            <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family={{ implode('&family=', $googleFontQueries) }}&display=swap">
          @endif
          <select
            name="font_family"
            id="font_family"
            class="form-select select2 @error('font_family') is-invalid @enderror"
            data-placeholder="Select font family"
            @disabled(! $canEdit)
          >
            @foreach($fontFamilies as $key => $meta)
              @php
                $optionFont = $key === 'system-ui'
                  ? 'system-ui, -apple-system, Segoe UI, Roboto, Helvetica Neue, Arial, sans-serif'
                  : $key;
              @endphp
              <option
                value="{{ $key }}"
                data-font="{{ $optionFont }}"
                @selected(old('font_family', $preferences['font_family'] ?? '') === $key)
              >
                {{ $meta['label'] ?? $key }}
              </option>
            @endforeach
          </select>
          @error('font_family')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
          <small class="text-muted d-block mt-1">Applied to the full application UI for this company. {{ count($fontFamilies) }} fonts available.</small>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">Number Formats</h5>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <label class="form-label" for="decimal_places">Decimal places</label>
              <select name="decimal_places" id="decimal_places" class="form-select @error('decimal_places') is-invalid @enderror" @disabled(! $canEdit)>
                @foreach(($prefConfig['decimal_places_options'] ?? [0, 1, 2, 3, 4]) as $places)
                  <option value="{{ $places }}" @selected((int) old('decimal_places', $preferences['decimal_places'] ?? 2) === (int) $places)>
                    {{ $places }}
                  </option>
                @endforeach
              </select>
              @error('decimal_places')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="currency_position">Currency position</label>
              <select name="currency_position" id="currency_position" class="form-select @error('currency_position') is-invalid @enderror" @disabled(! $canEdit)>
                @foreach(($prefConfig['currency_positions'] ?? []) as $key => $label)
                  <option value="{{ $key }}" @selected(old('currency_position', $preferences['currency_position'] ?? '') === $key)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              @error('currency_position')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="thousand_separator">Thousand separator</label>
              <select name="thousand_separator" id="thousand_separator" class="form-select @error('thousand_separator') is-invalid @enderror" @disabled(! $canEdit)>
                @foreach(($prefConfig['thousand_separators'] ?? []) as $key => $label)
                  <option value="{{ $key }}" @selected(old('thousand_separator', $preferences['thousand_separator'] ?? '') === $key)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              @error('thousand_separator')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
            <div class="col-md-6">
              <label class="form-label" for="decimal_separator">Decimal separator</label>
              <select name="decimal_separator" id="decimal_separator" class="form-select @error('decimal_separator') is-invalid @enderror" @disabled(! $canEdit)>
                @foreach(($prefConfig['decimal_separators'] ?? []) as $key => $label)
                  <option value="{{ $key }}" @selected(old('decimal_separator', $preferences['decimal_separator'] ?? '') === $key)>
                    {{ $label }}
                  </option>
                @endforeach
              </select>
              @error('decimal_separator')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>
      </div>

      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0">Additional Preferences</h5>
        </div>
        <div class="card-body">
          <p class="text-muted small mb-0">
            Future appearance and formatting options will appear here. New preferences are registered centrally without schema changes.
          </p>
        </div>
      </div>

      @if($canEdit)
      <div class="mb-4">
        <button type="submit" class="btn btn-primary">Save Global Settings</button>
      </div>
      @endif
    </div>

    <div class="col-lg-4">
      <div class="card mb-4 sticky-top" style="top: 1rem;">
        <div class="card-header">
          <h5 class="card-title mb-0">Live Preview</h5>
        </div>
        <div class="card-body">
          <div class="mb-3">
            <div class="text-muted small text-uppercase mb-1">Sample date</div>
            <div class="fw-semibold fs-5" id="preview-date">{{ $previewDateSample }}</div>
          </div>
          <div class="mb-3">
            <div class="text-muted small text-uppercase mb-1">Sample number</div>
            <div class="fw-semibold fs-5" id="preview-number">{{ $previewAmount }}</div>
          </div>
          <div>
            <div class="text-muted small text-uppercase mb-1">Sample currency</div>
            <div class="fw-semibold fs-5" id="preview-currency">{{ $previewCurrency }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</form>
@endsection

@push('page-scripts')
<script>
(function () {
  const fontSelect = document.getElementById('font_family');
  const placesSelect = document.getElementById('decimal_places');
  const thousandSelect = document.getElementById('thousand_separator');
  const decimalSelect = document.getElementById('decimal_separator');
  const positionSelect = document.getElementById('currency_position');
  const previewDate = document.getElementById('preview-date');
  const previewNumber = document.getElementById('preview-number');
  const previewCurrency = document.getElementById('preview-currency');
  let symbol = @json(\App\Helpers\Currency::symbol());
  const hasSelect2 = !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2);

  function fontTemplate(state) {
    if (!state.id) {
      return state.text;
    }
    const el = state.element;
    const family = el ? (el.getAttribute('data-font') || state.id) : state.id;
    const $node = window.jQuery('<span></span>').text(state.text).css('font-family', family);
    return $node;
  }

  if (hasSelect2 && fontSelect && !fontSelect.disabled) {
    const $font = window.jQuery(fontSelect);
    if ($font.data('select2')) {
      $font.select2('destroy');
    }
    $font.select2({
      width: '100%',
      placeholder: fontSelect.dataset.placeholder || 'Select font family',
      allowClear: false,
      templateResult: fontTemplate,
      templateSelection: fontTemplate
    });
  }

  function formatNumber(value, places, thousand, decimal) {
    const fixed = Number(value).toFixed(places);
    const parts = fixed.split('.');
    let intPart = parts[0];
    const decPart = parts[1] || '';
    if (thousand && thousand !== 'none') {
      const sep = thousand === 'space' ? ' ' : thousand;
      intPart = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, sep);
    }
    return places > 0 ? intPart + decimal + decPart : intPart;
  }

  function refreshPreview() {
    if (!placesSelect || !previewNumber) return;
    const places = parseInt(placesSelect.value, 10) || 0;
    const thousand = thousandSelect.value;
    const decimal = decimalSelect.value;
    const num = formatNumber(1234567.89, places, thousand, decimal);
    previewNumber.textContent = num;
    if (previewCurrency) {
      previewCurrency.textContent = positionSelect.value === 'after'
        ? num + ' ' + symbol
        : symbol + ' ' + num;
    }
  }

  [placesSelect, thousandSelect, decimalSelect, positionSelect].forEach(function (el) {
    if (el) el.addEventListener('change', refreshPreview);
  });
  refreshPreview();
})();
</script>
@endpush
