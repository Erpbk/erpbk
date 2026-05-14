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
    <ul class="timeline mb-0">
      @foreach($histories as $row)
      <li class="timeline-item timeline-item-transparent">
        <span class="timeline-point timeline-point-{{ $row->event_type === 'project_change' ? 'info' : 'primary' }}"></span>
        <div class="timeline-event">
          <div class="timeline-header mb-2">
            <h6 class="mb-0">{{ $row->title }}</h6>
            <small class="text-muted">
              Effective: {{ $row->effective_date ? \App\Helpers\General::DateFormat($row->effective_date) : '—' }}
              <span class="mx-1">·</span>
              Logged: {{ $row->created_at ? \App\Helpers\General::DateTimeFormat($row->created_at) : '—' }}
            </small>
          </div>
          @if(!empty($row->details))
          <p class="mb-2 text-body">{{ $row->details }}</p>
          @endif
          @if(!empty($row->meta) && is_array($row->meta))
          <div class="small text-muted">
            @if(($row->meta['source'] ?? null))
            <div><strong>Source:</strong> {{ $row->meta['source'] }}</div>
            @endif
            @if($row->event_type === 'status_change')
            @if(array_key_exists('previous_rider_status', $row->meta) || array_key_exists('new_rider_status', $row->meta))
            <div><strong>Status:</strong> {{ $row->meta['previous_rider_status'] ?? '—' }} → {{ $row->meta['new_rider_status'] ?? '—' }}</div>
            @endif
            @endif
          </div>
          @endif
        </div>
      </li>
      @endforeach
    </ul>
    <div class="mt-4">
      {{ $histories->withQueryString()->links() }}
    </div>
    @endif
  </div>
</div>

@endsection
