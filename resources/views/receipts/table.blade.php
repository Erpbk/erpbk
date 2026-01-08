@push('third_party_stylesheets')
@endpush
<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
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
            <th title="Action" width="120px" class="sorting_disabled" rowspan="1" colspan="1" aria-label="Action"><a data-bs-toggle="modal" data-bs-target="#searchModal" href="javascript:void(0);"> <i class="fa fa-search"></i></a></th>
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