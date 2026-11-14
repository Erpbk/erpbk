@php
  $demoMode = $demoMode ?? 'activities';
  $alertsCount = $alertsCount ?? 0;
  $filterLabel = $demoMode === 'live'
    ? 'Filter Live'
    : ($demoMode === 'summary' ? 'Filter Summary' : 'Filter Activities');
  $alertsLabel = 'Send Alerts';
  $liveClock = now()->format('H:i:s');
@endphp

<div class="ra-tb">
  <div class="l">
    <div class="ra-dd">
      <button type="button" class="ra-btn" id="raOpsBtn" aria-haspopup="true" aria-expanded="false">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
        Operations
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
      </button>
      <div class="ra-menu" id="raOpsMenu" role="menu">
        @if($demoMode === 'live')
          <button type="button" role="menuitem" class="ra-menu-wa" id="raWaMenuBtn" title="WhatsApp and Email alerts (preview only)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.6 7.3L3 20l1.3-5.1A8.5 8.5 0 1 1 21 11.5z"/></svg>
            <span><strong>{{ $alertsLabel }}</strong><em>Preview WhatsApp / Email alerts</em></span>
          </button>
          <a role="menuitem" href="{{ route('rider.live_activities_import_page') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M5.6 5.6a9 9 0 0 0 0 12.8M18.4 5.6a9 9 0 0 1 0 12.8"/></svg>
            <span><strong>Import Live Activities</strong><em>Import live activity data from file</em></span>
          </a>
          <a role="menuitem" href="{{ route('rider.live_activities_import_errors') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>
            <span><strong>Live Import Errors</strong><em>View last live activities import errors</em></span>
          </a>
        @elseif($demoMode === 'summary')
          <a role="menuitem" href="{{ route('rider.activities_import_page') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            <span><strong>Import Rider Activities</strong><em>Import rider activity data from file</em></span>
          </a>
          <a role="menuitem" href="{{ route('rider.activities_import_errors') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>
            <span><strong>Rider Import Errors</strong><em>View last rider activities import errors</em></span>
          </a>
        @else
          <button type="button" role="menuitem" class="ra-menu-wa" id="raWaMenuBtn" title="WhatsApp and Email alerts (preview only)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.6 7.3L3 20l1.3-5.1A8.5 8.5 0 1 1 21 11.5z"/></svg>
            <span><strong>{{ $alertsLabel }}</strong><em>Preview WhatsApp / Email alerts</em></span>
          </button>
          <a role="menuitem" href="{{ route('rider.activities_import_page') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
            <span><strong>Import Rider Activities</strong><em>Import rider activity data from file</em></span>
          </a>
          <a role="menuitem" href="{{ route('rider.activities_import_errors') }}">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 3l9 16H3z"/><path d="M12 10v4M12 17h.01"/></svg>
            <span><strong>Rider Import Errors</strong><em>View last rider activities import errors</em></span>
          </a>
        @endif
      </div>
    </div>
    @include('rider_activities.partials.filter_chips')
    @if($demoMode === 'live')
      <span class="ra-chip ra-chip-today">Today</span>
      <span class="ra-livedot" id="raLiveClock" aria-live="polite">
        <i></i>Live <time id="raLiveClockTime">{{ $liveClock }}</time>
      </span>
    @elseif($demoMode === 'activities')
      <span class="ra-chip ra-chip-today">Yesterday</span>
    @endif
  </div>
  <div class="r">
    @if($demoMode !== 'summary')
    <button type="button" class="ra-btn wa" id="raWaBtn" title="WhatsApp and Email alerts (preview only)">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.4 8.4 0 0 1-12.6 7.3L3 20l1.3-5.1A8.5 8.5 0 1 1 21 11.5z"/></svg>
      {{ $alertsLabel }}
      <span class="cnt" id="raWaCnt">{{ number_format((int) $alertsCount) }}</span>
    </button>
    @endif
    <form method="GET" action="{{ request()->url() }}" id="raQuickSearchForm" class="d-inline">
      @foreach(request()->except(['quick_search', 'page', 'q']) as $qk => $qv)
        @if(is_array($qv))
          @foreach($qv as $qvv)
            <input type="hidden" name="{{ $qk }}[]" value="{{ $qvv }}">
          @endforeach
        @else
          <input type="hidden" name="{{ $qk }}" value="{{ $qv }}">
        @endif
      @endforeach
      <input class="ra-search" id="raQ" name="quick_search" type="search" placeholder="Quick Search..." value="{{ request('quick_search') }}" aria-label="Quick search">
    </form>
    <button type="button" class="ra-btn openFilterSidebar" id="raFilterBtn">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/></svg>
      {{ $filterLabel }}
    </button>
  </div>
</div>
