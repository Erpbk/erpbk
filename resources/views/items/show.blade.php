<div class="container-fluid px-2 mt-3">
    <!-- Header Section -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">
                <i class="fa fa-cubes text-primary me-2"></i>Item Purchase History
            </h3>
        </div>
    </div>
    <div class="totals-cards">
        <div class="total-card total-blue">
            <div class="label"><i class="fa fa-times-circle"></i>Total Purchased</div>
            <div class="value" id="avg_ontime">{{ number_format($history->sum(function($q){ return $q->quantity;})) }}</div>
        </div>
        <div class="total-card total-2">
            <div class="label"><i class="far fa-money-bill-alt"></i>Total Used</div>
            <div class="value" id="total_hours">{{ number_format($history->sum(function($q){ return $q->maintenanceItems->sum('quantity');})) }}</div>
        </div>
        <div class="total-card total-red">
            <div class="label"><i class="fas fa-stamp"></i>Total Adjusted</div>
            <div class="value" id="total_rejected">{{ number_format($history->sum(function($q){ return $q->adjustments->sum('quantity');})) }}</div>
        </div>
        <div class="total-card total-green">
            <div class="label"><i class="fa fa-ticket"></i>Profit</div>
            <div class="value" id="total_orders">{{ number_format($history->sum(function($q){ return $q->maintenanceItems->sum('profit');})) }}</div>
        </div>
    </div>

    <!-- Purchases Table Card -->
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-4 px-4">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0">
                </h5>
                <span class="badge bg-secondary rounded-pill">{{ $history->count() }} records</span>
            </div>
        </div>
        
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small text-uppercase text-muted">
                            <th class="border-0 ps-4">Batch #</th>
                            <th class="border-0">Item</th>
                            <th class="border-0">Supplier</th>
                            <th class="border-0">Purchase Date</th>
                            <th class="border-0">Invoice</th>
                            <th class="border-0 text-center">Qty</th>
                            <th class="border-0 text-end">Unit Cost</th>
                            <th class="border-0 text-end">Total Cost</th>
                            <th class="border-0 text-center">Used</th>
                            <th class="border-0 text-center">Adjusted</th>
                            <th class="border-0 text-center">Available</th>
                            <th class="border-0 text-end pe-4">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($history as $purchase)
                            @php
                                $usedInMaintenance = $purchase->maintenanceItems->sum('quantity');
                                $adjustedOut = $purchase->adjustments->sum('quantity');
                                $available = $purchase->remaining_quantity;
                                $totalUsed = $usedInMaintenance + $adjustedOut;
                                $usagePercentage = $purchase->quantity > 0 ? ($totalUsed / $purchase->quantity) * 100 : 0;
                                // Determine stock status
                                $stockStatus = $available <= 0 ? 'danger' : ($available <= $purchase->quantity * 0.1 ? 'warning' : 'success');
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <a href="javascript:void(0);" data-action="{{ route('inventory.showBatch', $purchase->batch_no) }}" class="show-modal-right"><div class="fw-bold">{{ $purchase->batch_no }}</div>
                                 </td>
                                <td>
                                    <span class="fw-medium">{{ $purchase->item_name ?? 'N/A' }}</span>
                                 </td>
                                <td>{{ $purchase->supplier->name ?? 'N/A' }}</td>
                                <td>{{ $purchase->purchase_date->format('d-m-Y') }}</td>
                                <td><a href="javascript:void(0);" data-action="{{ route('supplierInvoices.show', $purchase->invoice->id) }}" class="show-modal-right">{{ $purchase->invoice?->inv_id ?? '-' }}</td>
                                <td class="text-center fw-semibold">{{ number_format($purchase->quantity) }}</td>
                                <td class="text-end">{{ number_format($purchase->unit_cost, 2) }}</td>
                                <td class="text-end text-primary fw-semibold">{{ number_format($purchase->quantity * $purchase->unit_cost, 2) }}</td>
                                <td class="text-center">
                                    <span class="badge bg-info bg-opacity-10 text-info rounded-pill px-3 py-1">
                                        {{ number_format($usedInMaintenance) }}
                                    </span>
                                    @if($usedInMaintenance > 0)
                                        <br><small class="text-muted">{{ number_format($usagePercentage, 1) }}%</small>
                                    @endif
                                 </td>
                                <td class="text-center">
                                    @if($adjustedOut > 0)
                                        <span class="badge bg-warning bg-opacity-10 text-warning rounded-pill px-3 py-1">
                                            {{ number_format($adjustedOut) }}
                                        </span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                 </td>
                                <td class="text-center">
                                    <span class="badge bg-{{ $stockStatus }} bg-opacity-10 text-{{ $stockStatus }} rounded-pill px-3 py-1 fw-semibold">
                                        {{ number_format($available) }}
                                    </span>
                                 </td>
                                <td class="text-end pe-4">
                                    <div class="btn-group gap-1">
                                        
                                        @if($totalUsed == 0)
                                            <a href="#" class="btn btn-sm btn-outline-primary rounded-pill" title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-danger rounded-pill" 
                                                    onclick="confirmDelete({{ $purchase->id }})"
                                                    title="Delete">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        @endif
                                        
                                        @if($available > 0)
                                            <button type="button" 
                                                    class="btn btn-sm btn-outline-warning rounded-pill" 
                                                    onclick="showAdjustmentModal({{ $purchase->id }}, '{{ $purchase->batch_number }}', {{ $available }})"
                                                    title="Adjust Stock">
                                                <i class="fa fa-adjust"></i>
                                            </button>
                                        @endif
                                        
                                        <a href="#" class="btn btn-sm btn-outline-secondary rounded-pill" title="Print Label" target="_blank">
                                            <i class="fa fa-print"></i>
                                        </a>
                                    </div>
                                 </td>
                            </tr>
                            
                            <!-- Progress bar row (shown when stock is partially used) -->
                            @if($usagePercentage > 0 && $usagePercentage < 100)
                            <tr class="border-0">
                                <td colspan="13" class="p-0 border-0">
                                    <div class="mx-3 mb-2" style="height: 4px;">
                                        <div class="progress rounded-pill" style="height: 4px;">
                                            <div class="progress-bar bg-success rounded-pill" role="progressbar" 
                                                 style="width: {{ ($usedInMaintenance / $purchase->quantity) * 100 }}%"></div>
                                            @if($adjustedOut > 0)
                                                <div class="progress-bar bg-warning rounded-pill" role="progressbar" 
                                                     style="width: {{ ($adjustedOut / $purchase->quantity) * 100 }}%"></div>
                                            @endif
                                        </div>
                                    </div>
                                 </td>
                            </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="13" class="text-center py-5">
                                    <i class="fa fa-inbox fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No inventory purchases found.</p>
                                 </td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-light border-top">
                        <tr>
                            <th colspan="5" class="ps-4 pt-3 small text-muted">TOTALS:</th>
                            <th class="text-center pt-3 fw-semibold">{{ number_format($history->sum('quantity')) }}</th>
                            <th class="text-end pt-3 small text-muted">Avg: {{ number_format($history->avg('unit_cost') ?? 0, 2) }}</th>
                            <th class="text-end pt-3 fw-bold text-primary">{{ number_format($history->sum(function($p) { return $p->quantity * $p->unit_cost; }), 2) }}</th>
                            <th colspan="5" class="pt-3"></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Adjustment Modal (Modern Design) -->
