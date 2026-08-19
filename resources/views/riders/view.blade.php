@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Rider Profile')

@section('content')
<style>
  .myform .required:after {
    content: " *";
    color: red;
    font-weight: 200;
  }

  @media print {
    body .content {
      font-size: 18px !important;
    }
  }

  /* Status Cards Styling */
  .status-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    border-radius: 12px;
    padding: 16px;
    min-width: 180px;
    flex: 1;
    max-width: 220px;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
  }

  .status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  .status-card.active {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: #28a745;
    color: white;
  }

  .absconder-card.active {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    border-color: #dc3545;
  }

  .flowup-card.active {
    background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
    border-color: #007bff;
  }

  .llicense-card.active {
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    border-color: #17a2b8;
  }

  .status-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
  }

  .status-card:hover::before {
    left: 100%;
  }

  .status-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    margin-bottom: 12px;
    font-size: 20px;
  }

  .status-card.active .status-icon {
    background: rgba(255, 255, 255, 0.3);
  }

  .status-content {
    margin-bottom: 12px;
  }

  .status-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #495057;
  }

  .status-card.active .status-title {
    color: white;
  }

  .status-subtitle {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
  }

  .status-card.active .status-subtitle {
    color: rgba(255, 255, 255, 0.9);
  }

  .status-toggle {
    display: flex;
    align-items: center;
  }

  .status-checkbox {
    display: none;
  }

  .toggle-switch {
    position: relative;
    width: 50px;
    height: 24px;
    background: #ccc;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .toggle-slider {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }

  .status-checkbox:checked+.toggle-switch {
    background: #28a745;
  }

  .status-checkbox:checked+.toggle-switch .toggle-slider {
    transform: translateX(26px);
  }

  .absconder-card .status-checkbox:checked+.toggle-switch {
    background: #dc3545;
  }

  .flowup-card .status-checkbox:checked+.toggle-switch {
    background: #007bff;
  }

  .llicense-card .status-checkbox:checked+.toggle-switch {
    background: #17a2b8;
  }

  .walker-card .status-checkbox:checked+.toggle-switch {
    background: #ff9800;
  }

  .vacation-card.active {
    background: linear-gradient(135deg, #00bcd4 0%, #009688 100%);
    border-color: #009688;
  }

  .vacation-card .status-checkbox:checked+.toggle-switch {
    background: #009688;
  }

  .pro-card.active {
    background: linear-gradient(135deg, #2196f3 0%, #00bcd4 100%);
    border-color: #2196f3;
  }

  .pro-card .status-checkbox:checked+.toggle-switch {
    background: #2196f3;
  }

  .cancel-card.active {
    background: linear-gradient(135deg, #607d8b 0%, #455a64 100%);
    border-color: #607d8b;
  }

  .cancel-card .status-checkbox:checked+.toggle-switch {
    background: #607d8b;
  }

  /* Disabled state: only one option selectable at a time */
  .status-card.disabled {
    opacity: 0.6;
    pointer-events: none;
    cursor: not-allowed;
  }

  .status-card.disabled .toggle-switch {
    cursor: not-allowed;
  }

  /* Loading state */
  .status-card.loading {
    opacity: 0.7;
    pointer-events: none;
  }

  .status-card.loading .toggle-switch {
    animation: pulse 1s infinite;
  }

  @keyframes pulse {
    0% {
      opacity: 1;
    }

    50% {
      opacity: 0.5;
    }

    100% {
      opacity: 1;
    }
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .status-card {
      min-width: 150px;
      max-width: 180px;
      padding: 12px;
      flex: 1;
    }

    .status-title {
      font-size: 14px;
    }

    .status-subtitle {
      font-size: 11px;
    }
  }

  @media (max-width: 576px) {
    .status-card {
      min-width: 140px;
      max-width: 160px;
      padding: 10px;
    }

    .status-title {
      font-size: 13px;
    }

    .status-subtitle {
      font-size: 10px;
    }

    .status-icon {
      width: 35px;
      height: 35px;
      font-size: 18px;
    }
  }

  .rider-tab-count-badge,
  .rider-inventory-count-badge {
    min-width: 1.1rem;
    padding: 0.15rem;
    margin-left: 5px;
    font-size: 0.8rem;
    line-height: 1.2;
    vertical-align: middle;
  }

  @keyframes rider-expired-tab-blink {

    0%,
    100% {
      opacity: 1;
    }

    50% {
      opacity: 0.4;
    }
  }


  .nav-align-top.has-expired-docs #mainNavigation {
    overflow: visible !important;
  }

  .nav-link.rider-expired-count-link {
    position: relative;
  }

  .rider-expired-count-dot {
    position: absolute;
    top: -0.35rem;
    right: -0.15rem;
    min-width: 1.15rem;
    height: 1.15rem;
    padding: 0 0.28rem;
    border-radius: 999px;
    background: #e53935;
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    line-height: 1.15rem;
    text-align: center;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 2px #fff;
    animation: rider-expired-tab-blink 0.9s ease-in-out infinite;
  }

  .rider-expired-docs-bubble {
    position: absolute;
    left: 50%;
    bottom: calc(100% + 10px);
    transform: translateX(-50%);
    background: #e53935;
    color: #fff;
    font-size: 0.75rem;
    font-weight: 700;
    white-space: nowrap;
    padding: 0.38rem 0.7rem;
    border-radius: 0.4rem;
    line-height: 1.2;
    pointer-events: none;
    z-index: 20;
    box-shadow: 0 4px 12px rgba(229, 57, 53, 0.28);
    animation: rider-expired-tab-blink 1.1s ease-in-out infinite;
  }

  .rider-expired-docs-bubble::after {
    content: '';
    position: absolute;
    top: 100%;
    left: 50%;
    margin-left: -6px;
    border-width: 6px;
    border-style: solid;
    border-color: #e53935 transparent transparent transparent;
  }

  .rider-view-card {
    border-radius: 1rem;
    overflow: hidden;
    border: 1px solid #e9ecef;
  }

  .rider-view-card-hero {
    position: relative;
    background: linear-gradient(180deg, #1e4b8e 0%, #163a6e 100%);
    min-height: 210px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 2.25rem 1rem 1.5rem;
    overflow: hidden;
  }

  .rider-view-card-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    pointer-events: none;
    opacity: 0.2;
    background-image:
      linear-gradient(135deg, rgba(255, 255, 255, 0.35) 25%, transparent 25%),
      linear-gradient(225deg, rgba(255, 255, 255, 0.2) 25%, transparent 25%);
    background-size: 42px 42px;
  }

  .rider-view-card-star {
    position: absolute;
    top: 0.85rem;
    left: 0.9rem;
    color: #fff;
    font-size: 1.15rem;
    opacity: 0.95;
    cursor: pointer;
    transition: all 0.2s ease;
    z-index: 2;
  }

  .rider-view-card-star:hover {
    opacity: 1;
    transform: scale(1.15);
  }

  .rider-view-card-star.is-favorited {
    color: #fbbf24;
    opacity: 1;
  }

  .rider-view-card-status {
    position: absolute;
    top: 0.75rem;
    right: 0.75rem;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.15rem;
    z-index: 1;
  }

  .rider-view-card-active {
    background: #22c55e;
    color: #fff;
    font-weight: 600;
    font-size: 0.72rem;
    padding: 0.28rem 0.6rem;
    border-radius: 999px;
  }

  .rider-view-card-days {
    color: #fff;
    font-size: 0.68rem;
    font-weight: 600;
    line-height: 1.2;
    text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
  }

  .rider-view-card-photo-wrap {
    position: relative;
    width: 148px;
    height: 148px;
  }

  .rider-view-card-photo {
    width: 148px;
    height: 148px;
    object-fit: cover;
    border-radius: 50%;
    border: 4px solid #fff;
    background: #fff;
    display: block;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
  }

  .rider-view-card-camera {
    position: absolute;
    right: 4px;
    bottom: 4px;
    width: 34px;
    height: 34px;
    border: 0;
    border-radius: 50%;
    background: #fff;
    color: #1e4b8e;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.16);
    cursor: pointer;
    padding: 0;
  }

  .rider-view-card-camera i {
    font-size: 1rem;
  }

  .rider-view-card .user-info h6 {
    font-size: 1.05rem;
    margin-bottom: 0.15rem;
  }

  .rider-view-card-id {
    color: #6c757d;
    font-size: 0.9rem;
  }

  .rider-view-card-active.is-inactive {
    background: #64748b;
  }

  .rider-view-card-active.is-vacation {
    background: #f59e0b;
  }

  .rider-view-card .user_list {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    border: 0;
    background: transparent;
    padding: 0.7rem 0;
    margin: 0;
  }

  .rider-view-card .user_list+.user_list {
    margin-top: 0;
  }

  .rider-view-card .user_list .icons {
    flex: 0 0 1.25rem;
    width: 1.25rem;
    color: #5b6472;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .rider-view-card .user_list .icons i {
    font-size: 1.05rem;
    line-height: 1;
  }

  .rider-view-card .user_list_content {
    display: grid;
    grid-template-columns: 7.5rem minmax(0, 1fr);
    align-items: center;
    column-gap: 0.4rem;
    flex: 1;
    min-width: 0;
  }

  .rider-view-card .user_list_content span {
    color: #8b8d97;
    font-size: 0.8125rem;
    font-weight: 500;
    line-height: 1.3;
  }

  .rider-view-card .user_list_content b,
  .rider-view-card .user_list_content a {
    color: #1f2937;
    font-weight: 700;
    font-size: 0.875rem;
    line-height: 1.3;
    text-decoration: none;
    word-break: break-word;
  }

  .rider-view-card .user_list_content .is-phone,
  .rider-view-card .user_list_content .is-phone a {
    color: #2f6fed;
  }

  .rider-view-card .user_list_content .is-whatsapp,
  .rider-view-card .user_list_content .is-whatsapp a {
    color: #22c55e;
  }

  .rider-profile-tabs .nav-pills .nav-link {
    background: transparent !important;
    border-radius: 0;
    color: #6c757d;
    border-bottom: 3px solid transparent;
    box-shadow: none !important;
  }

  .rider-profile-tabs .nav-pills .nav-link.active {
    color: #1e4b8e !important;
    background: transparent !important;
    border-bottom-color: #1e4b8e;
  }

  .rider-info-section.card {
    background: transparent;
    border: 0;
    box-shadow: none;
  }

  .rider-info-section .rider-info-group>.card,
  .rider-info-section [data-rfp-entity="rider"]>.card {
    background: #fff;
    border: 1px solid #e9ecef;
    border-radius: 0.9rem;
    box-shadow: 0 2px 14px rgba(16, 24, 40, 0.06);
    margin-bottom: 1rem;
    overflow: hidden;
  }

  .rider-info-section .rider-info-group>.card>.card-header,
  .rider-info-section [data-rfp-entity="rider"]>.card>.card-header {
    background: transparent;
    border-bottom: 0;
    padding: 1.1rem 1.25rem 0.25rem;
    color: #2c3345;
    font-size: 0.95rem;
  }

  .rider-info-section .rider-info-group>.card>.card-body,
  .rider-info-section [data-rfp-entity="rider"]>.card>.card-body {
    padding: 0.5rem 1.25rem 1.15rem;
  }

  .rider-info-field label,
  .rider-info-section .form-group label {
    display: block;
    font-size: 0.75rem;
    font-weight: 500 !important;
    color: #8b8d97;
    margin-bottom: 0.2rem;
  }

  .rider-info-field p,
  .rider-info-section .form-group p {
    font-size: 0.9rem;
    font-weight: 600;
    color: #2c3345;
    margin-bottom: 0.85rem;
  }

  .rider-view-card #rider-status-cards {
    margin-top: 0.75rem;
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.65rem;
  }

  .rider-view-card #rider-status-cards .status-card {
    min-width: 0;
    max-width: none;
    width: 100%;
    flex: unset;
    padding: 0.7rem 0.75rem;
  }

  .rider-view-card #rider-status-cards .status-icon {
    width: 28px;
    height: 28px;
    font-size: 14px;
    margin-bottom: 0.4rem;
  }

  .rider-view-card #rider-status-cards .status-title {
    font-size: 0.78rem;
  }

  .rider-view-card #rider-status-cards .status-subtitle {
    font-size: 0.65rem;
  }
