@extends('layouts.app', ['hideModuleTopBarSlider' => true])
@section('title', 'Bank Detail')

@section('content')
@include('partials.entity_profile_styles')
@php
  $isActive = isset($banks) && (int) ($banks->status ?? 0) === 1;
  $bankBranch = isset($banks) ? ($banks->getAttributes()['branch'] ?? null) : null;
  $entityExpiry = isset($banks)
    ? \App\Support\EntityExpiry::countsFor('bank', $banks)
    : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
@endphp
<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    @isset($banks)
    <x-entity-profile-card
      icon="ti ti-building-bank"
      :is-active="$isActive"
      :status-label="$isActive ? 'Active' : 'Inactive'"
      :name="$banks->name"
      :subtitle="$banks->account_no"
      :edit-url="route('banks.edit', $banks->id)"
      edit-title="Edit Bank Details"
    >
      <x-entity-profile-info-row icon="ti ti-credit-card" label="Account Type" :value="$banks->account_type" />
      <x-entity-profile-info-row icon="ti ti-user" label="Account Title" :value="$banks->title" />
      <x-entity-profile-info-row icon="ti ti-hash" label="Account No" :value="$banks->account_no" />
      <x-entity-profile-info-row icon="ti ti-building" label="Branch" :value="$bankBranch" />
      <x-entity-profile-info-row icon="ti ti-world" label="IBAN" :value="$banks->iban" />
      <x-entity-profile-info-row icon="ti ti-code" label="SWIFT" :value="$banks->swift" />
      <x-entity-profile-info-row icon="ti ti-cash" label="Balance" :value="number_format((float) $banks->balance, 2)" />
    </x-entity-profile-card>
    @endisset
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    @isset($banks)
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(Route::is('banks.show')) active @endif" href="{{ route('banks.show', $banks->id) }}">
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['info_expired'], 'expiringCount' => $entityExpiry['info_expiring']])
        </a>
      </li>
      @can('cash_&_banks_banks_view')
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(Route::is('bank.files')) active @endif" href="{{ route('bank.files', $banks->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Documents
          @include('riders._document_status_badges', ['expiredCount' => $entityExpiry['files_expired'], 'expiringCount' => $entityExpiry['files_expiring']])
        </a>
      </li>
      @endcan
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(Route::is('bank.ledger')) active @endif" href="{{ route('bank.ledger', $banks->id) }}">
          <i class="ti ti-file ti-sm me-1_5"></i>Ledger
        </a>
      </li>
      <li class="nav-item nav-priority-4">
        <a class="nav-link @if(Route::is('banks.receipts')) active @endif" href="{{ route('banks.receipts', $banks->id) }}">
          <i class="ti ti-receipt ti-sm me-1_5"></i>Receipts
        </a>
      </li>
      <li class="nav-item nav-priority-5">
        <a class="nav-link @if(Route::is('banks.payments')) active @endif" href="{{ route('banks.payments', $banks->id) }}">
          <i class="ti ti-cash ti-sm me-1_5"></i>Payments
        </a>
      </li>
      <li class="nav-item nav-priority-6">
        <a class="nav-link @if(Route::is('banks.cheques')) active @endif" href="{{ route('banks.cheques', $banks->id) }}">
          <i class="ti ti-file-check ti-sm me-1_5"></i>Cheques
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
