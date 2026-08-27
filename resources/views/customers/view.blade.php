@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Customer Detail')

@section('content')
@include('partials.entity_profile_styles')
@php
  $customer = $customer ?? $customers ?? null;
  $isActive = (int) ($customer?->status ?? 0) === 1;
  $entityExpiry = isset($customer)
    ? \App\Support\EntityExpiry::countsFor('customer', $customer)
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
      :subtitle="$customer->company_name"
      :edit-url="route('customers.edit', $customer->id)"
      edit-title="Edit Customer Details"
    >
      <x-entity-profile-info-row icon="ti ti-phone" label="Phone" :value="$customer->contact_number" value-class="is-phone" />
      <x-entity-profile-info-row icon="ti ti-mail" label="Email" :value="$customer->company_email" />
      <x-entity-profile-info-row icon="ti ti-building-warehouse" label="Company" :value="$customer->company_name" />
      <x-entity-profile-info-row icon="ti ti-receipt" label="TRN" :value="$customer->tax_number" />
      <x-entity-profile-info-row icon="ti ti-percentage" label="Tax %" :value="$customer->tax_percentage" />
      <x-entity-profile-info-row icon="ti ti-map-pin" label="Address" :value="$customer->address" />
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($customer)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('customers.show')) active @endif" href="{{ route('customers.show', $customer->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['info_expired'], 'expiringCount' => $entityExpiry['info_expiring']])
        </a>
      </li>
      @can('customers_documents_view')
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(Route::is('customer.files')) active @endif" href="{{ route('customer.files', $customer->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Documents
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['files_expired'], 'expiringCount' => $entityExpiry['files_expiring']])
        </a>
      </li>
      @endcan
      @can('customers_payments_view')
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(Route::is('customers.receipts')) active @endif" href="{{ route('customers.receipts', $customer->id) }}">
          <i class="ti ti-receipt ti-sm me-1_5"></i>Receipts
        </a>
      </li>
      <li class="nav-item nav-priority-4">
        <a class="nav-link @if(Route::is('customers.payments')) active @endif" href="{{ route('customers.payments', $customer->id) }}">
          <i class="ti ti-cash ti-sm me-1_5"></i>Payments
        </a>
      </li>
      @endcan
      @can('customers_invoices_view')
      <li class="nav-item nav-priority-5">
        <a class="nav-link @if(Route::is('customer.invoices')) active @endif" href="{{ route('customer.invoices', $customer->id) }}">
          <i class="ti ti-file-invoice ti-sm me-1_5"></i>Invoices
        </a>
      </li>
      @endcan
      <li class="nav-item nav-priority-6">
        <a class="nav-link @if(Route::is('customer.inventory')) active @endif" href="{{ route('customer.inventory', $customer->id) }}">
          <i class="ti ti-package ti-sm me-1_5"></i>Inventory
        </a>
      </li>
      <li class="nav-item nav-priority-7">
        <a class="nav-link @if(Route::is('customer.ledger')) active @endif" href="{{ route('customer.ledger', $customer->id) }}">
          <i class="ti ti-file ti-sm me-1_5"></i>Ledger
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