</style>
@php
$rider = $riders ?? $rider ?? null;
if(isset($riders)){
$result = $riders->toArray();
}
if(isset($result)){
$account = App\Models\ExpenseAccount::where('rider_id', $result['id'])
->whereNotNull('renewal_category_id')
->orderByDesc('id')
->first()
?? App\Models\ExpenseAccount::where('rider_id', $result['id'])->orderByDesc('id')->first();
}
$companySlug = request()->route('company_slug');
$inventoryTabRider = (isset($riders) && $riders instanceof \App\Models\Riders)
? $riders
: ((isset($rider) && $rider instanceof \App\Models\Riders) ? $rider : null);
$riderAssignedItemCount = ($inventoryTabRider && \App\Support\CompanyModuleVisibility::enabled('rider_inventory'))
? $inventoryTabRider->currentlyAssignedItemCount()
: 0;

$riderDocumentFrontend = [];
$riderExistingDocuments = [];
$riderDocumentDefinitions = [];
$riderDocumentFiles = [];
$riderExpiredDocumentCount = 0;
$riderExpiringDocumentCount = 0;
$riderInfoExpiredCount = 0;
$riderInfoExpiringCount = 0;
$riderFilesExpiredCount = 0;
$riderFilesExpiringCount = 0;
$riderTopViewCategories = collect();
if ($inventoryTabRider instanceof \App\Models\Riders) {
$riderDocumentFrontend = \App\Support\RiderDocumentReplacement::frontendConfig($inventoryTabRider);
$riderExistingDocuments = $riderDocumentFrontend['existing'] ?? [];
$riderDocumentDefinitions = \App\Support\RiderDocumentReplacement::definitions();
$riderDocumentFiles = $riderDocumentFrontend['files'] ?? [];
$riderExpiredDocumentCount = \App\Support\RiderDocumentReplacement::totalExpiredCountForRider($inventoryTabRider);
$riderExpiringDocumentCount = \App\Support\RiderDocumentReplacement::totalExpiringCountForRider($inventoryTabRider, 30);
$riderInfoExpiredCount = \App\Support\RiderDocumentReplacement::expiredCountForRider($inventoryTabRider);
$riderInfoExpiringCount = \App\Support\RiderDocumentReplacement::expiringCountForRider($inventoryTabRider, 30);
$riderFilesExpiredCount = \App\Support\RiderDocumentReplacement::expiredFilesCountForRider($inventoryTabRider);
$riderFilesExpiringCount = \App\Support\RiderDocumentReplacement::expiringFilesCountForRider($inventoryTabRider, 30);
}

