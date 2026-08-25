@push('third_party_stylesheets')
<style>
   .table-responsive {
      max-height: calc(100vh - 280px);
   }
   @keyframes fc-clickme-pulse {
      0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.55); }
      70% { transform: scale(1.05); box-shadow: 0 0 0 8px rgba(220, 38, 38, 0); }
      100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
   }
   .fc-clickme {
      animation: fc-clickme-pulse 1.2s infinite;
      font-size: 11px;
      padding: 2px 8px;
      line-height: 1.4;
   }
   .fc-assigned-to {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 4px 6px;
   }
</style>
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   @php $vf = static fn (string $f): bool => field_visible('fuel', $f); @endphp
   <thead class="text-center">
      <tr role="row">
         @if($vf('card_number'))<th title="Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Number: activate to sort column ascending">Card Number</th>@endif
         @if($vf('fuel_company_id'))<th title="Fuel company" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Fuel company: activate to sort column ascending">Fuel company</th>@endif
         <th title="Assigned To" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Assigned To: activate to sort column ascending">Assigned To</th>
         <th title="Bike" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Bike: activate to sort column ascending">Bike</th>
         @if($vf('status'))<th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>@endif
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php
         $r->rider?->loadMissing('bikes');
      @endphp
      <tr class="text-center">
         @if($vf('card_number'))<td>
            <a href="{{ route('fuelCards.show' , $r->id)}}" >
               {{$r->card_number}}
            </a>
         </td>@endif
         @if($vf('fuel_company_id'))<td>{{ $r->fuelCompany?->name ?? '—' }}</td>@endif
         <td style="text-align: left;">
            @if($r->rider)
            <div class="fc-assigned-to">
               <a href="{{ route('riders.show', $r->rider->id) }}" target="_blank">{{ $r->rider->name }}</a>
               @if($r->assigneeIsAbsconded())
                  <span class="badge bg-label-danger">Absconded</span>
               @endif
               @if($r->hasNoVehicleAssigned())
                  <span class="badge bg-label-warning">No Vehicle Assigned</span>
               @endif
               @if($r->vehicleChanged())
                  <span class="badge bg-label-danger">Vehicle Changed</span>
                  <a href="javascript:void(0);"
                     data-size="lg"
                     data-title="Update Bike Assignment"
                     data-action="{{ route('fuelCards.update_assignment', $r->id) }}"
                     class="show-modal btn btn-danger btn-sm fc-clickme">
                     Click me
                  </a>
               @endif
            </div>
            @else
            -
            @endif
         </td>
         <td><a @if($r->rider?->bikes) href="{{ route('bikes.show', $r->rider->bikes->id) }}" target="_blank" @else href="javascript:void(0);" @endif>{{ $r->rider?->bikes ? (($r->rider->bikes->emirates ?? '') . '-' . ($r->rider->bikes->plate ?? '')) : '-' }}</a></td>
         @if($vf('status'))<td>
            @php $cardStatus = \App\Models\FuelCards::statusDisplay($r->status); @endphp
            <span class="badge {{ $cardStatus['badge'] }}">{{ $cardStatus['label'] }}</span>
         </td>@endif
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  @canany(['fuel_cards_assign_create', 'fuel_cards_assign_edit'])
                     @if(!$r->assigned_to)
                        @if($r->status === \App\Models\FuelCards::STATUS_LOST)
                           <span class="dropdown-item text-muted" title="A lost card cannot be assigned.">
                              <i class="ti ti-alert-triangle my-1"></i>Lost
                           </span>
                        @elseif($r->status === \App\Models\FuelCards::STATUS_DEACTIVATED)
                           <span class="dropdown-item text-muted" title="Activate this card before assigning it.">
                              <i class="ti ti-ban my-1"></i>Deactivated
                           </span>
                        @else
                        <a href="javascript:void(0);" data-size="lg" data-title="Assign Fuel Card" data-action="{{ route('fuelCards.assign', $r->id) }}" class='show-modal dropdown-item waves-effect'>
                           <i class="ti ti-gas-station my-1"></i>Assign
                        </a>
                        @endif
                     @else
                        <a href="javascript:void(0);" data-size="lg" data-title="Return Fuel Card" data-action="{{ route('fuelCards.return', $r->id) }}" class='dropdown-item waves-effect show-modal'>
                           <i class="fa fa-undo my-1"></i>Return
                        </a>
                        @can('fuel_cards_card_edit')
                        <a href="javascript:void(0);" data-size="xl" data-title="Charge Rider For Lost Card" data-action="{{ route('fuelCards.chargeLost', $r->id) }}" class='dropdown-item waves-effect show-modal text-danger'>
                           <i class="ti ti-alert-triangle my-1"></i>Charge Lost
                        </a>
                        @endcan
                     @endif
                  @endcanany
                  @can('fuel_cards_card_edit')
                     <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Update Card Details" data-action="{{ route('fuelCards.edit', $r->id) }}">
                        <i class="fa fa-edit my-1"></i> Edit
                     </a>
                  @endcan
                  @can('fuel_cards_card_delete')
                  <a href="#" class='dropdown-item waves-effect' 
                    onclick="confirmDelete('{{route('fuelCards.destroy', $r->id) }}')">
                    <i class="fa fa-trash my-1"></i> Delete
                  </a>
                  @endcan
               </div>
            </div>
         </td>
      </tr>
      @endforeach
   </tbody>
</table>
@if($data->isEmpty())
   <div class="text-center mt-5">
      <h3>No Fuel Cards found</h3> 
   </div>
@endif
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
