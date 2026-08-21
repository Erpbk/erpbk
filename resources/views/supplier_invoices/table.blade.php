@push('third_party_stylesheets')
@endpush
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1">@if(str_contains(url()->current(), 'order'))Order Date @else Inv Date @endif</th>
         <th title="Inv Id" class="sorting" rowspan="1" colspan="1">@if(str_contains(url()->current(), 'order'))Order Id @else Inv Id @endif</th>
         <th title="Billing Month" class="sorting" rowspan="1" colspan="1">@if(str_contains(url()->current(), 'order')) Created By @else Billing Month @endif</th>
         <th title="Supplier" class="sorting" rowspan="1" colspan="1">Supplier</th>
         @if(!str_contains(url()->current(), 'order'))
         <th title="Garage" class="sorting" rowspan="1" colspan="1">Garage</th>
         @endif
         <th title="Descriptions" class="sorting" rowspan="1" colspan="1">Description</th>
         <th title="Total Amount" class="sorting" rowspan="1" colspan="1">Amount</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php $invoicePendingDeletion = record_is_pending_deletion($r); @endphp
      <tr class="text-center {{ $invoicePendingDeletion ? 'table-warning' : '' }}" data-id="{{ $r->id }}">
         @if(str_contains(url()->current(), 'order'))
         <td>{{$r->order_date?->format('d M Y') ?? ''}}</td>
         @else
         <td>{{$r->inv_date->format('d M Y')}}</td>
         @endif
         <td><a href="javascript:void(0);" class="show-modal-right" data-action="{{ route('supplierInvoices.show', $r->id) }}@if(str_contains(url()->current(), 'order'))?order={{ true }} @endif">{{$r->inv_id}}</a> @include('delete_requests._pending_badge', ['model' => $r])</td>
         @if(!str_contains(url()->current(), 'order')) <td>{{ $r->billing_month?->format('M Y') ?? '-' }}</td>
         @else <td>{{ $r->createdNy?->name ?? '-' }}</td>
         @endif
         <td>{{ $r->supplier?->name ?? '-' }}</td>
         @if(!str_contains(url()->current(), 'order'))
         <td>{{ $r->garage?->name ?? '—' }}</td>
         @endif
         <td>{{$r->descriptions ?? 'N/A' }}</td>
         <td>{{$r->total_amount ?? 'N/A' }}</td>
         <td style="position: relative;">
            @if($invoicePendingDeletion)
               <span class="text-muted small"><i class="ti ti-lock me-1"></i>Locked</span>
            @else
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  @can('suppliers_invoices_create')
                  @if(!$r->is_invoice)
                  <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Generate Invoice" data-action="{{ route('supplierInvoices.edit', $r->id) }}">
                     <i class="fa fa-credit-card my-1"></i> Generate Invoice
                  </a>
                  @endif
                  @endcan
                  @canany(['suppliers_invoices_view', 'suppliers_purchase_order_view'])
                  <a href="{{ route('supplierInvoices.show', $r->id) }}@if(str_contains(url()->current(), 'order'))?order={{ true }} @endif" target="_blank" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye my-1"></i> view
                  </a>
                  @endcanany
                  @canany(['suppliers_invoices_edit', 'suppliers_purchase_order_edit'])
                  <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Data" data-action="{{ route('supplierInvoices.edit', $r->id) }}@if(str_contains(url()->current(), 'order'))?order={{ true }} @endif">
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcanany
                  @canany(['suppliers_invoices_delete', 'suppliers_purchase_order_delete'])
                  <a href="javascript:void(0);" class='dropdown-item waves-effect delete-receipt'
                     onclick='confirmDelete("{{route('supplierInvoices.delete' , $r->id ) }}")'>
                     <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcanany
               </div>
            </div>
            @endif
            {{-- </td>
         <td>
            <div class='btn-group'>
               <a href="{{ route('supplierInvoices.show', $r->id) }}" class='btn btn-default btn-sm' target="_blank">
            <i class="fa fa-eye"></i>
            </a>
            <a href="javascript:void(0);" data-title="Edit Invoice" data-size="xl" data-action="{{ route('supplierInvoices.edit', $r->id) }}" class='btn btn-info btn-sm show-modal'>
               <i class="fa fa-edit"></i>
            </a>
            <a href="javascript:void(0);" onclick='confirmDelete("{{route('supplierInvoices.destroy' , $r->id ) }}")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Invoice">
               <i class="fa fa-trash"></i>
            </a>
            </div>
         </td> --}}
      </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif
@include('delete_requests._pending_table_script', ['items' => $data])