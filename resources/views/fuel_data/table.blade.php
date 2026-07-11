@push('third_party_stylesheets')
@endpush
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Transaction #</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Date</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Time</th>
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Billing Month</th>
         <th title="Billing Month" class="sorting" rowspan="1" colspan="1" >Card No</th>
         <th title="Supplier" class="sorting" rowspan="1" colspan="1" >Rider</th>
         <th title="Supplier" class="sorting" rowspan="1" colspan="1" >Rider Status</th>
         <th title="Bike" class="sorting" rowspan="1" colspan="1" >Bike</th>
         <th title="Total Amount" class="sorting" rowspan="1" colspan="1" >Amount</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td>{{ $r->trans_no }}</td>
         <td>{{ $r->trans_date->format('d M Y') }}</td>
         <td>{{ $r->trans_date->format('h:i:s') }}</td>
         <td>{{$r->billing_month->format('M Y') ?? ''}}</td>
         <td><a href="{{ route('fuelCards.show', $r->card?->id ?? 0) }}" target="_blank">{{ $r->card_no ?? '-' }}</a></td>
         <td><a href="{{ route('rider.ledger', $r->rider_id) }}" target="_blank">{{ $r->rider->name ?? '-' }}</a></td>
         <td>
            <span class="badge bg-{{ $r->rider_status['badge'] }}">{{ $r->rider_status['text'] }}</span>
         </td>
         <td><a @if($r->bike) href="{{ route('bikeHistories.index') }}?bike_id={{ $r->bike->id }}" target="_blank" @else href="javascript:void(0);" @endif" >{{ $r->bike_no }}</a></td>
         <td>{{$r->total ?? 'N/A' }}</td>
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  @can('fuel_cards_transactions_edit')
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Data" data-action="{{ route('fuel_data.edit', $r->id) }}">
                           <i class="fa fa-edit my-1"></i> Edit
                        </a>
                  @endcan
                  @can('fuel_cards_transactions_delete')
                  <a href="javascript:void(0);" class='dropdown-item waves-effect delete-receipt' 
                        onclick='confirmDelete("{{route('fuel_data.destroy' , $r->id ) }}")'>
                        <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
        </td>
      </tr>
      @endforeach
      @if($data->isEmpty())
        <tr>
             <td colspan="10" class="text-center pt-5"><h3>No records found</h3></td>
        </tr>
      @endif
   </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif