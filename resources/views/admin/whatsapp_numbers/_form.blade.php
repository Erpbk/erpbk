@php
$isEdit = $isEdit ?? false;
$number = $number ?? null;
$assignment = old('assignment', $number
    ? ($number->is_default ? 'default' : 'company')
    : 'company');
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="label">{{ __('Label') }}</label>
        <input type="text" name="label" id="label"
            class="form-control @error('label') is-invalid @enderror"
            value="{{ old('label', $number->label ?? '') }}"
            maxlength="255"
            placeholder="Ops line / Sales">
        @error('label')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label required" for="phone_number_id">{{ __('Phone Number ID') }}</label>
        <input type="text" name="phone_number_id" id="phone_number_id"
            class="form-control @error('phone_number_id') is-invalid @enderror"
            value="{{ old('phone_number_id', $number->phone_number_id ?? '') }}"
            required maxlength="64" autocomplete="off">
        @error('phone_number_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">{{ __('From Meta WhatsApp → API Setup → Phone number ID.') }}</div>
    </div>

    <div class="col-md-6">
        <label class="form-label" for="display_phone_number">{{ __('Display phone') }}</label>
        <input type="text" name="display_phone_number" id="display_phone_number"
            class="form-control @error('display_phone_number') is-invalid @enderror"
            value="{{ old('display_phone_number', $number->display_phone_number ?? '') }}"
            maxlength="40" placeholder="+9715…">
        @error('display_phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="waba_id">{{ __('WABA ID') }}</label>
        <input type="text" name="waba_id" id="waba_id"
            class="form-control @error('waba_id') is-invalid @enderror"
            value="{{ old('waba_id', $number->waba_id ?? '') }}"
            maxlength="64">
        @error('waba_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label {{ $isEdit ? '' : 'required' }}" for="access_token">{{ __('Access token') }}</label>
        <input type="password" name="access_token" id="access_token"
            class="form-control @error('access_token') is-invalid @enderror"
            {{ $isEdit ? '' : 'required' }}
            minlength="20"
            autocomplete="new-password"
            placeholder="{{ $isEdit ? __('Leave blank to keep current token') : __('Permanent / system user token') }}">
        @error('access_token')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label required" for="status">{{ __('Status') }}</label>
        <select name="status" id="status"
            class="form-select no-select2 @error('status') is-invalid @enderror"
            data-no-select2 required>
            <option value="active" @selected(old('status', $number->status ?? 'active') === 'active')>{{ __('Active') }}</option>
            <option value="inactive" @selected(old('status', $number->status ?? '') === 'inactive')>{{ __('Inactive') }}</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label required" for="assignment">{{ __('Assignment') }}</label>
        <select name="assignment" id="assignment"
            class="form-select no-select2 @error('assignment') is-invalid @enderror"
            data-no-select2 required>
            <option value="company" @selected($assignment === 'company')>{{ __('Assign to company') }}</option>
            <option value="default" @selected($assignment === 'default')>{{ __('Default number') }}</option>
        </select>
        @error('assignment')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            {{ __('A company can have multiple numbers. Companies with none assigned use the default when WhatsApp notifications are enabled for them.') }}
        </div>
    </div>

    <div class="col-md-6 js-company-assignment">
        <label class="form-label required" for="company_id">{{ __('Company') }}</label>
        <select name="company_id" id="company_id"
            class="form-select no-select2 @error('company_id') is-invalid @enderror"
            data-no-select2>
            <option value="">{{ __('Select company') }}</option>
            @foreach($companies as $company)
                <option value="{{ $company->id }}"
                    @selected((int) old('company_id', $number->company_id ?? 0) === (int) $company->id)>
                    {{ $company->name }}
                </option>
            @endforeach
        </select>
        @error('company_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>

@push('page-scripts')
<script>
(function () {
    const assignment = document.getElementById('assignment');
    const companyWrap = document.querySelector('.js-company-assignment');
    const companySelect = document.getElementById('company_id');
    if (!assignment || !companyWrap) return;
    function sync() {
        const isCompany = assignment.value === 'company';
        companyWrap.classList.toggle('d-none', !isCompany);
        if (companySelect) {
            companySelect.required = isCompany;
            if (!isCompany) companySelect.value = '';
        }
    }
    assignment.addEventListener('change', sync);
    sync();
})();
</script>
@endpush
