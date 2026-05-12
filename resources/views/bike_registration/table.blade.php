@push('third_party_stylesheets')
@endpush
<div id="bike-registration-inline-edit-scope">
   <table class="table table-striped dataTable no-footer" id="bikeRegistrationDataTable">
      <thead class="text-center">
         <tr role="row">
            <th title="Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
            <th title="Transation Date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Transation Date: activate to sort column ascending">Billing Month</th>
            <th title="Registration Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Registration Status: activate to sort column ascending" aria-sort="descending">Registration Status</th>
            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Amount</th>
            <th title="expiry date" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="expiry date: activate to sort column ascending">Expiry Date</th>
            <th title="Voucher IDs" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher ID: activate to sort column ascending">Voucher ID</th>
            <th title="Payment Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Payment Status: activate to sort column ascending" aria-sort="descending">Payment Status</th>
            <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Action</th>
         </tr>
      </thead>
      <tbody>
         @foreach($data as $r)
         <tr class="text-center" data-row-id="{{ $r->id }}">
            <td>
               <span id="date_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</span>
               @can('bike_registration_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-br-field" data-id="{{ $r->id }}" data-field="date">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="date"
                  id="date_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->date)->format('Y-m-d') }}"
                  class="form-control form-control-sm d-none">
            </td>
            <td>
               <span id="billing_display_{{ $r->id }}">{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</span>
               @can('bike_registration_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-br-field" data-id="{{ $r->id }}" data-field="billing">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="month"
                  id="billing_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->billing_month)->format('Y-m') }}"
                  class="form-control form-control-sm d-none">
            </td>
            <td>
               <span class="badge bg-primary">{{ $r->registration_status }}</span>
            </td>
            <td>
               <span id="amount_display_{{ $r->id }}">{{ number_format((float) $r->amount, 2) }}</span>
               @can('bike_registration_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-br-field" data-id="{{ $r->id }}" data-field="amount">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="number"
                  step="0.01"
                  min="0"
                  id="amount_input_{{ $r->id }}"
                  value="{{ number_format((float) $r->amount, 2, '.', '') }}"
                  class="form-control form-control-sm d-none">
            </td>
            <td>
               @if($r->expiry_date)
               <span id="expiry_date_display_{{ $r->id }}">{{ $r->expiry_date ? \Carbon\Carbon::parse($r->expiry_date)->format('d M Y') : '-' }}</span>
               @can('bike_registration_edit')
               <a href="javascript:void(0);" class="ms-2 js-edit-br-field" data-id="{{ $r->id }}" data-field="expiry_date">
                  <i class="fa fa-edit text-primary"></i>
               </a>
               @endcan
               <input
                  type="date"
                  id="expiry_date_input_{{ $r->id }}"
                  value="{{ \Carbon\Carbon::parse($r->expiry_date)->format('Y-m-d') }}"
                  class="form-control form-control-sm d-none">
               @else
               <span class="text-muted">-</span>
               @endif
            </td>
            <td>
               <span id="voucher_ids_display_{{ $r->id }}" class="d-inline-flex flex-wrap align-items-center justify-content-center gap-1">
                  @if($r->payment_status === 'paid')
                  @if($r->vouchers->isNotEmpty())
                  @foreach($r->vouchers as $voucher)
                  @php
                  $voucherNumber = $voucher->voucher_type . '-' . str_pad($voucher->id, 4, '0', STR_PAD_LEFT);
                  @endphp
                  <a href="{{ route('vouchers.show', $voucher->id) }}" target="_blank">{{ $voucherNumber }}</a>@if(!$loop->last), @endif
                  @endforeach
                  @can('bike_registration_edit')
                  <a href="javascript:void(0);"
                     class="show-modal text-body-secondary"
                     data-size="xl"
                     data-title="Edit payment account — {{ $r->vouchers->first()->voucher_type ?? 'BR' }}-{{ str_pad((string) ($r->vouchers->first()->id ?? 0), 4, '0', STR_PAD_LEFT) }}"
                     data-action="{{ route('BikeRegistration.editVoucherCreditForm', $r->id) }}"
                     title="Change credit / payment account only">
                     <i class="fa fa-edit text-primary"></i>
                  </a>
                  @endcan
                  @else
                  <span class="text-muted">No voucher</span>
                  @endif
                  @else
                  <span class="text-muted">-</span>
                  @endif
               </span>
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
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown">
                     <a href="{{ route('BikeRegistration.viewvoucher', $r->id) }}" class='dropdown-item waves-effect'>
                        View Expense Detail
                     </a>
                     <a href="javascript:void(0);" data-action="{{ route('BikeRegistration.edit' , $r->id) }}" data-size="lg" data-title="Edit registration expense" class='dropdown-item waves-effect show-modal'>
                        Edit
                     </a>
                     <a href="javascript:void(0);" data-delete-url="{{ route('BikeRegistration.delete', $r->id) }}" class='dropdown-item confirm-modal js-delete-bike-registration' data-size="lg" data-title="Delete entry">
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
      <h4 class="text-muted">No bike registration expenses found</h4>
   </div>
   @endif
   @if(method_exists($data, 'links'))
   {!! $data->links('components.global-pagination') !!}
   @endif
</div>