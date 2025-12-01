@extends('layouts.app')
@if (request()->segment(1) == 'rtaFines')
@section('title','RTA Fines')
@endif
@section('content')
<div style="display: none;" class="loading-overlay" id="loading-overlay">
    <div class="spinner-border text-primary" role="status"></div>
</div>
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6 d-flex gap-2">
                @if(request()->segment(1) =='rtaFines')
                <a href="{{ route('rtaFines.index') }}" class=" @if(request()->segment(1) =='rtaFines') btn btn-primary @else btn btn-default @endif action-btn"><i class="fa fa-receipt"></i> RTA Fines</a>
                @endif
            </div>
            <div class="col-sm-6 text-end">
                @if(request()->segment(1) =='rtaFines')
                <a class="btn btn-primary action-btn show-modal"
                    href="javascript:void(0);" data-bs-toggle="modal" data-bs-target="#createaccount">
                    Add New RTA Fines Account
                </a>
                @endif
            </div>
            <div class="col-12 col-md-12 mt-3">
                <div class="card h-100">
                    <div class="card-body d-flex align-items-end">
                        <div class="w-100">
                            <div class="d-flex flex-wrap gap-3">
                                <!-- Total Accounts -->
                                <div class="d-flex align-items-center flex-grow-1 p-3 border rounded shadow-sm">
                                    <div class="badge rounded bg-primary me-3 p-3">
                                        <i class="ti ti-user fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            {{ $data->count() ?? 0 }}
                                        </h5>
                                        <small>Total Accounts</small>
                                    </div>
                                </div>
                                <!-- Total Ticket Amount -->
                                <div class="d-flex align-items-center flex-grow-1 p-3 border rounded shadow-sm">
                                    <div class="badge rounded bg-info me-3 p-3">
                                        <i class="ti ti-cash fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            @if(request()->segment(1) == 'rtaFines')
                                            {{ DB::table('rta_fines')->sum('amount') }}
                                            @endif
                                        </h5>
                                        <small>Total Ticket Amount</small>
                                    </div>
                                </div>
                                <!-- Total Service Charges -->
                                <div class="d-flex align-items-center flex-grow-1 p-3 border rounded shadow-sm">
                                    <div class="badge rounded bg-warning me-3 p-3">
                                        <i class="ti ti-cash fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            @if(request()->segment(1) == 'rtaFines')
                                            {{ DB::table('rta_fines')->sum('service_charges') }}
                                            @endif
                                        </h5>
                                        <small>Total Service Charges</small>
                                    </div>
                                </div>

                                <!-- Total Admin Charges -->
                                <div class="d-flex align-items-center flex-grow-1 p-3 border rounded shadow-sm">
                                    <div class="badge rounded bg-danger me-3 p-3">
                                        <i class="ti ti-cash fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            @if(request()->segment(1) == 'rtaFines')
                                            {{ DB::table('rta_fines')->sum('admin_fee') }}
                                            @endif
                                        </h5>
                                        <small>Total Admin Charges</small>
                                    </div>
                                </div>

                                <!-- Total Amount -->
                                <div class="d-flex align-items-center flex-grow-1 p-3 border rounded shadow-sm">
                                    <div class="badge rounded bg-secondary me-3 p-3">
                                        <i class="ti ti-cash fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            @if(request()->segment(1) == 'rtaFines')
                                            {{ DB::table('rta_fines')->sum('total_amount') }}

                                            @endif
                                        </h5>
                                        <small>Total Amount</small>
                                    </div>
                                </div>

                                <!-- Paid Fines -->
                                <div class="d-flex align-items-center flex-grow-1 p-3 border rounded shadow-sm">
                                    <div class="badge rounded bg-success me-3 p-3">
                                        <i class="ti ti-receipt fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            @if(request()->segment(1) == 'rtaFines')
                                            {{ DB::table('rta_fines')->where('status', 'paid')->count() }}
                                            @endif
                                        </h5>
                                        <small>Paid Fines</small>
                                    </div>
                                </div>

                                <!-- Unpaid Fines -->
                                <div class="d-flex align-items-center flex-grow-1 p-3 border rounded shadow-sm">
                                    <div class="badge rounded bg-dark me-3 p-3">
                                        <i class="ti ti-receipt fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <h5 class="mb-0">
                                            @if(request()->segment(1) == 'rtaFines')
                                            {{ DB::table('rta_fines')->where('status', 'unpaid')->count() }}
                                            @endif
                                        </h5>
                                        <small>Unpaid Fines</small>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@yield('page_content')
@endsection