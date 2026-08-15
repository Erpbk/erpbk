@extends('riders.view')

@section('page_content')

@php
$allowedTabs = ['status', 'sim', 'bike', 'fuel', 'inventory'];
$activeTab = $activeTab ?? (in_array(request('tab'), $allowedTabs, true) ? request('tab') : 'status');
$fuelCardsById = $fuelCardsById ?? collect();
@endphp

<div class="card card-action mb-6">
  <div class="card-header align-items-center d-flex flex-wrap justify-content-between gap-2 pb-0">
    <h5 class="card-action-title mb-0"><i class="ti ti-history ti-lg text-body me-2"></i>History</h5>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      @if($statusHistories !== null)
      <span class="badge bg-label-secondary">Project changes: {{ (int) $projectChangeCount }}</span>
      @endif
      @if($simHistories !== null)
      <span class="badge bg-label-info">SIM: {{ (int) ($simHistoryCount ?? 0) }}</span>
      @endif
      @if($bikeHistories !== null)
      <span class="badge bg-label-primary">Bike: {{ (int) ($bikeHistoryCount ?? 0) }}</span>
      @endif
      @if($fuelHistories !== null)
      <span class="badge bg-label-warning">Fuel: {{ (int) ($fuelHistoryCount ?? 0) }}</span>
      @endif
      @if($inventoryHistories !== null)
      <span class="badge bg-label-success">Inventory: {{ (int) ($inventoryHistoryCount ?? 0) }}</span>
      @endif
    </div>
  </div>
  <ul class="nav nav-tabs px-4 pt-3 flex-nowrap overflow-auto" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab === 'status' ? 'active' : '' }}" id="rider-status-history-tab" data-bs-toggle="tab"
        data-bs-target="#rider-status-history-pane" type="button" role="tab"
        aria-controls="rider-status-history-pane" aria-selected="{{ $activeTab === 'status' ? 'true' : 'false' }}">
        <i class="ti ti-user-check me-1"></i>Status
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab === 'sim' ? 'active' : '' }}" id="rider-sim-history-tab" data-bs-toggle="tab"
        data-bs-target="#rider-sim-history-pane" type="button" role="tab"
        aria-controls="rider-sim-history-pane" aria-selected="{{ $activeTab === 'sim' ? 'true' : 'false' }}">
        <i class="ti ti-device-mobile me-1"></i>SIM
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab === 'bike' ? 'active' : '' }}" id="rider-bike-history-tab" data-bs-toggle="tab"
        data-bs-target="#rider-bike-history-pane" type="button" role="tab"
        aria-controls="rider-bike-history-pane" aria-selected="{{ $activeTab === 'bike' ? 'true' : 'false' }}">
        <i class="ti ti-motorbike me-1"></i>Bike
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab === 'fuel' ? 'active' : '' }}" id="rider-fuel-history-tab" data-bs-toggle="tab"
        data-bs-target="#rider-fuel-history-pane" type="button" role="tab"
        aria-controls="rider-fuel-history-pane" aria-selected="{{ $activeTab === 'fuel' ? 'true' : 'false' }}">
        <i class="ti ti-gas-station me-1"></i>Fuel
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab === 'inventory' ? 'active' : '' }}" id="rider-inventory-history-tab" data-bs-toggle="tab"
        data-bs-target="#rider-inventory-history-pane" type="button" role="tab"
        aria-controls="rider-inventory-history-pane" aria-selected="{{ $activeTab === 'inventory' ? 'true' : 'false' }}">
        <i class="ti ti-package me-1"></i>Inventory
      </button>
    </li>
  </ul>
  <div class="card-body pt-3 px-4 px-md-5">
    <div class="tab-content">
      {{-- Status history --}}
      <div class="tab-pane fade {{ $activeTab === 'status' ? 'show active' : '' }}" id="rider-status-history-pane" role="tabpanel"
        aria-labelledby="rider-status-history-tab">
        @if($statusHistories === null)
        <p class="text-muted mb-0">The rider history table is not available yet. Run database migrations to enable this feature.</p>
        @elseif($statusHistories->isEmpty())
        <p class="text-muted mb-0">No status history yet. Project moves, bike assignments, fleet supervisor changes, and employment status updates will appear here.</p>
        @else
        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>Date</th>
                <th>Project</th>
                <th>Branch</th>
                <th>Bike number</th>
                <th>Fleet supervisor</th>
                <th>Details</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($statusHistories as $row)
              @php
              $meta = is_array($row->meta) ? $row->meta : [];
              $projectName = $row->customer->name ?? ($meta['new_project_name'] ?? ($meta['old_project_name'] ?? '—'));
              $employmentStatus = $meta['employment_status'] ?? null;
              $optionText = $meta['rider_status_option'] ?? null;
              if ($employmentStatus === null && $row->event_type === 'status_change') {
              $employmentStatus = $meta['new_employment_status'] ?? null;
              }
              if ($optionText === null && $row->event_type === 'status_change') {
              $optionText = $meta['new_rider_status'] ?? null;
              }
              $historyStatus = $row->history_status ?? ($meta['display_status'] ?? null);
              $rowNum = ($statusHistories->currentPage() - 1) * $statusHistories->perPage() + $loop->iteration;
              @endphp
              <tr>
                <td>{{ $rowNum }}</td>
                <td>{{ $row->effective_date ? \App\Helpers\General::DateFormat($row->effective_date) : '—' }}</td>
                <td>{{ $projectName }}</td>
                <td>{{ $row->branch->name ?? ($row->branch_id ? $row->branch_id : '—') }}</td>
                <td>{{ $row->bike_number ?? '—' }}</td>
                <td>{{ $row->fleet_supervisor ?? '—' }}</td>
                <td>{{ $row->details ?? '—' }}</td>
                <td>
                  @if($employmentStatus !== null || $optionText || $historyStatus)
                  @include('riders._status_badges', [
                  'employmentStatus' => $employmentStatus,
                  'optionText' => $optionText,
                  ])
                  @elseif($historyStatus)
                  <span class="badge {{ strtolower($historyStatus) === 'joining' ? 'bg-label-success' : 'bg-label-primary' }}">{{ $historyStatus }}</span>
                  @else
                  —
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-4">
          {{ $statusHistories->appends(['tab' => 'status'])->withQueryString()->links() }}
        </div>
        @endif
      </div>

      {{-- SIM history --}}
      <div class="tab-pane fade {{ $activeTab === 'sim' ? 'show active' : '' }}" id="rider-sim-history-pane" role="tabpanel"
        aria-labelledby="rider-sim-history-tab">
        @if($simHistories === null)
        <p class="text-muted mb-0">The SIM history table is not available yet. Run database migrations to enable this feature.</p>
        @elseif($simHistories->isEmpty())
        <p class="text-muted mb-0">No SIM assignment history for this rider yet.</p>
        @else
        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>SIM number</th>
                <th>Company</th>
                <th>Assign date</th>
                <th>Assigned by</th>
                <th>Return date</th>
                <th>Returned by</th>
                <th>Notes</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($simHistories as $row)
              @php
              $sim = $row->sim;
              $assignedBy = $row->assigned_by ? \App\Models\User::find($row->assigned_by) : null;
              $returnedBy = $row->returned_by ? \App\Models\User::find($row->returned_by) : null;
              $rowNum = ($simHistories->currentPage() - 1) * $simHistories->perPage() + $loop->iteration;
              $isReturned = !empty($row->return_date);
              @endphp
              <tr>
                <td>{{ $rowNum }}</td>
                <td>
                  @if($sim)
                  <a href="{{ route('sims.show', $sim->id) }}" class="text-primary">{{ $sim->number ?? '—' }}</a>
                  @else
                  —
                  @endif
                </td>
                <td>{{ $sim->company ?? '—' }}</td>
                <td>{{ $row->note_date ? \App\Helpers\General::DateFormat($row->note_date) : '—' }}</td>
                <td>{{ $assignedBy->name ?? '—' }}</td>
                <td>{{ $row->return_date ? \App\Helpers\General::DateFormat($row->return_date) : '—' }}</td>
                <td>{{ $returnedBy->name ?? '—' }}</td>
                <td>{{ $row->notes ?: '—' }}</td>
                <td>
                  @if($isReturned)
                  <span class="badge bg-label-secondary">Returned</span>
                  @else
                  <span class="badge bg-label-success">Assigned</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-4">
          {{ $simHistories->appends(['tab' => 'sim'])->withQueryString()->links() }}
        </div>
        @endif
      </div>

      {{-- Bike history --}}
      <div class="tab-pane fade {{ $activeTab === 'bike' ? 'show active' : '' }}" id="rider-bike-history-pane" role="tabpanel"
        aria-labelledby="rider-bike-history-tab">
        @if($bikeHistories === null)
        <p class="text-muted mb-0">The bike history table is not available yet. Run database migrations to enable this feature.</p>
        @elseif($bikeHistories->isEmpty())
        <p class="text-muted mb-0">No bike assignment history for this rider yet.</p>
        @else
        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>Bike</th>
                <th>Company</th>
                <th>Assign date</th>
                <th>Assigned by</th>
                <th>Return date</th>
                <th>Returned by</th>
                <th>Status</th>
                <th>Notes</th>
              </tr>
            </thead>
            <tbody>
              @foreach($bikeHistories as $row)
              @php
              $bike = $row->bike;
              $assignedBy = $row->created_by ? \App\Models\User::find($row->created_by) : null;
              $returnedBy = $row->updated_by ? \App\Models\User::find($row->updated_by) : null;
              $rowNum = ($bikeHistories->currentPage() - 1) * $bikeHistories->perPage() + $loop->iteration;
              $isReturned = !empty($row->return_date);
              $plate = $bike ? $bike->emiratesPlateLabel() : ($row->bike_number ?: '—');
              @endphp
              <tr>
                <td>{{ $rowNum }}</td>
                <td>
                  @if($bike)
                  <a href="{{ route('bikes.show', $bike->id) }}" class="text-primary">{{ $plate }}</a>
                  @else
                  {{ $plate }}
                  @endif
                </td>
                <td>{{ optional($bike?->LeasingCompany)->name ?? '—' }}</td>
                <td>{{ $row->note_date ? \App\Helpers\General::DateFormat($row->note_date) : '—' }}</td>
                <td>{{ $assignedBy->name ?? '—' }}</td>
                <td>{{ $row->return_date ? \App\Helpers\General::DateFormat($row->return_date) : '—' }}</td>
                <td>{{ $isReturned ? ($returnedBy->name ?? '—') : '—' }}</td>
                <td>
                  @if($isReturned)
                  <span class="badge bg-label-secondary">Returned</span>
                  @else
                  <span class="badge bg-label-success">{{ $row->warehouse ?: 'Assigned' }}</span>
                  @endif
                </td>
                <td>{{ $row->notes ?: '—' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-4">
          {{ $bikeHistories->appends(['tab' => 'bike'])->withQueryString()->links() }}
        </div>
        @endif
      </div>

      {{-- Fuel history --}}
      <div class="tab-pane fade {{ $activeTab === 'fuel' ? 'show active' : '' }}" id="rider-fuel-history-pane" role="tabpanel"
        aria-labelledby="rider-fuel-history-tab">
        @if($fuelHistories === null)
        <p class="text-muted mb-0">The fuel card history table is not available yet. Run database migrations to enable this feature.</p>
        @elseif($fuelHistories->isEmpty())
        <p class="text-muted mb-0">No fuel card assignment history for this rider yet.</p>
        @else
        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>Card number</th>
                <th>Company</th>
                <th>Assign date</th>
                <th>Assigned by</th>
                <th>Return date</th>
                <th>Returned by</th>
                <th>Notes</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              @foreach($fuelHistories as $row)
              @php
              $card = $fuelCardsById->get($row->card_id);
              $rowNum = ($fuelHistories->currentPage() - 1) * $fuelHistories->perPage() + $loop->iteration;
              $isReturned = !empty($row->return_date);
              @endphp
              <tr>
                <td>{{ $rowNum }}</td>
                <td>
                  @if($card)
                  <a href="{{ route('fuelCards.show', $card->id) }}" class="text-primary">{{ $card->card_number ?? '—' }}</a>
                  @else
                  —
                  @endif
                </td>
                <td>{{ optional($card?->fuelCompany)->name ?? '—' }}</td>
                <td>{{ $row->assign_date ? \App\Helpers\General::DateFormat($row->assign_date) : '—' }}</td>
                <td>{{ $row->assignedBy->name ?? '—' }}</td>
                <td>{{ $row->return_date ? \App\Helpers\General::DateFormat($row->return_date) : '—' }}</td>
                <td>{{ $row->returnedBy->name ?? '—' }}</td>
                <td>{{ $row->note ?: '—' }}</td>
                <td>
                  @if($isReturned)
                  <span class="badge bg-label-secondary">Returned</span>
                  @else
                  <span class="badge bg-label-success">Assigned</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-4">
          {{ $fuelHistories->appends(['tab' => 'fuel'])->withQueryString()->links() }}
        </div>
        @endif
      </div>

      {{-- Inventory history --}}
      <div class="tab-pane fade {{ $activeTab === 'inventory' ? 'show active' : '' }}" id="rider-inventory-history-pane" role="tabpanel"
        aria-labelledby="rider-inventory-history-tab">
        @if($inventoryHistories === null)
        <p class="text-muted mb-0">The inventory history table is not available yet. Run database migrations to enable this feature.</p>
        @elseif($inventoryHistories->isEmpty())
        <p class="text-muted mb-0">No inventory assignment history for this rider yet.</p>
        @else
        <div class="table-responsive">
          <table class="table table-striped table-bordered align-middle mb-0">
            <thead class="table-light">
              <tr>
                <th style="width: 50px;">#</th>
                <th>Item</th>
                <th>Customer</th>
                <th>Qty</th>
                <th>Total</th>
                <th>Assigned</th>
                <th>Assigned by</th>
                <th>Status</th>
                <th>Return / Loss</th>
                <th>Handled by</th>
                <th>Remarks</th>
              </tr>
            </thead>
            <tbody>
              @foreach($inventoryHistories as $row)
              @php
              $rowNum = ($inventoryHistories->currentPage() - 1) * $inventoryHistories->perPage() + $loop->iteration;
              $statusLabel = match ($row->status) {
                \App\Models\RiderInventoryAssignment::STATUS_ASSIGNED => 'Assigned',
                \App\Models\RiderInventoryAssignment::STATUS_RETURNED => 'Returned',
                \App\Models\RiderInventoryAssignment::STATUS_RETURNED_TO_CUSTOMER => 'Returned to Customer',
                \App\Models\RiderInventoryAssignment::STATUS_LOST => 'Lost',
                default => ucfirst(str_replace('_', ' ', (string) $row->status)),
              };
              $statusBadge = match ($row->status) {
                'assigned' => 'bg-label-primary',
                'returned' => 'bg-label-success',
                'returned_to_customer' => 'bg-label-info',
                'lost' => 'bg-label-danger',
                default => 'bg-label-secondary',
              };
              $handledBy = $row->status === 'lost'
                ? ($row->lostByUser->name ?? '—')
                : (in_array($row->status, ['returned', 'returned_to_customer'], true) ? ($row->returnedByUser->name ?? '—') : '—');
              @endphp
              <tr>
                <td>{{ $rowNum }}</td>
                <td>{{ $row->inventoryItem->name ?? '—' }}</td>
                <td>
                  @if($row->customer_id && $row->customer)
                  {{ $row->customer->name }}{{ $row->customer->company_name ? ' — ' . $row->customer->company_name : '' }}
                  @else
                  —
                  @endif
                </td>
                <td>{{ (int) ($row->qty ?? 1) }}</td>
                <td>{{ number_format($row->lineTotal(), 2) }}</td>
                <td>{{ $row->assigned_date ? \App\Helpers\General::DateFormat($row->assigned_date) : '—' }}</td>
                <td>{{ $row->assignedByUser->name ?? '—' }}</td>
                <td><span class="badge {{ $statusBadge }}">{{ $statusLabel }}</span></td>
                <td>
                  @if($row->return_date)
                  {{ \App\Helpers\General::DateFormat($row->return_date) }}
                  @elseif($row->loss_date)
                  {{ \App\Helpers\General::DateFormat($row->loss_date) }}
                  @else
                  —
                  @endif
                </td>
                <td>{{ $handledBy }}</td>
                <td>{{ $row->remarks ?: '—' }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
        </div>
        <div class="mt-4">
          {{ $inventoryHistories->appends(['tab' => 'inventory'])->withQueryString()->links() }}
        </div>
        @endif
      </div>
    </div>
  </div>
</div>

@push('page-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const tabMap = {
      'rider-status-history-tab': 'status',
      'rider-sim-history-tab': 'sim',
      'rider-bike-history-tab': 'bike',
      'rider-fuel-history-tab': 'fuel',
      'rider-inventory-history-tab': 'inventory'
    };
    const tabButtons = document.querySelectorAll(Object.keys(tabMap).map(function(id) { return '#' + id; }).join(', '));
    tabButtons.forEach(function(btn) {
      btn.addEventListener('shown.bs.tab', function(e) {
        const tab = tabMap[e.target.id] || 'status';
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
      });
    });
  });
</script>
@endpush

@endsection
