@php
    $isPaginator = method_exists($records, 'total');
@endphp
<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <small class="text-muted" id="paymentRecordsCount">
        @if($isPaginator)
            Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ $records->total() }} record(s) in this filter
        @else
            {{ $records->count() }} record(s) in this filter
        @endif
    </small>
</div>
<table class="table table-striped table-sm mb-0" id="paymentSalikTable">
    <thead>
        <tr>
            <th style="width: 40px;"><input type="checkbox" id="checkAllSaliks" title="Select all on this page"></th>
            <th>Transaction ID</th>
            <th>Plate</th>
            <th>Company</th>
            <th>Rider</th>
            <th>Trip Date</th>
            <th>Salik Amount</th>
            <th>Admin</th>
            <th>VAT</th>
            <th>Total</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        @forelse($records as $record)
            @include('salik._payment_record_row', ['record' => $record, 'pinned' => false])
        @empty
        <tr>
            <td colspan="11" class="text-center text-muted py-3">No salik records found for the selected filters.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($isPaginator && $records->hasPages())
<div class="mt-3" id="paymentRecordsPagination">
    {!! $records->links('components.global-pagination') !!}
</div>
@endif
