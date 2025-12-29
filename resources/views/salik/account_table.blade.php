@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Ticket No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Ticket No: activate to sort column ascending" aria-sort="descending">Name</th>
            <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Balance</th>
            <th title="Rider" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Rider: activate to sort column ascending">Admin Charges</th>
            <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Status</th>
            <th title="Action" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($data as $r)
        <tr class="text-center">
            <td> <a href="{{ route('salik.tickets' , $r->id) }}">{{$r->name}}</a><br> </td>
            @php
            $balance = DB::table('saliks')->where('salik_account_id' , $r->id)->sum('total_amount')
            @endphp
            <td>@if($balance == '') - @else AED {{ $balance ?? '-' }} @endif</td>
            <td>@if($r->admin_charges == '') - @else AED {{ $r->admin_charges }}@endif</td>
            <td>
                @if($r->status == 1)
                <span class="badge  bg-success">Active</span>
                @else
                <span class="badge  bg-danger">Inactive</span>
                @endif
            </td>
            <td>
                <div class='btn-group'>
                    <a href="{{ route('salik.tickets' , $r->id) }}" class='btn btn-default btn-xs'>
                        <i class="fa fa-eye"></i>
                    </a>
                    <a href="javascript:void(0)" data-bs-toggle="modal" data-bs-target="#editaccount{{ $r->id }}" class='btn btn-default btn-xs'>
                        <i class="fa fa-edit"></i>
                    </a>
                    <a href="javascript:void(0);" onclick='confirmDelete("{{route('salik.deleteaccount', $r->id) }}")' class='btn btn-danger btn-sm confirm-modal' data-size="lg" data-title="Delete Account">
                        <i class="fa fa-trash"></i>
                    </a>
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
                        <form action="{{ route('salik.editaccount') }}" method="POST">
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
                                    <label for="admin_charges">Admin Charges</label>
                                    <input type="number" name="admin_charges" class="form-control" placeholder="Enter Your Admin Charges" value="{{ $r->admin_charges }}" step="0.01">
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