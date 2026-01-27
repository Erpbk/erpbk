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
        @can('payments_view')
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div class="card-search">
                    <input type="text" id="quickSearch" name="quick_search" class="form-control" placeholder="Quick Search..." value="{{ request('quick_search') }}">
                </div>
                @can('payments_create')
                    <button class="btn btn-primary btn-sm show-modal" href="javascript:void(0);" data-size="lg" data-title="Add New Cheque" data-action="{{ route('cheques.create') }}?id={{ request()->segment(3) }}">Add New</button>
                @endcan
            </div>
            <div class="card-body table-responsive py-0" id="table-data">
                <table class="table table-striped dataTable no-footer" id="dataTableBuilder">
                    <thead class="text-center">
                        <tr role="row">
                            <th title="Bank" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Bank: activate to sort column ascending">Reference</th>
                            <th title="Date of Payment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date of Payment: activate to sort column ascending">Cheque No.</th>
                            <th title="Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Account: activate to sort column ascending">Reciever</th>
                            <th title="Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Account: activate to sort column ascending">Sender</th>
                            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
                            <th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher: activate to sort column ascending">Voucher</th>
                            <th title="Attachmnet" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachment: activate to sort column ascending">Attachment</th>
                            <th title="Description" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Description: activate to sort column ascending">Description</th>
                            <th title="Voucher Type" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher Type: activate to sort column ascending">Status</th>
                            <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($data as $cheque)
                        <tr>
                            <td>{{ $cheque->reference ?? '-' }}</td>
                            <td>
                                <a href="javascript:void(0);" class="show-modal text-primary" data-size="xl"
                                data-action="{{ route('cheques.show', $cheque->id) }}"
                                data-title="Cheque Details">
                                    {{ $cheque->cheque_number }}
                                </a>
                            </td>
                            <td>{{ $cheque->payee->name ?? $cheque->payee_name ?? '-' }}</td>
                            <td>{{ $cheque->payer->name ?? $cheque->payer_name ?? '-' }}</td>
                            <td>AED {{ number_format($cheque->amount, 2) }}</td>
                            <td>
                                <a href="javascript:void(0);" data-action="{{ route('vouchers.show', $cheque->voucher_id) }}" class="text-primary show-modal" data-title="Cheque Voucher" data-size="xl">
                                    {{ $cheque->voucher->voucher_type . '-'. $cheque->voucher_id }}
                                </a>
                            </td>
                            <td>
                                @if($cheque->attachment)
                                    <a href="{{ url('storage/vouchers/' . $cheque->attachment) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                                        <i class="fa fa-file"></i> View
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $cheque->description }}</td>
                            @php
                                $badge=[
                                    'Issued' => 'primary',
                                    'Cleared' => 'success',
                                    'Returned' => 'danger',
                                    'Stop Payment' => 'warning',
                                    'Lost' => 'secondary',
                                ];
                            @endphp
                            <td>
                                <span class="badge bg-{{ $badge[$cheque->status] ?? 'secondary' }}">{{ $cheque->status }}</span>
                            </td>
                            <td style="position: relative;">
                                <div class="dropdown">
                                <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $cheque->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                                    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $cheque->id }}" style="z-index: 1050;">
                                    @can('payments_edit')
                                        <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Update Payment Details" data-action="{{ route('cheques.edit', $cheque->id) }}">
                                            <i class="fa fa-edit my-1"></i> Edit
                                        </a>
                                    @endcan
                                    @can('payments_delete')
                                    <a href="javascript:void(0);" class='dropdown-item waves-effect delete-cheque' 
                                        data-url="{{ route('cheques.destroy', $cheque->id) }}">
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
                        <h3>No Cheques found</h3> 
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
        $(document).on('click', '.delete-cheque', function(e) {
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
                                'Cheque has been deleted.',
                                'success'
                            ).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete Cheque. ' + (xhr.responseJSON?.message || xhr.statusText || 'Unknown error'),
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