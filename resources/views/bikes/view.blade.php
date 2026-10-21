@extends('layouts.app', ['hideModuleTopBarSlider' => true])

@section('title','Bikes')

@push('third_party_stylesheets')
<link href="https://fonts.googleapis.com/css2?family=Rockwell:wght@400;700&display=swap" rel="stylesheet">
<style>
  .bike-plate-wrap {
    display: flex;
    justify-content: center;
    margin: 0.75rem 0 0.35rem;
  }

  .number-plate {
    display: inline-block;
    padding: 8px 12px;
    border-radius: 6px;
    font-family: 'Rockwell', 'Rockwell Condensed', 'Bodoni MT', serif;
    font-weight: bold;
    text-align: center;
    min-width: 160px;
    max-width: 100%;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
    border: 1px solid #000;
    background: linear-gradient(to bottom, #fff, #f0f0f0);
    color: #000;
    position: relative;
    box-sizing: border-box;
  }

  .plate-arabic-corner,
  .plate-bike-code-corner {
    position: absolute;
    top: 5px;
    font-size: 0.85rem;
    font-weight: 600;
    line-height: 1;
    color: #000;
    max-width: 40%;
    word-break: break-word;
  }

  .plate-arabic-corner { left: 16px; text-align: left; }
  .plate-bike-code-corner { right: 16px; text-align: right; text-transform: uppercase; }

  .number-plate .plate-number {
    font-size: 1.35rem;
    letter-spacing: 1px;
    margin-top: 12px;
    margin-bottom: 4px;
    font-weight: 800;
    line-height: 1.2;
  }

  .number-plate .plate-emirate-english {
    font-size: 0.75rem;
    text-transform: uppercase;
    font-weight: 600;
    line-height: 1;
  }

  .bike-actions-compact {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 0.4rem;
    align-items: stretch;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #eef0f3;
  }

  .bike-actions-compact .btn,
  .bike-actions-compact .btn.btn-sm {
    width: 100%;
    min-width: 0;
    max-width: 100%;
    height: 100%;
    display: inline-flex !important;
    align-items: center;
    justify-content: center;
    gap: 0.25rem;
    padding: 0.3rem 0.45rem !important;
    font-size: 0.62rem !important;
    font-weight: 600 !important;
    line-height: 1.15 !important;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    border-radius: 0.35rem !important;
  }

  /* Odd last button spans full row so both columns stay aligned */
  .bike-actions-compact .btn:last-child:nth-child(odd) {
    grid-column: 1 / -1;
  }

  .bike-actions-compact .btn i {
    font-size: 0.72rem !important;
    margin: 0 !important;
    flex: 0 0 auto;
  }

  .bike-info-section.card {
    background: transparent;
    border: 0 !important;
    box-shadow: none !important;
  }

  .bike-info-section > .card,
  .bike-info-section .card.card-action {
    background: #fff !important;
    border: 1px solid #d8daf0 !important;
    border-top: 3px solid #e3e4f9 !important;
    border-radius: 0.9rem;
    box-shadow: 0 2px 14px rgba(16, 24, 40, 0.06);
    overflow: hidden;
  }

  /* Section titles like voucher "PAID TO" banner (same as rider view) */
  .bike-info-section .card-header,
  .bike-info-section .card.card-action > .card-header,
  .bike-info-section .card-action .card-header {
    background: #e3e4f9 !important;
    border-top: 0 !important;
    border-bottom: 1px solid #d8daf0 !important;
    border-radius: 0 !important;
    padding: 0.7rem 1.15rem !important;
    color: var(--company-brand-primary, #4338ca) !important;
    font-size: 0.72rem !important;
    margin: 0 !important;
  }

  .bike-info-section .card-header b,
  .bike-info-section .card-action-title,
  .bike-info-section h5.card-action-title {
    display: block !important;
    width: 100%;
    margin: 0 !important;
    font-weight: 700 !important;
    font-size: 0.72rem !important;
    line-height: 1.3 !important;
    letter-spacing: 0.45px !important;
    text-transform: uppercase !important;
    color: var(--company-brand-primary, #4338ca) !important;
  }

  .bike-info-section .card-header b::before,
  .bike-info-section .card-action-title::before {
    content: none !important;
    display: none !important;
  }

  .bike-info-section .bike-info-fields,
  .bike-info-section .card-body > .row {
    --bike-info-gap-x: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    align-items: stretch;
    gap: 0.85rem var(--bike-info-gap-x);
    margin-left: 0;
    margin-right: 0;
  }

  .bike-info-section .bike-info-field,
  .bike-info-section .card-body > .row > .form-group {
    flex: 1 1 calc((100% - (3 * var(--bike-info-gap-x))) / 4);
    width: auto;
    max-width: 100%;
    min-width: min(100%, max-content);
    margin: 0;
    padding-left: 0 !important;
    padding-right: 0 !important;
    box-sizing: border-box;
  }

  .bike-info-section .bike-info-field.is-full-row,
  .bike-info-section .card-body > .row > .form-group.col-md-12 {
    flex: 1 1 100%;
    width: 100%;
    min-width: 100%;
    max-width: 100%;
  }

  .bike-info-section .form-group label,
  .bike-info-section .bike-info-field label {
    display: block;
    font-size: 0.72rem;
    font-weight: 600 !important;
    color: #6b7280;
    margin-bottom: 0.28rem;
  }

  .bike-info-section .form-group > p,
  .bike-info-section .bike-info-field-value {
    display: block;
    width: 100%;
    min-width: max-content;
    max-width: 100%;
    margin: 0;
    padding: 0.5rem 0.75rem;
    background: #f4f7fb;
    border: 1px solid #e3ebf5;
    border-left: 3px solid #3b6fd9;
    border-radius: 0.55rem;
    font-size: 0.9rem;
    font-weight: 600;
    color: #1e293b;
    line-height: 1.35;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    box-sizing: border-box;
  }

  .bike-profile-tabs .nav-pills .nav-link {
    background: transparent !important;
    border-radius: 0;
    color: #6c757d;
    border-bottom: 3px solid transparent;
    box-shadow: none !important;
  }

  .bike-profile-tabs .nav-pills .nav-link.active {
    color: #1e4b8e !important;
    background: transparent !important;
    border-bottom-color: #1e4b8e;
  }

  @keyframes pulse {
    0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
    70% { transform: scale(1.02); box-shadow: 0 0 0 10px rgba(220, 38, 38, 0); }
    100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
  }

  .bike-expiry-badge {
    display: inline-block;
    padding: 0.2rem 0.55rem;
    border-radius: 999px;
    font-size: 0.78rem;
    font-weight: 700;
    color: #fff;
  }

  .bike-expiry-badge.is-expired {
    background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
    animation: pulse 1s infinite;
  }

  .bike-expiry-badge.is-expiring {
    background: linear-gradient(135deg, #f59e0b 0%, #fbbf24 100%);
    animation: pulse 1.5s infinite;
  }

  @media (max-width: 991.98px) {
    .bike-info-section .bike-info-field,
    .bike-info-section .card-body > .row > .form-group {
      flex-basis: calc((100% - var(--bike-info-gap-x)) / 2);
    }
  }

  @media (max-width: 575.98px) {
    .bike-info-section .bike-info-field,
    .bike-info-section .card-body > .row > .form-group {
      flex-basis: 100%;
      width: 100%;
      min-width: 100%;
    }

    .bike-actions-compact {
      grid-template-columns: 1fr;
    }

    .bike-actions-compact .btn:last-child:nth-child(odd) {
      grid-column: auto;
    }
  }
</style>
@endpush

@section('content')
@include('partials.entity_profile_styles')
<style>
  /* Page-level overrides so Vuexy card/btn styles cannot win */
  .bike-view-layout .entity-view-card--no-avatar {
    border-top: 3px solid #e3e4f9 !important;
  }
  .bike-view-layout .entity-view-card--no-avatar .entity-view-card-hero {
    background: #e3e4f9 !important;
    min-height: 0 !important;
    padding: 0.65rem 0.85rem 0.5rem !important;
  }
  .bike-view-layout .entity-view-card--no-avatar .entity-view-card-hero::before,
  .bike-view-layout .entity-view-card--no-avatar .entity-view-card-photo-wrap,
  .bike-view-layout .entity-view-card--no-avatar .entity-view-card-photo-icon {
    display: none !important;
  }
  .bike-view-layout .entity-view-card-section-title {
    background: #e3e4f9 !important;
    text-transform: uppercase !important;
    font-size: 0.72rem !important;
    letter-spacing: 0.45px !important;
  }
  .bike-info-section .card.card-action,
  .bike-info-section > .card {
    border: 1px solid #d8daf0 !important;
    border-top: 3px solid #e3e4f9 !important;
  }
  .bike-info-section .card-header {
    background: #e3e4f9 !important;
  }
  .bike-actions-compact {
    display: grid !important;
    grid-template-columns: repeat(2, minmax(0, 1fr)) !important;
    gap: 0.4rem !important;
  }
  .bike-actions-compact .btn {
    width: 100% !important;
    padding: 0.3rem 0.45rem !important;
    font-size: 0.62rem !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
  }

  .bike-info-section .bike-category-header,
  .bike-info-section .card-header.bike-category-header {
    display: flex !important;
    align-items: center !important;
    justify-content: space-between !important;
    flex-wrap: nowrap !important;
    gap: 0.5rem !important;
    background: #e3e4f9 !important;
    border-bottom: 1px solid #d8daf0 !important;
    padding: 0.45rem 0.85rem !important;
    min-height: 2.1rem;
  }

  .bike-info-section .bike-category-header b {
    margin: 0 !important;
    flex: 1 1 auto;
    min-width: 0;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .bike-category-header-actions {
    display: inline-flex;
    align-items: center;
    flex: 0 0 auto;
    margin-left: auto;
  }

  .bike-header-btn,
  .bike-info-section .bike-header-btn.btn,
  .bike-info-section .bike-header-btn.btn-sm {
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 0.2rem;
    padding: 0.15rem 0.4rem !important;
    font-size: 0.62rem !important;
    font-weight: 600 !important;
    line-height: 1.15 !important;
    border-radius: 0.3rem !important;
    min-height: 0 !important;
    white-space: nowrap;
  }

  .bike-header-btn i {
    font-size: 0.7rem !important;
    margin: 0 !important;
  }

  .bike-header-muted {
    font-size: 0.62rem;
    color: #64748b;
    white-space: nowrap;
  }
</style>
@php
  $companySlug = request()->route('company_slug');
  $emirateCode = strtolower(trim($bikes->emirates ?? ''));
  $plateNumber = $bikes->plate ?? '00000';
  $bikeCode = $bikes->bike_code ?? 'N/A';
  $emiratesData = [
    'dxb' => ['arabic' => 'دبي', 'english' => 'DUBAI'],
    'auh' => ['arabic' => 'أبوظبي', 'english' => 'ABU DHABI'],
    'shj' => ['arabic' => 'الشارقة', 'english' => 'SHARJAH'],
    'rak' => ['arabic' => 'رأس الخــيمة', 'english' => 'RAS AL KHAIMAH'],
    'fuj' => ['arabic' => 'الفجيرة', 'english' => 'FUJAIRAH'],
    'ajm' => ['arabic' => 'عجمان', 'english' => 'AJMAN'],
    'uaq' => ['arabic' => 'أم القيوين', 'english' => 'UMM AL QUWAIN'],
    '-' => ['arabic' => 'غير محدد', 'english' => 'UNSPECIFIED'],
  ];
  $currentEmirate = $emiratesData[$emirateCode] ?? $emiratesData['-'];
  $roadStatus = $bikes->road_status ?? ['label' => 'Unknown', 'class' => 'road-offroad', 'days' => null, 'title' => ''];
  $roadLabel = (string) ($roadStatus['label'] ?? 'Unknown');
  $roadLower = strtolower($roadLabel);
  $isOnRoad = str_contains($roadLower, 'on road') || $roadLower === 'active';
  $statusClass = $isOnRoad ? '' : (str_contains($roadLower, 'vacation') ? 'is-vacation' : 'is-inactive');

  $rider = $bikes->rider;
  $company = $bikes->rentalCompany;
  $assigneeName = $rider->name ?? $company->name ?? 'Not Assigned';
  if ($rider) {
    $assigneeHtml = '<a href="' . e(route('riders.show', $rider->id)) . '">' . e($assigneeName) . '</a>';
  } elseif ($company) {
    $assigneeRoute = ($company->customer_type ?? '') === 'garage'
      ? route('garage_customer.bikes', $company->id)
      : route('bikeRentCompanies.bikes', $company->id);
    $assigneeHtml = '<a href="' . e($assigneeRoute) . '">' . e($assigneeName) . '</a>';
  } else {
    $assigneeHtml = e($assigneeName);
  }

  $branchValue = $bikes->branch
    ? trim($bikes->branch->name . ' ( ' . $bikes->branch->code . ' )')
    : null;
  $leasingValue = $bikes->leasingCompany->name ?? null;

  $expiryRaw = $bikes->expiry_date ?? null;
  $expiryDate = $expiryRaw ? \Carbon\Carbon::parse($expiryRaw)->format('d M Y') : null;
  $isExpired = false;
  $isExpiring = false;
  if ($expiryRaw) {
    $expiry = \Carbon\Carbon::parse($expiryRaw)->startOfDay();
    $now = \Carbon\Carbon::now()->startOfDay();
    if ($expiry->lt($now)) {
      $isExpired = true;
    } elseif ($expiry->diffInDays($now) <= 30) {
      $isExpiring = true;
    }
  }
  if ($expiryDate && $isExpired) {
    $expiryHtml = '<span class="bike-expiry-badge is-expired">' . e($expiryDate) . '</span>';
  } elseif ($expiryDate && $isExpiring) {
    $expiryHtml = '<span class="bike-expiry-badge is-expiring">' . e($expiryDate) . ' (SOON!)</span>';
  } else {
    $expiryHtml = e($expiryDate ?: 'N/A');
  }

  $bikeWarehouse = (string) ($bikes->warehouse ?? '');
  $warehousesHideLeasingReturnBtn = ['Active', 'Absconded', 'Theft', 'Total Loss'];
  $leasedReturnCompleted = \Illuminate\Support\Facades\Schema::hasColumn('bikes', 'leased_return_date')
    && !empty($bikes->leased_return_date);
  $showLeasingReturnToCompany = !empty($bikes->company)
    && \Illuminate\Support\Facades\Schema::hasColumn('bikes', 'leased_return_by')
    && !in_array($bikeWarehouse, $warehousesHideLeasingReturnBtn, true)
    && !$leasedReturnCompleted;
  $hideAssignForWarehouse = ['Total Loss'];
  $showAssignRiderBlock = !in_array($bikeWarehouse, $hideAssignForWarehouse, true);
  $showChangeProject = !empty($bikes->rider_id)
    && strtolower($bikeWarehouse) === 'active'
    && !$leasedReturnCompleted;

  $leasingReturnHtml = null;
  if (\Illuminate\Support\Facades\Schema::hasColumn('bikes', 'leased_return_by')) {
    $lrDisplay = $bikes->leasedReturnDisplay();
    $lrByFmt = $bikes->leased_return_by ? \Carbon\Carbon::parse($bikes->leased_return_by)->format('d M Y') : null;
    $lrDoneFmt = $bikes->leased_return_date ? \Carbon\Carbon::parse($bikes->leased_return_date)->format('d M Y') : null;
    $parts = ['<span class="badge ' . e($lrDisplay['badge']) . '">' . e($lrDisplay['label']) . '</span>'];
    if (\Illuminate\Support\Facades\Schema::hasColumn('bikes', 'leased_return_company_id') && $bikes->leased_return_company_id && $bikes->leasedReturnCompany) {
      $parts[] = '<div>Return to: ' . e($bikes->leasedReturnCompany->name) . '</div>';
    }
    if ($lrByFmt) {
      $parts[] = '<div>Return by: ' . e($lrByFmt) . '</div>';
    }
    if ($lrDoneFmt) {
      $parts[] = '<div>Returned: ' . e($lrDoneFmt) . '</div>';
    }
    if (!$lrByFmt && !$lrDoneFmt) {
      $parts[] = '<span class="text-muted">No dates recorded</span>';
    }
    $leasingReturnHtml = implode('', $parts);
  }
@endphp

<div class="row mb-5 bike-view-layout">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1">
    <x-entity-profile-card
      icon="ti ti-motorbike"
      :show-avatar="false"
      :is-active="$isOnRoad"
      :status-label="$roadLabel"
      :status-class="$statusClass"
      :name="$bikes->model_type ?? ($bikes->plate ?? 'Vehicle')"
      :subtitle="$bikeCode !== 'N/A' ? $bikeCode : ($bikes->plate ?? null)"
      :edit-href="user_can('bikes_bike_edit') ? route('bikes.edit', $bikes->id) : null"
      edit-title="Edit Vehicle Details"
      section-title="Basic Information">
      <x-slot name="statusExtra">
        @if(($roadStatus['days'] ?? null) !== null)
          <small class="entity-view-card-id" title="Days since status change">
            {{ (int) $roadStatus['days'] }} {{ (int) $roadStatus['days'] === 1 ? 'day' : 'days' }}
          </small>
        @endif
      </x-slot>

      <x-slot name="afterHero">
        <div class="bike-plate-wrap">
          <div class="number-plate">
            @if($currentEmirate)
              <div class="plate-arabic-corner">{{ $currentEmirate['arabic'] }}</div>
            @endif
            @if($bikeCode !== 'N/A')
              <div class="plate-bike-code-corner">{{ $bikeCode }}</div>
            @endif
            <div class="plate-number">{{ $plateNumber }}</div>
            @if($currentEmirate)
              <div class="plate-emirate-english">{{ $currentEmirate['english'] }}</div>
            @endif
          </div>
        </div>
        <div class="text-center mb-2">
          @include('bikes.partials.road_status_badge', ['bike' => $bikes])
        </div>
      </x-slot>

      <x-entity-profile-info-row icon="ti ti-building" label="Branch" :value="$branchValue" />
      <x-entity-profile-info-row icon="ti ti-user" label="Driver / Customer" :value="$assigneeHtml" :html="true" />
      <x-entity-profile-info-row icon="ti ti-building-community" label="Leasing Company" :value="$leasingValue" />
      <x-entity-profile-info-row icon="ti ti-calendar-stats" label="Vehicle Expiry" :value="$expiryHtml" :html="true" />
      @if($leasingReturnHtml)
        <x-entity-profile-info-row icon="ti ti-arrow-back-up" label="Leasing return" :value="$leasingReturnHtml" :html="true" />
      @endif

      <x-slot name="footer">
        <div class="bike-actions-compact">
          @can('bikes_bike_edit')
            <a href="{{ route('bikes.edit', $bikes->id) }}"
              class="btn btn-primary btn-sm show-modal"
              data-title="Edit Vehicle #{{ $bikes->plate }}">
              <i class="ti ti-edit"></i><span>Edit</span>
            </a>
          @endcan
          @canany(['bikes_assign_edit', 'bikes_assign_create', 'bikes_bike_edit'])
            @if($showLeasingReturnToCompany)
              <a href="javascript:void(0);"
                class="btn btn-primary btn-sm show-modal"
                data-size="lg"
                data-title="Return to leasing company — {{ $bikes->plate }}"
                data-action="{{ route('bikes.leasingReturn', $bikes->id) }}">
                <i class="ti ti-building"></i><span>Return</span>
              </a>
            @endif
          @endcanany
          @canany(['bikes_assign_create', 'bikes_assign_edit'])
            @if($showAssignRiderBlock)
              @if($bikes->rider_id || $bikes->rental_company_id)
                <a href="javascript:void(0);"
                  class="btn btn-warning btn-sm show-modal"
                  data-size="xl"
                  data-title="Assigned Rider Details"
                  data-action="{{ route('bikes.assignrider', $bikes->id) }}">
                  <i class="ti ti-user-check"></i><span>Status</span>
                </a>
                @if($showChangeProject)
                  <a href="javascript:void(0);"
                    class="btn btn-primary btn-sm show-modal"
                    data-size="md"
                    data-title="Change Project — {{ $bikes->plate }}"
                    data-action="{{ route('bikes.change_project', $bikes->id) }}">
                    <i class="ti ti-arrows-exchange"></i><span>Project</span>
                  </a>
                @endif
              @else
                <a href="javascript:void(0);"
                  class="btn btn-success btn-sm show-modal"
                  data-size="xl"
                  data-title="Assign Rider to Vehicle #{{ $bikes->plate }}"
                  data-action="{{ route('bikes.assign_rider', $bikes->id) }}">
                  <i class="ti ti-user-plus"></i><span>Assign</span>
                </a>
              @endif
            @endif
          @endcanany
        </div>
      </x-slot>
    </x-entity-profile-card>
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-2 position-relative">
    <div class="nav-align-top bike-profile-tabs entity-profile-tabs mb-3 mb-md-4">
      <div class="card">
        <div class="card-body p-2">
          <ul class="nav nav-pills flex-nowrap mb-0 overflow-auto" id="mainNavigation">
            <li class="nav-item">
              <a class="nav-link @if(Route::is('bikes.show')) active @endif" href="{{ route('bikes.show', $bikes->id) }}">
                <i class="ti ti-motorbike ti-sm me-1_5"></i>Vehicle
              </a>
            </li>
            @can('bikes_assign_view')
            <li class="nav-item">
              <a href="{{ route('bikeHistories.index', ['bike_id' => $bikes->id]) }}" class="nav-link @if(Route::is('bikeHistories.index')) active @endif">
                <i class="ti ti-history ti-sm me-1_5"></i>History
              </a>
            </li>
            @endcan
            @can('bikes_documents_view')
            <li class="nav-item">
              <a href="{{ route('bikes.files', $bikes->id) }}" class="nav-link @if(Route::is('bikes.files')) active @endif">
                <i class="ti ti-file-upload ti-sm me-1_5"></i>Files
              </a>
            </li>
            @endcan
            @can('bikes_maintenance_view')
            <li class="nav-item">
              <a href="{{ route('bikes.maintenance', $bikes->id) }}" class="nav-link @if(Route::is('bikes.maintenance')) active @endif">
                <i class="ti ti-tool ti-sm me-1_5"></i>Maintenance
              </a>
            </li>
            @endcan
            @can('bikes_bike_view')
            <li class="nav-item">
              <a href="{{ route('bikes.fines', $bikes->id) }}" class="nav-link @if(Route::is('bikes.fines')) active @endif">
                <i class="ti ti-receipt ti-sm me-1_5"></i>Fines
              </a>
            </li>
            <li class="nav-item">
              <a href="{{ route('bikes.saliks', $bikes->id) }}" class="nav-link @if(Route::is('bikes.saliks')) active @endif">
                <i class="ti ti-road ti-sm me-1_5"></i>Saliks
              </a>
            </li>
            @endcan
            @can('bikes_registration_view')
            @php
              $bikeRegistrationExpenseAccountId = \App\Models\BikeRegistrationAccount::where('bike_id', $bikes->id)->value('id');
            @endphp
            @if($bikeRegistrationExpenseAccountId)
            <li class="nav-item">
              <a href="{{ route('BikeRegistration.generatentries', $bikeRegistrationExpenseAccountId) }}" class="nav-link @if(Route::is('BikeRegistration.generatentries')) active @endif">
                <i class="ti ti-id ti-sm me-1_5"></i>Registration
              </a>
            </li>
            @endif
            @endcan
          </ul>
        </div>
      </div>
    </div>

    <div class="card bike-info-section mb-5" id="cardBody" style="margin-top: 0; position: relative;">
      @yield('page_content')
    </div>
  </div>
</div>
@endsection
