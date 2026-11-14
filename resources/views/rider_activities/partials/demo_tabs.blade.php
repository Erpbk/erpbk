@php
  $activeActivitiesTab = $activeActivitiesTab
    ?? ($isLiveTab ?? false
      ? 'live'
      : (($isAllTab ?? false) ? 'summary' : 'activities'));
@endphp

<div class="ra-tabs" role="tablist">
  @can('riders_activities_view')
  <a class="ra-tab {{ $activeActivitiesTab === 'activities' ? 'on' : '' }}" href="{{ route('riderActivities.index') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>
    Rider Activities
  </a>
  @endcan
  @can('riders_activities_view')
  <a class="ra-tab {{ $activeActivitiesTab === 'summary' ? 'on' : '' }}" href="{{ route('riderActivities.index', ['tab' => 'all']) }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h10M7 17h6"/></svg>
    Rider Summary
  </a>
  @endcan
  @can('riders_live_activities_view')
  <a class="ra-tab {{ $activeActivitiesTab === 'live' ? 'on' : '' }}" href="{{ route('rider.liveactivities') }}">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="3"/><path d="M5.6 5.6a9 9 0 0 0 0 12.8M18.4 5.6a9 9 0 0 1 0 12.8"/></svg>
    Live Activities
  </a>
  @endcan
</div>
