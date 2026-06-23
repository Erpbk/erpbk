<table class="table table-striped" id="paymentSalikTable">
    <thead>
        <tr>
            <th><input type="checkbox" id="checkAllSaliks"></th>
            <th>Transaction ID</th>
            <th>Plate</th>
            <th>Leasing Company</th>
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
        <tr>
            <td><input type="checkbox" class="salik-checkbox" value="{{ $record->id }}"></td>
            <td>{{ $record->transaction_id }}</td>
            <td>{{ $record->plate }}</td>
            <td>{{ $record->bike?->leasingCompany?->name ?? 'Own Bike' }}</td>
            <td>{{ $record->rider ? $record->rider->rider_id . ' - ' . $record->rider->name : 'N/A' }}</td>
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

<script>
$('#checkAllSaliks').on('change', function () {
    $('.salik-checkbox').prop('checked', $(this).is(':checked')).trigger('change');
});
</script>
