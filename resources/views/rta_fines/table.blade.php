@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   @php $vf = static fn (string $f): bool => field_visible('rtafine', $f); @endphp
   <thead class="text-center">
      <tr role="row">
         @if($vf('trip_date'))<th title="Trip Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Date: activate to sort column ascending">Trip Date</th>@endif
         @if($vf('trip_time'))<th title="Trip Time" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Time: activate to sort column ascending">Trip Time</th>@endif
         @if($vf('billing_month'))<th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>@endif
         @if($vf('ticket_no'))<th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Ticket No</th>@endif
         @if($vf('is_impound'))<th title="impound" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Impound</th>@endif
         @if($vf('attachment'))<th title="Attachment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachment: activate to sort column ascending" aria-sort="descending">Attachment</th>@endif
         @if(Route::is('rtaFines.paid'))<th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher No: activate to sort column ascending">Voucher No</th>@endif
         @if($vf('rider_id'))<th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Rider Id</th>@endif
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Name</th>
         @if($vf('plate_no'))<th title="Plate No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Plate No: activate to sort column ascending">Plate No</th>@endif
         @if($vf('amount'))<th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>@endif
         @if($vf('service_charges'))<th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Service Charges</th>@endif
         @if($vf('admin_fee'))<th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Admin Fee</th>@endif
         @if($vf('total_amount'))<th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Total Amount</th>@endif
         @if($vf('reference_number'))<th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Reference No: activate to sort column ascending">Reference No</th>@endif
         @if($vf('status'))<th title="Reference" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>@endif
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @forelse($data as $r)
      <tr class="text-center" data-id="{{ $r->id }}">
         @if($vf('trip_date'))<td>{{ App\Helpers\General::DateFormat($r->trip_date) }}</td>@endif
         @if($vf('trip_time'))<td>{{$r->trip_time}}</td>@endif
         @if($vf('billing_month'))<td>{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</td>@endif
         @php
         $fileUrl = $r->paid_voucher_id ? asset('storage/' . $r->attachment) : asset('storage/' . $r->attachment_path);
         @endphp
         @if($vf('ticket_no'))<td><a data-action="{{ route('rtaFines.show',$r->id) }}" href="javascript:void(0);" class="show-modal-right">{{$r->ticket_no}}</a></td>@endif
         @if($vf('is_impound'))<td>
            @if($r->is_impound)
            <span style="font-weight: bold;">Yes</span><br>
            <span>{{ 'Black Points: '.$r->black_points }}</span>
            @else
            No
            @endif
         </td>@endif
         @if($vf('attachment'))<td><a href="{{ $fileUrl }}" target="_blank"><i class="fa fa-file"></i></a></td>@endif
         @if(Route::is('rtaFines.paid'))
         @php
            $voucher = $r->paidVoucher;
            $voucherNumber = $voucher ? $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) : null;
         @endphp
         <td><a href="{{ route('vouchers.show', $voucher?->id ?? 0) }}" target="_blank">{{ $voucherNumber }}</a></td>
         @endif
         @if($vf('rider_id'))<td>{{ $r->rider?->rider_id ?? '' }}</td>@endif
         <td>
            @if ($r->rider)
            <a href="{{ route('riders.show', $r->rider->id) }}" target="_blank">{{ $r->rider->name }}</a>
            @elseif($r->rentalCompany)
            <a href="{{ route('bikeRentCompanies.files', $r->rentalCompany->id) }}" target="_blank">{{ $r->rentalCompany->name }}</a>
            @else
            -
            @endif
         </td>
         @if($vf('plate_no'))<td>{{ $r->plate_no }}</td>@endif
         @if($vf('amount'))<td>{{ \App\Helpers\Currency::format($r->amount, 2) }}</td>@endif
         @if($vf('service_charges'))<td>{{ \App\Helpers\Currency::format($r->service_charges, 2) }}</td>@endif
         @if($vf('admin_fee'))<td>{{ \App\Helpers\Currency::format($r->admin_fee, 2) }}</td>@endif
         @if($vf('total_amount'))<td>{{ \App\Helpers\Currency::format($r->total_amount, 2) }}</td>@endif
         @if($vf('reference_number'))<td>{{ $r->reference_number ?? '-'}}</td>@endif
         @if($vf('status'))<td>
            @if($r->status == 'paid')
            <span class="badge bg-success">Paid</span>
            @else
            <span class="badge bg-danger">Unpaid</span>
            @endif
         </td>@endif
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                  @can('rtafine_edit')
                  <a href="javascript:void(0);" data-size="md" data-title="Upload Document" data-action="{{route('rtaFines.fileupload', $r->id) }}" class='dropdown-item waves-effect show-modal'>
                     Update Fine File
                  </a>
                  @if(!$r->paid_voucher_id)
                  <a href="javascript:void(0);" data-action="{{ route('rtaFines.viewvoucher', $r->id) }}" data-size="lg" data-title="Pay Fine:  {{ $r->ticket_no }}" class='dropdown-item waves-effect show-modal'>
                     Pay Fine
                  </a>
                  <a href="javascript:void(0);" data-action="{{ route('rtaFines.edit' , $r->id) }}" data-size="xl" data-title="New Fine" class='dropdown-item waves-effect show-modal'>
                     Edit
                  </a>
                  @endif
                  @endcan
                  @can('rtafine_delete')
                  <a href="javascript:void(0);" onclick='confirmDelete("{{route('rtaFines.destroy', $r->id) }}")' class='dropdown-item'>
                     delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @empty
      <tr>
         <td colspan="17" class="text-center text-muted py-4">
            No records found.
         </td>
      </tr>
      @endforelse
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif

<script>
   function confirmDelete(url) {
      Swal.fire({
         title: 'Are you sure?',
         text: "You won't be able to revert this!",
         icon: 'warning',
         showCancelButton: true,
         confirmButtonColor: '#3085d6',
         cancelButtonColor: '#d33',
         confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
         if (result.isConfirmed) {
            $.ajax({
               url: url,
               type: 'DELETE',
               data: {
                  _token: '{{ csrf_token() }}'
               },
               success: function(response) {
                  Swal.fire(
                     'Deleted!',
                     'Fine has been deleted.',
                     'success'
                  ).then(() => {
                     location.reload();
                  });
               },
               error: function(xhr) {
                  Swal.fire(
                     'Error!',
                     'Failed to delete Receipt. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
                     'error'
                  );
               }
            });
         }
      });
   };
</script>
@include('delete_requests._pending_table_script', ['items' => $data])