<div class="modal fade" id="adjustmentModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="adjustmentForm" method="POST">
                @csrf
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold">
                        <i class="fa fa-adjust text-warning me-2"></i>Adjust Stock
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="bg-light rounded-3 p-3 mb-4">
                        <div class="row">
                            <div class="col-6">
                                <small class="text-muted">Batch Number</small>
                                <div class="fw-semibold" id="batch_number_display">-</div>
                            </div>
                            <div class="col-6">
                                <small class="text-muted">Available Quantity</small>
                                <div class="fw-semibold text-success" id="available_quantity_display">-</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Adjustment Type</label>
                        <select name="adjustment_type" id="adjustment_type" class="form-select rounded-3" required>
                            <option value="">Select Type</option>
                            <option value="return_to_supplier">📦 Return to Supplier</option>
                            <option value="transfer_out">🚚 Transfer to Other Garage</option>
                            <option value="write_off">🗑️ Write Off (Damaged/Lost)</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity</label>
                        <input type="number" name="quantity" id="adjustment_quantity" 
                               class="form-control form-control-lg rounded-3" min="1" required>
                    </div>
                    
                    <div class="mb-3" id="destination_group" style="display:none;">
                        <label class="form-label fw-semibold">Destination Garage</label>
                        <input type="text" name="destination_garage" class="form-control rounded-3" 
                               placeholder="Enter garage name or location">
                    </div>
                    
                    <div class="mb-3" id="credit_note_group" style="display:none;">
                        <label class="form-label fw-semibold">Credit Note Number</label>
                        <input type="text" name="credit_note_number" class="form-control rounded-3" 
                               placeholder="Credit note from supplier">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" rows="2" class="form-control rounded-3" 
                                  placeholder="Enter reason for adjustment" required></textarea>
                    </div>
                    
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Additional Notes</label>
                        <textarea name="notes" rows="2" class="form-control rounded-3" 
                                  placeholder="Any additional information"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary rounded-pill px-4">Confirm Adjustment</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal (Modern) -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="deleteForm" method="POST">
                @csrf
                @method('DELETE')
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="fa fa-trash-alt me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <p>Are you sure you want to delete this purchase batch?</p>
                    <div class="alert alert-warning rounded-3">
                        <i class="fa fa-exclamation-triangle me-2"></i>
                        <strong>Note:</strong> Batches that have been used in maintenance cannot be deleted.
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Delete Batch</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Show/hide fields based on adjustment type
    $('#adjustment_type').on('change', function() {
        var type = $(this).val();
        
        $('#destination_group').hide();
        $('#credit_note_group').hide();
        
        if (type === 'transfer_out') {
            $('#destination_group').show();
        } else if (type === 'return_to_supplier') {
            $('#credit_note_group').show();
        }
    });
    
    // Validate quantity before submit
    $('#adjustmentForm').on('submit', function(e) {
        var quantity = parseInt($('#adjustment_quantity').val());
        var available = parseInt($('#available_quantity_display').text());
        
        if (quantity > available) {
            e.preventDefault();
            alert('Quantity cannot exceed available stock (' + available + ' units)');
            return false;
        }
        
        if (quantity <= 0 || isNaN(quantity)) {
            e.preventDefault();
            alert('Please enter a valid quantity greater than 0');
            return false;
        }
        
        return true;
    });
});

