@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Vendor Detail')

@section('content')
@include('partials.entity_profile_styles')
@php
  $vendors = $vendors ?? $vendor ?? null;
  $isActive = (int) ($vendors?->status ?? 0) === 1;
  $entityExpiry = isset($vendors)
    ? \App\Support\EntityExpiry::countsFor('vendor', $vendors)
    : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
@endphp
<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    @isset($vendors)
    <x-entity-profile-card
      icon="ti ti-briefcase"
      :is-active="$isActive"
      :status-label="$isActive ? 'Active' : 'Inactive'"
      :name="$vendors->name"
      :subtitle="$vendors->email"
      :edit-url="route('vendors.edit', $vendors->id)"
      edit-title="Edit Vendor Details"
    >
      <x-entity-profile-info-row icon="ti ti-phone" label="Phone" :value="$vendors->contact_number" value-class="is-phone" />
      <x-entity-profile-info-row icon="ti ti-mail" label="Email" :value="$vendors->email" />
      <x-entity-profile-info-row icon="ti ti-receipt" label="TRN" :value="$vendors->tax_number" />
      <x-entity-profile-info-row icon="ti ti-map-pin" label="Address" :value="$vendors->address" />
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($vendors)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('vendors.show')) active @endif" href="{{ route('vendors.show', $vendors->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['info_expired'], 'expiringCount' => $entityExpiry['info_expiring']])
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
