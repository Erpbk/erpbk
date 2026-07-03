<div class="card-body table-responsive py-0" id="table-data">
    <table class="table dataTable no-footer mt-3" id="dataTableBuilder">
        <thead class="text-center">
            <tr role="row">
                <th title="Transaction Number" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Transaction Number: activate to sort column ascending">Reference</th>
                <th title="Sender" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Sender: activate to sort column ascending">Sender</th>
                <th title="Bank" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Bank: activate to sort column ascending">Receiver</th>
                <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
                <th title="Voucher" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher: activate to sort column ascending">Voucher</th>
                <th title="Attachement" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachement: activate to sort column ascending">Attachement</th>
                <th title="Date of Receipt" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date of Receipt: activate to sort column ascending">Date of Receipt</th>
                <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
                <th title="Description" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Description: activate to sort column ascending">Description</th>
                <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
            </tr>
        </thead>
        <tbody>
            @php
              $__companySlug = \App\Support\CompanyRouteContext::slug();
              $voucherShowParams = static function ($voucherId) use ($__companySlug): array {
                $params = ['voucher' => $voucherId];
                if (!empty($__companySlug)) {
                  $params['company_slug'] = $__companySlug;
                }
                return $params;
              };
            @endphp
            @foreach($data as $receipt)
            <tr>
                <td>{{ $receipt->reference?? '-' }}</td>
                <td>{{ $receipt->payerAccount->account_code .'-'.  $receipt->payerAccount->name}}</td>
                <td>{{ $receipt->payeeAccount->account_code . '-' .  $receipt->payeeAccount->name}}</td>
                <td>{{ \App\Helpers\Currency::format($receipt->amount) }}</td>
                <td>
                    <a href="javascript:void(0);" data-action="{{ route('vouchers.show', $voucherShowParams($receipt->voucher_id)) }}" class="text-primary show-voucher-panel" data-title="Receipt Voucher" data-collapse-sidebar="1">
                        {{ $receipt->voucher->voucher_type . '-'. $receipt->voucher_id }}
                    </a>
                </td>
                <td>
                    @if($receipt->attachment)
                        <a href="{{ url('storage/vouchers/' . $receipt->attachment) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                            <i class="fa fa-file"></i> View
                        </a>
                    @else
                        -
                    @endif
                </td>
                <td>{{ \App\Helpers\Common::DateFormat($receipt->date_of_receipt) }}</td>
                <td>{{ \App\Helpers\Common::MonthFormat($receipt->billing_month) }}</td>
                <td>{{ $receipt->description }}</td>
                <td style="position: relative;">
                    <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $receipt->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $receipt->id }}" style="z-index: 1050;">
                        @can('receipt_create')
                            @if(!str_contains($receipt->reference, 'CI-') && !str_contains($receipt->reference, 'LBI-'))
                            <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Add New Receipt (Cloned From RV-{{ $receipt->voucher_id }})" data-action="{{ route('receipts.clone', $receipt->id) }}">
                                <i class="fa fa-copy my-1"></i>Clone Receipt
                            </a>
                            @endif
                        @endcan
                        @can('receipt_edit')
                            <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="xl" data-title="Update Receipt Details" data-action="{{ route('receipts.edit', $receipt->id) }}">
                                <i class="fa fa-edit my-1"></i> Edit
                            </a>
                        @endcan
                        @can('receipt_delete')
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
        <h3>No Receipts found</h3> 
    </div>
@endif
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif

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
