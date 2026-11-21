@php
    $panelKey = $panel ?? 'in_app';
    $name = $inputName ?? 'in_app_user_ids[]';
    $selected = array_map('intval', $selectedIds ?? []);
    $emailFirst = $panelKey === 'email';
    $whatsappMode = $panelKey === 'whatsapp';
    $showEmails = ($showEmails ?? true) && $panelKey !== 'in_app' && ! $emailFirst && ! $whatsappMode;
@endphp
<div class="nr-user-box" data-user-picker data-panel="{{ $panelKey }}">
    <input type="search"
           class="form-control nr-user-box-search form-control-sm"
           placeholder="{{ __('Search…') }}"
           data-user-search
           autocomplete="off">
    <div class="nr-user-list" data-user-list>
        @forelse($moduleUsers as $user)
            @php
                $displayName = trim((string) ($user->name ?: ''));
                if ($displayName === '') {
                    $displayName = trim(trim((string) ($user->first_name ?? '')) . ' ' . trim((string) ($user->last_name ?? '')));
                }
                if ($displayName === '') {
                    $displayName = $user->username ?: __('User #'.$user->id);
                }
                $userEmail = trim((string) ($user->email ?? ''));
                $waDisplay = $whatsappMode ? $user->whatsappPhoneDisplay() : null;
                $waDigits = $whatsappMode ? $user->whatsappPhone() : null;
                $waDisabled = $whatsappMode && $waDigits === null;

                if ($whatsappMode) {
                    $primary = $displayName;
                    $secondary = $waDisplay;
                    $label = strtolower(trim($displayName . ' ' . ($waDisplay ?? '') . ' ' . ($waDigits ?? '') . ' ' . ($user->username ?: '')));
                } elseif ($emailFirst) {
                    $primary = $userEmail !== '' ? $userEmail : $displayName;
                    $secondary = ($userEmail !== '' && $displayName !== '') ? $displayName : null;
                    $label = strtolower(trim($userEmail . ' ' . $displayName . ' ' . ($user->username ?: '')));
                } else {
                    $primary = $displayName;
                    $secondary = null;
                    $label = strtolower(trim($displayName . ' ' . ($showEmails ? $userEmail : '') . ' ' . ($user->username ?: '')));
                }
            @endphp
            <label class="nr-user-item{{ $waDisabled ? ' is-disabled' : '' }}" data-user-label="{{ $label }}">
                <input class="form-check-input" type="checkbox"
                       name="{{ $name }}" value="{{ $user->id }}"
                       @checked(! $waDisabled && in_array((int) $user->id, $selected, true))
                       @disabled($waDisabled)>
                <span class="nr-user-meta">
                    <span class="name d-block">
                        @if(($emailFirst || $whatsappMode) && $secondary)
                            {{ $primary }} <span class="text-muted fw-normal">({{ $secondary }})</span>
                        @else
                            {{ $primary }}
                        @endif
                    </span>
                    @if($whatsappMode && $waDisabled)
                        <span class="email d-block text-danger">{{ __('No phone number') }}</span>
                    @endif
                    @if($showEmails && $userEmail !== '')
                        <span class="email d-block">{{ $userEmail }}</span>
                    @endif
                </span>
            </label>
        @empty
            <div class="nr-user-empty">{{ __('No users with access to this module.') }}</div>
        @endforelse
    </div>
</div>
