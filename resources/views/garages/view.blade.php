@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Garage Detail')

@section('content')
@include('partials.entity_profile_styles')
@php
  $garages = $garages ?? $garage ?? null;
  $isActive = (int) ($garages?->status ?? 1) === 1;
  $garageExpiry = isset($garages)
    ? \App\Support\EntityExpiry::countsFor('garage', $garages)
    : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
  $garageType = (($garages?->garage_type ?? 'external') === 'internal') ? 'Internal' : 'External';
@endphp
<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    @isset($garages)
    <x-entity-profile-card
      icon="ti ti-building-warehouse"
      :is-active="$isActive"
      :status-label="$isActive ? 'Active' : 'Inactive'"
      :name="$garages->name"
      :subtitle="$garageType"
      :edit-url="route('garages.edit', $garages->id)"
      edit-title="Edit Garage Details"
    >
      <x-entity-profile-info-row icon="ti ti-user" label="Contact Person" :value="$garages->contact_person" />
      <x-entity-profile-info-row icon="ti ti-phone" label="Phone" :value="$garages->contact_number" value-class="is-phone" />
      <x-entity-profile-info-row icon="ti ti-map-pin" label="Address" :value="$garages->address" />
      <x-entity-profile-info-row icon="ti ti-notes" label="Detail" :value="$garages->detail" />
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($garages)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('garages.show')) active @endif" href="{{ route('garages.show', $garages->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $garageExpiry['info_expired'], 'expiringCount' => $garageExpiry['info_expiring']])
        </a>
      </li>
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(Route::is('garages.ledger')) active @endif" href="{{ route('garages.ledger', $garages->id) }}">
          <i class="ti ti-file ti-sm me-1_5"></i>Ledger
        </a>
      </li>
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(Route::is('garages.files')) active @endif" href="{{ route('garages.files', $garages->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Documents
          @include('riders._document_status_badges', ['expiredCount' => $garageExpiry['files_expired'], 'expiringCount' => $garageExpiry['files_expiring']])
        </a>
      </li>
    </x-entity-profile-tabs>
    @endisset

    <div class="card entity-info-section mb-5" id="cardBody" style="margin-top: 12px; position: relative;">
      @yield('page_content')
    </div>
  </div>
</div>
@include('partials.entity_profile_nav_script')
@endsection
