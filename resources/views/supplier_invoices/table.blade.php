@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Inv Date" class="sorting" rowspan="1" colspan="1" >Inv Date</th>
         <th title="Inv Id" class="sorting" rowspan="1" colspan="1" >Inv Id</th>
         <th title="Billing Month" class="sorting" rowspan="1" colspan="1" >Billing Month</th>
         <th title="Supplier" class="sorting" rowspan="1" colspan="1" >Supplier</th>
         <th title="Descriptions" class="sorting" rowspan="1" colspan="1" >Descriptions</th>
         <th title="Total Amount" class="sorting" rowspan="1" colspan="1" >Total Amount</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);" > <i class="fa fa-search"></i></a></th>
         <th tabindex="0" rowspan="1" colspan="1" aria-sort="descending">
            <a data-bs-toggle="modal" data-bs-target="#customoizecolmn" href="javascript:void(0);" > <i class="fa fa-filter"></i></a> 
         </th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td>{{$r->inv_date}}</td>
         <td>{{$r->inv_id}}</td>
         <td>{{ $r->billing_month ? \Carbon\Carbon::parse($r->expiry_date)->format('M Y') : '-' }}</td>
            @php
                $supplier = DB::table('suppliers')->where('id', $r->supplier_id)->first();
            @endphp
            <td>
                @if ($supplier)
                    {{ $supplier->name }}
                @else
                    -
                @endif
            </td>
         <td>{{$r->descriptions ?? 'N/A' }}</td>
         <td>{{$r->total_amount ?? 'N/A' }}</td>
         <td>
            <div class='btn-group'>
               <a href="{{ route('supplierInvoices.show', $r->id) }}"  class='btn btn-default btn-sm' target="_blank">
               <i class="fa fa-eye"></i>
               </a> 
               <a href="javascript:void(0);" data-title="Edit Invoice" data-size="xl" data-action="{{ route('supplierInvoices.edit', $r->id) }}" class='btn btn-info btn-sm show-modal'>
               <i class="fa fa-edit"></i>
               </a>
               <a href="javascript:void(0);"  onclick='confirmDelete("{{route('supplierInvoices.delete', $r->id) }}")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Invoice">
               <i class="fa fa-trash"></i>
               </a>
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