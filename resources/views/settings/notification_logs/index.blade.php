@extends($layout ?? 'layouts.app')

@section('title', __('Notification logs'))

@section('content')
@php
    $statusBadge = [
        'pending' => 'bg-label-warning',
        'sent' => 'bg-label-success',
        'failed' => 'bg-label-danger',
        'skipped' => 'bg-label-secondary',
    ];
@endphp

<style>
  .nl-page-header { margin-bottom: 1.5rem; }
  .nl-filters {
    border: 1px solid rgba(67, 89, 113, 0.12);
    border-radius: 0.75rem;
    padding: 1rem 1.25rem;
    background: var(--bs-body-bg, #fff);
    margin-bottom: 1rem;
  }
  .nl-stat {
    border: 1px solid rgba(67, 89, 113, 0.1);
    border-radius: 0.65rem;
    padding: 0.75rem 1rem;
    background: rgba(67, 89, 113, 0.02);
  }
  .nl-stat .label { font-size: 0.75rem; text-transform: uppercase; letter-spacing: .04em; color: #697a8d; }
  .nl-stat .value { font-size: 1.25rem; font-weight: 600; }
  .nl-table-card {
    border: 0;
    box-shadow: 0 0.125rem 0.5rem rgba(34, 48, 62, 0.08);
    overflow: hidden;
  }
  .nl-error { font-size: 0.8125rem; color: #a1acb8; max-width: 18rem; }
  .nl-row { cursor: pointer; }
  .nl-row:hover { background: rgba(105, 108, 255, 0.04); }
  .nl-detail dt { font-weight: 600; color: #697a8d; font-size: 0.75rem; text-transform: uppercase; letter-spacing: .03em; }
  .nl-detail dd { margin-bottom: 0.85rem; }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="nl-page-header d-flex flex-wrap align-items-start justify-content-between gap-3">
        <div>
            <h4 class="mb-1">{{ __('Notification logs') }}</h4>
            <p class="text-muted mb-0">
                {{ __('Delivery attempts for in-app and email notifications in this company.') }}
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="{{ route('settings-panel.notification-rules.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-bell-ringing me-1"></i>{{ __('Notification rules') }}
            </a>
            <a href="{{ route('settings-panel.email-accounts.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="ti ti-mail me-1"></i>{{ __('Email accounts') }}
            </a>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-bell me-1"></i>{{ __('Open inbox') }}
            </a>
        </div>
    </div>

    <div class="row g-3 mb-3">
        @foreach(['sent' => __('Sent'), 'failed' => __('Failed'), 'pending' => __('Pending'), 'skipped' => __('Skipped')] as $key => $label)
            <div class="col-6 col-md-3">
                <div class="nl-stat">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ (int) ($statusCounts[$key] ?? 0) }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form method="get" class="nl-filters">
        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label" for="nl_q">{{ __('Search') }}</label>
                <input type="search" class="form-control" id="nl_q" name="q"
                       value="{{ $filters['q'] }}"
                       placeholder="{{ __('Title, type, or email…') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="nl_channel">{{ __('Channel') }}</label>
                <select class="form-select" id="nl_channel" name="channel">
                    <option value="">{{ __('All channels') }}</option>
                    @foreach($channelLabels as $value => $label)
                        <option value="{{ $value }}" @selected($filters['channel'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="nl_status">{{ __('Status') }}</label>
                <select class="form-select" id="nl_status" name="status">
                    <option value="">{{ __('All statuses') }}</option>
                    @foreach($statusLabels as $value => $label)
                        <option value="{{ $value }}" @selected($filters['status'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">{{ __('Filter') }}</button>
                <a href="{{ route('settings-panel.notification-logs.index') }}" class="btn btn-outline-secondary">{{ __('Reset') }}</a>
            </div>
        </div>
    </form>

    <div class="card nl-table-card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead>
                    <tr>
                        <th>{{ __('When') }}</th>
                        <th>{{ __('Channel') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Recipient') }}</th>
                        <th>{{ __('Notification') }}</th>
                        <th>{{ __('Details') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                        @php
                            $notification = $log->notification;
                            $response = is_array($log->provider_response) ? $log->provider_response : [];
                            $error = $response['error'] ?? $response['reason'] ?? null;
                            $when = $log->sent_at ?? $log->last_attempt_at ?? $log->created_at;
                            $typeKey = $notification?->type;
                            $typeLabel = $typeKey ? ($typeLabels[$typeKey] ?? $typeKey) : null;
                            $recipient = \App\Http\Controllers\NotificationLogsSettingsController::recipientLabel($log);
                            $drawerPayload = [
                                'id' => $log->id,
                                'when' => optional($when)->format('d-M-Y H:i') ?? '—',
                                'created_at' => optional($log->created_at)->format('d-M-Y H:i') ?? '—',
                                'sent_at' => optional($log->sent_at)->format('d-M-Y H:i') ?? '—',
                                'last_attempt_at' => optional($log->last_attempt_at)->format('d-M-Y H:i') ?? '—',
                                'channel' => $channelLabels[$log->channel] ?? $log->channel,
                                'status' => $statusLabels[$log->status] ?? $log->status,
                                'status_class' => $statusBadge[$log->status] ?? 'bg-label-secondary',
                                'attempts' => (int) $log->attempts,
                                'recipient' => $recipient,
                                'email_only' => $log->user_notification_id === null,
                                'to_email' => $log->to_email,
                                'title' => $notification?->title,
                                'body' => $notification?->body,
                                'type_label' => $typeLabel,
                                'severity' => $notification?->severity,
                                'from' => $response['from'] ?? null,
                                'error' => $error,
                                'provider_response' => $response,
                                'action_url' => $notification?->action_url,
                                'source' => trim(($notification?->source_type ?? '').' '.($notification?->source_id ?? '')),
                            ];
                        @endphp
                        <tr class="nl-row" data-nl-detail='@json($drawerPayload)'>
                            <td class="text-nowrap">
                                {{ optional($when)->format('d-M-Y H:i') ?? '—' }}
                            </td>
                            <td>
                                <span class="badge bg-label-secondary">
                                    {{ $channelLabels[$log->channel] ?? $log->channel }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $statusBadge[$log->status] ?? 'bg-label-secondary' }}">
                                    {{ $statusLabels[$log->status] ?? $log->status }}
                                </span>
                                @if((int) $log->attempts > 1)
                                    <div class="text-muted small">{{ __('Attempts') }}: {{ $log->attempts }}</div>
                                @endif
                            </td>
                            <td>
                                <div class="text-break">{{ $recipient }}</div>
                                @if($log->user_notification_id === null)
                                    <div class="text-muted small">{{ __('Email only / external') }}</div>
                                @endif
                            </td>
                            <td>
                                @if($notification)
                                    <div class="fw-medium">{{ $notification->title }}</div>
                                    @if($typeLabel)
                                        <div class="text-muted small">{{ __($typeLabel) }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">{{ __('No inbox row') }}</span>
                                    @if($typeLabel)
                                        <div class="text-muted small">{{ __($typeLabel) }}</div>
                                    @endif
                                @endif
                            </td>
                            <td>
                                @if($error)
                                    <div class="nl-error text-truncate" title="{{ $error }}">{{ $error }}</div>
                                @elseif(!empty($response['from']))
                                    <div class="text-muted small">{{ __('From') }}: {{ $response['from'] }}</div>
                                @else
                                    <span class="text-muted">{{ __('View') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-muted py-5">
                                <i class="ti ti-list-details ti-lg d-block mb-2"></i>
                                {{ __('No notification deliveries yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="card-footer">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>

<div class="offcanvas offcanvas-end" tabindex="-1" id="nlDetailDrawer" aria-labelledby="nlDetailDrawerLabel">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="nlDetailDrawerLabel">{{ __('Delivery details') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="{{ __('Close') }}"></button>
    </div>
    <div class="offcanvas-body">
        <dl class="nl-detail mb-0">
            <dt>{{ __('When') }}</dt>
            <dd data-nl-field="when">—</dd>
            <dt>{{ __('Channel') }}</dt>
            <dd data-nl-field="channel">—</dd>
            <dt>{{ __('Status') }}</dt>
            <dd data-nl-field="status">—</dd>
            <dt>{{ __('Attempts') }}</dt>
            <dd data-nl-field="attempts">—</dd>
            <dt>{{ __('Recipient') }}</dt>
            <dd data-nl-field="recipient">—</dd>
            <dt>{{ __('Notification') }}</dt>
            <dd data-nl-field="title">—</dd>
            <dt>{{ __('Type') }}</dt>
            <dd data-nl-field="type_label">—</dd>
            <dt>{{ __('Body') }}</dt>
            <dd data-nl-field="body">—</dd>
            <dt>{{ __('From') }}</dt>
            <dd data-nl-field="from">—</dd>
            <dt>{{ __('Error') }}</dt>
            <dd data-nl-field="error">—</dd>
            <dt>{{ __('Created') }}</dt>
            <dd data-nl-field="created_at">—</dd>
            <dt>{{ __('Last attempt') }}</dt>
            <dd data-nl-field="last_attempt_at">—</dd>
            <dt>{{ __('Sent at') }}</dt>
            <dd data-nl-field="sent_at">—</dd>
            <dt>{{ __('Provider response') }}</dt>
            <dd><pre class="small bg-light p-2 rounded mb-0" data-nl-field="provider_response" style="white-space: pre-wrap; word-break: break-word;">—</pre></dd>
        </dl>
    </div>
</div>
@endsection

@section('page-script')
<script>
(function () {
  var drawerEl = document.getElementById('nlDetailDrawer');
  if (!drawerEl || !window.bootstrap) return;
  var drawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);

  function setField(name, value) {
    var el = drawerEl.querySelector('[data-nl-field="' + name + '"]');
    if (!el) return;
    if (name === 'status' && value && typeof value === 'object') {
      el.innerHTML = '<span class="badge ' + (value.class || 'bg-label-secondary') + '">' + (value.text || '—') + '</span>';
      return;
    }
    if (name === 'provider_response') {
      el.textContent = value && typeof value === 'object'
        ? JSON.stringify(value, null, 2)
        : (value || '—');
      return;
    }
    el.textContent = value && String(value).trim() !== '' ? value : '—';
  }

  document.querySelectorAll('[data-nl-detail]').forEach(function (row) {
    row.addEventListener('click', function () {
      var raw = row.getAttribute('data-nl-detail');
      if (!raw) return;
      var data;
      try { data = JSON.parse(raw); } catch (e) { return; }
      setField('when', data.when);
      setField('channel', data.channel);
      setField('status', { text: data.status, class: data.status_class });
      setField('attempts', data.attempts);
      setField('recipient', data.recipient + (data.email_only ? ' (email only / external)' : ''));
      setField('title', data.title);
      setField('type_label', data.type_label);
      setField('body', data.body);
      setField('from', data.from);
      setField('error', data.error);
      setField('created_at', data.created_at);
      setField('last_attempt_at', data.last_attempt_at);
      setField('sent_at', data.sent_at);
      setField('provider_response', data.provider_response || {});
      drawer.show();
    });
  });
})();
</script>
@endsection
