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
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="{{ route('vendors.index') }}">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-user-star ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ DB::table('vendors')->where('status' , 1)->get()->count() }}</h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Active Vendors</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="{{ route('vendors.index') }}">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-user-star ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ DB::table('vendors')->where('status' , 2)->get()->count() }}</h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">In Active Vendors</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="{{ route('riders.index') }}">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-user-star ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ DB::table('riders')->get()->count() }}</h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Riders</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="{{ route('bikes.index') }}">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-motorbike ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ DB::table('bikes')->get()->count() }}</h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Bikes</p>
          </a>
        </a>
      </div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3 mb-4">
    <div class="card card-border-shadow-primary">
      <div class="card-body">
        <a href="{{ route('sims.index') }}">
          <div class="d-flex align-items-center mb-2 pb-1">
            <div class="avatar me-2">
              <span class="avatar-initial rounded bg-label-primary"><i class=" ti ti-device-sim ti-md"></i></span>
            </div>
            <h4 class="ms-1 mb-0">{{ DB::table('sims')->get()->count() }}</h4>
          </div>
          <a href="" class="text-dark">
            <p class="mb-1">Sims</p>
          </a>
        </a>
      </div>
    </div>
  </div>
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