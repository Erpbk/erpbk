
    <table class="table dataTable no-footer" id="dataTableBuilder">
        <thead class="text-center">
            <tr role="row">
                <th title="Bank" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-sort="descending" aria-label="Bank: activate to sort column ascending">Reference</th>
                <th title="Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Account: activate to sort column ascending">Sender</th>
                <th title="Account" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Account: activate to sort column ascending">Reciever</th>
                <th title="Amount" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Amount: activate to sort column ascending">Amount</th>
                <th title="Voucher No" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Voucher: activate to sort column ascending">Voucher</th>
                <th title="Attachmnet" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Attachment: activate to sort column ascending">Attachment</th>
                <th title="Date of Payment" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Date of Payment: activate to sort column ascending">Date of Payment</th>
                <th title="Billing Month" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Billing Month: activate to sort column ascending">Billing Month</th>
                <th title="Description" class="sorting" tabindex="0" aria-controls="dataTableBuilder" rowspan="1" colspan="1" aria-label="Description: activate to sort column ascending">Description</th>
                <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action">Actions</th>
            </tr>
        </thead>
        <tbody>
            @foreach($data as $payment)
            <tr>
                <td>{{ $payment->reference ?? '-' }}</td>
                <td>{{ $payment->payer_account}} </td>
                <td>{{ $payment->payeeAccount->account_code .'-'.  $payment->payeeAccount->name}} </td>
                <td>AED {{ number_format($payment->amount, 2) }}</td>
                <td>
                    <a href="javascript:void(0);" data-action="{{ route('vouchers.show', $payment->voucher_id) }}" class="text-primary show-voucher-panel" data-title="Payment Voucher" data-collapse-sidebar="1">
                        {{ $payment->voucher->voucher_type . '-'. $payment->voucher_id }}
                    </a>
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
                <td style="position: relative;">
                    <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" type="button" id="actiondropdown_{{ $payment->id }}" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" style="visibility: visible !important; display: inline-block !important;">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $payment->id }}" style="z-index: 1050;">
                        {{-- @can('bank_view')
                            <a href="{{ route('receipts.show' , $receipt->id)}}" target="_blank" class='dropdown-item waves-effect'>
                                <i class="fa fa-eye my-1"></i>view
                            </a>
                        @endcan --}}
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