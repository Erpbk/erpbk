@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Supplier Profile')

@section('content')
@include('partials.entity_profile_styles')
@php
  $isActive = isset($supplier) && ((int) ($supplier->status ?? 0) === 1 || $supplier->status === 'Active' || $supplier->status === true);
  $supplierPhone = isset($supplier) ? ($supplier->contact_number ?? $supplier->phone ?? null) : null;
  $entityExpiry = isset($supplier)
    ? \App\Support\EntityExpiry::countsFor('supplier', $supplier)
    : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
@endphp
<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    @isset($supplier)
    <x-entity-profile-card
      icon="ti ti-truck"
      :is-active="$isActive"
      :status-label="$isActive ? 'Active' : 'Inactive'"
      :name="$supplier->name"
      :subtitle="$supplier->email"
      :edit-url="route('suppliers.edit', $supplier->id)"
      edit-title="Edit Supplier Details"
    >
      <x-entity-profile-info-row icon="ti ti-phone" label="Phone" :value="$supplierPhone" value-class="is-phone" />
      <x-entity-profile-info-row icon="ti ti-mail" label="Email" :value="$supplier->email" />
      <x-entity-profile-info-row icon="ti ti-receipt" label="TRN" :value="$supplier->tax_number" />
      <x-entity-profile-info-row icon="ti ti-map-pin" label="Address" :value="$supplier->address" />
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($supplier)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('suppliers.show')) active @endif" href="{{ route('suppliers.show', $supplier->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['info_expired'], 'expiringCount' => $entityExpiry['info_expiring']])
        </a>
      </li>
      @can('suppliers_ledger_view')
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(Route::is('suppliers.ledger')) active @endif" href="{{ route('suppliers.ledger', $supplier->id) }}">
          <i class="ti ti-file ti-sm me-1_5"></i>Ledger
        </a>
      </li>
      @endcan
      @can('suppliers_documents_view')
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(Route::is('suppliers.document')) active @endif" href="{{ route('suppliers.document', $supplier->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Documents
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['files_expired'], 'expiringCount' => $entityExpiry['files_expiring']])
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