@endphp
<div class="row" style="">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    <!-- User Card -->
    <div class="card rider-view-card mb-6">
      @isset($result)
      @php
      $profile = company_table('files')
      ->where('type', 'rider')
      ->where('type_id', $result['id'])
      ->where(function($query) {
      $query->where('name', 'LIKE', '%photo%')
      ->orWhere('name', 'LIKE', '%Photo%')
      ->orWhere('name', 'LIKE', '%picture%')
      ->orWhere('name', 'LIKE', '%Picture%')
      ->orWhere('name', 'LIKE', '%profile%')
      ->orWhere('name', 'LIKE', '%Profile%');
      })
      ->first();
      if (@$result['image_name']) {
      $image_name = storage_url('profile/'.$result['image_name']);
      } elseif (isset($profile)) {
      $image_name = storage_url($profile->type .'/'. $profile->type_id .'/'. $profile->file_name);
      } else {
      $image_name = asset('uploads/default.png');
      }
      $riderTopViewCategories = \App\Models\RiderTopCategory::with(['options' => function($q){
      $q->where('is_active', 1)->orderBy('display_order')->orderBy('id');
      }])->where('show_in_view_cards', 1)->orderBy('display_order')->orderBy('id')->get()
      ->filter(function ($category) {
      if (\App\Support\RoleFieldAccess::isAdmin() || ! \App\Services\Permissions\TopBarPermissionSync::isEnforced()) {
      return true;
      }
      if (\App\Services\Permissions\TopBarPermissionSync::canAccessCategory('riders', $category)) {
      return true;
      }
      $column = trim((string) ($category->rider_column ?? ''));
      return $column === 'rider_status'
      && \App\Services\Permissions\RiderStatusPermissionSync::userHasAnyVisibleStatusPermission($category->options);
      })->map(function ($category) {
      $column = trim((string) ($category->rider_column ?? ''));
      if ($column === 'rider_status') {
      $category->setRelation(
      'options',
      \App\Services\Permissions\RiderStatusPermissionSync::filterOptionsForTopBar($category->options)
      );
      } else {
      $category->setRelation(
      'options',
      \App\Services\Permissions\TopBarOptionPermissionSync::filterOptions('riders', $category->options)
      );
      }
      return $category;
      })->filter(fn ($cat) => $cat->options->isNotEmpty())->values();
      $canChangeRiderStatus = \App\Services\Permissions\RiderStatusPermissionSync::canChangeRiderStatus();
      $employmentBadge = \App\Models\Riders::employmentStatusDisplay($result['status'] ?? null);
      $currentStatusBadge = \App\Models\Riders::currentStatusDisplay($result['status'] ?? null, $result['rider_status'] ?? null);
      $statusDaysInfo = \App\Models\Riders::resolveEmploymentStatusDays(isset($rider) ? $rider : ($result ?? null));
      $statusDaysTitle = !empty($statusDaysInfo['changed_at'])
      ? 'Status changed on ' . \Carbon\Carbon::parse($statusDaysInfo['changed_at'])->format('d M Y')
      : 'Days in current status';
      $isFavorited = isset($rider) && in_array($rider->id, auth()->user()->favorite_rider_ids ?? [], true);
      @endphp
      @endisset
      <div class="user-avatar-section">
        <div class="rider-view-card-hero">
          <i class="ti ti-star-filled rider-view-card-star {{ $isFavorited ? 'is-favorited' : '' }}"
             id="rider-favorite-star"
             data-rider-id="{{ $result['id'] ?? '' }}"
             title="{{ $isFavorited ? 'Remove from favorites' : 'Add to favorites' }}"></i>
          @isset($result)
          <div class="rider-view-card-status">
            <span class="rider-view-card-active {{ strtolower($employmentBadge['label'] ?? '') === 'active' ? '' : (strtolower($employmentBadge['label'] ?? '') === 'vacation' ? 'is-vacation' : 'is-inactive') }}" id="rider-hero-status-badge">{{ $employmentBadge['label'] ?? 'Inactive' }}</span>
            <small class="rider-view-card-days" id="rider-status-days" title="{{ $statusDaysTitle }}" @if(($statusDaysInfo['days'] ?? null) === null) style="display:none" @endif>
              @if(($statusDaysInfo['days'] ?? null) !== null)
              {{ (int) $statusDaysInfo['days'] }} {{ (int) $statusDaysInfo['days'] === 1 ? 'day' : 'days' }}
              @endif
            </small>
          </div>
          <div class="rider-view-card-photo-wrap">
            <img src="{{ $image_name }}" id="output" class="rider-view-card-photo" alt="{{ $result['name'] ?? 'Rider' }}" />
            @can('riders_rider_edit')
            <button type="button" class="rider-view-card-camera" id="edit-icon" title="Change photo">
              <i class="ti ti-camera"></i>
            </button>
            @endcan
          </div>
          @endisset
        </div>
        <div class="card-body pt-3">
          <div class="user-info text-center mb-3">
            <h6 class="mb-0"><b>@isset($result){{ $result['name'] ?? 'not-set' }}@endisset</b></h6>
            <div class="rider-view-card-id">@isset($result){{ $result['rider_id'] ?? 'not-set' }}@endisset</div>
          </div>
          <div id="photo-upload-form" class="mt-2" style="display: none;">
            @isset($result)
            <form action="{{ route('rider_picture_upload', ['company_slug' => request()->route('company_slug'), 'id' => $result['id']]) }}" method="POST" enctype="multipart/form-data" id="formajax2">
              @csrf
              <div class="button-wrapper text-center">
                <label for="upload" class="btn btn-default me-2 mb-2" tabindex="0">
                  <span class="d-none d-sm-block">Change Photo</span>
                  <i class="ti ti-upload d-block d-sm-none"></i>
                  <input type="file" id="upload" name="image_name" class="account-file-input" hidden accept="image/png, image/jpeg" onchange="loadFile(event)" />
                </label>
                <button type="submit" class="btn btn-primary mb-2">Upload</button>
              </div>
            </form>
            @endisset
          </div>
        </div>
      </div>
      <div class="card-body pt-0">
        <div class="info-container">
          <ul class="list-unstyled mb-6">
            <script>
              var loadFile = function(event) {
                var image = document.getElementById("output");
                image.src = URL.createObjectURL(event.target.files[0]);
              };
            </script>
            {{-- <div class="text-center">
                         <img class="profile-user-img img-fluid" src="https://placehold.co/400X400" alt="User profile picture">
                      </div> --}}


            @isset($result)
            <ul class="p-0 mb-3 rider-view-card-list">
              @php
              $cardPhone = $result['personal_contact'] ?? $result['company_contact'] ?? 'not-set';
              $cardEmail = $result['email'] ?? 'not-set';
              $cardNationality = $rider?->country?->name
              ?? (isset($result['nationality']) ? (company_table('countries')->where('id', $result['nationality'])->first()->name ?? 'not-set') : 'not-set');
              $cardDob = !empty($result['dob'] ?? null) ? \App\Helpers\General::DateFormat($result['dob']) : 'not-set';
              $cardAge = !empty($result['dob'] ?? null) ? (\Carbon\Carbon::parse($result['dob'])->age . ' Years') : 'not-set';
              $cardDoj = !empty($result['doj'] ?? null) ? \App\Helpers\General::DateFormat($result['doj']) : 'not-set';
              $cardProject = $rider?->customer?->name ?? 'not-set';
              $cardAddress = $result['address']
              ?? $rider?->address
              ?? $rider?->emirate_hub
              ?? $rider?->customer?->address
              ?? 'not-set';
              if ($cardAddress === '' || $cardAddress === null) {
              $cardAddress = 'not-set';
              }
              @endphp
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-phone"></i>
                </div>
                <div class="user_list_content">
                  <span>Phone</span>
                  <b class="is-phone">{{ $cardPhone }}</b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-mail"></i>
                </div>
                <div class="user_list_content">
                  <span>Email</span>
                  <b>{{ $cardEmail }}</b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-brand-whatsapp"></i>
                </div>
                <div class="user_list_content">
                  <span>WhatsApp</span>
                  <b class="is-whatsapp">
                    @if($rider?->sim?->number)
                    @php
                    $phone = preg_replace('/[^0-9]/', '', $rider->sim->number);
                    if (strpos($phone, '971') === 0) { $whatsappNumber = '+' . $phone; $displayNumber = '0' . substr($phone, 3); }
                    else { $whatsappNumber = '+971' . ltrim($phone, '0'); $displayNumber = '0' . ltrim($phone, '0'); }
                    @endphp
                    <a href="https://wa.me/{{ $whatsappNumber }}" target="_blank">{{ $displayNumber }}</a>
                    @else
                    N/A
                    @endif
                  </b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-flag"></i>
                </div>
                <div class="user_list_content">
                  <span>Nationality</span>
                  <b>{{ $cardNationality }}</b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-calendar"></i>
                </div>
                <div class="user_list_content">
                  <span>Date of Birth</span>
                  <b>{{ $cardDob }}</b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-user"></i>
                </div>
                <div class="user_list_content">
                  <span>Age</span>
                  <b>{{ $cardAge }}</b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-calendar"></i>
                </div>
                <div class="user_list_content">
                  <span>Date of Joining</span>
                  <b>{{ $cardDoj }}</b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-briefcase"></i>
                </div>
                <div class="user_list_content">
                  <span>Project / Client</span>
                  <b>{{ $cardProject }}</b>
                </div>
              </li>
              <li class="list-group-item user_list">
                <div class="icons">
                  <i class="ti ti-map-pin"></i>
                </div>
                <div class="user_list_content">
                  <span>Address</span>
                  <b>{{ $cardAddress }}</b>
                </div>
              </li>
            </ul>
            @endisset
          </ul>
          @isset($result)
          <div id="rider-status-cards">
            @php $cardIndex = 0; @endphp
            @foreach($riderTopViewCategories as $category)
            @php $riderTopColumn = trim((string)($category->rider_column ?? '')); @endphp
            @if($riderTopColumn === 'status')
            @continue
            @endif
            @foreach($category->options as $option)
            @php
            $isSelected = $riderTopColumn !== ''
            && array_key_exists($riderTopColumn, $result)
            && (string)($result[$riderTopColumn] ?? '') === (string)$option->name;
            $cardKey = 'rider_top_option_' . $option->id;
            $icons = ['ti ti-bell', 'ti ti-user-check', 'ti ti-star', 'ti ti-flag'];
            $statusChangeLocked = $riderTopColumn === 'rider_status' && empty($canChangeRiderStatus);
            @endphp
            <div class="status-card rider-top-option-card {{ $isSelected ? 'active' : '' }} {{ $statusChangeLocked ? 'disabled' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-option-id="{{ $option->id }}"
              data-column="{{ $riderTopColumn }}"
              data-value="{{ $option->name }}"
              data-category="{{ $category->name }}"
              data-type="{{ $cardKey }}"
              @if($statusChangeLocked) title="You do not have permission to change Rider Status" @endif>
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="{{ $icons[$cardIndex % count($icons)] }}"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">{{ $option->name }}</div>
                  <div class="status-subtitle">{{ $isSelected ? 'Assigned to rider' : $category->name }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox rider-top-option-checkbox"
                  id="rider-top-option-{{ $option->id }}-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  data-option-id="{{ $option->id }}"
                  data-column="{{ $riderTopColumn }}"
                  data-value="{{ $option->name }}"
                  {{ $isSelected ? 'checked' : '' }}
                  {{ $statusChangeLocked ? 'disabled' : '' }}>
                <label for="rider-top-option-{{ $option->id }}-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            @php $cardIndex++; @endphp
            @endforeach
            @endforeach
          </div>

          <div class="modal fade" id="riderTopOptionDateModal" tabindex="-1" aria-labelledby="riderTopOptionDateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="riderTopOptionDateModalLabel">Confirm status</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <p class="mb-3">Choose the effective date for <strong id="riderTopOptionModalStatusName">—</strong>. Dates after today are not allowed.</p>
                  <label for="riderTopOptionEffectiveDate" class="form-label">Effective date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="riderTopOptionEffectiveDate" required autocomplete="off">
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="riderTopOptionDateSave">Save</button>
                </div>
              </div>
            </div>
          </div>
          @endisset
        </div>
      </div>
    </div>
  </div>
  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    <div class="nav-align-top rider-profile-tabs mb-4 @if(($riderExpiredDocumentCount ?? 0) > 0) has-expired-docs @endif" style="position: sticky; top: 0; z-index: 1000; width: 100%;">
      <div class="card" style="z-index: 1;">
        <div class="card-body p-2">
          <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
            <div class="flex-grow-1" style="min-width: 0;">
              <ul class="nav nav-pills flex-nowrap mb-0 overflow-hidden" id="mainNavigation" style="gap: 0.25rem;">
                <!-- Priority navigation items (always visible when possible) -->
                <li class="nav-item nav-priority-1">
                  <a class="nav-link rider-expired-count-link @if(Route::is('riders.show') || Route::is('riders.create')) active @endif"
                    href="@isset($result['id']){{route('riders.show',$result['id'])}}@else#@endif">
                    <i class="ti ti-user-check ti-sm me-1_5"></i>Information
                    @include('riders._document_status_badges', ['expiredCount' => $riderInfoExpiredCount ?? 0, 'expiringCount' => $riderInfoExpiringCount ?? 0])
                  </a>
                </li>

                @isset($result)
                @can('riders_timeline_view')
                <li class="nav-item nav-priority-2">
                  <a class="nav-link @if(Route::is('rider.timeline')) active @endif"
                    href="{{route('rider.timeline',$result['id'])}}">
                    <i class="ti ti-timeline ti-sm me-1_5"></i>Timeline
                  </a>
                </li>
                <li class="nav-item nav-priority-2">
                  <a class="nav-link @if(Route::is('rider.history')) active @endif"
                    href="{{route('rider.history',$result['id'])}}">
                    <i class="ti ti-history ti-sm me-1_5"></i>History
                  </a>
                </li>
                @endcan

                @if(\App\Support\CompanyModuleVisibility::enabled('rider_inventory'))
                <li class="nav-item nav-priority-2">
                  <a class="nav-link @if(Route::is('rider.inventory')) active @endif"
                    href="{{ route('rider.inventory', $result['id']) }}">
                    <i class="ti ti-package ti-sm me-1_5"></i>
                    Inventory
                    @if($riderAssignedItemCount > 0)
                    <span class="badge rounded-pill bg-danger rider-inventory-count-badge me-1">{{ $riderAssignedItemCount }}</span>
                    @endif
                  </a>
                </li>
                @endif

                @can('riders_documents_view')
                <li class="nav-item nav-priority-3" @if(($riderExpiredDocumentCount ?? 0)> 0) style="z-index: 5;" @endif>
                  <a class="nav-link rider-expired-count-link @if(Route::is('rider.files')) active @endif"
                    href="{{route('rider.files',$result['id'])}}">
                    <i class="ti ti-file-upload ti-sm me-1_5"></i>Files
                    @include('riders._document_status_badges', ['expiredCount' => $riderFilesExpiredCount ?? 0, 'expiringCount' => $riderFilesExpiringCount ?? 0])
                  </a>
                </li>
                @endcan

                @can('riders_invoices_view')
                <li class="nav-item nav-priority-4">
                  <a class="nav-link @if(Route::is('rider.invoices')) active @endif"
                    href="{{route('rider.invoices',$result['id'])}}">
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Invoices
                  </a>
                </li>
                @endcan

                @if(\App\Support\CompanyModuleVisibility::enabled('visa_expense'))
                @if(\App\Support\VisaExpenseAccess::visibleInRiderTab())
                @isset($result)
                @can('visa_expense_view')
                @php
                // Prefer a dedicated visa expense account (renewal category), then any linked expense account.
                $visaExpenseAccount = $account
                ?? company_table('expense_accounts')
                ->where('rider_id', $result['id'])
                ->whereNotNull('renewal_category_id')
                ->orderByDesc('id')
                ->first()
                ?? company_table('expense_accounts')
                ->where('rider_id', $result['id'])
                ->orderByDesc('id')
                ->first();
                @endphp
                @if($visaExpenseAccount)
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(Route::is('VisaExpense.generatentries')) active @endif"
                    href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($visaExpenseAccount->id, $visaExpenseAccount->rider_id ?? $result['id']) }}">
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Visa Expense
                  </a>
                </li>
                @endif
                @endcan
                @endif
                @endif
                @endif


                @if(\App\Support\CompanyModuleVisibility::enabled('license_expense'))
                @can('license_expense_view')
                @if(!empty($riders))
                @php
                $licenseExpenseAccount = company_table('expense_accounts')->where('rider_id', $result['id'])->first();
                @endphp
                @if($licenseExpenseAccount)
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(Route::is('LicenseExpense.generatentries')) active @endif"
                    href="{{ route('LicenseExpense.generatentries', $licenseExpenseAccount->id) }}">
                    <i class="ti ti-steering-wheel ti-sm me-1_5"></i>License Expense
                  </a>
                </li>
                @endif
                @endif
                @endcan
                @endif

                @can('legal_case_view')
                @if(\App\Support\CompanyModuleVisibility::enabled('legal_case'))
                @php
                $legalCaseAccount = company_table('legal_case_accounts')->where('rider_id', $result['id'])->first();
                @endphp
                @if($legalCaseAccount)
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(Route::is('LegalCase.generatentries')) active @endif"
                    href="{{ route('LegalCase.generatentries', $legalCaseAccount->id) }}">
                    <i class="ti ti-scale ti-sm me-1_5"></i>Legal Case
                  </a>
                </li>
                @endif
                @endif
                @endcan

                @can('riders_rider_view')
                <li class="nav-item nav-priority-6">
                  <a class="nav-link @if(Route::is('rider.items')) active @endif"
                    href="{{route('rider.items',$result['id'])}}">
                    <i class="ti ti-cash-banknote ti-sm me-1"></i>Salary
                  </a>
                </li>
                @endcan

                @can('riders_ledger_view')
                <li class="nav-item nav-priority-7">
                  <a class="nav-link @if(Route::is('rider.ledger')) active @endif"
                    href="{{route('rider.ledger',$result['id'])}}">
                    <i class="ti ti-file ti-sm me-1_5"></i>Ledger
                  </a>
                </li>
                @endcan

                @can('riders_activities_view')
                <li class="nav-item nav-priority-8">
                  <a class="nav-link @if(Route::is('rider.activities')) active @endif"
                    href="{{route('rider.activities',$result['id'])}}">
                    <i class="ti ti-motorbike ti-sm me-1_5"></i>Activities
                  </a>
                </li>
                @endcan

                @can('email_view')
                <li class="nav-item nav-priority-9">
                  <a class="nav-link @if(Route::is('rider.emails')) active @endif"
                    href="{{route('rider.emails',$result['id'])}}">
                    <i class="ti ti-mail ti-sm me-1_5"></i>Emails
                  </a>
                </li>
                @endcan

                @endisset
              </ul>
            </div>

            <!-- Dropdown for overflow items and actions -->
            <div class="dropdown">
              <button class="btn btn-outline-secondary rounded-pill p-2 waves-effect"
                type="button" id="actiondropdown" data-bs-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="ti ti-dots icon-md"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" id="dropdownMenu">
                <!-- Overflow navigation and action items will be moved here -->
                <div id="overflowItems"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card rider-info-section mb-5" id="cardBody" style="margin-top: 12px; position: relative;">
      @yield('page_content')
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Responsive Navigation Handler
    class ResponsiveNavigation {
      constructor() {
        this.mainNav = document.getElementById('mainNavigation');
        this.overflowContainer = document.getElementById('overflowItems');
        this.dropdownButton = document.getElementById('actiondropdown');
        this.allNavItems = [];
        this.init();
      }

      init() {
        // Store all navigation items with their priority
        this.allNavItems = Array.from(this.mainNav.querySelectorAll('.nav-item')).map(item => {
          const priorityClass = Array.from(item.classList).find(cls => cls.startsWith('nav-priority-'));
          const priority = priorityClass ? parseInt(priorityClass.split('-')[2]) : 999;
          return {
            element: item,
            priority: priority,
            html: item.outerHTML,
            isActive: item.querySelector('.nav-link.active') !== null
          };
        }).sort((a, b) => a.priority - b.priority);

        this.handleResize();

        // Debounced resize handler for better performance
        let resizeTimeout;
        window.addEventListener('resize', () => {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(() => this.handleResize(), 100);
        });

        window.addEventListener('load', () => {
          setTimeout(() => this.handleResize(), 200);
        });

        // Handle window focus to recalculate
        window.addEventListener('focus', () => {
          setTimeout(() => this.handleResize(), 100);
        });

        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
          if (!document.hidden) {
            setTimeout(() => this.handleResize(), 100);
          }
        });
      }

      handleResize() {
        // Reset all items to main navigation
        this.resetNavigation();

        // Wait for next frame to ensure layout is updated
        requestAnimationFrame(() => {
          // Wait another frame for styles to apply
          requestAnimationFrame(() => {
            this.redistributeItems();
          });
        });
      }

      resetNavigation() {
        // Clear overflow container
        this.overflowContainer.innerHTML = '';

        // Move all items back to main navigation
        this.mainNav.innerHTML = '';
        this.allNavItems.forEach(item => {
          this.mainNav.appendChild(item.element);
        });
      }

      redistributeItems() {
        const container = this.mainNav.closest('.card-body');
        if (!container) return;

        const containerRect = container.getBoundingClientRect();
        const containerWidth = containerRect.width;
        const dropdownWidth = this.dropdownButton.offsetWidth + 10;

        let currentWidth = 0;
        const visibleItems = [];
        const overflowItems = [];

        // First, try to fit all items without dropdown
        let totalItemsWidth = 0;
        const itemWidths = this.allNavItems.map(item => {
          const width = this.getItemWidth(item.element);
          totalItemsWidth += width;
          return {
            item,
            width
          };
        });

        // Calculate container padding and margins
        const containerStyles = window.getComputedStyle(container);
        const containerPadding = parseFloat(containerStyles.paddingLeft) + parseFloat(containerStyles.paddingRight);
        const safetyMargin = 20;
        const usableWidth = containerWidth - containerPadding - safetyMargin;

        // If all items can fit without dropdown, show them all
        if (totalItemsWidth <= usableWidth) {
          this.allNavItems.forEach(item => visibleItems.push(item));
        } else {
          // Otherwise, calculate what can fit with dropdown visible
          const availableWidth = usableWidth - dropdownWidth;

          for (let i = 0; i < itemWidths.length; i++) {
            const {
              item,
              width
            } = itemWidths[i];

            if (currentWidth + width <= availableWidth) {
              currentWidth += width;
              visibleItems.push(item);
            } else {
              overflowItems.push(item);
            }
          }

          // Ensure at least the first item (Information) is always visible
          if (visibleItems.length === 0 && this.allNavItems.length > 0) {
            visibleItems.push(this.allNavItems[0]);
            overflowItems.unshift(...this.allNavItems.slice(1));
          }
        }

        // Update the navigation
        this.updateNavigation(visibleItems, overflowItems);
      }

      getItemWidth(element) {
        // Create a temporary clone to measure width accurately
        const clone = element.cloneNode(true);
        clone.style.cssText = `
          visibility: hidden; 
          position: absolute; 
          white-space: nowrap; 
          top: -9999px; 
          left: -9999px;
          pointer-events: none;
          z-index: -1;
        `;

        // Append to the same container to inherit styles
        const container = this.mainNav.parentNode;
        container.appendChild(clone);

        const rect = clone.getBoundingClientRect();
        const width = Math.ceil(rect.width) + 6; // Add small margin and round up

        container.removeChild(clone);
        return width;
      }

      updateNavigation(visibleItems, overflowItems) {
        // Update main navigation
        this.mainNav.innerHTML = '';
        visibleItems.forEach(item => {
          this.mainNav.appendChild(item.element);
        });

        // Update overflow container and dropdown button visibility
        this.overflowContainer.innerHTML = '';

        // Show/hide dropdown button based on overflow items
        if (overflowItems.length > 0) {
          this.dropdownButton.style.display = 'flex';
          // Separate navigation and action items for better organization
          const navigationItems = overflowItems.filter(item => !item.element.classList.contains('nav-action-item'));
          const actionItems = overflowItems.filter(item => item.element.classList.contains('nav-action-item'));

          // Add navigation items first
          navigationItems.forEach(item => {
            const dropdownItem = this.createDropdownItem(item);
            this.overflowContainer.appendChild(dropdownItem);
          });

          // Add divider if both types exist
          if (navigationItems.length > 0 && actionItems.length > 0) {
            const divider = document.createElement('div');
            divider.className = 'dropdown-divider';
            this.overflowContainer.appendChild(divider);

            const header = document.createElement('h6');
            header.className = 'dropdown-header';
            header.textContent = 'Actions';
            this.overflowContainer.appendChild(header);
          }

          // Add action items
          actionItems.forEach(item => {
            const dropdownItem = this.createDropdownItem(item);
            this.overflowContainer.appendChild(dropdownItem);
          });
        } else {
          // Hide dropdown button if no overflow items
          this.dropdownButton.style.display = 'none';
        }
      }

      createDropdownItem(navItem) {
        const link = navItem.element.querySelector('.nav-link');
        const href = link.getAttribute('href');
        const icon = link.querySelector('i');
        const text = link.textContent.trim();
        const isActive = link.classList.contains('active');
        const isActionItem = navItem.element.classList.contains('nav-action-item');

        const dropdownItem = document.createElement('a');
        dropdownItem.className = `dropdown-item overflow-nav-item ${isActive ? 'active' : ''}`;
        dropdownItem.href = href;

        // Copy data attributes for action items
        if (isActionItem) {
          const dataAction = link.getAttribute('data-action');
          const dataSize = link.getAttribute('data-size');
          const dataTitle = link.getAttribute('data-title');

          if (dataAction) dropdownItem.setAttribute('data-action', dataAction);
          if (dataSize) dropdownItem.setAttribute('data-size', dataSize);
          if (dataTitle) dropdownItem.setAttribute('data-title', dataTitle);

          // Copy the show-modal class
          if (link.classList.contains('show-modal')) {
            dropdownItem.classList.add('show-modal');
          }
        }

        if (icon) {
          const iconClone = icon.cloneNode(true);
          iconClone.className = icon.className.replace('me-1_5', 'me-2');
          dropdownItem.appendChild(iconClone);
        }

        dropdownItem.appendChild(document.createTextNode(text));

        return dropdownItem;
      }
    }

    // Initialize responsive navigation
    const responsiveNav = new ResponsiveNavigation();

    // Force initial calculation after a short delay to ensure all styles are loaded
    setTimeout(() => {
      responsiveNav.handleResize();
    }, 500);

    // Rider Top view cards — per-column updates (matches employee profile cards)
    function syncRiderTopOptionCards(column) {
      const container = document.getElementById('rider-status-cards');
      if (!container) return;
      container.querySelectorAll('.rider-top-option-card[data-column="' + column + '"]').forEach((card) => {
        const checkbox = card.querySelector('.rider-top-option-checkbox');
        const subtitle = card.querySelector('.status-subtitle');
        const isActive = !!(checkbox && checkbox.checked);
        card.classList.toggle('active', isActive);
        if (subtitle) {
          const categoryName = card.getAttribute('data-category') || 'Not assigned';
          subtitle.textContent = isActive ? 'Assigned to rider' : categoryName;
        }
      });
    }

    function refreshRiderSidebarBadges(data) {
      const designationBadge = document.getElementById('rider-designation-badge');
      const statusValueBadge = document.getElementById('rider-status-value-badge');
      const statusDaysEl = document.getElementById('rider-status-days');
      const heroBadge = document.getElementById('rider-hero-status-badge');
      if (designationBadge && data.column === 'designation' && data.value) {
        designationBadge.textContent = data.value;
      }
      const currentLabel = data.status_label || data.rider_status || data.employment_label || 'Inactive';
      const employmentLabel = data.employment_label || currentLabel;
      if (heroBadge) {
        heroBadge.textContent = employmentLabel;
        heroBadge.classList.remove('is-inactive', 'is-vacation');
        const employmentLower = String(employmentLabel).toLowerCase();
        if (employmentLower === 'vacation') {
          heroBadge.classList.add('is-vacation');
        } else if (employmentLower !== 'active') {
          heroBadge.classList.add('is-inactive');
        }
      }
      if (statusValueBadge) {
        let text = currentLabel;
        const days = data.employment_status_days;
        if (days !== null && days !== undefined && days !== '') {
          const dayNum = parseInt(days, 10);
          if (!Number.isNaN(dayNum)) {
            text = currentLabel + ' ' + dayNum + (dayNum === 1 ? ' day' : ' days');
          }
        }
        statusValueBadge.textContent = text;
        statusValueBadge.className = 'badge ' + (data.status_badge || data.employment_badge || 'bg-label-danger');
      }
      if (statusDaysEl) {
        const days = data.employment_status_days;
        if (days === null || days === undefined || days === '') {
          statusDaysEl.style.display = 'none';
          statusDaysEl.textContent = '';
        } else {
          const dayNum = parseInt(days, 10);
          statusDaysEl.textContent = dayNum + (dayNum === 1 ? ' day' : ' days');
          statusDaysEl.style.display = '';
          if (data.last_employment_status_change_date) {
            statusDaysEl.title = 'Status changed on ' + data.last_employment_status_change_date;
          } else {
            statusDaysEl.title = 'Days in current status';
          }
        }
      }
    }

    function submitRiderTopOptionRequest(checkbox, requestOptionId, effectiveDate, isClear) {
      const riderId = checkbox.getAttribute('data-rider-id');
      const column = checkbox.getAttribute('data-column');
      const card = checkbox.closest('.status-card');
      if (!riderId || !card || !column) {
        showNotification('Rider ID not found', 'error');
        return Promise.resolve(false);
      }
      const subtitle = card.querySelector('.status-subtitle');
      card.classList.add('loading');
      if (subtitle) subtitle.textContent = 'Updating...';

      const setOptionUrlTemplate = "{{ route('riders.setRiderTopOption', ['id' => '__RID__']) }}";
      const setOptionUrl = setOptionUrlTemplate.replace('__RID__', riderId);
      const body = isClear ? {
        clear_option_id: requestOptionId
      } : {
        option_id: requestOptionId,
        effective_date: effectiveDate
      };

      return fetch(setOptionUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify(body)
        })
        .then(async (response) => {
          const data = await response.json().catch(() => ({}));
          if (response.ok && data.success) {
            if (isClear) {
              checkbox.checked = false;
            } else {
              statusCardsContainer.querySelectorAll('.rider-top-option-card[data-column="' + column + '"]').forEach((c) => {
                const cb = c.querySelector('.rider-top-option-checkbox');
                if (cb && cb !== checkbox) cb.checked = false;
              });
              checkbox.checked = true;
            }
            syncRiderTopOptionCards(column);
            refreshRiderSidebarBadges(data);
            showNotification(data.message, 'success');
            return true;
          }
          let msg = data.message || 'Unknown error';
          if (data.errors) {
            const flat = Object.values(data.errors).flat();
            if (flat.length) msg = flat.join(' ');
          }
          showNotification('Error: ' + msg, 'error');
          return false;
        })
        .catch((error) => {
          console.error('Error:', error);
          showNotification('An error occurred while updating status', 'error');
          return false;
        })
        .finally(() => {
          card.classList.remove('loading');
        });
    }

    const statusCardsContainer = document.getElementById('rider-status-cards');
    const riderTopOptionDateModalEl = document.getElementById('riderTopOptionDateModal');
    const riderTopOptionModal = riderTopOptionDateModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal ?
      new bootstrap.Modal(riderTopOptionDateModalEl) :
      null;
    let pendingRiderTopOptionCheckbox = null;
    let riderTopOptionRequestInFlight = false;

    function riderTopOptionTodayYmd() {
      const t = new Date();
      const y = t.getFullYear();
      const m = String(t.getMonth() + 1).padStart(2, '0');
      const d = String(t.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
    }

    if (statusCardsContainer) {
      statusCardsContainer.addEventListener('click', function(e) {
        const toggleArea = e.target.closest('.status-toggle');
        if (!toggleArea || !statusCardsContainer.contains(toggleArea)) return;
        const checkbox = toggleArea.querySelector('.rider-top-option-checkbox');
        if (!checkbox) return;

        const optionId = parseInt(checkbox.getAttribute('data-option-id') || '0', 10);
        if (!optionId) return;

        if (checkbox.checked) {
          e.preventDefault();
          e.stopPropagation();
          if (riderTopOptionRequestInFlight) return;
          riderTopOptionRequestInFlight = true;
          submitRiderTopOptionRequest(checkbox, optionId, null, true).then((ok) => {
            if (!ok) {
              checkbox.checked = true;
              syncRiderTopOptionCards(checkbox.getAttribute('data-column'));
            }
          }).finally(() => {
            riderTopOptionRequestInFlight = false;
          });
          return;
        }

        e.preventDefault();
        e.stopPropagation();
        pendingRiderTopOptionCheckbox = checkbox;
        const card = checkbox.closest('.status-card');
        const titleEl = card ? card.querySelector('.status-title') : null;
        const nameEl = document.getElementById('riderTopOptionModalStatusName');
        if (nameEl) nameEl.textContent = (titleEl && titleEl.textContent) ? titleEl.textContent.trim() : 'Status';
        const dateInput = document.getElementById('riderTopOptionEffectiveDate');
        const today = riderTopOptionTodayYmd();
        if (dateInput) {
          dateInput.max = today;
          dateInput.value = today;
        }
        riderTopOptionModal ? riderTopOptionModal.show() : (function() {
          showNotification('Date dialog is unavailable', 'error');
          pendingRiderTopOptionCheckbox = null;
        })();
      }, true);

      if (riderTopOptionDateModalEl) {
        riderTopOptionDateModalEl.addEventListener('hidden.bs.modal', function() {
          pendingRiderTopOptionCheckbox = null;
        });
      }

      document.getElementById('riderTopOptionDateSave')?.addEventListener('click', function() {
        const checkbox = pendingRiderTopOptionCheckbox;
        if (!checkbox) return;
        const dateInput = document.getElementById('riderTopOptionEffectiveDate');
        const effectiveDate = dateInput ? dateInput.value : '';
        const today = riderTopOptionTodayYmd();
        if (!effectiveDate) {
          showNotification('Please select an effective date', 'error');
          return;
        }
        if (effectiveDate > today) {
          showNotification('Future dates are not allowed', 'error');
          return;
        }
        const optionId = parseInt(checkbox.getAttribute('data-option-id') || '0', 10);
        submitRiderTopOptionRequest(checkbox, optionId, effectiveDate, false).then((ok) => {
          if (ok) {
            riderTopOptionModal ? riderTopOptionModal.hide() : null;
          }
        });
      });

      statusCardsContainer.addEventListener('change', function(e) {
        const checkbox = e.target;
        if (!checkbox.classList.contains('rider-top-option-checkbox')) return;
        if (checkbox.checked || riderTopOptionRequestInFlight) return;

        const optionId = parseInt(checkbox.getAttribute('data-option-id') || '0', 10);
        if (!optionId) {
          showNotification('Option ID not found', 'error');
          checkbox.checked = true;
          return;
        }

        riderTopOptionRequestInFlight = true;
        submitRiderTopOptionRequest(checkbox, optionId, null, true).then((ok) => {
          if (!ok) {
            checkbox.checked = true;
            syncRiderTopOptionCards(checkbox.getAttribute('data-column'));
          }
        }).finally(() => {
          riderTopOptionRequestInFlight = false;
        });
      });
    }

    // Toggle favorite rider
    const favoriteStar = document.getElementById('rider-favorite-star');
    if (favoriteStar) {
      favoriteStar.addEventListener('click', function() {
        const riderId = this.getAttribute('data-rider-id');
        if (!riderId) {
          showNotification('Rider ID not found', 'error');
          return;
        }

        const isFavorited = this.classList.contains('is-favorited');
        const toggleFavoriteUrlTemplate = "{{ route('riders.toggleFavorite', ['id' => '__RID__']) }}";
        const toggleFavoriteUrl = toggleFavoriteUrlTemplate.replace('__RID__', riderId);

        fetch(toggleFavoriteUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          }
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            if (data.favorited) {
              this.classList.add('is-favorited');
              this.setAttribute('title', 'Remove from favorites');
            } else {
              this.classList.remove('is-favorited');
              this.setAttribute('title', 'Add to favorites');
            }
            showNotification(data.message, 'success');
          } else {
            showNotification(data.message || 'Failed to update favorite', 'error');
          }
        })
        .catch(error => {
          console.error('Favorite toggle error:', error);
          showNotification('An error occurred while updating favorite', 'error');
        });
      });
    }

    // Function to show notifications
    function showNotification(message, type) {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `notification notification-${type}`;
      notification.innerHTML = `
        <div class="notification-content">
          <i class="ti ti-${type === 'success' ? 'check' : 'x'}"></i>
          <span>${message}</span>
        </div>
      `;

      // Add styles
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
        max-width: 300px;
      `;

      // Add to page
      document.body.appendChild(notification);

      // Remove after 3 seconds
      setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 300);
      }, 3000);
    }

    // Add CSS for notifications
    const style = document.createElement('style');
    style.textContent = `
      @keyframes slideIn {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
      }
      @keyframes slideOut {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
      }
      .notification-content {
        display: flex;
        align-items: center;
        gap: 8px;
      }
    `;
    document.head.appendChild(style);

    // Add responsive navigation styles
    const navStyle = document.createElement('style');
    navStyle.textContent = `
      /* Responsive Navigation Styles */
      .nav-align-top {
        width: 100%;
        max-width: 100%;
      }
      
      .nav-align-top .card {
        width: 100%;
        max-width: 100%;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      
      .nav-align-top .card-body {
        padding: 0.75rem 1rem !important;
      }
      
      #mainNavigation {
        display: flex;
        flex-wrap: nowrap;
        overflow: hidden;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 0.25rem;
      }
      
      #mainNavigation .nav-item {
        flex-shrink: 0;
        white-space: nowrap;
        display: flex;
      }
      
      #mainNavigation .nav-link {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
      }
      
      .overflow-nav-item {
        display: flex;
        align-items: center;
      }
      
      .overflow-nav-item.active {
        background-color: var(--bs-primary);
        color: white;
      }
      
      .overflow-nav-item i {
        width: 16px;
        height: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }
      
      .permanent-action {
        border-top: 1px solid var(--bs-border-color);
        margin-top: 0.25rem;
        padding-top: 0.5rem;
      }
      
      .permanent-action:first-of-type {
        border-top: none;
        margin-top: 0;
        padding-top: 0.25rem;
      }
      
      /* Action items styling */
      .nav-action-item .nav-link {
        background-color: var(--bs-secondary-bg);
        border: 1px solid var(--bs-border-color);
        color: var(--bs-secondary-color);
        transition: all 0.2s ease;
      }
      
      .nav-action-item .nav-link:hover {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
        transform: translateY(-1px);
      }
      
      /* Let JavaScript handle responsive behavior dynamically */
      .nav-item {
        display: flex !important; /* Override any CSS hiding */
      }
      
      /* Dropdown styling */
      #actiondropdown {
        flex-shrink: 0 !important;
        border: 1px solid var(--bs-border-color);
        background: white;
        color: var(--bs-body-color);
        // display: none; /* Initially hidden */
        align-items: center;
        justify-content: center;
      }
      
      #actiondropdown:hover {
        background-color: var(--bs-light);
        border-color: var(--bs-primary);
      }
      
      .dropdown-menu {
        max-height: 400px;
        overflow-y: auto;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: 1px solid var(--bs-border-color);
        margin-top: 0.25rem;
        min-width: 180px;
      }
      
      /* Ensure dropdown stays within viewport */
      .dropdown-menu-end {
        right: 0 !important;
        left: auto !important;
      }
      
      /* Loading state for navigation */
      .nav-loading {
        opacity: 0.7;
        pointer-events: none;
      }
      
      /* Better spacing for icons in dropdown */
      .dropdown-item i {
        width: 20px;
        text-align: center;
      }
      
      /* Highlight active items in dropdown */
      .dropdown-item.active {
        background-color: var(--bs-primary) !important;
        color: white !important;
      }
      
      /* Make navigation more compact on smaller screens */
      @media (max-width: 768px) {
        .nav-align-top .card-body {
          padding: 0.5rem !important;
        }
        
        #mainNavigation .nav-link {
          padding: 0.25rem 0.5rem !important;
          font-size: 0.8rem;
        }
        
        #mainNavigation .nav-link i {
          font-size: 0.8rem !important;
          margin-right: 0.25rem !important;
        }
        
        .nav-action-item .nav-link {
          padding: 0.25rem 0.5rem !important;
          font-size: 0.75rem;
        }
        
        #actiondropdown {
          padding: 0.25rem 0.5rem !important;
        }
      }
      
      /* Extra small screens - only essential items */
      @media (max-width: 480px) {
        .nav-align-top .card-body {
          padding: 0.25rem 0.5rem !important;
        }
        
        #mainNavigation .nav-link {
          padding: 0.25rem 0.4rem !important;
          font-size: 0.75rem;
        }
        
        #mainNavigation .nav-link i {
          margin-right: 0.1rem !important;
        }
        
        .dropdown-menu {
          min-width: 160px;
          font-size: 0.8rem;
        }
      }
      
      /* Very small screens */
      @media (max-width: 380px) {
        #mainNavigation .nav-link {
          padding: 0.2rem 0.3rem !important;
          font-size: 0.7rem;
        }
        
        #mainNavigation .nav-link i {
          display: none; /* Hide icons on very small screens */
        }
        
        #actiondropdown {
          padding: 0.2rem 0.4rem !important;
        }
      }
      
      /* Ensure smooth transitions */
      .nav-item {
        transition: all 0.3s ease;
      }
      
      /* Visual separator between nav and action items */
      .nav-action-item:first-of-type {
        margin-left: 0.5rem;
        position: relative;
      }
      
      .nav-action-item:first-of-type::before {
        content: '';
        position: absolute;
        left: -0.25rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1px;
        height: 20px;
        background-color: var(--bs-border-color);
      }
      
      /* Improve dropdown visibility on mobile */
      @media (max-width: 576px) {
        .dropdown-menu {
          right: 0 !important;
          left: auto !important;
          min-width: 200px;
          font-size: 0.875rem;
        }
        
        .dropdown-item {
          padding: 0.5rem 1rem;
        }
        
        .dropdown-header {
          font-size: 0.75rem;
          padding: 0.25rem 1rem;
        }
      }
    `;
    document.head.appendChild(navStyle);
  });
</script>

@include('riders.action-buttons')
@include('riders._document_replacement_script')

@endsection