<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Branch" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Branch: activate to sort column ascending">Branch</th>
            <th title="Transaction ID" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Transaction ID: activate to sort column ascending">Transaction ID</th>
            <th title="Charged To" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Charged To: activate to sort column ascending">Charged To</th>
            <th title="Admin Charges" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Admin Charges: activate to sort column ascending">Billing Month</th>
            <th title="Trip Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Date: activate to sort column ascending">Trip Date</th>
            <th title="Trip Time" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Time: activate to sort column ascending">Trip Time</th>
            <th title="Toll Gate" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Toll Gate: activate to sort column ascending">Toll Gate</th>
            <th title="Direction" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Direction: activate to sort column ascending">Direction</th>
            <th title="Tag Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Tag Number: activate to sort column ascending">Tag Number</th>
            <th title="Plate No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Plate No: activate to sort column ascending">Plate No</th>
            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1">Total Amount</th>
            <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1">Status</th>
            <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $r)
        <tr class="text-center">
            <td>{{ $r->branch?->name ?? 'N/A' }}</td>
            <td>{{ $r->transaction_id }}</td>
            @if($r->rider)
            <td><a href="{{ route('riders.show', $r->rider->id) }}">{{ $r->rider->rider_id }} - {{ $r->rider->name }}</a></td>
            @elseif($r->rentalCompany)
            <td><a href="{{ route('bikeRentCompanies.files', $r->rentalCompany->id) }}" target="_blank">{{ $r->rentalCompany->name }}</a></td>
            @else
            <td>N/A</td>
            @endif
            <td>{{ $r->billing_month ? \Carbon\Carbon::parse($r->billing_month)->format('M-Y') : 'N/A' }}</td>
            <td>{{ App\Helpers\General::DateFormat($r->trip_date) }}</td>
            <td>{{ $r->trip_time }}</td>
            <td>{{ $r->toll_gate }}</td>
            <td>{{ $r->direction }}</td>
            <td>{{ $r->tag_number }}</td>
            <td>{{ $r->plate }}</td>
            <td>{{ \App\Helpers\Currency::format($r->total_amount, 2) }}</td>
            <td>
                @if(\App\Models\salik::normalizePaymentStatus($r->status, !empty($r->payment_voucher_id)) === 'paid')
                <span class="badge bg-success">Paid</span>
                @else
                <span class="badge bg-warning text-dark">Unpaid</span>
                @endif
            </td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                        @can('rta_saliks_salik_view')
                        <a href="{{ route('salik.show', $r->id) }}" class="dropdown-item waves-effect">
                            View
                        </a>
                        @endcan
                        @can('rta_saliks_salik_edit')
                        <a href="javascript:void(0);" data-action="{{ route('salik.edit' , $r->id) }}" data-size="lg" data-title="Update Salik" class='dropdown-item waves-effect show-modal'>
                            Edit
                        </a>
                        @endcan
                        @can('rta_saliks_salik_delete')
                        <a href="javascript:void(0);" onclick='confirmDelete("{{route('salik.delete', $r->id) }}")' class='dropdown-item confirm-modal' data-size="lg" data-title="Delete Ticket">
                            Delete
                        </a>
                        @endcan
                    </div>
                </div>

            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif