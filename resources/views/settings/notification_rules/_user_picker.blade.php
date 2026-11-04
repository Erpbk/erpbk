@php
    $panelKey = $panel ?? 'both';
    $name = $inputName ?? 'both_user_ids[]';
    $selected = array_map('intval', $selectedIds ?? []);
    $emailFirst = $panelKey === 'email';
    $showEmails = ($showEmails ?? true) && $panelKey !== 'in_app' && ! $emailFirst;
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
                if ($emailFirst) {
                    $primary = $userEmail !== '' ? $userEmail : $displayName;
                    $secondary = ($userEmail !== '' && $displayName !== '') ? $displayName : null;
                    $label = strtolower(trim($userEmail . ' ' . $displayName . ' ' . ($user->username ?: '')));
                } else {
                    $primary = $displayName;
                    $secondary = null;
                    $label = strtolower(trim($displayName . ' ' . ($showEmails ? $userEmail : '') . ' ' . ($user->username ?: '')));
                }
            @endphp
            <label class="nr-user-item" data-user-label="{{ $label }}">
                <input class="form-check-input" type="checkbox"
                       name="{{ $name }}" value="{{ $user->id }}"
                       @checked(in_array((int) $user->id, $selected, true))>
                <span class="nr-user-meta">
                    <span class="name d-block">
                        @if($emailFirst && $secondary)
                            {{ $primary }} <span class="text-muted fw-normal">({{ $secondary }})</span>
                        @else
                            {{ $primary }}
                        @endif
                    </span>
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
