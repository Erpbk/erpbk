@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Trip Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Date: activate to sort column ascending">Trip Date</th>
         <th title="Trip Time" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Time: activate to sort column ascending">Trip Time</th>
         <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
         <th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Ticket No</th>
         <th title="impound" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Impound</th>
         <th title="Attachment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachment: activate to sort column ascending" aria-sort="descending">Attachment</th>
         <th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher No: activate to sort column ascending">Voucher No</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Rider Id</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Name</th>
         <th title="Plate No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Plate No: activate to sort column ascending">Plate No</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Service Charges</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Admin Fee</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Total Amount</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Reference No: activate to sort column ascending">Reference No</th>
         <th title="Reference" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @forelse($data as $r)
      <tr class="text-center">
         <td>{{ App\Helpers\General::DateFormat($r->trip_date) }}</td>
         <td>{{$r->trip_time}}</td>
         <td>{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</td>
         @php
         $fileUrl = $r->paid_voucher_id ? asset('storage/' . $r->attachment) : asset('storage/' . $r->attachment_path);
         $voucher = $r->paid_voucher_id ? $r->paidVoucher : $r->voucher;
         $voucherNumber = $voucher ? $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) : null;
         @endphp
         <td><a data-action="{{ route('rtaFines.show',$r->id) }}" href="javascript:void(0);" class="show-modal-right">{{$r->ticket_no}}</a></td>
         <td>
            @if($r->is_impound)
            <span style="font-weight: bold;">Yes</span><br>
            <span>{{ 'Black Points: '.$r->black_points }}</span>
            @else
            No
            @endif
         </td>
         <td><a href="{{ $fileUrl }}" target="_blank"><i class="fa fa-file"></i></a></td>
         <td><a href="{{ route('vouchers.show', $voucher?->id ?? 0) }}" target="_blank">{{ $voucherNumber }}</a></td>
         <td>{{ $r->rider?->rider_id ?? '' }}</td>
         <td>
            @if ($r->rider)
            <a href="{{ route('riders.show', $r->rider->id) }}" target="_blank">{{ $r->rider->name }}</a>
            @elseif($r->rentalCompany)
            <a href="{{ route('bikeRentCompanies.files', $r->rentalCompany->id) }}" target="_blank">{{ $r->rentalCompany->name }}</a>
            @else
            -
            @endif
         </td>
         <td>{{ $r->plate_no }}</td>
         <td>{{ \App\Helpers\Currency::format($r->amount, 2) }}</td>
         <td>{{ \App\Helpers\Currency::format($r->service_charges, 2) }}</td>
         <td>{{ \App\Helpers\Currency::format($r->admin_fee, 2) }}</td>
         <td>{{ \App\Helpers\Currency::format($r->total_amount, 2) }}</td>
         <td>{{ $r->reference_number ?? '-'}}</td>
         <td>
            @if($r->status == 'paid')
            <span class="badge bg-success">Paid</span>
            @else
            <span class="badge bg-danger">Unpaid</span>
            @endif
         </td>
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