@extends($layout ?? 'layouts.app')
@section('title','Settings')

@section('content')

<div class="card">

  <div class="card-header">
    <h4 class="card-title">Application Settings</h4>
  </div>
  <div class="card-body">
    @if($errors->any())
    <div class="alert alert-danger">
      <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
    @endif

    @php $settingsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.company' : 'settings'; @endphp
    @php $isSettingsPanel = (bool) (View::shared('settings_panel') ?? false); @endphp
    <form id="companySettingsForm" action="{{ route($settingsRoute, ['company_slug' => request()->route('company_slug') ?? session('company_slug')]) }}" method="post" enctype="multipart/form-data">
      @csrf
      <div class="row">

        <div class="col-md-4 mb-3">
          <label class="">Company Name</label>
          <div class="input-group ">
            <input type="text" name="{{ $isSettingsPanel ? 'company_name' : 'settings[company_name]' }}" class="form-control" value="{{ old('company_name', $currentCompany->name ?? ($settings['company_name']??'')) }}" />
            {{-- <div class="input-group-text">USD</div> --}}
          </div>
        </div>

        <div class="col-md-4 mb-3">
          <label class="">Email</label>
          <div class="input-group ">
            <input
              type="email"
              id="company_email"
              name="{{ $isSettingsPanel ? 'company_email' : 'settings[company_email]' }}"
              class="form-control @error('company_email') is-invalid @enderror"
              value="{{ old('company_email', $currentCompany->email ?? ($settings['company_email']??'')) }}"
              @if($isSettingsPanel) data-original-email="{{ old('company_email', $currentCompany->email ?? '') }}" @endif />
          </div>
          @error('company_email')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
          <div id="company_email_ajax_error" class="text-danger small mt-1 d-none"></div>
          @if($isSettingsPanel)
          <small class="text-muted d-block mt-1">Changing your email requires a verification code sent to the new address.</small>
          @endif
        </div>

        <div class="col-md-4 mb-3">
          <label class="">Phone</label>
          <div class="input-group ">
            <input type="text" name="{{ $isSettingsPanel ? 'company_phone' : 'settings[company_phone]' }}" class="form-control" value="{{ old('company_phone', $currentCompany->phone ?? ($settings['company_phone']??'')) }}" />
            {{-- <div class="input-group-text">USD</div> --}}
          </div>
        </div>

        <div class="col-md-8 mb-3">
          <label class="">Address</label>
          <div class="input-group ">
            <input type="text" name="{{ $isSettingsPanel ? 'company_address' : 'settings[company_address]' }}" class="form-control" value="{{ old('company_address', $currentCompany->address ?? ($settings['company_address']??'')) }}" />
            {{-- <div class="input-group-text">USD</div> --}}
          </div>
        </div>

        @if($isSettingsPanel)
        <div class="col-md-2 mb-3">
          <label class="">Country</label>
          <div class="input-group ">
            <select
              id="company_country"
              name="company_country"
              class="form-select select2"
              data-placeholder="Select Country"
              data-selected="{{ old('company_country', $currentCompany->country ?? '') }}">
              <option value="">Select Country</option>
            </select>
          </div>
        </div>
        <div class="col-md-2 mb-3">
          <label class="">City</label>
          <div class="input-group ">
            <select
              id="company_city"
              name="company_city"
              class="form-select select2"
              data-placeholder="Select City"
              data-selected="{{ old('company_city', $currentCompany->city ?? '') }}">
              <option value="">Select City</option>
            </select>
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <label class="">Company Logo</label>
          <input type="file" name="company_logo" class="form-control" accept=".jpg,.jpeg,.png,.webp" />
          @error('company_logo')
          <div class="text-danger small mt-1">{{ $message }}</div>
          @enderror
          @if(!empty($currentCompany?->logo))
          <div class="mt-2">
            <img src="{{ storage_url($currentCompany->logo) }}" alt="Company Logo" style="max-height: 60px; max-width: 200px; object-fit: contain;">
          </div>
          @endif
          <small class="text-muted d-block mt-1">Used in PDFs and outbound emails for this company only.</small>
        </div>
        <div class="col-md-2 mb-3">
          <label>Email header color</label>
          <input type="color" name="company_primary_color" class="form-control form-control-color w-100"
            value="{{ old('company_primary_color', $currentCompany->primary_color ?? '#2563eb') }}" />
        </div>
        <div class="col-md-2 mb-3">
          <label>Email accent color</label>
          <input type="color" name="company_secondary_color" class="form-control form-control-color w-100"
            value="{{ old('company_secondary_color', $currentCompany->secondary_color ?? '#1e3a8a') }}" />
        </div>
        @endif

        <!-- <div class="col-md-4 mb-3">
          <label class="">Currency Code</label>
          <div class="input-group ">
            <input type="text" name="settings[currency_code]" class="form-control" value="{{ $settings['currency_code'] ?? ($appCurrencyCode ?? 'AED') }}" placeholder="{{ $appCurrencyCode ?? 'AED' }}, USD, PKR" />
          </div>
        </div> -->
        <div class="col-md-4 mb-3">
          <label class="">Currency</label>
          <div class="input-group ">
            <input type="text" name="settings[currency_symbol]" class="form-control" value="{{ $settings['currency_symbol'] ?? ($appCurrencySymbol ?? 'AED') }}" placeholder="{{ $appCurrencySymbol ?? 'AED' }}, $, Rs" />
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <label class="">VAT Number</label>
          <div class="input-group ">
            <input type="text" name="settings[vat_number]" class="form-control" value="{{$settings['vat_number']??''}}" />
          </div>
        </div>
        <div class="col-md-4 mb-3">
          <label class="">VAT Percentage</label>
          <div class="input-group ">
            <input type="number" step="any" name="settings[vat_percentage]" class="form-control" value="{{$settings['vat_percentage']??''}}" />
            <div class="input-group-text">%</div>
          </div>
        </div>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-primary" style="float:right;">Save Settings</button>
      </div>
    </form>
  </div>
