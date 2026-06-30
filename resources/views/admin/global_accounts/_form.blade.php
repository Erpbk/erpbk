@php
    $isEdit = isset($globalAccount) && $globalAccount;
    $linkMode = old('link_mode', $isEdit ? 'keep' : 'link');
@endphp

<div class="row">
    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __('Code') }}</label>
        @if($isEdit)
            <input type="text" class="form-control" value="{{ $globalAccount->code }}" readonly>
            <div class="form-text">{{ __('Code cannot be changed after creation.') }}</div>
        @else
            <input type="text" name="code" class="form-control text-uppercase" value="{{ old('code') }}" placeholder="VAT_ON_SALES" required pattern="[A-Z][A-Z0-9_]*">
            @error('code')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        @endif
    </div>

    <div class="col-md-6 mb-3">
        <label class="form-label">{{ __('Label') }}</label>
        <input type="text" name="label" class="form-control" value="{{ old('label', $globalAccount->label ?? '') }}" required>
        @error('label')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="mb-3">
    <label class="form-label">{{ __('Description') }}</label>
    <textarea name="description" class="form-control" rows="2">{{ old('description', $globalAccount->description ?? '') }}</textarea>
</div>

<div class="mb-3">
    <div class="form-check">
        <input type="hidden" name="is_active" value="0">
        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="is_active"
            {{ old('is_active', $globalAccount->is_active ?? true) ? 'checked' : '' }}>
        <label class="form-check-label" for="is_active">{{ __('Active') }}</label>
    </div>
</div>

<hr>

<div class="mb-3">
    <label class="form-label d-block">{{ __('Account setup') }}</label>
    @if($isEdit)
        <div class="form-check form-check-inline">
            <input class="form-check-input link-mode-radio" type="radio" name="link_mode" id="link_mode_keep" value="keep" {{ $linkMode === 'keep' ? 'checked' : '' }}>
            <label class="form-check-label" for="link_mode_keep">{{ __('Keep current account') }}</label>
        </div>
    @endif
    <div class="form-check form-check-inline">
        <input class="form-check-input link-mode-radio" type="radio" name="link_mode" id="link_mode_link" value="link" {{ $linkMode === 'link' ? 'checked' : '' }}>
        <label class="form-check-label" for="link_mode_link">{{ __('Link existing account') }}</label>
    </div>
    <div class="form-check form-check-inline">
        <input class="form-check-input link-mode-radio" type="radio" name="link_mode" id="link_mode_create" value="create" {{ $linkMode === 'create' ? 'checked' : '' }}>
        <label class="form-check-label" for="link_mode_create">{{ __('Create new account') }}</label>
    </div>
</div>

@if($isEdit && $globalAccount->account)
    <div class="alert alert-light border mb-3" id="currentAccountInfo">
        {{ __('Current linked account:') }}
        <strong>{{ $globalAccount->account->account_code }} — {{ $globalAccount->account->name }}</strong>
        (#{{ $globalAccount->account_id }})
    </div>
@endif

<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">{{ __('Account type') }}</label>
        <select name="account_type" id="account_type" class="form-select" required>
            <option value="">{{ __('Select type') }}</option>
            @foreach($accountTypes as $typeKey => $typeLabel)
                <option value="{{ $typeKey }}" {{ old('account_type', $globalAccount->account_type ?? '') === $typeKey ? 'selected' : '' }}>{{ $typeLabel }}</option>
            @endforeach
        </select>
        @error('account_type')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
    </div>
</div>

<div id="linkAccountPanel" class="border rounded p-3 mb-3" style="display: none;">
    <div class="mb-3">
        <label class="form-label">{{ __('Select account to link') }}</label>
        <select name="account_id" id="account_id" class="form-select">
            <option value="">{{ __('Select account type first, then choose account') }}</option>
        </select>
        @error('account_id')
            <div class="text-danger small mt-1">{{ $message }}</div>
        @enderror
        <div class="form-text">{{ __('Only shared accounts (company_id NULL) are listed.') }}</div>
    </div>
</div>

<div id="createAccountPanel" class="border rounded p-3 mb-3" style="display: none;">
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label">{{ __('Account name') }}</label>
            <input type="text" name="account_name" id="account_name" class="form-control" value="{{ old('account_name') }}">
            @error('account_name')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>
        <div class="col-md-6 mb-3">
            <label class="form-label">{{ __('Parent account') }}</label>
            <select name="parent_id" class="form-select">
                <option value="">{{ __('None (root account)') }}</option>
                @foreach($parents as $parent)
                    <option value="{{ $parent->id }}" {{ (string) old('parent_id') === (string) $parent->id ? 'selected' : '' }}>
                        {{ $parent->account_code }} — {{ $parent->name }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">{{ __('Opening balance') }}</label>
            <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}">
        </div>
        <div class="col-md-4 mb-3">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-select">
                <option value="1" {{ old('status', '1') === '1' ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="2" {{ old('status') === '2' ? 'selected' : '' }}>{{ __('Inactive') }}</option>
            </select>
        </div>
        <div class="col-md-12 mb-3">
            <label class="form-label">{{ __('Notes') }}</label>
            <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>
    </div>
</div>
