@extends('layouts.app')
@section('title', 'Customer Detail')
@php
$statusColor = $customer->status == 1 ? 'success' : 'danger';
$statusText = $customer->status == 1 ? 'Active' : 'Inactive';
@endphp
@section('content')
<!-- Tabs Navigation -->
<div class="card shadow-sm border-0 mb-4">
  <div class="card-body p-3">
    <ul class="nav nav-pills nav-justified nav-pills-custom gap-2" role="tablist">
      <li class="nav-item" role="presentation">
        <a class="nav-link d-flex align-items-center justify-content-center py-3 info-link"
          href="javascript:void(0);">
          <i class="fas fa-building fa-lg me-2"></i>
          <span class="fw-semibold">Company Info</span>
        </a>
      </li>
      @can('customers_documents_view')
      <li class="nav-item" role="presentation">
        <a class="nav-link @if(Route::is('customer.files')) active @endif d-flex align-items-center justify-content-center py-3"
          href="{{ route('customer.files', $customer->id) }}">
          <i class="fas fa-file-upload fa-lg me-2"></i>
          <span class="fw-semibold">Documents</span>
        </a>
      </li>
      @endcan
      @can('customers_payments_view')
      <li class="nav-item" role="presentation">
        <a class="nav-link @if(Route::is('customers.receipts')) active @endif d-flex align-items-center justify-content-center py-3"
          href="{{ route('customers.receipts', $customer->id) }}">
          <i class="fa fa-receipt fa-lg me-2"></i>
          <span class="fw-semibold">Receipts</span>
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link @if(Route::is('customers.payments')) active @endif d-flex align-items-center justify-content-center py-3"
          href="{{ route('customers.payments', $customer->id) }}">
          <i class="fas fa-dollar-sign fa-lg me-2"></i>
          <span class="fw-semibold">Payments</span>
        </a>
      </li>
      @endcan
      @can('customers_invoices_view')
      <li class="nav-item" role="presentation">
        <a class="nav-link @if(Route::is(('customer.invoices'))) active @endif d-flex align-items-center justify-content-center py-3"
          href="{{ route('customer.invoices', $customer->id) }}">
          <i class="tf-icons ti ti-notes me-2"></i>
          <span class="fw-semibold">Invoices</span>
        </a>
      </li>
      @endcan
      <li class="nav-item" role="presentation">
        <a class="nav-link @if(Route::is('customer.inventory')) active @endif d-flex align-items-center justify-content-center py-3"
          href="{{ route('customer.inventory', $customer->id) }}">
          <i class="ti ti-package fa-lg me-2"></i>
          <span class="fw-semibold">Inventory</span>
        </a>
      </li>
      <li class="nav-item" role="presentation">
        <a class="nav-link @if(Route::is('customer.ledger')) active @endif d-flex align-items-center justify-content-center py-3"
          href="{{ route('customer.ledger', $customer->id) }}">
          <i class="fas fa-book fa-lg me-2"></i>
          <span class="fw-semibold">Ledger</span>
        </a>
      </li>
    </ul>
  </div>
</div>

