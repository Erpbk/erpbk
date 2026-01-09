@extends('banks.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>

        <div class="card">
            <div class="card-body table-responsive py-0" id="table-data">
                <table class="table table-striped dataTable no-footer mt-3" id="dataTableBuilder">
                    <thead class="text-center">
                        <tr role="row">
                            <th title="Transaction Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Transaction Number: activate to sort column ascending">Transaction Number</th>
                            <th title="Sender" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Sender: activate to sort column ascending">Sender</th>
                            <th title="Bank" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Bank: activate to sort column ascending">Bank</th>
                            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
                            <th title="Date of Receipt" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date of Receipt: activate to sort column ascending">Date of Receipt</th>
                            <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
                            <th title="Description" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Description: activate to sort column ascending">Description</th>
                            <th title="Status" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Status: activate to sort column ascending">Status</th>
                            <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $receipt)
                        <tr>
                            <td>{{ $receipt->transaction_number }}</td>
                            <td>
                                @php
                                $account = $receipt->account_id ? \App\Models\Accounts::find($receipt->account_id) : null;
                                @endphp
                                {{ $account ? $account->name : '-' }}
                            </td>
                            <td>
                                @php
                                $bank = $receipt->bank_id ? \App\Models\Banks::find($receipt->bank_id) : null;
                                @endphp
                                {{ $bank ? $bank->name : '-' }}
                            </td>
                            <td>AED {{ number_format($receipt->amount, 2) }}</td>
                            <td>{{ $receipt->date_of_receipt }}</td>
                            <td>{{ $receipt->billing_month }}</td>
                            <td>{{ $receipt->description }}</td>
                            <td>
                                @if($receipt->status == 1)
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td style="position: relative;">
                                <div class="dropdown">
                                <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $receipt->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                                    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $receipt->id }}" style="z-index: 1050;">
                                    @can('bank_view')
                                        <a href="{{ route('receipts.show' , $receipt->id)}}" class='dropdown-item waves-effect'>
                                            <i class="fa fa-eye my-1"></i>view
                                        </a>
                                    @endcan
                                    @can('bank_edit')
                                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="Update Bank Details" data-action="{{ route('receipts.edit', $receipt->id) }}">
                                            <i class="fa fa-edit my-1"></i> Edit
                                        </a>
                                    @endcan
                                    @can('sim_delete')
                                    <a href="javascript:void(0);" class='dropdown-item waves-effect delete-receipt' 
                                        data-url="{{ route('receipts.destroy', $receipt->id) }}">
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
                @if($data->isEmpty())
                    <div class="text-center mt-5">
                        <h3>No Payments found</h3> 
                    </div>
                @endif
                @if(method_exists($data, 'links'))
                    {!! $data->links('components.global-pagination') !!}
                @endif
            </div>
        </div>
    </div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        $(document).on('click', '.delete-receipt', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Receipt has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete Receipt. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
                                'error'
                            );
                        }
                    });
                }
            });
        });
    })
</script>

@endsection