<table class="table table-striped dataTable no-footer" id="dataTableBuilder">
    <thead class="text-center">
        <tr role="row">
            <th title="Date" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Date: activate to sort column ascending">Date</th>
            <th title="Account" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Account: activate to sort column ascending">Account</th>
            <th title="Month" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Month: activate to sort column ascending">Month</th>
            <th reference="reference" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Reference: activate to sort column ascending">Reference</th>
            <th title="Voucher" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Voucher: activate to sort column ascending">Voucher</th>
            <th title="Narration" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Narration: activate to sort column ascending">Narration</th>
            <th title="Debit" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Debit: activate to sort column ascending">Debit</th>
            <th title="Credit" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Credit: activate to sort column ascending">Credit</th>
            <th title="Balance" class="sorting" tabindex="0" rowspan="1" colspan="1" aria-label="Balance: activate to sort column ascending">Balance</th>
        </tr>
    </thead>
    <tbody>
        @if(isset($data) && $data->count() > 0)
        @foreach($data as $entry)
        <tr class="text-center {{ isset($entry->is_balance_forward) && $entry->is_balance_forward ? 'table-info' : '' }} {{ isset($entry->is_total) && $entry->is_total ? 'table-warning' : '' }}">
            <td>{!! $entry->date ?? '' !!}</td>
            <td>{{ $entry->account_name ?? '' }}</td>
            <td>{!! $entry->billing_month ?? '' !!}</td>
            <td>{!! $entry->reference ?? 'N/A' !!}</td>
            <td>{!! $entry->voucher ?? '' !!}</td>
            <td class="text-start">{!! $entry->narration ?? '' !!}</td>
            <td class="text-end">{!! $entry->debit ?? '' !!}</td>
            <td class="text-end">{!! $entry->credit ?? '' !!}</td>
            <td class="text-end">{!! $entry->balance ?? '' !!}</td>
        </tr>
        @endforeach
        @else
        <tr>
            <td colspan="10" class="text-center">
                <div class="py-4">
                    <i class="fa fa-info-circle text-muted"></i>
                    <p class="text-muted mb-0">No ledger entries found</p>
                </div>
            </td>
        </tr>
        @endif
    </tbody>
</table>

@if(isset($data))
<div class="pagination-wrapper">
    {!! $data->appends(request()->query())->links('pagination') !!}
</div>
@endif