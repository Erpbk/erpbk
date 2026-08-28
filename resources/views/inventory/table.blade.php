@push('third_party_stylesheets')
@endpush
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Batch</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Invoice</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Garage</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Purchse Date</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >quantity</th>
         <th title="Supplier" class="sorting" rowspan="1" colspan="1" >used</th>
         <th title="Supplier" class="sorting" rowspan="1" colspan="1" >Remaining</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @php
         $batch = '';
      @endphp
      @foreach($purchases as $r)
      @continue($batch == $r->batch_no)
      @php
         $batch = $r->batch_no;
      @endphp
      <tr class="text-center">
         <td><a href="javascript:void(0);" data-action="{{ route('inventory.showBatch', $r->batch_no) }}" class="show-modal-right">{{ $r->batch_no }}</a></td>
         <td><a href="javascript:void(0);" data-action="{{ route('supplier_invoices.show', $r->invoice?->id ?? 0) }}" class="show-modal-right">{{ $r->invoice?->inv_id ?? 'N/A' }}</a></td>
         <td>{{ $r->garage?->name ?? 'N/A' }}</td>
         <td>{{ $r->purchase_date->format('d M Y') }}</td>
         <td>{{ $purchases->where('batch_no', $r->batch_no)->sum('quantity') ?? 'N/A'}}</td>
         <td>{{ $purchases->where('batch_no', $r->batch_no)->sum(function($p) { return $p->quantity - $p->remaining_quantity;}) }}</td>
         <td>{{ $purchases->where('batch_no', $r->batch_no)->sum('remaining_quantity')}}</td>
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  @include('layouts.partials.module_contract_action', [
                     'module' => 'inventory',
                     'recordId' => $r->id,
                  ])
               </div>
            </div>
        </td>
      </tr>
      @endforeach
      @if($purchases->isEmpty())
        <tr>
             <td colspan="10" class="text-center pt-5"><h3>No records found</h3></td>
        </tr>
      @endif
   </tbody>
</table>
@if(method_exists($purchases, 'links'))
    {!! $purchases->links('components.global-pagination') !!}
@endif