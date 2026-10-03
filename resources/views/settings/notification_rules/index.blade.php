@extends($layout ?? 'layouts.app')

@section('title', __('Notification rules'))

@section('content')
@php
    $triggerLabels = [
        'scheduled' => __('Scheduled'),
        'condition' => __('Condition'),
        'event' => __('Event'),
    ];
    $triggerBadge = [
        'scheduled' => 'bg-label-info',
        'condition' => 'bg-label-warning',
        'event' => 'bg-label-primary',
    ];
@endphp

<style>
  .nr-page-header { margin-bottom: 1.5rem; }
  .nr-module-card { border: 0; box-shadow: 0 0.125rem 0.5rem rgba(34, 48, 62, 0.08); overflow: hidden; }
  .nr-module-toggle {
    width: 100%;
    background: linear-gradient(180deg, rgba(105, 108, 255, 0.06), transparent);
    border: 0;
    border-bottom: 1px solid rgba(67, 89, 113, 0.1);
    text-align: left;
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    color: inherit;
  }
  .nr-module-toggle:hover { background: rgba(105, 108, 255, 0.08); }
  .nr-module-toggle .nr-chevron {
    transition: transform .2s ease;
    color: #697a8d;
  }
  .nr-module-toggle[aria-expanded="false"] .nr-chevron { transform: rotate(-90deg); }
  .nr-rule {
    border: 1px solid rgba(67, 89, 113, 0.12);
    border-radius: 0.75rem;
    background: var(--bs-body-bg, #fff);
    transition: border-color .15s ease, opacity .15s ease;
  }
  .nr-rule:hover { border-color: rgba(105, 108, 255, 0.35); }
  .nr-rule.is-disabled { opacity: 0.72; background: rgba(67, 89, 113, 0.03); }
  .nr-rule-head {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem 1rem;
    padding: 1rem 1.25rem;
    border-bottom: 1px solid rgba(67, 89, 113, 0.08);
  }
  .nr-rule-body { padding: 1.25rem; }
  .nr-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: .04em;
    text-transform: uppercase;
    color: #697a8d;
    margin-bottom: 0.65rem;
  }
  .nr-chip-group { display: flex; flex-wrap: wrap; gap: 0.5rem; }
  .nr-chip { position: relative; margin: 0; }
  .nr-chip input { position: absolute; opacity: 0; pointer-events: none; }
  .nr-chip span {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.8rem;
    border-radius: 999px;
    border: 1px solid rgba(67, 89, 113, 0.2);
    background: #fff;
    color: #566a7f;
    font-size: 0.875rem;
    cursor: pointer;
    user-select: none;
    transition: all .15s ease;
  }
  .nr-chip input:checked + span {
    border-color: rgba(105, 108, 255, 0.55);
    background: rgba(105, 108, 255, 0.1);
    color: #696cff;
    font-weight: 600;
  }
  .nr-summary { display: flex; flex-wrap: wrap; gap: 0.4rem; margin-top: 0.45rem; }
  .nr-summary .badge { font-weight: 500; }
  .nr-footer {
    display: flex;
    justify-content: flex-end;
    padding-top: 1rem;
    margin-top: 0.25rem;
    border-top: 1px dashed rgba(67, 89, 113, 0.15);
  }
  .nr-hint { font-size: 0.8125rem; color: #a1acb8; margin-top: 0.35rem; }
  .nr-user-box {
    border: 1px solid #d9dee3;
    border-radius: 0.5rem;
    overflow: hidden;
    background: #fff;
  }
  .nr-user-box-search {
    border: 0;
    border-bottom: 1px solid #d9dee3;
    border-radius: 0;
    box-shadow: none !important;
  }
  .nr-user-list {
    max-height: 200px;
    overflow-y: auto;
    padding: 0.35rem;
  }
  .nr-user-item {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.55rem 0.65rem;
    border-radius: 0.375rem;
    margin: 0;
    cursor: pointer;
  }
  .nr-user-item:hover { background: rgba(105, 108, 255, 0.06); }
  .nr-user-item .form-check-input { margin-top: 0.2rem; flex-shrink: 0; }
  .nr-user-meta { min-width: 0; }
  .nr-user-meta .name { font-weight: 500; line-height: 1.25; }
  .nr-user-meta .email { font-size: 0.8125rem; color: #a1acb8; word-break: break-all; }
  .nr-user-empty { padding: 1rem; text-align: center; color: #a1acb8; font-size: 0.875rem; }
  .nr-panel {
    border: 1px solid rgba(67, 89, 113, 0.12);
    border-radius: 0.65rem;
    padding: 0.85rem;
    height: 100%;
    background: rgba(67, 89, 113, 0.02);
  }
  .nr-panel-title {
    font-size: 0.875rem;
    font-weight: 600;
    margin-bottom: 0.15rem;
    display: flex;
    align-items: center;
    gap: 0.4rem;
  }
  .nr-panel-desc {
    font-size: 0.75rem;
    color: #a1acb8;
    margin-bottom: 0.65rem;
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="nr-page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h4 class="mb-1">{{ __('Notification rules') }}</h4>
            <p class="text-muted mb-0">
                {{ __('Choose who gets in-app alerts, email, or both. Delivery channels follow the panels you fill.') }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('settings-panel.notification-preferences.edit') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-adjustments-alt me-1"></i>{{ __('My notifications') }}
            </a>
            <a href="{{ route('settings-panel.email-accounts.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-mail me-1"></i>{{ __('Email accounts') }}
            </a>
            <a href="{{ route('settings-panel.notification-logs.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-list-details me-1"></i>{{ __('Notification logs') }}
            </a>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-bell me-1"></i>{{ __('Open inbox') }}
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success d-flex align-items-center" role="alert">
            <i class="ti ti-check me-2"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if($errors->any())
        <div class="alert alert-danger">
            <div class="fw-medium mb-1">{{ __('Could not save some settings') }}</div>
            <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    @if($availableChannels === [])
        <div class="alert alert-warning d-flex align-items-start gap-2">
            <i class="ti ti-alert-triangle ti-md mt-1"></i>
            <div>{{ __('No notification channels are enabled for this company. Contact the platform administrator.') }}</div>
        </div>
    @endif

    @forelse($typesByModule as $moduleKey => $moduleTypes)
        @php
            $moduleRuleCount = 0;
            $moduleEnabledCount = 0;
            foreach ($moduleTypes as $tk => $td) {
                $r = $rulesByKey->get($tk);
                if (!$r) continue;
                $moduleRuleCount++;
                if ($r->enabled) $moduleEnabledCount++;
            }
            $collapseId = 'nr-module-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $moduleKey);
            $isFirstModule = $loop->first;
        @endphp
        <div class="card nr-module-card mb-3">
            <button type="button"
                    class="nr-module-toggle"
                    data-bs-toggle="collapse"
                    data-bs-target="#{{ $collapseId }}"
                    aria-expanded="{{ $isFirstModule ? 'true' : 'false' }}"
                    aria-controls="{{ $collapseId }}">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <span class="avatar avatar-sm rounded bg-label-primary flex-shrink-0">
                        <i class="ti ti-folder ti-sm"></i>
                    </span>
                    <div class="min-w-0">
                        <h5 class="mb-0 text-truncate">{{ __($registry->moduleLabel($moduleKey)) }}</h5>
                        <div class="text-muted small">
                            {{ $moduleRuleCount }} {{ __('rules') }}
                            · {{ $moduleEnabledCount }} {{ __('enabled') }}
                        </div>
                    </div>
                </div>
                <i class="ti ti-chevron-down ti-md nr-chevron"></i>
            </button>

            <div id="{{ $collapseId }}" class="collapse {{ $isFirstModule ? 'show' : '' }}">
                <div class="card-body">
                    <div class="d-flex flex-column gap-3">
                        @foreach($moduleTypes as $typeKey => $typeDef)
                            @php
                                $rule = $rulesByKey->get($typeKey);
                                if (!$rule) { continue; }
                                $config = is_array($rule->config) ? $rule->config : ($typeDef['default_config'] ?? []);
                                $daysBefore = $config['days_before'] ?? [3];
                                $ruleStatuses = array_values(array_map('strval', $config['statuses'] ?? ($typeDef['default_config']['statuses'] ?? [])));
                                $statusOptions = array_values(array_map('strval', $typeDef['status_options'] ?? []));
                                $recipientConfig = \App\Services\Notifications\RecipientBuckets::normalize(
                                    is_array($rule->recipient_config) ? $rule->recipient_config : []
                                );
                                $bothIds = $recipientConfig['both_user_ids'];
                                $inAppOnlyIds = $recipientConfig['in_app_only_user_ids'];
                                $emailOnlyIds = $recipientConfig['email_only_user_ids'];
                                $externalEmails = $recipientConfig['emails'];
                                $moduleUsers = $usersByModule[$moduleKey] ?? collect();
                                $selectedAccountId = array_key_exists('email_account_id', $config)
                                    ? (int) $config['email_account_id']
                                    : null;
                                $trigger = $typeDef['trigger'] ?? 'event';
                                $selectedAccount = $selectedAccountId
                                    ? $emailAccounts->firstWhere('id', $selectedAccountId)
                                    : null;
                                if ($selectedAccountId === null) {
                                    $fromSummary = __('Not set');
                                } elseif ($selectedAccountId === 0) {
                                    $fromSummary = __('System default') . ($defaultFromEmail ? ' ('.$defaultFromEmail.')' : '');
                                } elseif ($selectedAccount) {
                                    $accName = trim((string) ($selectedAccount->display_name ?? ''));
                                    $accEmail = trim((string) ($selectedAccount->email ?? ''));
                                    $fromSummary = $accName !== '' ? $accName.' ('.$accEmail.')' : $accEmail;
                                } else {
                                    $fromSummary = __('Not set');
                                }
                                $bothCount = count($bothIds);
                                $inAppOnlyCount = count($inAppOnlyIds);
                                $emailOnlyCount = count($emailOnlyIds) + count($externalEmails);
                            @endphp
                            <div class="nr-rule {{ $rule->enabled ? '' : 'is-disabled' }}" data-rule-card="{{ $rule->id }}">
                                <form method="post" action="{{ route('settings-panel.notification-rules.update', $rule) }}">
                                    @csrf
                                    @method('PUT')

                                    <div class="nr-rule-head">
                                        <div class="min-w-0">
                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                                <h6 class="mb-0">{{ __($typeDef['label'] ?? $typeKey) }}</h6>
                                                <span class="badge {{ $triggerBadge[$trigger] ?? 'bg-label-secondary' }}">
                                                    {{ $triggerLabels[$trigger] ?? ucfirst($trigger) }}
                                                </span>
                                                <span class="badge {{ $rule->enabled ? 'bg-label-success' : 'bg-label-secondary' }}" data-enabled-badge>
                                                    {{ $rule->enabled ? __('On') : __('Off') }}
                                                </span>
                                            </div>
                                            <div class="nr-summary">
                                                @if(($typeDef['trigger'] ?? '') === 'scheduled' && !empty($daysBefore))
                                                    <span class="badge bg-label-secondary">
                                                        <i class="ti ti-calendar-event me-1"></i>
                                                        {{ __('Days') }}:
                                                        {{ collect($daysBefore)->map(fn ($d) => (int) $d === 0 ? __('Today') : $d)->implode(', ') }}
                                                    </span>
                                                @endif
                                                @if($statusOptions !== [] && $ruleStatuses !== [])
                                                    <span class="badge bg-label-secondary text-truncate" style="max-width: 14rem;" title="{{ implode(', ', $ruleStatuses) }}">
                                                        <i class="ti ti-flag me-1"></i>{{ implode(', ', $ruleStatuses) }}
                                                    </span>
                                                @endif
                                                @if($bothCount)
                                                    <span class="badge bg-label-primary">
                                                        <i class="ti ti-bell me-1"></i><i class="ti ti-mail me-1"></i>{{ $bothCount }}
                                                    </span>
                                                @endif
                                                @if($inAppOnlyCount)
                                                    <span class="badge bg-label-info">
                                                        <i class="ti ti-bell me-1"></i>{{ $inAppOnlyCount }}
                                                    </span>
                                                @endif
                                                @if($emailOnlyCount)
                                                    <span class="badge bg-label-warning">
                                                        <i class="ti ti-mail me-1"></i>{{ $emailOnlyCount }}
                                                    </span>
                                                @endif
                                                @if($emailEntitled)
                                                    <span class="badge bg-label-secondary text-truncate" style="max-width: 16rem;" title="{{ $fromSummary }}">
                                                        <i class="ti ti-send me-1"></i>{{ $fromSummary }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="form-check form-switch mb-0">
                                            <input class="form-check-input" type="checkbox" role="switch"
                                                   id="enabled_{{ $rule->id }}" name="enabled" value="1"
                                                   @checked($rule->enabled)
                                                   data-rule-enable="{{ $rule->id }}">
                                            <label class="form-check-label" for="enabled_{{ $rule->id }}">{{ __('Enabled') }}</label>
                                        </div>
                                    </div>

                                    <div class="nr-rule-body">
                                        <div class="row g-4">
                                            @if(($typeDef['trigger'] ?? '') === 'scheduled')
                                                <div class="col-12">
                                                    <div class="nr-section-title">{{ __('Remind before due') }}</div>
                                                    <div class="nr-chip-group">
                                                        @foreach([0, 1, 3, 5, 7, 14, 30] as $day)
                                                            <label class="nr-chip">
                                                                <input type="checkbox" name="days_before[]" value="{{ $day }}"
                                                                       id="day_{{ $rule->id }}_{{ $day }}"
                                                                       @checked(in_array($day, array_map('intval', (array) $daysBefore), true))>
                                                                <span>
                                                                    @if($day === 0)
                                                                        {{ __('Today') }}
                                                                    @else
                                                                        {{ $day }} {{ $day === 1 ? __('day') : __('days') }}
                                                                    @endif
                                                                </span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endif

                                            @if($statusOptions !== [])
                                                <div class="col-12">
                                                    <div class="nr-section-title">{{ __('Cheque statuses') }}</div>
                                                    <div class="nr-chip-group">
                                                        @foreach($statusOptions as $status)
                                                            <label class="nr-chip">
                                                                <input type="checkbox" name="statuses[]" value="{{ $status }}"
                                                                       id="status_{{ $rule->id }}_{{ md5($status) }}"
                                                                       @checked(in_array($status, $ruleStatuses, true))>
                                                                <span>{{ $status }}</span>
                                                            </label>
                                                        @endforeach
                                                    </div>
                                                    <div class="nr-hint">{{ __('Only cheques in these statuses are considered.') }}</div>
                                                </div>
                                            @endif

                                            @if($emailEntitled)
                                                <div class="col-12 col-lg-6">
                                                    <div class="nr-section-title">{{ __('Send email from') }}</div>
                                                    <select class="form-select" name="email_account_id" id="email_account_{{ $rule->id }}">
                                                        <option value="" @selected($selectedAccountId === null)>
                                                            {{ __('Select sender email…') }}
                                                        </option>
                                                        <option value="0" @selected($selectedAccountId === 0)>
                                                            {{ __('System default') }}@if($defaultFromEmail) ({{ $defaultFromEmail }})@endif
                                                        </option>
                                                        @foreach($emailAccounts as $account)
                                                            @php
                                                                $accountName = trim((string) ($account->display_name ?? ''));
                                                                $accountEmail = trim((string) ($account->email ?? ''));
                                                                $accountLabel = $accountName !== ''
                                                                    ? $accountName.' ('.$accountEmail.')'
                                                                    : $accountEmail;
                                                            @endphp
                                                            <option value="{{ $account->id }}" @selected($selectedAccountId !== null && $selectedAccountId === (int) $account->id)>
                                                                {{ $accountLabel }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    <div class="nr-hint">
                                                        {{ __('Used for Email and In-app & email recipients. System default uses MAIL_FROM_ADDRESS.') }}
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="col-12">
                                                <div class="nr-section-title">{{ __('Recipients') }}</div>
                                                <div class="nr-hint mb-2">
                                                    {{ __('A user can only be in one panel. At least one recipient is required to enable this rule.') }}
                                                </div>
                                                <div class="row g-3" data-recipient-panels>
                                                    @if($inAppEntitled && $emailEntitled)
                                                        <div class="col-md-4">
                                                            <div class="nr-panel">
                                                                <div class="nr-panel-title">
                                                                    <i class="ti ti-bell"></i><i class="ti ti-mail"></i>
                                                                    {{ __('In-app & email') }}
                                                                </div>
                                                                <div class="nr-panel-desc">{{ __('Inbox alert plus email') }}</div>
                                                                @include('settings.notification_rules._user_picker', [
                                                                    'ruleId' => $rule->id,
                                                                    'panel' => 'both',
                                                                    'inputName' => 'both_user_ids[]',
                                                                    'moduleUsers' => $moduleUsers,
                                                                    'selectedIds' => $bothIds,
                                                                ])
                                                            </div>
                                                        </div>
                                                    @elseif($inAppEntitled)
                                                        <div class="col-md-6">
                                                            <div class="nr-panel">
                                                                <div class="nr-panel-title"><i class="ti ti-bell"></i> {{ __('In-app') }}</div>
                                                                <div class="nr-panel-desc">{{ __('Inbox alert only') }}</div>
                                                                @include('settings.notification_rules._user_picker', [
                                                                    'ruleId' => $rule->id,
                                                                    'panel' => 'both',
                                                                    'inputName' => 'both_user_ids[]',
                                                                    'moduleUsers' => $moduleUsers,
                                                                    'selectedIds' => $bothIds,
                                                                ])
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if($inAppEntitled && $emailEntitled)
                                                        <div class="col-md-4">
                                                            <div class="nr-panel">
                                                                <div class="nr-panel-title"><i class="ti ti-bell"></i> {{ __('In-app only') }}</div>
                                                                <div class="nr-panel-desc">{{ __('No email') }}</div>
                                                                @include('settings.notification_rules._user_picker', [
                                                                    'ruleId' => $rule->id,
                                                                    'panel' => 'in_app',
                                                                    'inputName' => 'in_app_only_user_ids[]',
                                                                    'moduleUsers' => $moduleUsers,
                                                                    'selectedIds' => $inAppOnlyIds,
                                                                ])
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if($emailEntitled)
                                                        <div class="{{ ($inAppEntitled && $emailEntitled) ? 'col-md-4' : 'col-md-6' }}">
                                                            <div class="nr-panel">
                                                                <div class="nr-panel-title"><i class="ti ti-mail"></i> {{ __('Email only') }}</div>
                                                                <div class="nr-panel-desc">{{ __('Users and external addresses — no inbox') }}</div>
                                                                @include('settings.notification_rules._user_picker', [
                                                                    'ruleId' => $rule->id,
                                                                    'panel' => 'email',
                                                                    'inputName' => 'email_only_user_ids[]',
                                                                    'moduleUsers' => $moduleUsers,
                                                                    'selectedIds' => $emailOnlyIds,
                                                                ])
                                                                <label class="form-label small mt-2 mb-1" for="emails_text_{{ $rule->id }}">{{ __('Extra emails') }}</label>
                                                                <textarea class="form-control form-control-sm"
                                                                          name="emails_text"
                                                                          id="emails_text_{{ $rule->id }}"
                                                                          rows="2"
                                                                          placeholder="{{ __('one@example.com two@example.com') }}">{{ implode(' ', $externalEmails) }}</textarea>
                                                                <div class="nr-hint">{{ __('Separate addresses with spaces only.') }}</div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <div class="nr-footer">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="ti ti-device-floppy me-1"></i>{{ __('Save rule') }}
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                <i class="ti ti-bell-off ti-lg mb-2 d-block"></i>
                {{ __('No notification types are registered yet.') }}
            </div>
        </div>
    @endforelse
</div>

<script>
(function () {
  function initUserSearch(root) {
    root.querySelectorAll('[data-user-picker]').forEach(function (box) {
      var input = box.querySelector('[data-user-search]');
      var items = box.querySelectorAll('[data-user-label]');
      if (!input) return;
      input.addEventListener('input', function () {
        var q = (input.value || '').toLowerCase().trim();
        items.forEach(function (item) {
          var match = !q || (item.getAttribute('data-user-label') || '').indexOf(q) !== -1;
          item.style.display = match ? '' : 'none';
        });
      });
    });
  }

  function initMutualExclusive(root) {
    root.querySelectorAll('[data-recipient-panels]').forEach(function (group) {
      group.addEventListener('change', function (e) {
        var target = e.target;
        if (!target || target.type !== 'checkbox' || !target.value) return;
        if (!target.checked) return;
        var uid = String(target.value);
        group.querySelectorAll('input[type="checkbox"]').forEach(function (cb) {
          if (cb !== target && String(cb.value) === uid) {
            cb.checked = false;
          }
        });
      });
    });
  }

  function initEnableToggles(root) {
    root.querySelectorAll('[data-rule-enable]').forEach(function (toggle) {
      toggle.addEventListener('change', function () {
        var card = root.querySelector('[data-rule-card="' + toggle.getAttribute('data-rule-enable') + '"]');
        if (!card) return;
        card.classList.toggle('is-disabled', !toggle.checked);
        var badge = card.querySelector('[data-enabled-badge]');
        if (badge) {
          badge.textContent = toggle.checked ? @json(__('On')) : @json(__('Off'));
          badge.className = 'badge ' + (toggle.checked ? 'bg-label-success' : 'bg-label-secondary');
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    initUserSearch(document);
    initMutualExclusive(document);
    initEnableToggles(document);
  });
})();
</script>
@endsection
