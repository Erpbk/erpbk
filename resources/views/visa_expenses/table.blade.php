@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Transation Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Transation Date: activate to sort column ascending">Billing Month</th>
         <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
         <th title="Voucher IDs" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
         <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Amount</th>
         <th title="Visa Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Visa Status: activate to sort column ascending" aria-sort="descending">Visa Status</th>
         <th title="Payment Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Payment Status: activate to sort column ascending" aria-sort="descending">Payment Status</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Action</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td>{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</td>
         <td>{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</td>
         <td>
            <span id="voucher_ids_display_{{ $r->id }}">
               @if($r->payment_status === 'paid')
               @if($r->vouchers->isNotEmpty())
               @foreach($r->vouchers as $voucher)
               @php
               $voucherNumber = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
               @endphp
               <a href="{{ route('vouchers.show', $voucher->id) }}" target="_blank">{{ $voucherNumber }}</a>@if(!$loop->last), @endif
               @endforeach
               @else
               <span class="text-muted">No voucher</span>
               @endif
               @else
               <span class="text-muted">-</span>
               @endif
            </span>
         </td>
         <td>{{ number_format($r->amount, 2) }}</td>
         <td>
            <span class="badge bg-primary">{{ $r->visa_status }}</span>
         </td>
         <td>
            @if($r->payment_status == 'paid')
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
                  <a href="{{ route('VisaExpense.viewvoucher', $r->id) }}" class='dropdown-item waves-effect'>
                     View Expense Detail
                  </a>
                  <a href="javascript:void(0);" data-action="{{ route('VisaExpense.edit' , $r->id) }}" data-size="lg" data-title="New Fine" class='dropdown-item waves-effect show-modal'>
                     Edit
                  </a>
                  <a href="javascript:void(0);" onclick='confirmDelete("{{route('VisaExpense.delete', $r->id) }}")' class='dropdown-item confirm-modal' data-size="lg" data-title="Delete Sim">
                     delete
                  </a>
               </div>
            </div>
         </td>
      </tr>
      @endforeach
   </tbody>
</table>
@if($data->isEmpty())
   <div class="text-center mt-5">
      <h4 class="text-muted">No Visa Expenses found</h4> 
   </div>
@endif
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