</div>

@if($isSettingsPanel)
{{-- Email change verification modal --}}
<div class="modal fade" id="companyEmailOtpModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Verify new email</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-2 text-muted" id="companyEmailOtpHint">Enter the 6-digit code we sent to your new email address.</p>
        <form id="companyEmailOtpForm">
          @csrf
          <div class="mb-3">
            <label for="company_email_otp" class="form-label">Verification code</label>
            <input type="text" class="form-control form-control-lg text-center" id="company_email_otp" name="otp" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" placeholder="000000" autocomplete="one-time-code" required>
            <div id="companyEmailOtpError" class="invalid-feedback d-block"></div>
          </div>
          <button type="submit" class="btn btn-primary w-100" id="companyEmailOtpSubmitBtn">Verify &amp; save</button>
        </form>
      </div>
    </div>
  </div>
</div>

@push('page-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('companySettingsForm');
    const emailInput = document.getElementById('company_email');
    const otpModalEl = document.getElementById('companyEmailOtpModal');
    const otpForm = document.getElementById('companyEmailOtpForm');
    const saveBtn = form ? form.querySelector('button[type="submit"]') : null;

    if (!form || !emailInput || !otpModalEl || !otpForm) {
      return;
    }

    const sendOtpUrl = @json(route('settings-panel.company.email.send-otp', ['company_slug' => request() -> route('company_slug') ?? session('company_slug')]));
    const verifyOtpUrl = @json(route('settings-panel.company.email.verify-otp', ['company_slug' => request() -> route('company_slug') ?? session('company_slug')]));
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const saveBtnDefaultHtml = saveBtn ? saveBtn.innerHTML : '';

    function normalizeEmail(value) {
      return (value || '').trim().toLowerCase();
    }

    function emailChanged() {
      return normalizeEmail(emailInput.value) !== normalizeEmail(emailInput.dataset.originalEmail || '');
    }

    function showEmailError(message) {
      const ajaxErr = document.getElementById('company_email_ajax_error');
      if (ajaxErr) {
        ajaxErr.textContent = message;
        ajaxErr.classList.remove('d-none');
      }
      emailInput.classList.add('is-invalid');
    }

    function clearEmailError() {
      const ajaxErr = document.getElementById('company_email_ajax_error');
      if (ajaxErr) {
        ajaxErr.textContent = '';
        ajaxErr.classList.add('d-none');
      }
      emailInput.classList.remove('is-invalid');
    }

    function setSaveButtonLoading(loading) {
      if (!saveBtn) {
        return;
      }
      saveBtn.disabled = loading;
      saveBtn.innerHTML = loading ?
        '<span class="spinner-border spinner-border-sm me-1"></span> Sending code...' :
        saveBtnDefaultHtml;
    }

    // Step 1: User clicks Save — if email changed, send OTP and open modal (do not save yet).
    form.addEventListener('submit', function(e) {
      if (form.dataset.emailVerifiedSubmit === '1' || !emailChanged()) {
        return;
      }

      e.preventDefault();
      e.stopPropagation();
      clearEmailError();
      setSaveButtonLoading(true);

      const body = new FormData();
      body.append('email', emailInput.value.trim());
      body.append('_token', csrfToken);

      fetch(sendOtpUrl, {
          method: 'POST',
          body: body,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
        })
        .then(function(r) {
          return r.json().then(function(data) {
            return {
              ok: r.ok,
              data: data
            };
          });
        })
        .then(function(result) {
          if (!result.ok || !result.data.success) {
            const msg = result.data.message ||
              (result.data.errors && result.data.errors.email && result.data.errors.email[0]) ||
              'Could not send verification code.';
            showEmailError(msg);
            return;
          }

          clearEmailError();
          document.getElementById('companyEmailOtpHint').textContent =
            'We sent a 6-digit code to ' + emailInput.value.trim() + '. Enter it below, then click Verify & save.';
          document.getElementById('company_email_otp').value = '';
          document.getElementById('companyEmailOtpError').textContent = '';
          document.getElementById('company_email_otp').classList.remove('is-invalid');

          if (typeof bootstrap !== 'undefined') {
            bootstrap.Modal.getOrCreateInstance(otpModalEl).show();
            document.getElementById('company_email_otp').focus();
          } else {
            alert('Verification code sent. Please refresh the page and try again.');
          }
        })
        .catch(function() {
          alert('Network error. Please try again.');
        })
        .finally(function() {
          setSaveButtonLoading(false);
        });
    });

    // Step 2: User enters OTP in modal and submits — verify, then save the full form.
    otpForm.addEventListener('submit', function(e) {
      e.preventDefault();

      const otp = document.getElementById('company_email_otp').value.trim();
      const errEl = document.getElementById('companyEmailOtpError');
      const otpInput = document.getElementById('company_email_otp');
      const verifyBtn = document.getElementById('companyEmailOtpSubmitBtn');

      if (otp.length !== 6) {
        errEl.textContent = 'Please enter the 6-digit code.';
        otpInput.classList.add('is-invalid');
        return;
      }

      otpInput.classList.remove('is-invalid');
      errEl.textContent = '';
      verifyBtn.disabled = true;
      verifyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Verifying...';

      const body = new FormData();
      body.append('otp', otp);
      body.append('_token', csrfToken);

      fetch(verifyOtpUrl, {
          method: 'POST',
          body: body,
          credentials: 'same-origin',
          headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
          },
        })
        .then(function(r) {
          return r.json().then(function(data) {
            return {
              ok: r.ok,
              data: data
            };
          });
        })
        .then(function(result) {
          if (!result.ok || !result.data.success) {
            errEl.textContent = result.data.message || 'Invalid or expired verification code.';
            otpInput.classList.add('is-invalid');
            return;
          }

          const modal = typeof bootstrap !== 'undefined' ?
            bootstrap.Modal.getInstance(otpModalEl) :
            null;
          if (modal) {
            modal.hide();
          }

          // Native submit skips the submit listener — saves all settings including verified email.
          form.dataset.emailVerifiedSubmit = '1';
          if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Saving...';
          }
          form.submit();
        })
        .catch(function() {
          alert('Network error. Please try again.');
        })
        .finally(function() {
          verifyBtn.disabled = false;
          verifyBtn.innerHTML = 'Verify &amp; save';
        });
    });
  });
