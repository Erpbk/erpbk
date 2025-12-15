@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Trip Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Date: activate to sort column ascending">Trip Date</th>
         <th title="Trip Time" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Trip Time: activate to sort column ascending">Trip Time</th>
         <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
         <th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Ticket No</th>
         <th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher No: activate to sort column ascending">Voucher No</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Rider Id</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Rider</th>
         <th title="Plate No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Plate No: activate to sort column ascending">Plate No</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Service Charges</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Admin Fee</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Total Amount</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Status</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);"> <i class="fa fa-filter"></i></a>
         </th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td>{{ App\Helpers\General::DateFormat($r->trip_date) }}</td>
         <td>{{$r->trip_time}}</td>
         <td>{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</td>
         @php
         $fileUrl = asset('storage/' . $r->attachment_path);
         $voucher = null;
         $voucherNumber = null;

         if ($r->status) {
         $voucher = DB::table('vouchers')
         ->where('ref_id', $r->id)
         ->where('voucher_type', 'RFV')
         ->orderByDesc('id')
         ->first();
         $voucherNumber = $voucher ? $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT) : null;
         }
         @endphp
         <td><a href="{{ $fileUrl }}" target="_blank">{{$r->ticket_no}}</a></td>
         <td>
            @if ($voucher)
            <a href="{{ route('vouchers.show', $voucher->id) }}" target="_blank">{{ $voucherNumber }}</a>
            @else
            -
            @endif
         </td>
         @php
         $rider_account = DB::table('riders')->where('id', $r->rider_id)->first();
         if ($rider_account) {
         $rider = $rider_account;
         } else {
         $rider = DB::table('accounts')->where('ref_name', 'Rider')->where('id', $r->rider_id)->first();
         }
         @endphp

         <td>{{ $rider->rider_id ?? '' }}</td>
         <td>
            @if ($rider)
            <a href="{{ route('riders.show', $rider->id) }}">{{ $rider->name }}</a>
            @else
            -
            @endif
         </td>
         <td>{{ $r->plate_no }}</td>
         <td>AED {{ number_format($r->amount, 2) }}</td>
         <td>AED {{ number_format($r->service_charges, 2) }}</td>
         <td>AED {{ number_format($r->admin_fee, 2) }}</td>
         <td>AED {{ number_format($r->total_amount, 2) }}</td>
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
                  <a href="javascript:void(0);" data-size="sm" data-title="Upload Document" data-action="{{ url('rtaFines/attach_file/'.$r->id) }}" class='dropdown-item waves-effect show-modal'>
                     Update Fine File
                  </a>
                  <a href="{{ route('rtaFines.viewvoucher', $r->id) }}" class='dropdown-item waves-effect'>
                     View Fine Detail
                  </a>
                  <a href="javascript:void(0);" data-action="{{ route('rtaFines.edit' , $r->id) }}" data-size="lg" data-title="New Fine" class='dropdown-item waves-effect show-modal'>
                     Edit
                  </a>
                  <a href="javascript:void(0);" onclick='confirmDelete("{{route('rtaFines.delete', $r->id) }}")' class='dropdown-item confirm-modal' data-size="lg" data-title="Delete Sim">
                     delete
                  </a>
               </div>
            </div>
         </td>
         <td></td>
      </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Filter Riders</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body" id="searchTopbody">
            <div style="display: none;" class="loading-overlay" id="loading-overlay">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
            <form id="filterForm" action="{{ route('banks.index') }}" method="GET">
               <div class="row">
                  <div class="form-group col-md-12">
                     <input type="number" name="search" class="form-control" placeholder="Search">
                  </div>
                  <div class="col-md-12 form-group text-center">
                     <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>