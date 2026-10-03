@extends($layout ?? 'layouts.app')

@section('title', __('Notifications'))

@section('content')
<style>
  .ni-shell { max-width: 860px; }
  .ni-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.25rem;
  }
  .ni-hero-actions {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
  }
  .ni-hero-actions > form {
    margin: 0;
    display: inline-flex;
  }
  .ni-hero-actions .btn {
    min-height: 2rem;
    display: inline-flex;
    align-items: center;
    line-height: 1.25;
  }
  .ni-filters {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }
  .ni-pill {
    border: 1px solid rgba(67, 89, 113, 0.16);
    background: #fff;
    color: #566a7f;
    border-radius: 999px;
    padding: 0.4rem 0.9rem;
    font-size: 0.8125rem;
    font-weight: 600;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
  }
  .ni-pill:hover { border-color: rgba(105, 108, 255, 0.4); color: #696cff; }
  .ni-pill.is-active {
    border-color: rgba(105, 108, 255, 0.55);
    background: rgba(105, 108, 255, 0.1);
    color: #696cff;
  }
  .ni-sev {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
    margin-bottom: 1rem;
  }
  .ni-card {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 0.15rem 0.65rem rgba(34, 48, 62, 0.08);
    overflow: hidden;
    background: var(--bs-body-bg, #fff);
  }
  .ni-item {
    display: grid;
    grid-template-columns: 0.35rem minmax(0, 1fr);
    border-bottom: 1px solid rgba(67, 89, 113, 0.07);
  }
  .ni-item:last-child { border-bottom: 0; }
  .ni-rail { background: #d9dee3; }
  .ni-item.is-info .ni-rail { background: #8592a3; }
  .ni-item.is-warning .ni-rail { background: #ffab00; }
  .ni-item.is-critical .ni-rail { background: #ff3e1d; }
  .ni-item.is-unread { background: rgba(105, 108, 255, 0.04); }
  .ni-body { padding: 1.05rem 1.15rem; }
  .ni-top {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.5rem 1rem;
    margin-bottom: 0.35rem;
  }
  .ni-title {
    font-weight: 650;
    margin: 0;
    line-height: 1.35;
    overflow-wrap: anywhere;
  }
  .ni-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
    align-items: center;
  }
  .ni-text {
    color: #697a8d;
    font-size: 0.875rem;
    margin: 0 0 0.75rem;
    white-space: normal;
    overflow-wrap: anywhere;
  }
  .ni-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; }
  .ni-empty {
    padding: 2.75rem 1.5rem;
    text-align: center;
    color: #a1acb8;
  }
</style>

@php
    $filter = request('filter', '');
    $severity = request('severity', '');
    $baseQuery = array_filter([
        'severity' => $severity !== '' ? $severity : null,
    ]);
@endphp

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="ni-shell mx-auto">
        <div class="ni-hero">
            <div>
                <h4>{{ __('Notifications') }}</h4>
                <p>{{ __('Your inbox for alerts across modules you work in.') }}</p>
            </div>
            <div class="ni-hero-actions">
                <a href="{{ route('settings-panel.notification-preferences.edit') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="ti ti-adjustments-alt me-1"></i>{{ __('Preferences') }}
                </a>
                <form action="{{ route('notifications.read-all') }}" method="post">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-primary">
                        <i class="ti ti-checks me-1"></i>{{ __('Mark all as read') }}
                    </button>
                </form>
            </div>
        </div>

        <div class="ni-filters">
            <a class="ni-pill {{ $filter === '' ? 'is-active' : '' }}"
               href="{{ route('notifications.index', $baseQuery) }}">{{ __('All') }}</a>
            <a class="ni-pill {{ $filter === 'unread' ? 'is-active' : '' }}"
               href="{{ route('notifications.index', array_merge($baseQuery, ['filter' => 'unread'])) }}">{{ __('Unread') }}</a>
            <a class="ni-pill {{ $filter === 'read' ? 'is-active' : '' }}"
               href="{{ route('notifications.index', array_merge($baseQuery, ['filter' => 'read'])) }}">{{ __('Read') }}</a>
            <a class="ni-pill {{ $filter === 'dismissed' ? 'is-active' : '' }}"
               href="{{ route('notifications.index', array_merge($baseQuery, ['filter' => 'dismissed'])) }}">{{ __('Dismissed') }}</a>
        </div>

        <div class="ni-sev">
            @php $sevBase = array_filter(['filter' => $filter !== '' ? $filter : null]); @endphp
            <a class="ni-pill {{ $severity === '' ? 'is-active' : '' }}"
               href="{{ route('notifications.index', $sevBase) }}">{{ __('Any severity') }}</a>
            @foreach(['info' => __('Info'), 'warning' => __('Warning'), 'critical' => __('Critical')] as $sevKey => $sevLabel)
                <a class="ni-pill {{ $severity === $sevKey ? 'is-active' : '' }}"
                   href="{{ route('notifications.index', array_merge($sevBase, ['severity' => $sevKey])) }}">{{ $sevLabel }}</a>
            @endforeach
        </div>

        <div class="ni-card">
            @forelse($notifications as $notification)
                @php
                    $data = is_array($notification->data) ? $notification->data : [];
                    $actionMode = $data['action_mode'] ?? (is_string($notification->type) && str_starts_with($notification->type, 'cheques.') ? 'modal' : 'navigate');
                    $actionUrl = $notification->action_url
                        ? (str_starts_with((string) $notification->action_url, 'http') ? $notification->action_url : url($notification->action_url))
                        : null;
                    $actionSize = $data['action_size'] ?? 'xl';
                    $actionTitle = $data['action_title'] ?? ($notification->title ?: __('Details'));
                    $sev = $notification->severity ?? 'info';
                    $typeLabel = $typeLabels[$notification->type] ?? null;
                    $unread = $notification->isUnread();
                @endphp
                <div class="ni-item is-{{ $sev }} {{ $unread ? 'is-unread' : '' }}">
                    <div class="ni-rail"></div>
                    <div class="ni-body">
                        <div class="ni-top">
                            <h6 class="ni-title">{{ $notification->title }}</h6>
                            <div class="ni-meta">
                                @if($unread)
                                    <span class="badge bg-label-primary">{{ __('New') }}</span>
                                @endif
                                <span class="badge bg-label-{{ $sev === 'critical' ? 'danger' : ($sev === 'warning' ? 'warning' : 'secondary') }}">
                                    {{ ucfirst($sev) }}
                                </span>
                                @if($typeLabel)
                                    <span class="badge bg-label-secondary">{{ __($typeLabel) }}</span>
                                @endif
                                <span class="text-muted small">{{ $notification->created_at?->diffForHumans() }}</span>
                            </div>
                        </div>
                        @if($notification->body)
                            <p class="ni-text">{{ $notification->body }}</p>
                        @endif
                        <div class="ni-actions">
                            @if($actionUrl && $actionMode === 'modal')
                                <button type="button"
                                        class="btn btn-sm btn-primary js-notification-open-modal"
                                        data-read-url="{{ route('notifications.read', $notification) }}"
                                        data-action-url="{{ $actionUrl }}"
                                        data-action-size="{{ $actionSize }}"
                                        data-action-title="{{ $actionTitle }}">
                                    <i class="ti ti-external-link me-1"></i>{{ __('Open') }}
                                </button>
                            @else
                                <form action="{{ route('notifications.read', $notification) }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-primary">
                                        @if($actionUrl)
                                            <i class="ti ti-external-link me-1"></i>{{ __('Open') }}
                                        @else
                                            {{ __('Mark read') }}
                                        @endif
                                    </button>
                                </form>
                            @endif
                            @if(request('filter') !== 'dismissed')
                                <form action="{{ route('notifications.dismiss', $notification) }}" method="post">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-secondary">{{ __('Dismiss') }}</button>
                                </form>
                            @else
                                <span class="badge bg-label-secondary align-self-center">{{ __('Dismissed') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="ni-empty">
                    <i class="ti ti-bell-off ti-lg d-block mb-2"></i>
                    {{ __('No notifications match this filter.') }}
                </div>
            @endforelse
        </div>

        @if($notifications->hasPages())
            <div class="mt-3">{{ $notifications->links() }}</div>
        @endif
    </div>
</div>
@endsection

@section('page-script')
<script>
(function () {
  var csrf = @json(csrf_token());
  document.querySelectorAll('.js-notification-open-modal').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var readUrl = btn.getAttribute('data-read-url');
      var action = btn.getAttribute('data-action-url');
      var size = btn.getAttribute('data-action-size') || 'xl';
      var title = btn.getAttribute('data-action-title') || 'Details';

      fetch(readUrl, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrf,
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin'
      }).finally(function () {
        if (!action || !window.jQuery) {
          if (action) window.location.href = action;
          return;
        }
        var $el = window.jQuery('<a href="javascript:void(0);" class="show-modal d-none"></a>');
        $el.attr('data-action', action);
        $el.attr('data-size', size);
        $el.attr('data-title', title);
        window.jQuery('body').append($el);
        $el.trigger('click');
        $el.remove();
      });
    });
  });
})();
</script>
@endsection