</script>
@endpush

<script>
  document.addEventListener('DOMContentLoaded', async function() {
    const countrySelect = document.getElementById('company_country');
    const citySelect = document.getElementById('company_city');
    const currencyCodeInput = document.querySelector('input[name="settings[currency_code]"]');
    const currencySymbolInput = document.querySelector('input[name="settings[currency_symbol]"]');
    const rtaFeeCurrencyText = document.querySelector('input[name="settings[rta_admin_fee]"]')?.closest('.input-group')?.querySelector('.input-group-text');
    const hasSelect2 = !!(window.jQuery && window.jQuery.fn && window.jQuery.fn.select2);

    if (!countrySelect || !citySelect) {
      return;
    }

    function reinitializeSelect2(selectElement, placeholder) {
      if (!hasSelect2) {
        return;
      }

      const $select = window.jQuery(selectElement);
      if ($select.data('select2')) {
        $select.select2('destroy');
      }

      $select.select2({
        width: '100%',
        placeholder: placeholder,
        allowClear: true
      });
    }

    const selectedCountry = countrySelect.dataset.selected || '';
    let selectedCity = citySelect.dataset.selected || '';
    let countriesWithCities = [];
    const countryCurrencyMap = {};
    const uaeEmirates = [
      'Abu Dhabi',
      'Dubai',
      'Sharjah',
      'Ajman',
      'Umm Al Quwain',
      'Ras Al Khaimah',
      'Fujairah'
    ];
    const currencySymbols = {
      AED: 'AED',
      USD: '$',
      EUR: 'EUR',
      GBP: 'GBP',
      PKR: 'Rs',
      INR: 'Rs',
      SAR: 'SAR',
      QAR: 'QAR',
      KWD: 'KWD',
      BHD: 'BHD',
      OMR: 'OMR',
      JPY: 'JPY',
      CNY: 'CNY'
    };

    function getCurrencySymbolByCode(code) {
      const normalizedCode = (code || '').trim().toUpperCase();
      return currencySymbols[normalizedCode] || normalizedCode || 'AED';
    }

    function applyCurrencyForCountry(countryName, forceUpdate) {
      const normalizedCountry = (countryName || '').trim().toLowerCase();
      const code = countryCurrencyMap[normalizedCountry];

      if (!code) {
        return;
      }

      if (currencyCodeInput && (forceUpdate || !currencyCodeInput.value.trim())) {
        currencyCodeInput.value = code;
      }

      const symbol = getCurrencySymbolByCode(code);
      if (currencySymbolInput && (forceUpdate || !currencySymbolInput.value.trim())) {
        currencySymbolInput.value = symbol;
      }

      if (rtaFeeCurrencyText) {
        rtaFeeCurrencyText.textContent = symbol;
      }
    }

    function renderCityOptions(cities, keepSelectedCity) {
      citySelect.innerHTML = '<option value="">Select City</option>';

      cities.forEach(function(city) {
        const option = document.createElement('option');
        option.value = city;
        option.textContent = city;
        if (keepSelectedCity && city === selectedCity) {
          option.selected = true;
        }
        citySelect.appendChild(option);
      });

      if (keepSelectedCity && selectedCity && !cities.includes(selectedCity)) {
        const fallbackOption = document.createElement('option');
        fallbackOption.value = selectedCity;
        fallbackOption.textContent = selectedCity;
        fallbackOption.selected = true;
        citySelect.appendChild(fallbackOption);
      }

      reinitializeSelect2(citySelect, 'Select City');
    }

    function getMainCities(countryName, cities) {
      const normalized = (countryName || '').trim().toLowerCase();

      // UAE should always show Emirates only.
      if (normalized === 'united arab emirates' || normalized === 'uae') {
        return uaeEmirates.slice();
      }

      // Keep a short, unique, alphabetic list for other countries.
      const uniqueCities = Array.from(new Set((cities || []).filter(Boolean)));
      return uniqueCities
        .sort(function(a, b) {
          return a.localeCompare(b);
        })
        .slice(0, 20);
    }

    async function loadCitiesForCountry(countryName, keepSelectedCity) {
      const normalizedCountryName = (countryName || '').trim();

      if (!normalizedCountryName) {
        renderCityOptions([], false);
        return;
      }

      // First use cities already returned in the countries list API.
      const selectedCountryData = countriesWithCities.find(function(item) {
        return (item.country || '').trim().toLowerCase() === normalizedCountryName.toLowerCase();
      });

      const localCities = selectedCountryData && Array.isArray(selectedCountryData.cities) ?
        selectedCountryData.cities : [];

      if (localCities.length > 0) {
        renderCityOptions(getMainCities(normalizedCountryName, localCities), keepSelectedCity);
        return;
      }

      // Fallback: fetch cities by country endpoint.
      try {
        const response = await fetch('https://countriesnow.space/api/v0.1/countries/cities/q?country=' + encodeURIComponent(normalizedCountryName));
        const payload = await response.json();
        const queriedCities = Array.isArray(payload.data) ? payload.data : [];

        if (queriedCities.length > 0) {
          renderCityOptions(getMainCities(normalizedCountryName, queriedCities), keepSelectedCity);
          return;
        }
      } catch (error) {
        // ignore and try fallback method below
      }

      // Fallback for environments where /q endpoint is blocked.
      try {
        const fallbackResponse = await fetch('https://countriesnow.space/api/v0.1/countries/cities', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            country: normalizedCountryName
          })
        });
        const fallbackPayload = await fallbackResponse.json();
        const fallbackCities = Array.isArray(fallbackPayload.data) ? fallbackPayload.data : [];
        renderCityOptions(getMainCities(normalizedCountryName, fallbackCities), keepSelectedCity);
      } catch (error) {
        renderCityOptions(getMainCities(normalizedCountryName, []), keepSelectedCity);
      }
    }

    try {
      const [countriesResponse, currencyResponse] = await Promise.all([
        fetch('https://countriesnow.space/api/v0.1/countries'),
        fetch('https://countriesnow.space/api/v0.1/countries/currency')
      ]);
      const countriesPayload = await countriesResponse.json();
      countriesWithCities = Array.isArray(countriesPayload.data) ? countriesPayload.data : [];

      const currencyPayload = await currencyResponse.json();
      const countryCurrencies = Array.isArray(currencyPayload.data) ? currencyPayload.data : [];
      countryCurrencies.forEach(function(item) {
        const countryName = (item.name || '').trim().toLowerCase();
        const currencyCode = (item.currency || '').trim().toUpperCase();
        if (countryName && currencyCode) {
          countryCurrencyMap[countryName] = currencyCode;
        }
      });

      countrySelect.innerHTML = '<option value="">Select Country</option>';

      countriesWithCities
        .sort(function(a, b) {
          return a.country.localeCompare(b.country);
        })
        .forEach(function(item) {
          const option = document.createElement('option');
          option.value = item.country;
          option.textContent = item.country;
          if (item.country === selectedCountry) {
            option.selected = true;
          }
          countrySelect.appendChild(option);
        });

      if (selectedCountry && !countriesWithCities.some(function(item) {
          return item.country === selectedCountry;
        })) {
        const fallbackOption = document.createElement('option');
        fallbackOption.value = selectedCountry;
        fallbackOption.textContent = selectedCountry;
        fallbackOption.selected = true;
        countrySelect.appendChild(fallbackOption);
      }

      reinitializeSelect2(countrySelect, 'Select Country');
      await loadCitiesForCountry(countrySelect.value, true);
      applyCurrencyForCountry(countrySelect.value, false);
    } catch (error) {
      countrySelect.innerHTML = '<option value="">Select Country</option>';
      citySelect.innerHTML = '<option value="">Select City</option>';

      if (selectedCountry) {
        const countryOption = document.createElement('option');
        countryOption.value = selectedCountry;
        countryOption.textContent = selectedCountry;
        countryOption.selected = true;
        countrySelect.appendChild(countryOption);
      }

      if (selectedCity) {
        const cityOption = document.createElement('option');
        cityOption.value = selectedCity;
        cityOption.textContent = selectedCity;
        cityOption.selected = true;
        citySelect.appendChild(cityOption);
      }

      reinitializeSelect2(countrySelect, 'Select Country');
      reinitializeSelect2(citySelect, 'Select City');
    }

    async function onCountryChanged() {
      selectedCity = '';
      await loadCitiesForCountry(countrySelect.value, false);
      applyCurrencyForCountry(countrySelect.value, true);
    }

    countrySelect.addEventListener('change', onCountryChanged);

    if (hasSelect2) {
      const $countrySelect = window.jQuery(countrySelect);
      $countrySelect.on('select2:select', onCountryChanged);
      $countrySelect.on('select2:clear', onCountryChanged);
    }
  });
</script>
@endif
@endsection