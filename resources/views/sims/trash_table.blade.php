@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Number: activate to sort column ascending" >Number</th>
         <th title="Company" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Company: activate to sort column ascending" >Company</th>
         <th title="Emi" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Emi: activate to sort column ascending" >Emi</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending" >Status</th> 
         <th title="Delete Permanently">Delete/Restore</th>
        </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td>
            <a href="{{ route('sims.showTrash', $r->id) }}" class="table-link">
                  {{$r->number}}
            </a>
         </td>
         <td>{{$r->company}}</td>
         <td>{{$r->emi}}</td>
         <td>
            @if($r->status && $r->status == 1)
               <span class="badge bg-success" style="font-size: 1rem;">Active</span>
            @elseif ($r->status && $r->status == 0)
               <span class="badge bg-danger" style="font-size: 1rem;">Inactive</span>
            @else
               <span class="badge bg-secondary" >Unknown</span>
            @endif
         </td>
         <td>
            <div class='btn-group'>
               @can('sim_delete')
               <a href="javascript:void(0);"  onclick='confirmDelete("{{route('sims.delete', $r->id) }}")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Sim">
               <i class="fa fa-trash"></i>
               </a>
               <a href="javascript:void(0);" style="margin-left: 2px;" onclick='confirmRestore("{{route('sims.restore', $r->id) }}")' class='btn btn-success btn-sm confirm-modal' data-size="lg" data-title="Restore Sim">
               <i class="fa fa-reply"></i>
               </a>
               @endcan
            </div>
         </td>
         <td></td>
      </tr>
      @endforeach
   </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif