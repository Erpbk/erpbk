@push('third_party_stylesheets')
<style>
   .table-responsive {
      max-height: calc(100vh - 280px);
   }
</style>
@endpush
@php
   $visibleFieldMap = \App\Support\ModuleFieldSettings::visibleFieldMap('cash_banks');
   $showField = function (string $key) use ($visibleFieldMap): bool {
      return array_key_exists($key, $visibleFieldMap);
   };
   $labelFor = function (string $key, string $fallback) use ($visibleFieldMap): string {
      return $visibleFieldMap[$key] ?? $fallback;
   };
   $colspan = 1; // actions
   foreach (['name', 'title', 'account_no', 'branch', 'balance', 'status'] as $k) {
      if ($showField($k)) {
         $colspan++;
      }
   }
@endphp
<table class="table dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         @if($showField('name'))<th title="{{ $labelFor('name', 'Name') }}" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="{{ $labelFor('name', 'Name') }}: activate to sort column ascending">{{ $labelFor('name', 'Name') }}</th>@endif
         @if($showField('title'))<th title="{{ $labelFor('title', 'Title') }}" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="{{ $labelFor('title', 'Title') }}: activate to sort column ascending">{{ $labelFor('title', 'Title') }}</th>@endif
         @if($showField('account_no'))<th title="{{ $labelFor('account_no', 'Account No') }}" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="{{ $labelFor('account_no', 'Account No') }}: activate to sort column ascending">{{ $labelFor('account_no', 'Account No') }}</th>@endif
         @if($showField('branch'))<th title="{{ $labelFor('branch', 'Branch') }}" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="{{ $labelFor('branch', 'Branch') }}: activate to sort column ascending">{{ $labelFor('branch', 'Branch') }}</th>@endif
         @if($showField('balance'))<th title="{{ $labelFor('balance', 'Balance') }}" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="{{ $labelFor('balance', 'Balance') }}: activate to sort column ascending">{{ $labelFor('balance', 'Balance') }}</th>@endif
         @if($showField('status'))<th title="{{ $labelFor('status', 'Status') }}" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="{{ $labelFor('status', 'Status') }}: activate to sort column ascending">{{ $labelFor('status', 'Status') }}</th>@endif
         <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
      </tr>
   </thead>
   <tbody>
      @forelse($data as $r)
      <tr class="text-center">
         @if($showField('name'))<td><a href="{{ route('bank.files' , $r->id)}}">{{$r->name}}</a><br/></td>@endif
         @if($showField('title'))<td>{{$r->title}}</td>@endif
         @if($showField('account_no'))<td>{{$r->account_no}}</td>@endif
         @if($showField('branch'))<td>{{ $r->branch_name }}</td>@endif
         @if($showField('balance'))<td>{{$r->balance}}</td>@endif
         @if($showField('status'))<td>
            @if($r->status == 1)
                <span class="badge  bg-success">Active</span>
            @else
                <span class="badge  bg-danger">Inactive</span>
            @endif
            </td>@endif
         <td style="position: relative;">
               <div class="dropdown">
                  <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $r->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                     <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                  </button>
                  <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $r->id }}" style="z-index: 1050;">
                     @can('bank_view')
                        <a href="{{ route('bank.files' , $r->id)}}" target="_blank" class='dropdown-item waves-effect'>
                           <i class="fa fa-eye my-1"></i>view
                        </a>
                     @endcan
                     @can('bank_edit')
                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Update Bank Details" data-action="{{ route('banks.edit', $r->id) }}">
                           <i class="fa fa-edit my-1"></i> Edit
                        </a>
                     @endcan
                     @can('bank_delete')
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