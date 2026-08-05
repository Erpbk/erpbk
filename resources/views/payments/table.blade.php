<table class="table dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Date of Payment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date of Payment: activate to sort column ascending">Date of Payment</th>
            <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
            <th title="Voucher" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher: activate to sort column ascending">Voucher</th>
            <th title="Reference" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Reference: activate to sort column ascending">Reference</th>
            <th title="Sender" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Sender: activate to sort column ascending">Sender</th>
            <th title="Description" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Description: activate to sort column ascending">Description</th>
            <th title="Receiver" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Receiver: activate to sort column ascending">Receiver</th>
            <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
            <th title="Attachment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachment: activate to sort column ascending">Attachment</th>
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
        $voucherFileParams = static function ($voucherId) use ($__companySlug): array {
            return array_filter([
                'id' => $voucherId,
                'company_slug' => $__companySlug,
            ]);
        };
        $listSidebarParams = array_filter(['company_slug' => $__companySlug]);
        @endphp
        @foreach($data as $payment)
        @php
            $voucherId = $payment->voucher_id;
            $voucherType = $payment->voucher->voucher_type ?? 'PV';
            $voucherLabel = $voucherType . '-' . $voucherId;
            $paymentPendingDeletion = record_is_pending_deletion($payment)
                || ($payment->voucher && record_is_pending_deletion($payment->voucher));
            $pendingDeletionModel = record_is_pending_deletion($payment)
                ? $payment
                : ($payment->voucher ?? $payment);
        @endphp
        <tr class="{{ $paymentPendingDeletion ? 'table-warning' : '' }}" data-id="{{ $payment->id }}">
            <td>
                {{ \App\Helpers\Common::DateFormat($payment->date_of_payment) }}
                @if($paymentPendingDeletion)
                @include('delete_requests._pending_badge', ['model' => $pendingDeletionModel])
                @endif
            </td>
            <td>{{ \App\Helpers\Common::MonthFormat($payment->billing_month) }}</td>
            <td>
                @if($voucherId)
                <a href="javascript:void(0);"
                    data-action="{{ route('vouchers.show', $voucherShowParams($voucherId)) }}"
                    class="text-primary show-voucher-panel"
                    data-title="Payment Voucher {{ $voucherLabel }}"
                    data-collapse-sidebar="1"
                    data-list-url="{{ route('vouchers.list-sidebar', $listSidebarParams) }}">
                    {{ $voucherLabel }}
                </a>
                @else
                -
                @endif
            </td>
            <td>{{ $payment->reference ?? '-' }}</td>
            <td>{{ $payment->payer_account }}</td>
            <td>{{ $payment->description }}</td>
            <td>{{ $payment->payeeAccount->account_code . '-' . $payment->payeeAccount->name }}</td>
            <td>{{ \App\Helpers\Currency::format($payment->amount) }}</td>
            <td>
                @if($payment->attachment)
                <a href="{{ url('storage/vouchers/' . $payment->attachment) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                    <i class="fa fa-file"></i> View
                </a>
                @else
                -
                @endif
            </td>
            <td>
                @if($paymentPendingDeletion)
                @include('delete_requests._locked_cell', ['model' => $pendingDeletionModel])
                @else
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $payment->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $payment->id }}">
                        @if($voucherId)
                        @can('voucher_view')
                        <a href="javascript:void(0);"
                            class="dropdown-item waves-effect show-voucher-panel"
                            data-action="{{ route('vouchers.show', $voucherShowParams($voucherId)) }}"
                            data-title="Payment Voucher {{ $voucherLabel }}"
                            data-collapse-sidebar="1"
                            data-list-url="{{ route('vouchers.list-sidebar', $listSidebarParams) }}">
                            <i class="fa fa-eye my-1"></i> View Voucher
                        </a>
                        @endcan
                        @canany(['voucher_create', 'voucher_edit'])
                        @if(!in_array($voucherType, ['PV', 'RV', 'EXP', 'RFV', 'SV', 'VL', 'LV', 'FAV', 'FDV'], true))
                        <a href="javascript:void(0);"
                            data-size="sm"
                            data-title="Upload Document"
                            data-action="{{ route('voucher.fileupload', $voucherFileParams($voucherId)) }}"
                            class="dropdown-item waves-effect show-modal">
                            <i class="fa fa-file my-1"></i> Upload Document
                        </a>
                        @endif
                        @endcanany
                        @endif
                        @canany([
                        'cash_&_banks_payments_create',
                        'employees_payments_create',
                        'customers_payments_create',
                        'riders_payments_create',
                        'sims_payments_create',
                        'leasing_companies_payments_create',
                        'suppliers_payments_create',
                        ])
                        @if(!str_contains((string) $payment->reference, 'LCI') && !str_contains((string) $payment->reference, 'SUP'))
                        <a href="javascript:void(0);" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Add New Payment (Cloned From PV-{{ $payment->voucher_id }})" data-action="{{ route('payments.clone', $payment->id) }}">
                            <i class="fa fa-copy my-1"></i> Clone Payment
                        </a>
                        @endif
                        @endcanany
                        @canany([
                        'cash_&_banks_payments_edit',
                        'employees_payments_edit',
                        'customers_payments_edit',
                        'riders_payments_edit',
                        'sims_payments_edit',
                        'leasing_companies_payments_edit',
                        'suppliers_payments_edit',
                        ])
                        <a href="javascript:void(0);" class="dropdown-item waves-effect show-modal" data-size="xl" data-title="Update Payment Details" data-action="{{ route('payments.edit', $payment->id) }}">
                            <i class="fa fa-edit my-1"></i> Edit
                        </a>
                        @endcanany
                        @canany([
                        'cash_&_banks_payments_delete',
                        'employees_payments_delete',
                        'customers_payments_delete',
                        'riders_payments_delete',
                        'sims_payments_delete',
                        'leasing_companies_payments_delete',
                        'suppliers_payments_delete',
                        ])
                        <a href="javascript:void(0);" class="dropdown-item waves-effect delete-payment"
                            data-url="{{ route('payments.destroy', $payment->id) }}">
                            <i class="fa fa-trash my-1"></i> Delete
                        </a>
                        @endcanany
                    </div>
                </div>
                @endif
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

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $(document).ready(function() {
        $(document).on('click', '.delete-payment', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            const deleteApprovalEnabled = @json(delete_approval_enabled());

            Swal.fire({
                title: deleteApprovalEnabled ? 'Submit delete request?' : 'Are you sure?',
                text: deleteApprovalEnabled
                    ? 'This payment voucher will stay visible as Pending Deletion until an administrator approves.'
                    : "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: deleteApprovalEnabled ? 'Yes, submit request' : 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            Accept: 'application/json'
                        },
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            const pending = !!(response && response.pending_deletion);
                            Swal.fire(
                                pending ? 'Request submitted' : 'Deleted!',
                                response?.message || (pending
                                    ? 'Delete request submitted and awaiting administrator approval.'
                                    : 'Payment has been deleted.'),
                                pending ? 'info' : 'success'
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
    });
</script>
@include('delete_requests._pending_table_script', ['items' => $data])
@endsection
