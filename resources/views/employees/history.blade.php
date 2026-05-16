@extends('employees.view')

@section('page_content')

<div class="card card-action mb-6">
  <div class="card-header align-items-center d-flex flex-wrap justify-content-between gap-2 pb-0">
    <h5 class="card-action-title mb-0"><i class="ti ti-device-mobile ti-lg text-body me-2"></i>Employee SIM history</h5>
    @if($simHistories !== null)
    <span class="badge bg-label-info">SIM records: {{ (int) ($simHistoryCount ?? 0) }}</span>
    @endif
  </div>
  <div class="card-body pt-3 px-4 px-md-5">
    @if($simHistories === null)
    <p class="text-muted mb-0">The SIM history table is not available yet. Run database migrations to enable this feature.</p>
    @elseif($simHistories->isEmpty())
    <p class="text-muted mb-0">No SIM assignment history for this employee yet. Assignments and returns are recorded when a SIM is assigned to or returned from this employee.</p>
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
      {{ $simHistories->withQueryString()->links() }}
    </div>
    @endif
  </div>
</div>

@endsection
