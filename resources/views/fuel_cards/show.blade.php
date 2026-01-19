<div class="card">
    <div class="card-header bg-light">
        <h5 class="card-title mb-0">Fuel Card Information</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <tbody class="text-start">
                <tr>
                    <th style="width: 30%;" class="ps-4">Card Number</th>
                    <td class="fw-bold text-primary">{{ $fuelCard->card_number ?? 'N/A' }}</td>
                </tr>
                <tr>
                    <th class="ps-4">Card Type</th>
                    <td>
                        <span class="badge bg-primary">
                            {{ $fuelCard->card_type ?? 'Not specified' }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Status</th>
                    <td>
                        <span class="badge {{ $fuelCard->status == 'Active' ? 'bg-success' : 'bg-danger' }}">
                            {{ ucfirst($fuelCard->status ?? 'inactive') }}
                        </span>
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Assigned To</th>
                    <td>
                        <i class="fas fa-user me-1 text-muted"></i>
                        {{ $fuelCard->rider ? ($fuelCard->rider->rider_id.'-'.$fuelCard->rider->name) : 'Unassigned' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Created By</th>
                    <td>
                        <i class="fas fa-user-plus me-1 text-muted"></i>
                        {{ App\Models\User::find($fuelCard->created_by)->name ?? 'System' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Updated By</th>
                    <td>
                        <i class="fas fa-user-edit me-1 text-muted"></i>
                        {{ App\Models\User::find($fuelCard->updated_by)->name ?? 'System' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Created At</th>
                    <td>
                        <i class="fas fa-calendar-plus me-1 text-muted"></i>
                        {{ $fuelCard->created_at ? $fuelCard->created_at->format('M d, Y h:i A') : 'N/A' }}
                    </td>
                </tr>
                <tr>
                    <th class="ps-4">Updated At</th>
                    <td>
                        <i class="fas fa-calendar-check me-1 text-muted"></i>
                        {{ $fuelCard->updated_at ? $fuelCard->updated_at->format('M d, Y h:i A') : 'N/A' }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>