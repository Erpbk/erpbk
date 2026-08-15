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
    $isPinned = !empty($pinned);
    $isPaid = $record->isPaid();
@endphp
<tr class="{{ $isPinned ? 'table-warning' : '' }}"
    data-transaction-id="{{ $record->transaction_id }}"
    data-plate="{{ $record->plate }}"
    data-rider="{{ $riderLabel }}">
    <td>
        <input type="checkbox"
               class="salik-checkbox"
               value="{{ $record->id }}"
               {{ $isPinned ? 'checked' : '' }}>
    </td>
    <td>{{ $record->transaction_id }}</td>
    <td>{{ $record->plate }}</td>
    <td>{{ $companyLabel }}</td>
    <td>{{ $riderLabel }}</td>
    <td>{{ \App\Helpers\General::DateFormat($record->trip_date) }}</td>
    <td>{{ \App\Helpers\Currency::format($record->amount, 2) }}</td>
    <td>{{ \App\Helpers\Currency::format($record->admin_charges ?? 0, 2) }}</td>
    <td>{{ \App\Helpers\Currency::format($record->vat ?? 0, 2) }}</td>
    <td>{{ \App\Helpers\Currency::format($record->total_amount, 2) }}</td>
    <td>
        @if($isPaid)
            <span class="badge bg-success">On voucher</span>
        @else
            <span class="badge bg-secondary">Unpaid</span>
        @endif
        @if($isPinned)
            <span class="badge bg-warning text-dark">Pinned</span>
        @endif
    </td>
</tr>
