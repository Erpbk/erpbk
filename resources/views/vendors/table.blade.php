@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   @php $vf = static fn (string $f): bool => field_visible('vendor', $f); @endphp
   <thead class="text-center">
      <tr role="row">
         @if($vf('name'))<th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Name: activate to sort column ascending">Name</th>@endif
         @if($vf('email'))<th title="Email" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Email: activate to sort column ascending">Email</th>@endif
         @if($vf('contact_number'))<th title="Contact Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact Number: activate to sort column ascending">Contact Number</th>@endif
         @if($vf('status'))<th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>@endif
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);"> <i class="fa fa-filter"></i></a>
         </th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      @php $vendorPendingDeletion = record_is_pending_deletion($r); @endphp
      <tr class="text-center {{ $vendorPendingDeletion ? 'table-warning' : '' }}">
         @if($vf('name'))<td><a href="{{ route('vendors.show', $r->id) }}">{{$r->name}}</a> @include('delete_requests._pending_badge', ['model' => $r])<br /></td>@endif
         @if($vf('email'))<td>{{$r->email }}</td>@endif
         @if($vf('contact_number'))<td>{{$r->contact_number }}</td>@endif
         @if($vf('status'))<td>
            @if($vendorPendingDeletion)
               @include('delete_requests._pending_badge', ['model' => $r])
            @elseif($r->status == 1)
            <span class="badge  bg-success">Active</span>
            @else
            <span class="badge  bg-danger">Inactive</span>
            @endif
         </td>@endif
         <td>
            @if($vendorPendingDeletion)
               <span class="text-muted small">Locked</span>
            @else
            <div class='btn-group'>
               @can('vendors_edit')
               <a href="javascript:void(0);" data-action="{{ route('vendors.edit', $r->id) }}" class='btn btn-info btn-sm show-modal' data-size="lg" data-title="Update Vendors">
                  <i class="fa fa-edit"></i>
               </a>
               @endcan
               @can('vendors_delete')
               <a href="javascript:void(0);" data-delete-url="{{ route('vendors.destroy', $r->id) }}" onclick="confirmDelete(this.dataset.deleteUrl)" class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Item">
                  <i class="fa fa-trash"></i>
               </a>
               @endcan
            </div>
            @endif
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
