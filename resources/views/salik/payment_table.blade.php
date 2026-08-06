@php
    $isPaginator = method_exists($records, 'total');
@endphp
<div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
    <small class="text-muted" id="paymentRecordsCount">
        @if($isPaginator)
            Showing {{ $records->firstItem() ?? 0 }}–{{ $records->lastItem() ?? 0 }} of {{ $records->total() }} unpaid record(s)
        @else
            {{ $records->count() }} unpaid record(s)
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
        </tr>
    </thead>
    <tbody>
        @forelse($records as $record)
        @php
            $bike = $record->bike;
            $isOwnedBike = $bike && (
                strcasecmp((string) ($bike->bike_owner ?? ''), 'Owned') === 0
                || !$bike->leasingCompany
            );
            if (!$bike) {
                $companyLabel = '-';
            } elseif ($isOwnedBike) {
                $companyLabel = trim((string) (\App\Helpers\Common::getSetting('company_name') ?: ''));
                if ($companyLabel === '') {
                    $currentCompany = view()->shared('currentCompany');
                    $companyLabel = is_object($currentCompany) ? trim((string) ($currentCompany->name ?? '')) : '';
                }
                if ($companyLabel === '') {
                    $companyLabel = '-';
                }
            } else {
                $companyLabel = $bike->leasingCompany?->name ?? '-';
            }
            $riderLabel = $record->rider
                ? trim(($record->rider->rider_id ?? '') . ' - ' . ($record->rider->name ?? ''))
                : 'N/A';
        @endphp
        <tr data-transaction-id="{{ $record->transaction_id }}"
            data-plate="{{ $record->plate }}"
            data-rider="{{ $riderLabel }}">
            <td><input type="checkbox" class="salik-checkbox" value="{{ $record->id }}"></td>
            <td>{{ $record->transaction_id }}</td>
            <td>{{ $record->plate }}</td>
            <td>{{ $companyLabel }}</td>
            <td>{{ $riderLabel }}</td>
            <td>{{ \App\Helpers\General::DateFormat($record->trip_date) }}</td>
            <td>{{ \App\Helpers\Currency::format($record->amount, 2) }}</td>
            <td>{{ \App\Helpers\Currency::format($record->admin_charges ?? 0, 2) }}</td>
            <td>{{ \App\Helpers\Currency::format($record->vat ?? 0, 2) }}</td>
            <td>{{ \App\Helpers\Currency::format($record->total_amount, 2) }}</td>
        </tr>
        @empty
        <tr>
            <td colspan="10" class="text-center text-muted py-3">No unpaid salik records found for the selected filters.</td>
        </tr>
        @endforelse
    </tbody>
</table>
@if($isPaginator && $records->hasPages())
<div class="mt-3" id="paymentRecordsPagination">
    {!! $records->links('components.global-pagination') !!}
</div>
@endif
