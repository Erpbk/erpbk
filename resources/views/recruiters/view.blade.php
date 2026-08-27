@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Recruiter Detail')

@section('content')
@include('partials.entity_profile_styles')
@php
  $recruiters = $recruiters ?? $recruiter ?? null;
  $isActive = (int) ($recruiters?->status ?? 0) === 1;
  $recruiterExpiry = isset($recruiters)
    ? \App\Support\EntityExpiry::countsFor('recruiter', $recruiters)
    : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
@endphp
<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    @isset($recruiters)
    <x-entity-profile-card
      icon="ti ti-users"
      :is-active="$isActive"
      :status-label="$isActive ? 'Active' : 'Inactive'"
      :name="$recruiters->name"
      :subtitle="$recruiters->email"
      :edit-url="route('recruiters.edit', $recruiters->id)"
      edit-title="Edit Recruiter Details"
    >
      <x-entity-profile-info-row icon="ti ti-phone" label="Phone" :value="$recruiters->contact_number" value-class="is-phone" />
      <x-entity-profile-info-row icon="ti ti-mail" label="Email" :value="$recruiters->email" />
      <x-entity-profile-info-row icon="ti ti-receipt" label="TRN" :value="$recruiters->tax_number" />
      <x-entity-profile-info-row icon="ti ti-map-pin" label="Address" :value="$recruiters->address" />
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($recruiters)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('recruiters.show')) active @endif" href="{{ route('recruiters.show', $recruiters->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $recruiterExpiry['info_expired'], 'expiringCount' => $recruiterExpiry['info_expiring']])
        </a>
      </li>
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(Route::is('recruiters.ledger')) active @endif" href="{{ route('recruiters.ledger', $recruiters->id) }}">
          <i class="ti ti-file ti-sm me-1_5"></i>Ledger
        </a>
      </li>
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(Route::is('recruiters.files')) active @endif" href="{{ route('recruiters.files', $recruiters->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Documents
          @include('riders._document_status_badges', ['expiredCount' => $recruiterExpiry['files_expired'], 'expiringCount' => $recruiterExpiry['files_expiring']])
        </a>
      </li>
      <li class="nav-item nav-priority-4">
        <a class="nav-link @if(Route::is('recruiters.riders')) active @endif" href="{{ route('recruiters.riders', $recruiters->id) }}">
          <i class="ti ti-motorbike ti-sm me-1_5"></i>Riders
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
