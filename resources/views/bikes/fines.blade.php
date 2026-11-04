@extends('bikes.view')

@section('page_content')
<div class="clearfix"></div>
@can('bikes_bike_view')
<div class="card">
    <div class="card-header">
        <h5 class="mb-0">
            <i class="fas fa-file-invoice-dollar me-2"></i> Fines
        </h5>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-striped dataTable no-footer">
            <thead class="text-center">
                <tr>
                    <th>Trip Date</th>
                    <th>Ticket No</th>
                    <th>Impound</th>
                    <th>Charged To</th>
                    <th>Total Amount</th>
                    <th>Status</th>
                    <th>Attachment</th>
                </tr>
            </thead>
            <tbody>
                @forelse($fines as $fine)
                <tr class="text-center">
                    <td>{{ \App\Helpers\General::DateFormat($fine->trip_date) }}</td>
                    <td>
                        @if($fine->ticket_no)
                        <a data-action="{{ route('rtaFines.show', $fine->id) }}" href="javascript:void(0);" class="show-modal-right">{{ $fine->ticket_no }}</a>
                        @else
                        -
                        @endif
                    </td>
                    <td>
                        @if($fine->is_impound)
                        <span class="fw-bold">Yes</span><br>
                        <span>{{ 'Black Points: ' . ($fine->black_points ?? 0) }}</span>
                        @else
                        No
                        @endif
                    </td>
                    <td>
                        @if($fine->rider)
                        <a href="{{ route('riders.show', $fine->rider->id) }}" target="_blank">{{ $fine->rider->name }}</a>
                        @elseif($fine->rentalCompany)
                        <a href="{{ route('bikeRentCompanies.files', $fine->rentalCompany->id) }}" target="_blank">{{ $fine->rentalCompany->name }}</a>
                        @else
                        -
                        @endif
                    </td>
                    <td>{{ \App\Helpers\Currency::format($fine->total_amount, 2) }}</td>
                    <td>
                        @if($fine->status === 'paid')
                        <span class="badge bg-success">Paid</span>
                        @else
                        <span class="badge bg-danger">Unpaid</span>
                        @endif
                    </td>
                    @php
                        $attachment = $fine->paid_voucher_id ? $fine->attachment : $fine->attachment_path;
                    @endphp
                    <td>
                        @if($attachment)
                        <a href="{{ asset('storage/' . $attachment) }}" target="_blank"><i class="fa fa-file"></i></a>
                        @else
                        -
                        @endif
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-4">No fines recorded for this vehicle.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
        @if(method_exists($fines, 'links'))
        {!! $fines->links('components.global-pagination') !!}
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
