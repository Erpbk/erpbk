@extends($layout ?? 'layouts.app')

@section('title', __('My notifications'))

@section('content')
<style>
  .np-shell { max-width: 920px; }
  .np-hero {
    display: flex;
    flex-wrap: wrap;
    align-items: flex-start;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  .np-hero h4 { margin-bottom: 0.35rem; }
  .np-hero p { color: #697a8d; margin: 0; max-width: 36rem; }
  .np-module {
    border: 0;
    border-radius: 1rem;
    box-shadow: 0 0.15rem 0.65rem rgba(34, 48, 62, 0.08);
    overflow: hidden;
    margin-bottom: 1rem;
    background: var(--bs-body-bg, #fff);
  }
  .np-module-head {
    width: 100%;
    padding: 1rem 1.25rem;
    border: 0;
    border-bottom: 1px solid rgba(67, 89, 113, 0.08);
    background: linear-gradient(180deg, rgba(105, 108, 255, 0.06), transparent);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    text-align: left;
    color: inherit;
  }
  .np-module-head:hover { background: rgba(105, 108, 255, 0.08); }
  .np-module-head .np-module-head-main {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
  }
  .np-module-head .avatar {
    width: 2.25rem;
    height: 2.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }
  .np-module-head .np-chevron {
    color: #697a8d;
    transition: transform .2s ease;
    flex-shrink: 0;
  }
  .np-module-head[aria-expanded="false"] .np-chevron { transform: rotate(-90deg); }
  .np-module-head[aria-expanded="false"] { border-bottom-color: transparent; }
  .np-type {
    padding: 1.1rem 1.25rem;
    border-bottom: 1px solid rgba(67, 89, 113, 0.06);
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto;
    gap: 1rem;
    align-items: center;
  }
  .np-type:last-child { border-bottom: 0; }
  .np-type-title { font-weight: 600; margin-bottom: 0.2rem; }
  .np-type-meta { display: flex; flex-wrap: wrap; gap: 0.35rem; }
  .np-channels { display: flex; flex-wrap: wrap; gap: 0.55rem; justify-content: flex-end; }
  .np-channel {
    min-width: 7.5rem;
    border: 1px solid rgba(67, 89, 113, 0.14);
    border-radius: 0.85rem;
    padding: 0.65rem 0.8rem;
    background: rgba(67, 89, 113, 0.02);
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.65rem;
  }
  .np-channel.is-on {
    border-color: rgba(105, 108, 255, 0.45);
    background: rgba(105, 108, 255, 0.08);
  }
  .np-channel-label {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #566a7f;
  }
  .np-channel.is-on .np-channel-label { color: #696cff; }
  .np-footer {
    position: sticky;
    bottom: 0.75rem;
    display: flex;
    justify-content: flex-end;
    margin-top: 1rem;
    z-index: 2;
  }
  .np-footer .btn {
    box-shadow: 0 0.35rem 1rem rgba(105, 108, 255, 0.28);
  }
  .np-empty {
    border-radius: 1rem;
    border: 1px dashed rgba(67, 89, 113, 0.2);
    padding: 2.5rem 1.5rem;
    text-align: center;
    color: #a1acb8;
  }
  @media (max-width: 767.98px) {
    .np-type { grid-template-columns: 1fr; }
    .np-channels { justify-content: flex-start; }
  }
</style>

<div class="container-xxl flex-grow-1 container-p-y">
    <div class="np-shell mx-auto">
        <div class="np-hero">
            <div>
                <h4>{{ __('My notifications') }}</h4>
                <p>{{ __('Turn channels on or off for enabled rules in modules you can access. Company rules still decide who is listed as a recipient.') }}</p>
            </div>
            <a href="{{ route('notifications.index') }}" class="btn btn-sm btn-outline-primary">
                <i class="ti ti-bell me-1"></i>{{ __('Open inbox') }}
            </a>
        </div>

        @if(session('success'))
            <div class="alert alert-success d-flex align-items-center" role="alert">
                <i class="ti ti-check me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
        @endif

        @if($availableChannels === [])
            <div class="alert alert-warning">
                {{ __('No notification channels are enabled for this company.') }}
            </div>
        @elseif($typesByModule === [])
            <div class="np-empty">
                <i class="ti ti-bell-off ti-lg d-block mb-2"></i>
                {{ __('No enabled notification rules are available for your access level.') }}
            </div>
        @else
            <form method="post" action="{{ route('settings-panel.notification-preferences.update') }}">
                @csrf
                @method('PUT')

                @foreach($typesByModule as $moduleKey => $moduleTypes)
                    @php
                        $collapseId = 'np-module-' . preg_replace('/[^a-zA-Z0-9_-]/', '-', $moduleKey);
                        $isFirstModule = $loop->first;
                    @endphp
                    <div class="np-module">
                        <button type="button"
                                class="np-module-head"
                                data-bs-toggle="collapse"
                                data-bs-target="#{{ $collapseId }}"
                                aria-expanded="{{ $isFirstModule ? 'true' : 'false' }}"
                                aria-controls="{{ $collapseId }}">
                            <span class="np-module-head-main">
                                <span class="avatar rounded bg-label-primary">
                                    <i class="ti ti-folder"></i>
                                </span>
                                <span class="min-w-0">
                                    <span class="fw-semibold d-block text-truncate">{{ __($registry->moduleLabel($moduleKey)) }}</span>
                                    <span class="text-muted small">{{ count($moduleTypes) }} {{ __('notification types') }}</span>
                                </span>
                            </span>
                            <i class="ti ti-chevron-down ti-md np-chevron"></i>
                        </button>

                        <div id="{{ $collapseId }}" class="collapse {{ $isFirstModule ? 'show' : '' }}">
                            @foreach($moduleTypes as $typeKey => $typeDef)
                                @php
                                    $trigger = (string) ($typeDef['trigger'] ?? 'event');
                                @endphp
                                <div class="np-type">
                                    <div class="min-w-0">
                                        <div class="np-type-title">{{ __($typeDef['label'] ?? $typeKey) }}</div>
                                        <div class="np-type-meta">
                                            <span class="badge bg-label-secondary">{{ $triggerLabels[$trigger] ?? ucfirst($trigger) }}</span>
                                            @if(($typeDef['severity'] ?? null) === 'critical')
                                                <span class="badge bg-label-danger">{{ __('Critical') }}</span>
                                            @elseif(($typeDef['severity'] ?? null) === 'warning')
                                                <span class="badge bg-label-warning">{{ __('Warning') }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="np-channels">
                                        @foreach($availableChannels as $channel)
                                            @php $on = !empty($matrix[$typeKey][$channel]); @endphp
                                            <label class="np-channel {{ $on ? 'is-on' : '' }}" data-np-channel>
                                                <span class="np-channel-label">
                                                    <i class="ti {{ $channelIcons[$channel] ?? 'ti-bell' }}"></i>
                                                    {{ $channelLabels[$channel] ?? $channel }}
                                                </span>
                                                <input class="form-check-input m-0"
                                                       type="checkbox"
                                                       role="switch"
                                                       name="prefs[{{ $typeKey }}][{{ $channel }}]"
                                                       value="1"
                                                       @checked($on)
                                                       data-np-switch>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="np-footer">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-device-floppy me-1"></i>{{ __('Save preferences') }}
                    </button>
                </div>
            </form>
        @endif
    </div>
</div>
@endsection

@section('page-script')
<script>
(function () {
  document.querySelectorAll('[data-np-channel]').forEach(function (box) {
    var input = box.querySelector('[data-np-switch]');
    if (!input) return;
    var sync = function () {
      box.classList.toggle('is-on', !!input.checked);
    };
    input.addEventListener('change', sync);
    sync();
  });
})();
</script>
@endsection
