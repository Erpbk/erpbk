@push('third_party_stylesheets')
<style>
   .table-responsive {
      max-height: calc(100vh - 280px);
   }
</style>
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Number: activate to sort column ascending">Card Number</th>
         <th title="Fuel company" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Fuel company: activate to sort column ascending">Fuel company</th>
         <th title="Bike" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Bike: activate to sort column ascending">Notification</th>
         <th title="User" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Balance: activate to sort column ascending">Rider</th>
         <th title="User" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Balance: activate to sort column ascending">Bike</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php
         $r->rider?->load('bikes') ?? '';
      @endphp
      <tr class="text-center">
         <td>
            <a href="{{ route('fuelCards.show' , $r->id)}}" >
               {{$r->card_number}}
            </a>
         </td>
         <td>{{ $r->fuelCompany?->name ?? '—' }}</td>
         <td>
            @if((! $r->bike_no ?? 1 == $r->rider?->bikes?->plate ?? 0) && $r->status == 'Active')
               <br><a href="javascript:void(0);" data-size="lg" data-title="Update Bike Assignment" data-action="{{ route('fuelCards.update_assignment', $r->id) }}" class='show-modal btn btn-danger btn-sm'>
                  Update
               </a>
            @else
               <a @if($r->attachment) href="{{ storage_url($r->attachment) }}" target="_blank" @else href="javascript:void(0);" @endif class="btn btn-success btn-sm">OK</a>
            @endif

         </td>
         <td style="text-align: left;">
            <a @if($r->rider) href="{{ route('riders.show', $r->rider->id) }}" target="_blank" @else href="javascript:void(0);" @endif" >{{$r->rider? ($r->rider->name) : '-'}}</a>
         </td>
         <td><a @if($r->rider?->bikes) href="{{ route('bikes.show', $r->rider->bikes->id) }}" target="_blank" @else href="javascript:void(0);" @endif">{{ ($r->rider?->bikes?->emirates ?? '') .'-'. ($r->rider?->bikes?->plate ?? '') }}</a></td>
         <td>
            @if($r->status == 'Active')
                <span class="badge  bg-success">Active</span>
            @else
                <span class="badge  bg-danger">Inactive</span>
            @endif
         </td>
         <td style="position: relative;">
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                  @can('fuel_assign')
                     @if(!$r->assigned_to)
                        <a href="javascript:void(0);" data-size="lg" data-title="Assign Fuel Card" data-action="{{ route('fuelCards.assign', $r->id) }}" class='show-modal dropdown-item waves-effect'>
                           <i class="ti ti-gas-station my-1"></i>Assign
                        </a>
                     @else
                        <a href="javascript:void(0);" data-size="lg" data-title="Return Fuel Card" data-action="{{ route('fuelCards.return', $r->id) }}" class='dropdown-item waves-effect show-modal'>
                           <i class="fa fa-undo my-1"></i>Return
                        </a>
                        <a href="javascript:void(0);" data-size="lg" data-title="Update Assignment" data-action="{{ route('fuelCards.update_assignment', $r->id) }}" class='dropdown-item waves-effect show-modal'>
                           <i class="fa fa-undo my-1"></i>Return & Assign
                        </a>
                     @endif
                  @endcan
                  @can('fuel_edit')
                     <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Update Card Details" data-action="{{ route('fuelCards.edit', $r->id) }}">
                        <i class="fa fa-edit my-1"></i> Edit
                     </a>
                  @endcan
                  @can('fuel_delete')
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