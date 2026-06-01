@extends('layouts.app')

@section('title', 'Dashboard')

@section('vendor-style')
<link rel="stylesheet" href="{{asset('assets/vendor/libs/apex-charts/apex-charts.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/swiper/swiper.css')}}" />
{{-- <link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-bs5/datatables.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.css')}}" />
<link rel="stylesheet" href="{{asset('assets/vendor/libs/datatables-checkboxes-jquery/datatables.checkboxes.css')}}" /> --}}
@endsection

@section('page-style')
<!-- Page -->
<link rel="stylesheet" href="{{asset('assets/vendor/css/pages/cards-advance.css')}}">
<style>
  .dashboard-cards-swiper {
    overflow: hidden;
    padding: 2px 2px 8px;
    height: 240px;
  }

  .dashboard-cards-swiper .swiper-slide {
    height: auto;
    box-sizing: border-box;
  }

  .dashboard-cards-swiper .swiper-button-next,
  .dashboard-cards-swiper .swiper-button-prev,
  .dashboard-cards-swiper .swiper-pagination {
    display: none !important;
  }

  .dashboard-doc-items {
    max-height: 200px;
    overflow-y: auto;
  }

  .dashboard-doc-module-list {
    max-height: 120px;
    overflow-y: auto;
  }

  @keyframes dashboard-doc-blink-warning {

    0%,
    100% {
      box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.45);
      border-color: #f59e0b;
    }

    50% {
      box-shadow: 0 0 0 10px rgba(245, 158, 11, 0);
      border-color: #d97706;
    }
  }

  @keyframes dashboard-doc-blink-danger {

    0%,
    100% {
      box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.45);
      border-color: #dc2626;
    }

    50% {
      box-shadow: 0 0 0 10px rgba(220, 38, 38, 0);
      border-color: #b91c1c;
    }
  }

  .dashboard-doc-alert {
    border: 2px solid transparent;
    transition: border-color 0.2s ease;
  }

  .dashboard-doc-alert-expiring.dashboard-doc-alert-active {
    animation: dashboard-doc-blink-warning 2s ease-in-out infinite;
    background: linear-gradient(135deg, rgba(245, 158, 11, 0.06) 0%, rgba(255, 255, 255, 1) 100%);
  }

  .dashboard-doc-alert-expired.dashboard-doc-alert-active {
    animation: dashboard-doc-blink-danger 2s ease-in-out infinite;
    background: linear-gradient(135deg, rgba(220, 38, 38, 0.06) 0%, rgba(255, 255, 255, 1) 100%);
  }
</style>
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
{{-- <script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
--}}@endsection

@section('page-script')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    var el = document.getElementById('dashboard-cards-swiper');
    if (!el || typeof Swiper === 'undefined') {
      return;
    }

    var slideCount = el.querySelectorAll('.swiper-slide').length;
    if (slideCount < 1) {
      return;
    }

    var swiper = new Swiper(el, {
      slidesPerView: 1.12,
      spaceBetween: 16,
      slidesPerGroup: 1,
      grabCursor: true,
      watchOverflow: true,
      resistanceRatio: 0.85,
      speed: 1000,
      rewind: slideCount > 1,
      autoplay: slideCount > 1 ? {
        delay: 1000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true,
        waitForTransition: true
      } : false,
      breakpoints: {
        576: {
          slidesPerView: 2,
          spaceBetween: 16
        },
        992: {
          slidesPerView: 3,
          spaceBetween: 20
        },
        1200: {
          slidesPerView: 4,
          spaceBetween: 24
        }
      }
    });

    if (swiper.autoplay && slideCount > 1) {
      swiper.autoplay.start();
    }
  });
</script>
@endsection

@section('content')

@php
$dashboardCards = $dashboardCards ?? [];
@endphp

@if(count($dashboardCards) > 0)
<div class="dashboard-cards-swiper swiper mb-4" id="dashboard-cards-swiper">
  <div class="swiper-wrapper">
    @foreach($dashboardCards as $card)
    <div class="swiper-slide">
      <div class="card card-border-shadow-primary">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3 pb-1 border-bottom">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti {{ $card['icon'] }} ti-md"></i></span>
            </div>
            <h5 class="mb-0 fw-semibold">{{ $card['label'] }}</h5>
          </div>
          <div class="row g-2">
            @php
            $showSecondaryStat = (($card['label_inactive'] ?? '') !== '');
            @endphp
            <div class="{{ $showSecondaryStat ? 'col-6' : 'col-12' }}">
              <a href="{{ $card['url_active'] }}" class="text-decoration-none d-block p-2 rounded bg-label-success bg-opacity-10 text-center">
                <span class="d-block small text-muted">{{ $card['label_active'] ?? __('Active') }}</span>
                <span class="fs-4 fw-bold text-success">{{ number_format($card['active']) }}</span>
              </a>
            </div>
            @if($showSecondaryStat)
            <div class="col-6">
              <a href="{{ $card['url_inactive'] }}" class="text-decoration-none d-block p-2 rounded bg-label-secondary bg-opacity-10 text-center">
                <span class="d-block small text-muted">{{ $card['label_inactive'] ?? __('Inactive') }}</span>
                <span class="fs-4 fw-bold text-secondary">{{ number_format($card['inactive']) }}</span>
              </a>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>
    @endforeach
  </div>
</div>
@else
<div class="row mb-4">
  <div class="col-12">
    <div class="alert alert-info mb-0">
      {{ __('No dashboard cards are enabled.') }}
      @auth
      @if(Route::has('settings-panel.module-settings.index'))
      <a href="{{ route('settings-panel.module-settings.index', ['company_slug' => request()->route('company_slug') ?? session('company_slug'), 'module' => 'dashboard']) }}">{{ __('Dashboard Settings') }}</a>
      @endif
      @endauth
    </div>
  </div>
</div>
@endif

@include('content._document_expiry_alerts', ['documentExpiryAlerts' => $documentExpiryAlerts ?? []])

@endsection