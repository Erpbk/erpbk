@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
   <thead class="text-center">
      <tr role="row">
         <th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Name</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Total Ticket Amountq</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Total Service Charges</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Total Admin Charges</th>
         <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Total Amount</th>
         <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Status</th>
         <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"> Actions </th>
      </tr>
   </thead>
   <tbody>
      @foreach($data as $r)
      <tr class="text-center">
         <td> <a href="{{ route('rtaFines.tickets' , $r->id) }}">{{$r->name}}</a><br> </td>
         @php
         $balance = DB::table('rta_fines')->where('rta_account_id' , $r->id)->sum('amount');
         $account_tax = DB::table('rta_fines')->where('rta_account_id' , $r->id)->sum('service_charges');
         $admin_charges = DB::table('rta_fines')->where('rta_account_id' , $r->id)->sum('admin_fee');
         $total_amount = DB::table('rta_fines')->where('rta_account_id' , $r->id)->sum('total_amount');
         @endphp
         <td>@if($balance == '') - @else AED {{ $balance ?? '-' }} @endif</td>
         <td>@if($account_tax == '') - @else AED {{ $account_tax }}@endif</td>
         <td>@if($admin_charges == '') - @else AED {{ $admin_charges }}@endif</td>
         <td>@if($total_amount == '') - @else AED {{ $total_amount ?? '-' }} @endif</td>
         <td>
            @if($r->status == 1)
            <span class="badge  bg-success">Active</span>
            @else
            <span class="badge  bg-danger">Inactive</span>
            @endif
         </td>
         <td>
            <div class="dropdown">
               <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                  <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
               </button>
               <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" style="">
                  <a href="{{ route('rtaFines.tickets' , $r->id) }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-eye"></i>View
                  </a>
                  <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount{{ $r->id }}" class='dropdown-item waves-effect'>
                     <i class="fa fa-edit"></i>Edit
                  </a>
                  <a href="javascript:void(0);" onclick='confirmDelete("{{route('rtaFines.deleteaccount', $r->id) }}")' class='dropdown-item confirm-modal' data-size="lg" data-title="Delete Account">
                     <i class="fa fa-trash"></i>Delete
                  </a>
               </div>
            </div>
         </td>
      </tr>

      <div class="modal modal-default filtetmodal fade" id="editaccount{{ $r->id }}" tabindex="-1" data-bs-backdrop="static" role="dialog" aria-hidden="true">
         <div class="modal-dialog modal-lg modal-slide-top modal-full-top">
            <div class="modal-content">
               <div class="modal-header">
                  <h5 class="modal-title">Update Account</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
               </div>
               <div class="modal-body" id="searchTopbody">
                  <form action="{{ route('rtaFines.editaccount') }}" method="POST">
                     @csrf
                     <input type="hidden" name="id" name="id" value="{{ $r->id }}">
                     <div class="row">
                        <div class="form-group col-md-12">
                           <label for="name">Name</label>
                           <input type="text" name="name" class="form-control" placeholder="Enter Your Account Name" value="{{ $r->name }}">
                        </div>
                        <div class="form-group col-md-12">
                           <label for="name">Traffic Code Number</label>
                           <input type="text" name="traffic_code_number" class="form-control" placeholder="Enter Your Account Name" value="{{ $r->traffic_code_number }}">
                        </div>
                        <div class="form-group col-md-12">
                           <label for="account_tax">Service Charges</label>
                           <input type="number" name="account_tax" class="form-control" placeholder="Enter Your Service" value="{{ $r->account_tax }}">
                        </div>
                        <div class="form-group col-md-12">
                           <label for="admin_charges">Admin Charges</label>
                           <input type="number" name="admin_charges" class="form-control" placeholder="Enter Your Admin Charges" value="{{ $r->admin_charges }}">
                        </div>
                        <div class="col-md-12 form-group text-center">
                           <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Submit</button>
                        </div>
                     </div>
                  </form>
               </div>
            </div>
         </div>
      </div>
      @endforeach
   </tbody>
</table>
{!! $data->links('pagination') !!}
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