<div class="row">
  <!-- Left Column - Customer Information -->
  <div class="col-md-4 d-none" id="company-info">
    <!-- Customer Profile Card -->
    <div class="card mb-4 shadow-sm border-0">
      <div class="card-header bg-gradient-primary text-white py-3">
        <div class="d-flex align-items-center">
          <div class="avatar avatar-lg bg-white rounded-circle p-2 me-3">
            <i class="fas fa-building fa-3x text-primary"></i>
          </div>
          <div>
            <h4 class="mb-0 text-white">{{ $customer->name }}</h4>
          </div>
          <a class="btn btn-primary btn-sm show-modal d-flex align-items-center justify-content-right ms-auto"
            data-title="Edit Customer Details"
            data-size="lg"
            data-action="{{ route('customers.edit', $customer->id) }}"
            href="javascript:void(0);">
            <i class="fas fa-edit me-2"></i>
            <span class="fw-semibold">Edit</span>
          </a>
        </div>
      </div>

      <!-- Quick Overview Section (Under Header) -->
      <div class="card-body py-4 border-bottom">
        <h6 class="text-muted mb-3 d-flex align-items-center">
          <i class="fas fa-chart-line me-2"></i>Quick Overview
        </h6>

        <!-- Current Balance (Prominent Display) -->
        <div class="text-center mb-4">
          <div class="p-4 rounded @if($details['balance'] >= 0) bg-opacity-10 border border-success border-opacity-25 @else border border-danger border-opacity-25 @endif">
            <p class="mb-1 text-muted small">Current Balance</p>
            <p class="mb-0 fw-bold @if($details['balance'] >= 0) text-success @else text-danger @endif" style="font-size: 1.5rem">
              {{ number_format($details['balance'], 2) }}
            </p>
            <small class="text-muted">As of {{ date('M d, Y') }}</small>
          </div>
        </div>

        <!-- Monthly Summary -->
        <div class="row g-3">
          <div class="col-6">
            <div class="p-2 rounded bg-opacity-10 border-start border-success border-3">
              <div class="d-flex align-items-center">
                <div class="bg-opacity-25 rounded-circle p-1 me-2">
                  <i class="fas fa-arrow-up text-warning fa-sm"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted small">Month Credit</p>
                  <p class="mb-0 fw-bold text-warning">{{ number_format($details['currentMonthCredit'], 2) }}</p>
                </div>
              </div>
            </div>
          </div>

          <div class="col-6">
            <div class="p-2 rounded bg-opacity-10 border-start border-warning border-3">
              <div class="d-flex align-items-center">
                <div class="bg-opacity-25 rounded-circle p-1 me-2">
                  <i class="fas fa-arrow-down text-success fa-sm"></i>
                </div>
                <div>
                  <p class="mb-1 text-muted small">Month Debit</p>
                  <p class="mb-0 fw-bold text-success">{{ number_format($details['currentMonthDebit'], 2) }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Net Flow -->
        <div class="mt-4 pt-3 border-top">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <span class="text-muted">
                <i class="fas fa-exchange-alt me-1"></i>Net Flow
              </span>
              <small class="d-block text-muted">{{ date('M Y') }}</small>
            </div>
            <span class="fw-bold fs-5 @if($details['netFlow'] >= 0) text-success @else text-danger @endif">
              {{ number_format($details['netFlow'], 2) }}
            </span>
          </div>

          <!-- Progress bar -->
          @if(($details['currentMonthCredit'] + $details['currentMonthDebit']) > 0)
          <div class="progress mt-3" style="height: 8px;">
            @php
            $creditPercentage = ($details['currentMonthCredit'] / ($details['currentMonthCredit'] + $details['currentMonthDebit'])) * 100;
            $debitPercentage = ($details['currentMonthDebit'] / ($details['currentMonthCredit'] + $details['currentMonthDebit'])) * 100;
            @endphp
            <div class="progress-bar bg-warning" role="progressbar"
              style="width: {{ $creditPercentage }}%"
              title="Credit: {{ number_format($details['currentMonthCredit'], 2) }}">
            </div>
            <div class="progress-bar bg-success" role="progressbar"
              style="width: {{ $debitPercentage }}%"
              title="Debit: {{ number_format($details['currentMonthDebit'], 2) }}">
            </div>
          </div>
          <div class="d-flex justify-content-between mt-2">
            <small class="text-warning">
              <i class="fas fa-circle fa-xs"></i> Credit ({{ round($creditPercentage, 1) }}%)
            </small>
            <small class="text-success">
              <i class="fas fa-circle fa-xs"></i> Debit ({{ round($debitPercentage, 1) }}%)
            </small>
          </div>
          @endif
        </div>
      </div>

      <!-- Customer Details Section (After Quick Overview) -->
      <div class="card-body pt-3">
        <!-- Status Badge -->
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h6 class="mb-0 text-muted small">Customer Status</h6>
          <span class="badge bg-label-{{ $statusColor }} bg-opacity-10 text-{{ $statusColor }} border border-{{ $statusColor }} border-opacity-25 py-1 px-2 rounded-pill">
            <i class="fas fa-circle fa-xs me-1"></i><span class="small">{{ $statusText }}</span>
          </span>
        </div>

        <!-- Customer Details -->
        <div class="company-details-card">
          <h6 class="section-title text-muted mb-2 pb-1 border-bottom small">
            <i class="fas fa-info-circle me-2 fa-sm"></i>Customer Information
          </h6>

          <div class="info-list">
            <div class="info-item d-flex align-items-center mb-2 p-2 rounded bg-light-hover">
              <div class="icon-container p-1 me-2">
                <i class="fas fa-user fa-sm text-info"></i>
              </div>
              <div class="flex-grow-1">
                <small class="text-muted d-block">Contact Person</small>
                <p class="mb-0 fw-semibold small">{{ $customer->name ?? '-' }}</p>
              </div>
            </div>

            <div class="info-item d-flex align-items-center mb-2 p-2 rounded bg-light-hover">
              <div class="icon-container p-1 me-2">
                <i class="fas fa-phone fa-sm text-success"></i>
              </div>
              <div class="flex-grow-1">
                <small class="text-muted d-block">Contact Number</small>
                <p class="mb-0 fw-semibold small">{{ $customer->contact_number ?? '-' }}</p>
              </div>
            </div>

            <div class="info-item d-flex align-items-center mb-2 p-2 rounded bg-light-hover">
              <div class="icon-container p-1 me-2">
                <i class="fas fa-receipt fa-sm text-warning"></i>
              </div>
              <div class="flex-grow-1">
                <small class="text-muted d-block">TRN Number</small>
                <p class="mb-0 fw-semibold small font-monospace">{{ $customer->tax_number ?? '-' }}</p>
              </div>
            </div>

            @if($customer->detail)
            <div class="info-item mb-2 p-2 rounded bg-light-hover">
              <div class="d-flex align-items-start">
                <div class="icon-container p-1 me-2">
                  <i class="fas fa-sticky-note fa-sm text-secondary"></i>
                </div>
                <div class="flex-grow-1">
                  <small class="text-muted d-block mb-1">Additional Details</small>
                  <p class="mb-0 text-muted small">{{ $customer->detail }}</p>
                </div>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Right Column - Tabs Content -->
  <div class="col-md-12 h-100" id="company-files">

    <!-- Tab Content Area -->
    <div id="cardBody">
      @yield('page_content')
    </div>
  </div>

  <style>
    .company-details-card {
      background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
      border-radius: 10px;
      padding: 1.5rem;
    }

    .info-item:hover {
      background-color: rgba(var(--bs-primary-rgb), 0.05);
      transform: translateY(-2px);
      transition: all 0.3s ease;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
    }

    .nav-pills-custom .nav-link {
      border-radius: 8px;
      margin: 0 5px;
      color: black;
      border: 1px solid #e9ecef;
      transition: all 0.3s ease;
    }

    .nav-pills-custom .nav-link.active {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      border: none;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
    }

    .nav-pills-custom .nav-link:not(.active):hover {
      background-color: #f8f9fa;
      border-color: #dee2e6;
    }

    .bg-gradient-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .btn-gradient {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      color: white;
      transition: all 0.3s ease;
    }

    .btn-gradient:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .icon-container {
      width: 40px;
      height: 40px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .section-title {
      position: relative;
      padding-left: 10px;
    }

    .section-title:before {
      content: '';
      position: absolute;
      left: 0;
      top: 50%;
      transform: translateY(-50%);
      width: 3px;
      height: 20px;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border-radius: 3px;
    }

    .bg-light-hover {
      background-color: rgba(248, 249, 250, 0.5);
    }
  </style>

  @endsection

  @push('third_party_scripts')
  <script>
    $(document).ready(function() {
      $('.info-link').on('click', function(e) {
        e.preventDefault();

        const $companyInfo = $('#company-info');
        const $companyFiles = $('#company-files');
        const $icon = $(this).find('i');

        // Toggle visibility
        $companyInfo.toggleClass('d-none');

        // Adjust layout
        if ($companyInfo.hasClass('d-none')) {
          // Hidden
          $companyFiles.removeClass('col-md-8').addClass('col-md-12');
          $icon.removeClass('fa-times').addClass('fa-building');
        } else {
          // Visible
          $companyFiles.removeClass('col-md-12').addClass('col-md-8');
          $icon.removeClass('fa-building').addClass('fa-times');
        }
      });
    });
  </script>
  @endpush