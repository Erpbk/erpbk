@extends('layouts.app')
@section('title','Salik Details')
@section('content')
<div class="container">
    <h3>Salik Details</h3>
    <div class="card">
        <div class="card-body">
            <table class="table table-bordered">
                <tr>
                    <th>ID</th>
                    <td>{{ $salik->id }}</td>
                </tr>
                <tr>
                    <th>Transaction ID</th>
                    <td>{{ $salik->transaction_id }}</td>
                </tr>
                <tr>
                    <th>Trip Date</th>
                    <td>{{ $salik->trip_date }}</td>
                </tr>
                <tr>
                    <th>Trip Time</th>
                    <td>{{ $salik->trip_time }}</td>
                </tr>
                <tr>
                    <th>Post Date</th>
                    <td>{{ $salik->transaction_post_date }}</td>
                </tr>
                <tr>
                    <th>Toll Gate</th>
                    <td>{{ $salik->toll_gate }}</td>
                </tr>
                <tr>
                    <th>Direction</th>
                    <td>{{ $salik->direction }}</td>
                </tr>
                <tr>
                    <th>Tag Number</th>
                    <td>{{ $salik->tag_number }}</td>
                </tr>
                <tr>
                    <th>Plate</th>
                    <td>{{ $salik->plate }}</td>
                </tr>
                <tr>
                    <th>Amount</th>
                    <td>AED {{ number_format($salik->amount, 2) }}</td>
                </tr>
                <tr>
                    <th>Status</th>
                    <td>{{ $salik->status }}</td>
                </tr>
                <tr>
                    <th>Created By</th>
                    <td>{{ $salik->created_by }}</td>
                </tr>
                <tr>
                    <th>Updated By</th>
                    <td>{{ $salik->updated_by }}</td>
                </tr>
            </table>
            <a href="{{ route('salik.index') }}" class="btn btn-default">Back</a>
        </div>
    </div>
</div>
@endsection