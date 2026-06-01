@push('third_party_stylesheets')
@endpush
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Name: activate to sort column ascending">Image</th>
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Name: activate to sort column ascending">Name</th>
         <th title="Customer" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Customer: activate to sort column ascending">Owner</th>
         <th title="Price" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Price: activate to sort column ascending">Available</th>
         <th title="Price" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Price: activate to sort column ascending">Price</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td>
            @if($r->attachment)
               <div class="image-container">
                     <img src="{{ storage_url($r->attachment) }}" 
                        alt="{{ $r->name }}" 
                        style="width: 60px; height: 60px; object-fit: cover; border-radius: 8px; cursor: pointer; transition: transform 0.3s;"
                        onmouseover="this.style.transform='scale(1.5)'"
                        onmouseout="this.style.transform='scale(1)'"
                        onclick="window.open('{{ storage_url($r->attachment) }}')"
                        class="shadow-sm">
               </div>
            @else
               <span class="text-muted">No image</span>
            @endif
         </td>
         <td><a href="javascript:void(0);" data-action="{{ route('items.show', $r->id) }}" class="show-modal-right">{{ $r->name }}</a></td>
         <td>
            @if($r->owners)
               @foreach ($r->owners as $owner)
                  {!! $owner !!}<br>
               @endforeach
            @else
               {{ 'Unknown' }}
            @endif
         </td>
         <td>{{$r->available }}</td>
         <td>{{$r->price }}</td>
         <td>
            @if($r->status == 1)
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
                  @can('item_edit')
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Edit Data" data-action="{{ route('items.edit', $r->id) }}">
                           <i class="fa fa-edit my-1"></i> Edit
                        </a>
                  @endcan
                  @can('item_delete')
                  <a href="javascript:void(0);" class='dropdown-item waves-effect delete-receipt' 
                        onclick='confirmDelete("{{route('items.destroy' , $r->id ) }}")'>
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
@if(method_exists($data, 'links'))
{!! $data->links('components.global-pagination') !!}
@endif