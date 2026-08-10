@extends('layouts.app')

@section('title','Bike Registration Detail')
@section('content')
@php
$rider = ($accounts && $accounts->rider_id) ? company_table('riders')->where('id', $accounts->rider_id)->first() : null;
$riderBalance = $accounts ? company_table('bike_registrations')->where('bike_registration_account_id', $accounts->id)->sum('amount') : 0;
$riderPaidTotal = $accounts ? company_table('bike_registrations')->where('bike_registration_account_id', $accounts->id)->where('payment_status', 'paid')->sum('amount') : 0;
$riderUnpaidTotal = $accounts ? company_table('bike_registrations')->where('bike_registration_account_id', $accounts->id)->where('payment_status', 'unpaid')->sum('amount') : 0;
@endphp
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Registration — {{ $data->registration_status }}</h3>
            </div>
        </div>
    </div>
</section>

<div class="content px-3">
    @include('flash::message')
    <div class="clearfix"></div>
    <div class="row">
        <div class="col-xl-3 col-md-3 col-lg-5 order-1 order-md-0">
            <div class="card mb-6">
                <div class="card-body pt-12">
                    <div class="user-avatar-section">
                        <div class=" d-flex align-items-center flex-column">
                            <div class="user-info text-center">
                                <h6>@if($rider){{ $rider->rider_id }} — {{ $rider->name }}@elseif($accounts){{ $accounts->name }}@else—@endif</h6>
                            </div>
                        </div>
                    </div>
                    <h5 class="pb-4 border-bottom mb-4"></h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Status:</b>
                                <span>
                                    @if($rider)
                                    @if($rider->status == '1')
                                    <span class="badge bg-success">Active</span>
                                    @else
                                    <span class="badge bg-danger">Inactive</span>
                                    @endif
                                    @else
                                    <span class="text-muted">—</span>
                                    @endif
                                </span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Rider ID:</b>
                                <span>{{ optional($rider)->rider_id ?? '—' }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Person Code:</b>
                                <span>{{ optional($rider)->person_code ?? '—' }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Labour Card:</b>
                                <span>{{ optional($rider)->labor_card_number ?? '—' }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Policy #:</b>
                                <span>{{ optional($rider)->policy_no ?? '—' }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Registration Balance:</b>
                                <span>{{ \App\Helpers\Currency::format($riderBalance, 2) }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Paid Total:</b>
                                <span class="text-success">{{ \App\Helpers\Currency::format($riderPaidTotal, 2) }}</span>
                            </li>
                            <li class="list-group-item pb-1 d-flex justify-content-between">
                                <b>Unpaid Total:</b>
                                <span class="text-danger">{{ \App\Helpers\Currency::format($riderUnpaidTotal, 2) }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-9 col-md-9 col-lg-7 order-0 order-md-1">
            <div class="nav-align-top">
                <ul class="nav nav-pills flex-column flex-md-row flex-wrap mb-3 row-gap-2">
                    <li class="nav-item"><a class="nav-link  active  " href="javascript:void(0)"><i class="ti ti-file-upload ti-sm me-1_5"></i>Files</a></li>
                </ul>
            </div>
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <tr>
                                    <th>Registration Status</th>
                                    <td class="text-end">{{ $data->registration_status }}</td>
                                </tr>
                                <tr>
                                    <th>Rider name</th>
                                    <td class="text-end">{{ $rider->name ?? ($accounts->name ?? '—') }}</td>
                                </tr>
                                <tr>
                                    <th>Date</th>
                                    <td class="text-end">{{ $data->date }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td class="text-end">{{ \App\Helpers\Currency::format($data->amount , 2) }}</td>
                                </tr>
                                @if($data->payment_status == 'paid')
                                <tr>
                                    <th>Paid By</th>
                                    <td class="text-end">{{ company_table('accounts')->where('id' , $data->pay_account)->first()->name ?? '-' }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <th></th>
                                    @if($data->payment_status == 'paid')
                                    <td class="text-end"><a href="javascript:void(0);" class="btn btn-action btn-success">Paid</a> </td>
                                    @else
                                    <td class="text-end">
                                        <a href="javascript:void(0);"
                                           class="btn btn-action btn-primary show-modal"
                                           data-action="{{ route('BikeRegistration.payForm', $data->id) }}"
                                           data-size="xl"
                                           data-title="Pay Bike Registration">
                                            Proceed to Pay
                                        </a>
                                    </td>
                                    @endif
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
@endsection
