@extends('riders.view')

@section('page_content')

@php
$activeTab = $activeTab ?? (in_array(request('tab'), ['status', 'sim'], true) ? request('tab') : 'status');
@endphp

<div class="card card-action mb-6">
  <div class="card-header align-items-center d-flex flex-wrap justify-content-between gap-2 pb-0">
    <h5 class="card-action-title mb-0"><i class="ti ti-history ti-lg text-body me-2"></i>Rider history</h5>
    <div class="d-flex flex-wrap gap-2 align-items-center">
      @if($statusHistories !== null)
      <span class="badge bg-label-secondary">Project changes: {{ (int) $projectChangeCount }}</span>
      @endif
      @if($simHistories !== null)
      <span class="badge bg-label-info">SIM records: {{ (int) ($simHistoryCount ?? 0) }}</span>
      @endif
    </div>
  </div>
  <ul class="nav nav-tabs px-4 pt-3" role="tablist">
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab === 'status' ? 'active' : '' }}" id="rider-status-history-tab" data-bs-toggle="tab"
        data-bs-target="#rider-status-history-pane" type="button" role="tab"
        aria-controls="rider-status-history-pane" aria-selected="{{ $activeTab === 'status' ? 'true' : 'false' }}">
        <i class="ti ti-user-check me-1"></i>Rider status history
      </button>
    </li>
    <li class="nav-item" role="presentation">
      <button class="nav-link {{ $activeTab === 'sim' ? 'active' : '' }}" id="rider-sim-history-tab" data-bs-toggle="tab"
        data-bs-target="#rider-sim-history-pane" type="button" role="tab"
        aria-controls="rider-sim-history-pane" aria-selected="{{ $activeTab === 'sim' ? 'true' : 'false' }}">
        <i class="ti ti-device-mobile me-1"></i>Rider SIM history
      </button>
    </li>
  </ul>
  <div class="card-body pt-3 px-4 px-md-5">
    <div class="tab-content">
      {{-- Rider status history --}}
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

      {{-- Rider SIM history --}}
      <div class="tab-pane fade {{ $activeTab === 'sim' ? 'show active' : '' }}" id="rider-sim-history-pane" role="tabpanel"
        aria-labelledby="rider-sim-history-tab">
        @if($simHistories === null)
        <p class="text-muted mb-0">The SIM history table is not available yet. Run database migrations to enable this feature.</p>
        @elseif($simHistories->isEmpty())
        <p class="text-muted mb-0">No SIM assignment history for this rider yet. Assignments and returns are recorded when a SIM is assigned to or returned from this rider.</p>
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
    </div>
  </div>
</div>

@push('page-scripts')
<script>
  document.addEventListener('DOMContentLoaded', function() {
    const tabButtons = document.querySelectorAll('#rider-status-history-tab, #rider-sim-history-tab');
    tabButtons.forEach(function(btn) {
      btn.addEventListener('shown.bs.tab', function(e) {
        const tab = e.target.id === 'rider-sim-history-tab' ? 'sim' : 'status';
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
      });
    });
  });
</script>
@endpush

@endsection
