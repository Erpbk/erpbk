@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Customer Detail')

@section('content')
@include('partials.entity_profile_styles')
@php
  $customer = $customer ?? $bikeRentCompany ?? null;
  $isActive = (int) ($customer?->status ?? 0) === 1;
  $isBikeRental = ($customer?->customer_type ?? null) === 'bike_rental';
  $editUrl = $customer ? route('bikeRentCompanies.edit', $customer->id) : null;
  $fileType = $isBikeRental ? 'rentCompany' : 'garage';
  $entityExpiry = isset($customer)
    ? \App\Support\EntityExpiry::countsFor($fileType, $customer)
    : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
@endphp
<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    @isset($customer)
    <x-entity-profile-card
      icon="ti ti-building"
      :is-active="$isActive"
      :status-label="$isActive ? 'Active' : 'Inactive'"
      :name="$customer->name"
      :subtitle="$isBikeRental ? (($customer->party_type ?? '') === 'individual' ? 'Individual' : 'Company') : null"
      :edit-url="$editUrl"
      edit-title="Edit Customer Details"
    >
      <x-entity-profile-info-row icon="ti ti-phone" label="Contact" :value="$customer->company_contact" value-class="is-phone" />
      <x-entity-profile-info-row icon="ti ti-mail" label="Email" :value="$customer->email" />
      <x-entity-profile-info-row icon="ti ti-map-pin" label="Address" :value="$customer->address" />
      @if(($customer->party_type ?? null) === 'individual')
        <x-entity-profile-info-row icon="ti ti-id" label="Emirates ID" :value="$customer->emirates_id" />
        <x-entity-profile-info-row icon="ti ti-flag" label="Nationality" :value="$customer->nationality" />
        <x-entity-profile-info-row icon="ti ti-id-badge" label="License" :value="$customer->license_no" />
      @endif
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($customer)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('bikeRentCompanies.show')) active @endif" href="{{ route('bikeRentCompanies.show', $customer->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['info_expired'], 'expiringCount' => $entityExpiry['info_expiring']])
        </a>
      </li>
      @canany(['bike_on_rent_documents_view', 'garages_documents_view'])
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(Route::is('bikeRentCompanies.files') || Route::is('garage_customer.files')) active @endif"
           href="{{ $isBikeRental ? route('bikeRentCompanies.files', $customer->id) : route('garage_customer.files', $customer->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Documents
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['files_expired'], 'expiringCount' => $entityExpiry['files_expiring']])
        </a>
      </li>
      @endcanany
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(Route::is('bikeRentCompanies.bikes') || Route::is('garage_customer.bikes')) active @endif"
           href="{{ $isBikeRental ? route('bikeRentCompanies.bikes', $customer->id) : route('garage_customer.bikes', $customer->id) }}">
          <i class="ti ti-motorbike ti-sm me-1_5"></i>Vehicles
        </a>
      </li>
      @canany(['bike_on_rent_payments_view', 'garages_payments_view'])
      <li class="nav-item nav-priority-4">
        <a class="nav-link @if(Route::is('bikeRentCompanies.receipts') || Route::is('garage_customer.receipts')) active @endif"
           href="{{ $isBikeRental ? route('bikeRentCompanies.receipts', $customer->id) : route('garage_customer.receipts', $customer->id) }}">
          <i class="ti ti-receipt ti-sm me-1_5"></i>Receipts
        </a>
      </li>
      @endcanany
      @can('bike_on_rent_invoices_view')
      @if($isBikeRental)
      <li class="nav-item nav-priority-5">
        <a class="nav-link @if(Route::is('bikeRentCompanies.invoices')) active @endif" href="{{ route('bikeRentCompanies.invoices', $customer->id) }}">
          <i class="ti ti-file-invoice ti-sm me-1_5"></i>Invoices
        </a>
      </li>
      @endif
      @endcan
      @canany(['bike_on_rent_maintenance_view', 'garages_maintenance_view'])
      <li class="nav-item nav-priority-6">
        <a class="nav-link @if(Route::is('garage_customer.maintenances')) active @endif" href="{{ route('garage_customer.maintenances', $customer->id) }}">
          <i class="ti ti-tool ti-sm me-1_5"></i>Maintenances
        </a>
      </li>
      @endcanany
      @canany(['bike_on_rent_ledger_view', 'garages_ledger_view'])
      <li class="nav-item nav-priority-7">
        <a class="nav-link @if(Route::is('bikeRentCompanies.ledger') || Route::is('garage_customer.ledger')) active @endif"
           href="{{ $isBikeRental ? route('bikeRentCompanies.ledger', $customer->id) : route('garage_customer.ledger', $customer->id) }}">
          <i class="ti ti-file ti-sm me-1_5"></i>Ledger
        </a>
      </li>
      @endcanany
    </x-entity-profile-tabs>
    @endisset

    <div class="card entity-info-section mb-5" id="cardBody" style="margin-top: 12px; position: relative;">
      @yield('page_content')
    </div>
  </div>
</div>
@include('partials.entity_profile_nav_script')
@endsection
