@extends('riders.view')

@section('page_content')

<div class="card card-action mb-6">
  <div class="card-header align-items-center d-flex flex-wrap justify-content-between gap-2">
    <h5 class="card-action-title mb-0"><i class="ti ti-history ti-lg text-body me-2"></i>Rider history</h5>
    @if($histories !== null)
    <span class="badge bg-label-secondary">Project changes recorded: {{ (int) $projectChangeCount }}</span>
    @endif
  </div>
  <div class="card-body pt-3 px-4 px-md-5">
    @if($histories === null)
    <p class="text-muted mb-0">The rider history table is not available yet. Run database migrations to enable this feature.</p>
    @elseif($histories->isEmpty())
    <p class="text-muted mb-0">No history entries yet. Project moves, bike assignments, and fleet supervisor changes will appear here.</p>
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
          @foreach($histories as $row)
          @php
          $meta = is_array($row->meta) ? $row->meta : [];
          $projectName = $row->customer->name ?? ($meta['new_project_name'] ?? ($meta['old_project_name'] ?? '—'));
          $employmentStatus = $meta['employment_status'] ?? null;
          $optionText = $meta['rider_status_option'] ?? null;
          if ($employmentStatus === null && $row->event_type === 'status_change') {
          $employmentStatus = $meta['new_employment_status'] ?? null;
          }
          $historyStatus = $row->history_status ?? ($meta['display_status'] ?? null);
          $rowNum = ($histories->currentPage() - 1) * $histories->perPage() + $loop->iteration;
          @endphp
          <tr>
            <td>{{ $rowNum }}</td>
            <td>{{ $row->effective_date ? \App\Helpers\General::DateFormat($row->effective_date) : '—' }}</td>
            <td>{{ $projectName }}</td>
            <td>{{ $row->branch->name ?? ($row->branch_id ? $row->branch_id : '—') }}</td>
            <td>{{ $row->bike_number ?? '—' }}</td>
            <td>{{ $row->fleet_supervisor ?? '—' }}</td>
            <td>{{ $row->title }}{{ $row->details ? ' — ' . $row->details : '' }}</td>
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
      {{ $histories->withQueryString()->links() }}
    </div>
    @endif
  </div>
</div>

@endsection