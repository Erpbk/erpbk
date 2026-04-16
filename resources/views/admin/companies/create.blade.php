@extends('layouts.app')
@section('title', __('Create Company'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <a href="{{ route('admin.companies.index') }}" class="btn btn-outline-secondary btn-sm">← {{ __('Back to list') }}</a>
        </div>
    </div>

    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.companies.store') }}">
                @csrf

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Company Name') }} <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Email') }} <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Phone') }} <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Country') }} <span class="text-danger">*</span></label>
                        <select name="country_id" id="country_id" class="form-select select2" required>
                            <option value="">{{ __('Select Country') }}</option>
                            @foreach($countries as $id => $name)
                                <option value="{{ $id }}" {{ (string) old('country_id') === (string) $id ? 'selected' : '' }}>
                                    {{ $name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('City') }} <span class="text-danger">*</span></label>
                        <input type="text" name="city" class="form-control" value="{{ old('city') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Address') }} <span class="text-danger">*</span></label>
                        <input type="text" name="address" class="form-control" value="{{ old('address') }}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">{{ __('Confirm Password') }} <span class="text-danger">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="form-check mt-4">
                            <input class="form-check-input" type="checkbox" value="1" id="is_taxpayer" name="is_taxpayer" {{ old('is_taxpayer') ? 'checked' : '' }}>
                            <label class="form-check-label" for="is_taxpayer">{{ __('Is Taxpayer') }}</label>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3 taxpayer-only" style="display:none;">
                        <label class="form-label">{{ __('NTN Number') }} <span class="text-danger">*</span></label>
                        <input type="text" name="ntn_number" id="ntn_number" class="form-control" value="{{ old('ntn_number') }}">
                    </div>
                    <div class="col-md-4 mb-3 taxpayer-only" style="display:none;">
                        <label class="form-label">{{ __('Tax Registration Date') }} <span class="text-danger">*</span></label>
                        <input type="date" name="tax_registration_date" id="tax_registration_date" class="form-control" value="{{ old('tax_registration_date') }}">
                    </div>
                </div>

                <button type="submit" class="btn btn-primary">{{ __('Create Company') }}</button>
            </form>
        </div>
    </div>
</div>
@endsection

@section('page-script')
<script>
    (function () {
        function toggleTaxpayerFields() {
            var isTaxpayer = document.getElementById('is_taxpayer').checked;
            document.querySelectorAll('.taxpayer-only').forEach(function (el) {
                el.style.display = isTaxpayer ? '' : 'none';
            });
            document.getElementById('ntn_number').required = isTaxpayer;
            document.getElementById('tax_registration_date').required = isTaxpayer;
        }

        if (typeof window.$ !== 'undefined' && $.fn.select2) {
            $('#country_id').select2({
                placeholder: "{{ __('Select Country') }}",
                allowClear: true,
                width: '100%'
            });
        }

        document.getElementById('is_taxpayer').addEventListener('change', toggleTaxpayerFields);
        toggleTaxpayerFields();
    })();
</script>
@endsection

