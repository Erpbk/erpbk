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
@endsection

@section('vendor-script')
<script src="{{asset('assets/vendor/libs/apex-charts/apexcharts.js')}}"></script>
<script src="{{asset('assets/vendor/libs/swiper/swiper.js')}}"></script>
{{-- <script src="{{asset('assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js')}}"></script>
--}}@endsection

@section('page-script')
<script src="{{asset('assets/js/dashboards-analytics.js')}}"></script>
<script>
  window.chartData = {
    pie: {
      labels: @json($pieData['labels']),
      values: @json($pieData['data']),
      colors: @json($pieData['colors']),
    },
    line: {
      labels: @json($lineData['x']),
      values: @json($lineData['y']),
    }
  };
</script>
<script src="{{ asset('assets/js/barchat.js') }}"></script>
@endsection

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.9.4/Chart.js"></script>

@section('content')

<div class="row">
  @forelse(($dashboardCards ?? []) as $card)
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary h-100">
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
        <a href="{{ $card['url_all'] }}" class="btn btn-sm btn-outline-primary w-100 mt-3">{{ __('Open module') }}</a>
      </div>
    </div>
  </div>
  @empty
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
  @endforelse
</div>
<div class="row">
  <div class="col-md-6">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <canvas id="myChart" style="width:100%;max-width:600px"></canvas>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <canvas id="newChart" style="width:100%;max-width:600px"></canvas>
      </div>
    </div>
  </div>
</div>
@endsection