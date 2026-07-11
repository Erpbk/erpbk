@push('third_party_stylesheets')
<style>
   .table-responsive {
      max-height: calc(100vh - 280px);
   }
</style>
@endpush
@php
   $bankColumns = \App\Support\BankFormLayout::userFacingFieldKeys();
   $colspan = count($bankColumns) + 1;
@endphp
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         @foreach($bankColumns as $col)
            <th title="{{ \App\Support\BankFormLayout::labelForFieldKey($col) }}" class="sorting" tabindex="0" rowspan="1" colspan="1">{{ \App\Support\BankFormLayout::labelForFieldKey($col) }}</th>
         @endforeach
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @forelse($data as $r)
      <tr class="text-center">
         @foreach($bankColumns as $col)
            <td>
               @if($col === 'name')
                  <a href="{{ route('bank.ledger' , $r->id)}}">{{ $r->name }}</a>
               @elseif($col === 'status')
                  @if($r->status == 1)
                     <span class="badge bg-success">Active</span>
                  @else
                     <span class="badge bg-danger">Inactive</span>
                  @endif
               @elseif($col === 'branch_id')
                  {{ $r->branch_name ?? $r->branch_id }}
               @else
                  {{ data_get($r, $col) }}
               @endif
            </td>
         @endforeach
         <td style="position: relative;">
               <div class="dropdown">
                  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                     <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                     @can('cash_&_banks_banks_view')
                        <a href="{{ route('bank.ledger' , $r->id)}}" target="_blank" class='dropdown-item waves-effect'>
                           <i class="fa fa-eye my-1"></i>view
                        </a>
                     @endcan
                     @can('cash_&_banks_banks_edit')
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Update Bank Details" data-action="{{ route('banks.edit', $r->id) }}">
                           <i class="fa fa-edit my-1"></i> Edit
                        </a>
                     @endcan
                     @can('cash_&_banks_banks_delete')
                     <a href="#" class='dropdown-item waves-effect'
                     data-delete-url="{{ route('bank.delete', $r->id) }}"
                     onclick="confirmDelete(this.dataset.deleteUrl)">
                     <i class="fa fa-trash my-1"></i> Delete
                     </a>
                     @endcan
                  </div>
               </div>
            </td>
      </tr>
      @empty
      <tr>
         <td colspan="{{ $colspan }}" class="text-center">
            <h4 class="mt-3">No Data Found</h4>
         </td>
      </tr>
      @endforelse
   </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
<div class="modal modal-default filtetmodal fade" id="customoizecolmn" tabindex="-1" data-bs-backdrop="static"role="dialog" aria-hidden="true">
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
