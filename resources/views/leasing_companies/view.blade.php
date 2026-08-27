@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Leasing Company Detail')

@section('content')
@include('partials.entity_profile_styles')
@php
  $leasingCompany = $leasingCompany ?? $leasingCompanies ?? null;
  $isActive = (int) ($leasingCompany?->status ?? 0) === 1;
  $entityExpiry = isset($leasingCompany)
    ? \App\Support\EntityExpiry::countsFor('leasing_company', $leasingCompany)
    : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
@endphp
<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    @isset($leasingCompany)
    <x-entity-profile-card
      icon="ti ti-building"
      :is-active="$isActive"
      :status-label="$isActive ? 'Active' : 'Inactive'"
      :name="$leasingCompany->name"
      :subtitle="$leasingCompany->contact_person"
      :edit-url="route('leasingCompanies.edit', $leasingCompany->id)"
      edit-title="Edit Leasing Company Details"
    >
      <x-entity-profile-info-row icon="ti ti-user" label="Contact Person" :value="$leasingCompany->contact_person" />
      <x-entity-profile-info-row icon="ti ti-phone" label="Phone" :value="$leasingCompany->contact_number" value-class="is-phone" />
      <x-entity-profile-info-row icon="ti ti-receipt" label="TRN" :value="$leasingCompany->trn_number" />
      <x-entity-profile-info-row icon="ti ti-notes" label="Details" :value="$leasingCompany->detail" />
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($leasingCompany)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('leasingCompanies.show')) active @endif" href="{{ route('leasingCompanies.show', $leasingCompany->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['info_expired'], 'expiringCount' => $entityExpiry['info_expiring']])
        </a>
      </li>
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(Route::is('leasingCompany.bikes')) active @endif" href="{{ route('leasingCompany.bikes', $leasingCompany->id) }}">
          <i class="ti ti-motorbike ti-sm me-1_5"></i>Bikes
        </a>
      </li>
      @can('leasing_companies_documents_view')
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(Route::is('leasingCompany.files')) active @endif" href="{{ route('leasingCompany.files', $leasingCompany->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Documents
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['files_expired'], 'expiringCount' => $entityExpiry['files_expiring']])
        </a>
      </li>
      @endcan
      @can('leasing_companies_ledger_view')
      <li class="nav-item nav-priority-4">
        <a class="nav-link @if(Route::is('leasingCompany.ledger')) active @endif" href="{{ route('leasingCompany.ledger', $leasingCompany->id) }}">
          <i class="ti ti-file ti-sm me-1_5"></i>Ledger
        </a>
      </li>
      @endcan
    </x-entity-profile-tabs>
    @endisset

    <div class="card entity-info-section mb-5" id="cardBody" style="margin-top: 12px; position: relative;">
      @yield('page_content')
    </div>
  </div>
</div>
@include('partials.entity_profile_nav_script')
@endsection
