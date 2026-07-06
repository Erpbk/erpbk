@php
$isEdit = $isEdit ?? false;
$assignedUserIds = array_map('intval', (array) ($assignedUserIds ?? []));
@endphp
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label required" for="{{ $isEdit ? 'edit_' : 'create_' }}email">Email Address</label>
        <input type="email"
            name="email"
            id="{{ $isEdit ? 'edit_' : 'create_' }}email"
            class="form-control @error('email') is-invalid @enderror"
            value="{{ old('email') }}"
            required
            maxlength="255"
            autocomplete="off">
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label" for="{{ $isEdit ? 'edit_' : 'create_' }}display_name">Display Name</label>
        <input type="text"
            name="display_name"
            id="{{ $isEdit ? 'edit_' : 'create_' }}display_name"
            class="form-control @error('display_name') is-invalid @enderror"
            value="{{ old('display_name') }}"
            maxlength="255">
        @error('display_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label class="form-label {{ $isEdit ? '' : 'required' }}" for="{{ $isEdit ? 'edit_' : 'create_' }}app_password">
            Gmail App Password
        </label>
        <input type="password"
            name="app_password"
            id="{{ $isEdit ? 'edit_' : 'create_' }}app_password"
            class="form-control @error('app_password') is-invalid @enderror"
            {{ $isEdit ? '' : 'required' }}
            minlength="16"
            autocomplete="new-password"
            placeholder="{{ $isEdit ? 'Leave blank to keep current password' : '16-character app password' }}">
        @error('app_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">
            Create under Google Account → Security → App passwords. Spaces are removed automatically.
        </div>
    </div>

    <div class="col-md-6">
        <label class="form-label required" for="{{ $isEdit ? 'edit_' : 'create_' }}status">Status</label>
        <select name="status"
            id="{{ $isEdit ? 'edit_' : 'create_' }}status"
            class="form-select @error('status') is-invalid @enderror"
            required>
            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12">
        <label class="form-label" for="{{ $isEdit ? 'edit_' : 'create_' }}user_ids">Assigned Users</label>
        <select name="user_ids[]"
            id="{{ $isEdit ? 'edit_' : 'create_' }}user_ids"
            class="form-select js-email-account-user-select @error('user_ids') is-invalid @enderror"
            multiple
            data-placeholder="Select users who may send from this account"
            style="width: 100%;">
            @foreach($companyUsers as $u)
            @php
            $displayEmail = $u->email ?: $u->username;
            $displayName = $u->name ?: trim(($u->first_name ?? '').' '.($u->last_name ?? ''));
            $label = trim($displayName) !== '' ? $displayName : 'User';
            if ($displayEmail) {
                $label .= ' (' . $displayEmail . ')';
            }
            @endphp
            <option value="{{ $u->id }}" {{ in_array((int) $u->id, $assignedUserIds, true) ? 'selected' : '' }}>
                {{ $label }}
            </option>
            @endforeach
        </select>
        @error('user_ids')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
        @error('user_ids.*')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
    </div>
</div>
