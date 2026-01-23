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
         <th title="Type" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Type: activate to sort column ascending">Card Type</th>
         <th title="User" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Balance: activate to sort column ascending">Assigned To</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td>
            <a href="{{ route('fuelCards.show' , $r->id)}}" >
               {{$r->card_number}}
            </a>
         </td>
         <td>{{$r->card_type}}</td>
         <td>{{$r->rider? ($r->rider->rider_id. '-'. $r->rider->name) : '-'}}</td>
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