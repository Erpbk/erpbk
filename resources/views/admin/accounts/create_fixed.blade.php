@extends('layouts.app')
@section('title', __('Add Fixed Account'))

@section('content')
<div class="container-fluid px-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">{{ __('Add Fixed Account') }}</h3>
                <p class="text-white-50 mb-0">{{ __('Create a fixed chart account shared across all companies.') }}</p>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="POST" action="{{ route('admin.accounts.fixed.store') }}">
                @csrf

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">{{ __('Account Name') }}</label>
                        <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Account Type') }}</label>
                        <select name="account_type" class="form-select" required>
                            <option value="">{{ __('Select') }}</option>
                            @foreach(\App\Helpers\Accounts::AccountTypes() as $value => $label)
                                <option value="{{ $value }}" @selected(old('account_type') == $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Parent Account') }}</label>
                        <select name="parent_id" class="form-select">
                            <option value="">{{ __('None') }}</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}" @selected((string) old('parent_id') === (string) $parent->id)>
                                    {{ ($parent->account_code ? $parent->account_code . ' - ' : '') . $parent->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Opening Balance') }}</label>
                        <input type="number" step="any" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">{{ __('Status') }}</label>
                        <select name="status" class="form-select">
                            <option value="1" @selected((string) old('status', '1') === '1')>{{ __('Active') }}</option>
                            <option value="2" @selected((string) old('status') === '2')>{{ __('Inactive') }}</option>
                        </select>
                    </div>

                    <div class="col-12">
                        <label class="form-label">{{ __('Notes') }}</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="mt-4 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">{{ __('Save Fixed Account') }}</button>
                    <a href="{{ route('admin.accounts.fixed.index') }}" class="btn btn-outline-secondary">{{ __('Cancel') }}</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
