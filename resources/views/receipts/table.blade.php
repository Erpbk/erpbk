@push('third_party_stylesheets')
<style>
    .table-responsive {
        max-height: calc(100vh - 200px);
    }
</style>
@endpush
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
                    @can('sim_delete')
                    <a href="#" class='dropdown-item waves-effect' 
                        onclick="confirmDelete('{{route('bank.delete', $r->id) }}')">
                        <i class="fa fa-trash my-1"></i> Delete
                    </a>
                    @endcan
                </div>
                </div>
            </td>
            <td>
                <a href="{{ route('receipts.show', $receipt->id) }}" class="btn btn-sm btn-info" title="View"><i class="fa fa-eye"></i></a>
                <a href="javascript:void(0);" class="btn btn-sm btn-warning show-modal" data-title="Update" data-size="lg" data-action="{{ route('receipts.edit', $receipt->id) }}"><i class="fa fa-pencil"></i></a>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@if(method_exists($data, 'links'))
    {!! $data->links('components.global-pagination') !!}
@endif
