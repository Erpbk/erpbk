@php
$activeActivitiesTab = $activeActivitiesTab
?? ($isLiveTab ?? false
? 'live'
: (($isAllTab ?? false) ? 'summary' : 'activities'));
@endphp

<style>
  .rider-activities-tabs .nav-link {
    color: #6b7280;
    font-weight: 600;
  }

  .rider-activities-tabs .nav-link.active {
    color: #0d6efd;
    border-bottom-color: #0d6efd;
  }

  .rider-activities-ops {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 8px;
    margin-bottom: 12px;
  }

  .rider-activities-ops .action-dropdown-container {
    position: relative;
    display: inline-block;
  }

  .rider-activities-ops .action-dropdown-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #0d6efd;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 8px 14px;
    font-weight: 600;
  }

  .rider-activities-ops .action-dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    top: calc(100% + 6px);
    min-width: 280px;
    background: #fff;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
    z-index: 1050;
    padding: 6px 0;
  }

  .rider-activities-ops .action-dropdown-menu.show {
    display: block;
  }

  .rider-activities-ops .action-dropdown-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 10px 14px;
    color: #111827;
    text-decoration: none;
  }

  .rider-activities-ops .action-dropdown-item:hover {
    background: #f3f4f6;
  }

  .rider-activities-ops .action-dropdown-item i {
    color: #2563eb;
    margin-top: 2px;
  }

  .rider-activities-ops .action-dropdown-item-text {
    font-weight: 600;
  }

  .rider-activities-ops .action-dropdown-item-desc {
    font-size: 12px;
    color: #6b7280;
  }
</style>

<div class="content mb-2">
  <ul class="nav nav-tabs rider-activities-tabs" role="tablist">
    @can('riders_activities_view')
    <li class="nav-item" role="presentation">
      <a class="nav-link {{ $activeActivitiesTab === 'activities' ? 'active' : '' }}" href="{{ route('riderActivities.index') }}">
        Rider Activities
      </a>
    </li>
    @endcan
    @can('riders_activities_view')
    <li class="nav-item" role="presentation">
      <a class="nav-link {{ $activeActivitiesTab === 'summary' ? 'active' : '' }}" href="{{ route('riderActivities.index', ['tab' => 'all']) }}">
        Rider Summary
      </a>
    </li>
    @endcan
    @can('riders_live_activities_view')
    <li class="nav-item" role="presentation">
      <a class="nav-link {{ $activeActivitiesTab === 'live' ? 'active' : '' }}" href="{{ route('rider.liveactivities') }}">
        Live Activities
      </a>
    </li>
    @endcan
  </ul>
</div>

<div class="content rider-activities-ops">
  <div class="action-dropdown-container">
    <button type="button" class="action-dropdown-btn" id="riderActivitiesOpsBtn">
      <i class="ti ti-plus"></i>
      <span>Operations</span>
      <i class="ti ti-chevron-down"></i>
    </button>
    <div class="action-dropdown-menu" id="riderActivitiesOpsMenu">
      <a class="action-dropdown-item" href="{{ route('rider.activities_import_page') }}">
        <i class="ti ti-activity"></i>
        <div>
          <div class="action-dropdown-item-text">Import Rider Activities</div>
          <div class="action-dropdown-item-desc">Import rider activity data from file</div>
        </div>
      </a>
      <a class="action-dropdown-item" href="{{ route('rider.activities_import_errors') }}">
        <i class="fa fa-exclamation-triangle"></i>
        <div>
          <div class="action-dropdown-item-text">Rider Import Errors</div>
          <div class="action-dropdown-item-desc">View last rider activities import errors</div>
        </div>
      </a>
      <a class="action-dropdown-item" href="{{ route('rider.live_activities_import_page') }}">
        <i class="ti ti-live-photo"></i>
        <div>
          <div class="action-dropdown-item-text">Import Live Activities</div>
          <div class="action-dropdown-item-desc">Import live activity data from file</div>
        </div>
      </a>
      <a class="action-dropdown-item" href="{{ route('rider.live_activities_import_errors') }}">
        <i class="fa fa-exclamation-triangle"></i>
        <div>
          <div class="action-dropdown-item-text">Live Import Errors</div>
          <div class="action-dropdown-item-desc">View last live activities import errors</div>
        </div>
      </a>
    </div>
  </div>
  <a class="btn btn-primary openFilterSidebar" href="javascript:void(0);" title="Open Filters">
    <i class="fa fa-search"></i>
  </a>
</div>