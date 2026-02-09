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
         <th title="Name" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Name: activate to sort column ascending">Name</th>
         <th title="Contact Person" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact Person: activate to sort column ascending">Contact Person</th>
         <th title="Contact Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Contact Number: activate to sort column ascending">Contact Number</th>
         <th title="TRN Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="TRN Number: activate to sort column ascending">TRN Number</th>
         <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td><a href="{{ route('leasingCompany.files', $r->id) }}">{{$r->name}}</a></td>
         <td>{{$r->contact_person}}</td>
         <td>{{$r->contact_number}}</td>
         <td>{{$r->trn_number}}</td>
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
                  @can('leasing_view')
                  <a href="{{ route('leasingCompany.files', $r->id) }}" target="_blank" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye my-1"></i> View
                  </a>
                  @endcan
                  @can('leasing_edit')
                  <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Update Leasing Company" data-action="{{ route('leasingCompanies.edit', $r->id) }}">
                     <i class="fa fa-edit my-1"></i> Edit
                  </a>
                  @endcan
                  @can('leasing_delete')
                  <a href="#" class='dropdown-item waves-effect'
                     onclick="confirmDelete('{{route('leasingCompanies.delete', $r->id) }}')">
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
<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
   <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
      <div class="modal-content">
         <div class="modal-header">
            <h5 class="modal-title">Filter Leasing Companies</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
         </div>
         <div class="modal-body" id="searchTopbody">
            <div style="display: none;" class="loading-overlay" id="loading-overlay">
               <div class="spinner-border text-primary" role="status"></div>
            </div>
            <form id="filterForm" action="{{ route('leasingCompanies.index') }}" method="GET">
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