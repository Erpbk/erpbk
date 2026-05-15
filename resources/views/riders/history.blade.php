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
    <p class="text-muted mb-0">No history entries yet. Project moves and view-card status changes will appear here.</p>
    @else
    <div class="table-responsive">
      <table class="table table-striped table-bordered align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th style="width: 50px;">#</th>
            <th>Effective date</th>
            <th>Type</th>
            <th>Title</th>
            <th>Details</th>
            <th>Status change</th>
            <th>Source</th>
            <th>Logged at</th>
          </tr>
        </thead>
        <tbody>
          @foreach($histories as $row)
          @php
          $meta = is_array($row->meta) ? $row->meta : [];
          $typeLabel = ucwords(str_replace('_', ' ', $row->event_type ?? ''));
          $typeBadge = match($row->event_type) {
            'project_change' => 'bg-label-info',
            'status_change' => 'bg-label-primary',
            default => 'bg-label-secondary',
          };
          $statusChange = '—';
          if ($row->event_type === 'status_change' && (array_key_exists('previous_rider_status', $meta) || array_key_exists('new_rider_status', $meta))) {
            $statusChange = ($meta['previous_rider_status'] ?? '—') . ' → ' . ($meta['new_rider_status'] ?? '—');
          }
          $rowNum = ($histories->currentPage() - 1) * $histories->perPage() + $loop->iteration;
          @endphp
          <tr>
            <td>{{ $rowNum }}</td>
            <td>{{ $row->effective_date ? \App\Helpers\General::DateFormat($row->effective_date) : '—' }}</td>
            <td><span class="badge {{ $typeBadge }}">{{ $typeLabel }}</span></td>
            <td>{{ $row->title }}</td>
            <td>{{ $row->details ?: '—' }}</td>
            <td>{{ $statusChange }}</td>
            <td>{{ $meta['source'] ?? '—' }}</td>
            <td>{{ $row->created_at ? \App\Helpers\General::DateTimeFormat($row->created_at) : '—' }}</td>
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
