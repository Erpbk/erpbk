@extends('bikes.view')

@section('page_content')
<div class="clearfix"></div>
@can('bikes_bike_view')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-road me-2"></i> Saliks
        </h5>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped dataTable no-footer">
            <thead class="text-center">
                <tr>
                    <th>Transaction ID</th>
                    <th>Charged To</th>
                    <th>Billing Month</th>
                    <th>Trip Date</th>
                    <th>Trip Time</th>
                    <th>Toll Gate</th>
                    <th>Direction</th>
                    <th>Tag Number</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($saliks as $trip)
                <tr class="text-center">
                    <td>{{ $trip->transaction_id ?: '-' }}</td>
                    <td>
                        @if($trip->rider)
                        <a href="{{ route('riders.show', $trip->rider->id) }}" target="_blank">{{ $trip->rider->rider_id }} - {{ $trip->rider->name }}</a>
                        @elseif($trip->rentalCompany)
                        <a href="{{ route('bikeRentCompanies.files', $trip->rentalCompany->id) }}" target="_blank">{{ $trip->rentalCompany->name }}</a>
                        @else
                        -
                        @endif
                    </td>
                    <td>{{ $trip->billing_month ? \Carbon\Carbon::parse($trip->billing_month)->format('M Y') : '-' }}</td>
                    <td>{{ \App\Helpers\General::DateFormat($trip->trip_date) }}</td>
                    <td>{{ $trip->trip_time instanceof \Carbon\CarbonInterface ? $trip->trip_time->format('H:i') : ($trip->trip_time ?: '-') }}</td>
                    <td>{{ $trip->toll_gate ?: '-' }}</td>
                    <td>{{ $trip->direction ?: '-' }}</td>
                    <td>{{ $trip->tag_number ?: '-' }}</td>
                    <td>{{ \App\Helpers\Currency::format($trip->total_amount, 2) }}</td>
                    <td>
                        @if(\App\Models\salik::normalizePaymentStatus($trip->status, !empty($trip->payment_voucher_id)) === 'paid')
                        <span class="badge bg-success">Paid</span>
                        @else
                        <span class="badge bg-warning text-dark">Unpaid</span>
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="10" class="text-center text-muted py-4">No salik trips recorded for this vehicle.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($saliks, 'links'))
        {!! $saliks->links('components.global-pagination') !!}
        @endif
    </div>
</div>
@else
<div class="card">
    <div class="card-body">
        <h5 class="card-title">You are not authorized to access this page</h5>
    </div>
</div>
@endcan
@endsection
