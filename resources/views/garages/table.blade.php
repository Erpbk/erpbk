@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   @php $vf = static fn (string $f): bool => field_visible('garage', $f); @endphp
   <thead class="text-center">
      <tr role="row">
      <tr role="row">
         @if($vf('name'))<th title="Name" class="sorting_desc" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Name: activate to sort column ascending">Name</th>@endif
         @if($vf('garage_type'))<th title="Type" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1">Type</th>@endif
         @if($vf('contact_person'))<th title="Contact Person" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact Person: activate to sort column ascending">Contact Person</th>@endif
         @if($vf('contact_number'))<th title="Contact Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact Number: activate to sort column ascending">Contact Number</th>@endif
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);"> <i class="fa fa-filter"></i></a>
         </th>
      </tr>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center" data-id="{{ $r->id }}">
         @if($vf('name'))<td><a href="{{ route('garages.show', $r->id) }}">{{$r->name}}</a></td>@endif
         @if($vf('garage_type'))<td @if($r->garage_type === 'internal') class="text-success" @else class="text-danger" @endif>{{ ($r->garage_type ?? 'external') === 'internal' ? 'Internal' : 'External' }}</td>@endif
         @if($vf('contact_person'))<td>{{$r->contact_person}}</td>@endif
         @if($vf('contact_number'))<td>{{$r->contact_number}}</td>@endif
         <td>
            <div class='btn-group'>
               @include('layouts.partials.module_contract_action', [
                  'module' => 'garages',
                  'recordId' => $r->id,
                  'variant' => 'btn-group',
               ])
               <!-- <a href="javascript:void(0);" data-size="md" data-title="New Item" data-action=="{{ route('garages.show', $r->id) }}" class='btn btn-default btn-xs'>
                    <i class="fa fa-eye"></i>
                </a> -->
               @can('garages_garage_edit')
               <a href="javascript:void(0);" data-size="lg" data-title="Update Garage" data-action="{{ route('garages.edit', $r->id) }}" class='btn btn-info btn-sm show-modal'>
                  <i class="fa fa-edit"></i>
               </a>
               @endcan
               @can('garages_garage_delete')
               <a href="javascript:void(0);" onclick='confirmDelete("{{route('garages.delete', $r->id) }}")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Sim">
                  <i class="fa fa-trash"></i>
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
<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Filter Riders</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body" id="searchTopbody">
            <div style="display: none;" class="loading-overlay" id="loading-overlay">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
            <form id="filterForm" action="{{ route('banks.index') }}" method="GET">
               <div class="row">
                  <div class="form-group col-md-12">
                     <input type="number" name="search" class="form-control" placeholder="Search">
                  </div>
                  <div class="col-md-12 form-group text-center">
                     <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                  </div>
               </div>
            </form>
         </div>
      </div>
   </div>
</div>
@include('delete_requests._pending_table_script', ['items' => $data])
