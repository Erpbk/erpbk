@extends('leasing_companies.view')
<style>
    .table-responsive {
        max-height: calc(100vh + 350px);
    }
</style>
@section('page_content')
    <div class="content">
        @include('flash::message')
        <div class="clearfix"></div>
        @can('payments_view')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                @can('payments_create')
                    <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="xl" data-title="Add New Payment" data-action="{{ route('payments.create') }}?leasing_company_id={{ $leasingCompany->id }}">Add New</button>
                @endcan
            </div>
            <div class="card-body table-responsive py-0" id="table-data">
                <table class="table table-striped dataTable no-footer" id="dataTableBuilder">
                    <thead class="text-center">
                        <tr role="row">
                            <th title="Leasing Company" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Leasing Company: activate to sort column ascending">Reference</th>
                            <th title="Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Account: activate to sort column ascending">Receiver</th>
                            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
                            <th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher: activate to sort column ascending">Voucher</th>
                            <th title="Attachmnet" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachment: activate to sort column ascending">Attachment</th>
                            <th title="Date of Payment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date of Payment: activate to sort column ascending">Date of Payment</th>
                            <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
                            <th title="Description" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Description: activate to sort column ascending">Description</th>
                            <th title="Voucher Type" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher Type: activate to sort column ascending">Status</th>
                            <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $payment)
                        <tr>
                            <td>{{ $payment->reference ?? '-' }}</td>
                            <td> {!! nl2br($payment->payedTo()) !!} </td>
                            <td>AED {{ number_format($payment->amount, 2) }}</td>
                            <td>
                                @if($payment->voucher_id)
                                    <a href="javascript:void(0);" data-action="{{ route('vouchers.show', $payment->voucher_id) }}" class="text-primary show-modal" data-title="Payment Voucher" data-size="xl">
                                        {{ $payment->voucher->voucher_type . '-'. $payment->voucher_id }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                @if($payment->attachment)
                                    <a href="{{ url('storage/vouchers/' . $payment->attachment) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fa fa-file"></i> View
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ \App\Helpers\Common::DateFormat($payment->date_of_payment) }}</td>
                            <td>{{ \App\Helpers\Common::MonthFormat($payment->billing_month) }}</td>
                            <td>{{ $payment->description }}</td>
                            <td>
                                @if($payment->status == 1)
                                <span class="badge bg-success">Active</span>
                                @else
                                <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td style="position: relative;">
                                <div class="dropdown">
                                <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $payment->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                                    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $payment->id }}" style="z-index: 1050;">
                                    @can('payments_edit')
                                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Update Payment Details" data-action="{{ route('payments.edit', $payment->id) }}">
                                            <i class="fa fa-edit my-1"></i> Edit
                                        </a>
                                    @endcan
                                    @can('payments_delete')
                                    <a href="javascript:void(0);" class='dropdown-item waves-effect delete-payment' 
                                        data-url="{{ route('payments.destroy', $payment->id) }}">
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
        @endcan
        @cannot('payments_view')
            <div class="text-center mt-5">
                <h3>You do not have permission to view Payments.</h3> 
            </div>
        @endcannot
    </div>
@endsection

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function(){
        $(document).on('click', '.delete-payment', function(e) {
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
                                'Payment has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete Payment. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
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