// Show adjustment modal
function showAdjustmentModal(purchaseId, batchNumber, available) {
    $('#adjustmentForm').attr('action', '/inventory/purchases/' + purchaseId + '/adjust');
    $('#batch_number_display').text(batchNumber);
    $('#available_quantity_display').text(available);
    $('#adjustment_quantity').attr('max', available);
    $('#adjustment_quantity').val('');
    $('#adjustment_type').val('');
    $('#destination_group').hide();
    $('#credit_note_group').hide();
    $('#adjustmentModal').modal('show');
}

// Confirm delete
function confirmDelete(purchaseId) {
    $('#deleteForm').attr('action', '/inventory/purchases/' + purchaseId);
    $('#deleteModal').modal('show');
}
</script>

<style>
.rounded-4 {
    border-radius: 1rem !important;
}
.rounded-3 {
    border-radius: 0.75rem !important;
}
.bg-opacity-10 {
    --bs-bg-opacity: 0.1;
}
.btn-group {
    gap: 0.25rem;
}
.table > :not(caption) > * > * {
    padding: 1rem 0.75rem;
}
.table tr {
    transition: background-color 0.2s ease;
}
.table tbody tr:hover {
    background-color: #f8fafc;
}
.progress {
    background-color: #e9ecef;
}
.form-select, .form-control {
    border-color: #e2e8f0;
}
.form-select:focus, .form-control:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}
</style>