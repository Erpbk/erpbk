<div class="container-fluid px-1">
    @if($batches->count() > 0)
        @php
            // Get the first batch to display common info (assuming all records belong to same batch)
            $firstBatch = $batches->first();
            $totalRecords = $batches->count();
            $totalQuantity = $batches->sum('quantity');
            $totalValue = $batches->sum(function($p) { return $p->quantity * $p->unit_cost; });
            $totalUsed = $batches->sum(function($p) { 
                return $p->quantity - $p->remaining_quantity;
            });
            $totalAvailable = $batches->sum('remaining_quantity');
        @endphp
        
        <!-- Batch Master Header Card -->
        <div class="card border-0 shadow-lg mb-4 overflow-hidden">
            <div class="bg-light text-white p-4">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <div class="d-flex align-items-center gap-3">
                            <i class="fa fa-cubes fa-3x"></i>
                            <div>
                                <h2 class="fw-bold mb-1">Purchase Details</h2>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card-body p-4">
                <!-- Batch Stats Row -->
                <div class="row g-4 mb-4">
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded-4">
                            <i class="fa fa-boxes fa-2x text-primary mb-2"></i>
                            <h5 class="mb-0 fw-bold">{{ number_format($totalQuantity) }}</h5>
                            <small class="text-muted">Total Units</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded-4">
                            <i class="fa fa-coins fa-2x text-success mb-2"></i>
                            <h5 class="mb-0 fw-bold">{{ number_format($totalValue, 2) }}</h5>
                            <small class="text-muted">Total Value (AED)</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded-4">
                            <i class="fa fa-chart-line fa-2x text-warning mb-2"></i>
                            <h5 class="mb-0 fw-bold">{{ number_format($totalUsed) }}</h5>
                            <small class="text-muted">Units Used/Adjusted</small>
                        </div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="text-center p-3 border rounded-4">
                            <i class="fa fa-check-circle fa-2x text-info mb-2"></i>
                            <h5 class="mb-0 fw-bold">{{ number_format($totalAvailable) }}</h5>
                            <small class="text-muted">Available Units</small>
                        </div>
                    </div>
                </div>
                
                <!-- Common Batch Details Grid -->
                <div class="row g-4">
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-info-circle me-2"></i>Batch Information</h6>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Batch No:</div>
                                <div class="col-7 fw-semibold">{{ $firstBatch->batch_no ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Batch Created:</div>
                                <div class="col-7">{{ $firstBatch->created_at->format('d-m-Y H:i') }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Created By:</div>
                                <div class="col-7">{{ $firstBatch->createdBy->name ?? 'System' }}</div>
                            </div>
                            @if($firstBatch->notes)
                            <div class="row">
                                <div class="col-5 text-muted">Batch Notes:</div>
                                <div class="col-7">{{ $firstBatch->notes }}</div>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded-4 p-3 h-100">
                            <h6 class="fw-bold mb-3 text-primary"><i class="fa fa-truck me-2"></i>Supplier Information</h6>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Supplier Name:</div>
                                <div class="col-7 fw-semibold">{{ $firstBatch->supplier->name ?? 'N/A' }}</div>
                            </div>
                            @if($firstBatch->supplier && $firstBatch->supplier->trn_number)
                            <div class="row mb-2">
                                <div class="col-5 text-muted">TRN Number:</div>
                                <div class="col-7">{{ $firstBatch->supplier->trn_number }}</div>
                            </div>
                            @endif
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Invoice:</div>
                                <div class="col-7">{{ $firstBatch->invoice?->inv_id ?? 'N/A' }}</div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-5 text-muted">Purchase Date:</div>
                                <div class="col-7">{{ $firstBatch->purchase_date->format('d-m-Y') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Overall Progress Bar for Batch -->
                <div class="mt-4">
                    @php
                        $usedPercent = $totalQuantity > 0 ? ($totalUsed / $totalQuantity) * 100 : 0;
                        $availPercent = $totalQuantity > 0 ? ($totalAvailable / $totalQuantity) * 100 : 0;
                    @endphp
                    <label class="fw-semibold mb-2"><i class="fa fa-chart-simple me-1"></i> Overall Batch Utilization</label>
                    <div class="progress" style="height: 12px; border-radius: 10px;">
                        <div class="progress-bar bg-success" role="progressbar" 
                             style="width: {{ $usedPercent }}%">
                        </div>
                        @if($availPercent > 0)
                            <div class="progress-bar bg-info" role="progressbar" 
                                 style="width: {{ $availPercent }}%">
                            </div>
                        @endif
                    </div>
                    <div class="d-flex justify-content-between small mt-2">
                        <span><i class="fa fa-circle text-success"></i> Used/Adjusted: {{ number_format($usedPercent, 1) }}% ({{ number_format($totalUsed) }} units)</span>
                        <span><i class="fa fa-circle text-info"></i> Available: {{ number_format($availPercent, 1) }}% ({{ number_format($totalAvailable) }} units)</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="d-flex justify-content-between align-items-center mb-3 px-2">
            <h5 class="fw-bold mb-0"><i class="fa fa-list-alt me-2"></i>Purchased Items</h5>
            <span class="badge bg-secondary rounded-pill">{{ $totalRecords }} items</span>
        </div>
        
        <div class="vstack gap-3 px-2">
            @foreach($batches as $index => $purchase)
                @php
                    // Calculate metrics for each purchase record
                    $usedInMaintenance = $purchase->maintenanceItems->sum('quantity');
                    $adjustedOut = $purchase->adjustments->sum('quantity');
                    $available = $purchase->remaining_quantity;
                    $item = $purchase->item;
                    $recordNumber = $index + 1;
                @endphp
                
                <!-- Individual Record Card (Compact) -->
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-primary rounded-circle p-2" style="width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;">
                                    {{ $recordNumber }}
                                </span>
                                <span>
                                    <a href="javascript:void(0);" data-action="{{ route('items.show', $purchase->item_id) }}" class="show-modal-right"> {{ $purchase->item_name }}</a>
                                </span>
                            </div>
                            <div class="mt-2 mt-sm-0">
                                @if($available <= 0)
                                    <span class="badge bg-danger rounded-pill"><i class="fa fa-ban me-1"></i> Consumed</span>
                                @elseif($available < $purchase->quantity * 0.1)
                                    <span class="badge bg-warning rounded-pill"><i class="fa fa-exclamation-triangle me-1"></i> Low Stock</span>
                                @else
                                    <span class="badge bg-success rounded-pill"><i class="fa fa-check me-1"></i> Available</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="card-body pt-0">
                        <div class="row g-3">
                            <!-- Quantity Information -->
                            <div class="col-md-4">
                                <div class="rounded-3 p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted">Quantity:</small>
                                        <strong>{{ number_format($purchase->quantity) }} units</strong>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted">Unit Cost:</small>
                                        <strong>{{ number_format($purchase->unit_cost, 2) }} AED</strong>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <small class="text-muted">Total Cost:</small>
                                        <strong class="text-primary">{{ number_format($purchase->quantity * $purchase->unit_cost, 2) }} AED</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Usage Stats -->
                            <div class="col-md-5">
                                <div class="rounded-3 p-3">
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted"><i class="fa fa-wrench me-1"></i> Used in Maintenance:</small>
                                        <span>{{ number_format($usedInMaintenance) }} units</span>
                                    </div>
                                    <div class="d-flex justify-content-between mb-2">
                                        <small class="text-muted"><i class="fa fa-adjust me-1"></i> Adjusted Out:</small>
                                        <span class="{{ $adjustedOut > 0 ? 'text-warning' : '' }}">{{ number_format($adjustedOut) }} units</span>
                                    </div>
                                    <div class="d-flex justify-content-between pt-2 border-top">
                                        <small class="fw-semibold"><i class="fa fa-check-circle me-1 text-success"></i> Available:</small>
                                        <strong class="text-success">{{ number_format($available) }} units</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Actions -->
                            <div class="col-md-3">
                                <div class="d-flex flex-column gap-2 h-100 justify-content-center">
                                    @if($available > 0)
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill w-100" 
                                                onclick="showReturnModal({{ $purchase->id }}, {{ $available }})">
                                            <i class="fa fa-undo"></i> Return
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill w-100" 
                                                onclick="showTransferModal({{ $purchase->id }}, {{ $available }})">
                                            <i class="fa fa-exchange"></i> Transfer
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill w-100" 
                                                onclick="showWriteOffModal({{ $purchase->id }}, {{ $available }})">
                                            <i class="fa fa-trash"></i> Write Off
                                        </button>
                                    @else
                                        <button class="btn btn-sm btn-secondary rounded-pill w-100" disabled>
                                            <i class="fa fa-lock"></i> No Stock
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Show adjustments if any for this record -->
                        @if($purchase->adjustments->count() > 0)
                        <div class="mt-3">
                            <button class="btn btn-sm btn-link text-warning p-0" type="button" data-bs-toggle="collapse" data-bs-target="#adjustments-{{ $purchase->id }}">
                                <i class="fa fa-adjust"></i> View Adjustments ({{ $purchase->adjustments->count() }})
                            </button>
                            <div class="collapse mt-2" id="adjustments-{{ $purchase->id }}">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr><th>Date</th><th>Type</th><th>Qty</th><th>Reason</th><th>By</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($purchase->adjustments as $adj)
                                            <tr>
                                                <td>{{ $adj->adjustment_date->format('d-m-Y') }}</td>
                                                <td>{{ ucfirst(str_replace('_', ' ', $adj->adjustment_type)) }}</td>
                                                <td class="text-danger">-{{ number_format($adj->quantity) }}</td>
                                                <td>{{ $adj->reason ?? '-' }}</td>
                                                <td>{{ $adj->adjustedBy->name ?? 'System' }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                        
                        <!-- Show maintenance usage if any -->
                        @if($purchase->maintenanceItems->count() > 0)
                        <div class="mt-2">
                            <button class="btn btn-sm btn-link text-primary p-0" type="button" data-bs-toggle="collapse" data-bs-target="#maintenance-{{ $purchase->id }}">
                                <i class="fa fa-wrench"></i> View Maintenance Usage ({{ $purchase->maintenanceItems->count() }})
                            </button>
                            <div class="collapse mt-2" id="maintenance-{{ $purchase->id }}">
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr><th>Maintenance ID</th><th>Date</th><th>Bike</th><th>Qty</th><th>Profit</th></tr>
                                        </thead>
                                        <tbody>
                                            @foreach($purchase->maintenanceItems as $mi)
                                            <tr>
                                                <td>#{{ $mi->bike_maintenance_id }}</td>
                                                <td>{{ $mi->created_at->format('D m Y') }}</td>
                                                <td>{{ $mi->bikeMaintenance->bike->plate_number ?? $mi->bikeMaintenance->bike->plate ?? 'N/A' }}</td>
                                                <td class="text-center">{{ number_format($mi->quantity) }}</td>
                                                <td>{{ $mi->profit }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
        
    @else
        <!-- Empty State -->
        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-body py-5 text-center">
                <i class="fa fa-inbox fa-4x text-muted mb-3"></i>
                <h5 class="fw-light">No Inventory Batches Found</h5>
                <p class="text-muted">Click "New Purchase" to add your first batch</p>
                <a href="#" class="btn btn-primary rounded-pill"><i class="fa fa-plus"></i> New Purchase</a>
            </div>
        </div>
    @endif
</div>

<!-- Required Modals for Stock Actions (same as before) -->
<div class="modal fade" id="returnModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="returnForm" method="POST">
                @csrf
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold"><i class="fa fa-undo-alt text-warning me-2"></i>Return to Supplier</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Return</label>
                        <input type="number" name="quantity" id="return_quantity" class="form-control form-control-lg rounded-3" required min="1">
                        <div class="form-text">Available: <span id="return_available"></span> units</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Credit Note Number</label>
                        <input type="text" name="credit_note_number" class="form-control rounded-3" placeholder="Reference from supplier">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason <span class="text-danger">*</span></label>
                        <textarea name="reason" class="form-control rounded-3" rows="2" required></textarea>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Notes</label>
                        <textarea name="notes" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm">Confirm Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="transferForm" method="POST">
                @csrf
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold"><i class="fa fa-exchange-alt text-info me-2"></i>Transfer Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Transfer</label>
                        <input type="number" name="quantity" id="transfer_quantity" class="form-control form-control-lg rounded-3" required min="1">
                        <div class="form-text">Available: <span id="transfer_available"></span> units</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Destination Garage*</label>
                        <input type="text" name="destination_garage" class="form-control rounded-3" required placeholder="Enter garage name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Transfer Number</label>
                        <input type="text" name="transfer_number" class="form-control rounded-3" placeholder="Optional reference">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Reason / Notes</label>
                        <textarea name="reason" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info rounded-pill px-4 text-white">Transfer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="writeOffModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <form id="writeOffForm" method="POST">
                @csrf
                <div class="modal-header border-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold"><i class="fa fa-trash-alt text-danger me-2"></i>Write Off Stock</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body px-4">
                    <div class="alert alert-warning bg-warning-subtle border-0 rounded-3"><i class="fa fa-exclamation-triangle me-2"></i> This action permanently removes stock from inventory.</div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Quantity to Write Off</label>
                        <input type="number" name="quantity" id="writeoff_quantity" class="form-control form-control-lg rounded-3" required min="1">
                        <div class="form-text">Available: <span id="writeoff_available"></span> units</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Reason *</label>
                        <select name="reason" class="form-select rounded-3" required>
                            <option value="">Select reason</option>
                            <option value="Damaged">Damaged</option>
                            <option value="Lost">Lost</option>
                            <option value="Expired">Expired</option>
                            <option value="Quality Issue">Quality Issue</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" name="reference_number" class="form-control rounded-3" placeholder="Optional reference">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Detailed Notes</label>
                        <textarea name="notes" class="form-control rounded-3" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pb-4 px-4">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger rounded-pill px-4">Write Off</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function showReturnModal(purchaseId, available) {
        const form = document.getElementById('returnForm');
        form.action = '/inventory/purchases/' + purchaseId + '/return-to-supplier';
        document.getElementById('return_quantity').max = available;
        document.getElementById('return_available').innerText = available;
        new bootstrap.Modal(document.getElementById('returnModal')).show();
    }

    function showTransferModal(purchaseId, available) {
        const form = document.getElementById('transferForm');
        form.action = '/inventory/purchases/' + purchaseId + '/transfer';
        document.getElementById('transfer_quantity').max = available;
        document.getElementById('transfer_available').innerText = available;
        new bootstrap.Modal(document.getElementById('transferModal')).show();
    }

    function showWriteOffModal(purchaseId, available) {
        const form = document.getElementById('writeOffForm');
        form.action = '/inventory/purchases/' + purchaseId + '/write-off';
        document.getElementById('writeoff_quantity').max = available;
        document.getElementById('writeoff_available').innerText = available;
        new bootstrap.Modal(document.getElementById('writeOffModal')).show();
    }

    document.querySelectorAll('#returnForm, #transferForm, #writeOffForm').forEach(form => {
        form.addEventListener('submit', function(e) {
            const quantity = parseInt(this.querySelector('input[name="quantity"]').value);
            const maxAvailable = parseInt(this.querySelector('input[name="quantity"]').max);
            
            if (quantity > maxAvailable) {
                e.preventDefault();
                alert('Quantity cannot exceed available stock (' + maxAvailable + ' units)');
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
</script>

<style>
    .bg-gradient-primary {
        background: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
    }
    .rounded-4 {
        border-radius: 1rem !important;
    }
    .rounded-3 {
        border-radius: 0.75rem !important;
    }
    .card {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .card:hover {
        transform: translateY(-2px);
        box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.08) !important;
    }
    .progress {
        background-color: #e9ecef;
        border-radius: 10px;
    }
    .btn-link {
        text-decoration: none;
    }
    .btn-link:hover {
        text-decoration: underline;
    }
</style>