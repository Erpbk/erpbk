@extends('layouts.app')

@section('title','Salik Voucher Details')
@section('content')
<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h3>Salik Voucher #{{ $data->transaction_id }}</h3>
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
                                <h6>{{ $accounts->name ?? '-' }}</h6>
                            </div>
                        </div>
                    </div>
                    <h5 class="pb-4 border-bottom mb-4"></h5>
                    <div class="info-container">
                        <ul class="list-unstyled mb-6">
                            <ul class="p-0 mb-3">
                                <li class="list-group-item pb-1">
                                    <b>Account Code:</b> <span class="float-right">{{ $accounts->account_code ?? '-' }}</span>
                                </li>
                                <li class="list-group-item pb-1">
                                    <b>Account Type:</b> <span class="float-right">{{ $accounts->account_type ?? '-' }}</span>
                                </li>
                                <li class="list-group-item pb-1">
                                    <b>Status:</b> <span class="float-right">
                                        @if(($accounts->status ?? 0) == 1)
                                        <span class="badge  bg-success">Active</span></span>
                                    @else
                                    <span class="badge  bg-danger">Inactive</span></span>
                                    @endif
                                </li>
                            </ul>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-9 col-md-9 col-lg-7 order-0 order-md-1">
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-striped">
                                <tr>
                                    <th>Transaction ID</th>
                                    <td class="text-end">{{ $data->transaction_id }}</td>
                                </tr>
                                <tr>
                                    <th>Trip Date</th>
                                    <td class="text-end">{{ $data->trip_date }}</td>
                                </tr>
                                <tr>
                                    <th>Trip Time</th>
                                    <td class="text-end">{{ $data->trip_time }}</td>
                                </tr>
                                <tr>
                                    <th>Toll Gate</th>
                                    <td class="text-end">{{ $data->toll_gate }}</td>
                                </tr>
                                <tr>
                                    <th>Direction</th>
                                    <td class="text-end">{{ $data->direction }}</td>
                                </tr>
                                <tr>
                                    <th>Tag Number</th>
                                    <td class="text-end">{{ $data->tag_number }}</td>
                                </tr>
                                <tr>
                                    <th>Plate</th>
                                    <td class="text-end">{{ $data->plate }}</td>
                                </tr>
                                <tr>
                                    <th>Amount</th>
                                    <td class="text-end">AED {{ number_format($data->amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <th>Status</th>
                                    <td class="text-end">{{ $data->status }}</td>
                                </tr>
                                <tr>
                                    <th>View Files</th>
                                    @php
                                    $fileUrl = asset('storage/' . $data->attachment_path);
                                    @endphp
                                    <td class="text-end"> <a target="_blank" href="{{ $fileUrl }}">View File</a> </td>
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