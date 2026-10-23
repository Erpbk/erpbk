@push('third_party_stylesheets')
@endpush
<div class="rider-invoices-table-scroll table-responsive">
<table class="table dataTable" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Select All" class="sorting_disabled" rowspan="1" colspan="1" width="50px">
            <input type="checkbox" id="selectAllCheckbox" onchange="toggleSelectAll(this)">
         </th>
         <th title="Invoice Number" class="sorting" rowspan="1" colspan="1">Invoice</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1">Inv Date</th>
         <th title="Billing Month" class="sorting" rowspan="1" colspan="1">Billing Month</th>
         <th title="Person Code" class="sorting" rowspan="1" colspan="1">Person Code</th>
         <th title="Rider" class="sorting" rowspan="1" colspan="1">Rider</th>
         <th title="Rider Status" class="sorting" rowspan="1" colspan="1">Rider Status</th>
         <th title="Descriptions" class="sorting" rowspan="1" colspan="1">Descriptions</th>
         <th title="Zone" class="sorting" rowspan="1" colspan="1">Project</th>
         <th title="Invoice Amount" class="sorting" rowspan="1" colspan="1">Invoice Amount</th>
         <th title="Payable Salary" class="sorting" rowspan="1" colspan="1">Payable Salary</th>
         <th title="Total Amount" class="sorting" rowspan="1" colspan="1">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center" data-id="{{ $r->id }}">
         <td>
            <input type="checkbox" class="invoice-checkbox" value="{{ $r->id }}" onchange="updateDeleteButton()">
         </td>
         <td style="white-space: nowrap;"><a href="javascript:void(0);" data-action="{{ route('riderInvoices.show', $r->id) }}" data-size="xl" class="show-modal-right">{{ $r->invoice_number }}</a></td>
         <td>{{ \Carbon\Carbon::parse($r->inv_date)->format('d M Y') }}</td>
         <td>{{ \Carbon\Carbon::parse($r->billing_month)->format('M Y') }}</td>
         @php
         $rider = company_table('riders')->where('id' , $r->rider_id)->first();
         $riderStatus = $rider
            ? \App\Models\Riders::employmentStatusDisplay($rider->status ?? null)
            : ['label' => '—', 'badge' => 'bg-label-secondary'];
         @endphp
         <td>{{ $rider?->person_code ?? '—' }}</td>
         <td>
            @if($rider)
            <a href="{{ route('rider.ledger', $rider->id) }}" target="_blank">{{ $rider->rider_id . '-' . $rider->name }}</a>
            @else
            —
            @endif
         </td>
         <td>
            <span class="badge {{ $riderStatus['badge'] }}">{{ $riderStatus['label'] }}</span>
         </td>
         <td>{{ $r->descriptions }}</td>
         <td>
            {{ company_table('customers')->where('id' , $rider->customer_id)->first()->name ?? '-'}}
         </td>
         <td>{{ number_format((float) $r->total_amount, 2) }}</td>
         <td>{{ number_format((float) $r->final_amount, 2) }}</td>
         <td>
            @include('invoices.partials.status_badge', ['status' => $r->paymentStatusKey()])
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                  @php
                     $riStatus = (int) ($r->status ?? 0);
                     $riLocked = in_array($riStatus, [1, 3], true);
                     $riPaymentId = $riLocked
                        ? \App\Support\InvoicePaymentLink::latestId(
                            $r,
                            isset($rider->account_id) ? (int) $rider->account_id : null
                        )
                        : null;
                  @endphp
                  @if(! $riLocked)
                  @can('riders_invoices_edit')
                  <a href="javascript:void(0);" data-action="{{ route('riderInvoices.edit', $r->id) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Update Invoice">
                     <i class="fa fa-edit mx-1"></i> Update
                  </a>
                  @endcan
                  @endif
                  @if($riLocked && $riPaymentId)
                  @canany(['riders_payments_edit', 'cash_&_banks_payments_edit'])
                  <a href="javascript:void(0);" data-action="{{ route('payments.edit', $riPaymentId) }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Payment">
                     <i class="fa fa-money-bill mx-1 text-success"></i> Edit Payment
                  </a>
                  @endcanany
                  @endif
                  @if($riStatus !== 1)
                  @can('riders_payments_create')
                  <a href="javascript:void(0);" data-action="{{ route('payments.create') }}?invoice_type=rider&invoice_id={{ $r->id }}" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Record Rider Payment">
                     <i class="fa fa-money-bill mx-1 text-success"></i> Record Payment
                  </a>
                  @endcan
                  @canany(['riders_invoices_edit', 'riders_invoice_edit'])
                  <a href="javascript:void(0);" data-action="{{ route('riderInvoices.markAsSettled', $r->id) }}" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Mark Settled (no payment)">
                     <i class="fa fa-check-double mx-1 text-primary"></i> Mark Settled (no payment)
                  </a>
                  @endcanany
                  @endif
                  @can('riders_invoices_delete')
                  <a href="javascript:void(0);" onclick="confirmDelete('{{route('riderInvoices.delete', $r->id)}}')" class='dropdown-item waves-effect'>
                     <i class="fa fa-trash mx-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @endforeach
   </tbody>
</table>
</div>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
@include('delete_requests._pending_table_script', ['items' => $data])