<div class="table-responsive">
  <table class="table table-striped table-bordered align-middle mb-0" id="employeeAttendanceTable">
    <thead class="table-light">
      <tr>
        <th style="width: 50px;">#</th>
        <th>Date</th>
        <th>Check in</th>
        <th>Check out</th>
        <th>Working hours</th>
        <th>Status</th>
        <th>Notes</th>
        <th style="width: 80px;">Actions</th>
      </tr>
    </thead>
    <tbody>
      @foreach($attendances as $attendance)
      @php
      $checkInTime = $attendance->check_in ?? null;
      $checkOutTime = $attendance->check_out ?? null;
      $workingHours = null;
      if ($checkInTime && $checkOutTime) {
        $minutes = $checkOutTime->diffInMinutes($checkInTime);
        $workingHours = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
      }
      $statusColors = [
        'present' => 'success',
        'absent' => 'danger',
        'late' => 'warning',
        'half day' => 'info',
        'on leave' => 'primary',
        'holiday' => 'secondary',
      ];
      $statusColor = $statusColors[$attendance->status] ?? 'secondary';
      @endphp
      <tr>
        <td>{{ $loop->iteration }}</td>
        <td style="white-space: nowrap">{{ $attendance->date ? $attendance->date->format('d M Y') : '—' }}</td>
        <td>
          @if($checkInTime)
          <span class="badge bg-light text-dark">
            <i class="fas fa-sign-in-alt text-success"></i> {{ $checkInTime->format('h:i A') }}
          </span>
          @else
          <span class="text-muted">—</span>
          @endif
        </td>
        <td>
          @if($checkOutTime)
          <span class="badge bg-light text-dark">
            <i class="fas fa-sign-out-alt text-danger"></i> {{ $checkOutTime->format('h:i A') }}
          </span>
          @else
          <span class="text-muted">—</span>
          @endif
        </td>
        <td>
          @if($workingHours)
          <span class="badge bg-light text-dark"><i class="fas fa-clock"></i> {{ $workingHours }} hrs</span>
          @else
          <span class="text-muted">—</span>
          @endif
        </td>
        <td>
          <span class="badge bg-{{ $statusColor }}">{{ ucwords($attendance->status) }}</span>
        </td>
        <td>
          @if($attendance->notes)
          <span data-bs-toggle="tooltip" title="{{ $attendance->notes }}">{{ Str::limit($attendance->notes, 40) }}</span>
          @else
          <span class="text-muted">—</span>
          @endif
        </td>
        <td>
          <div class="dropdown">
            <button class="btn btn-text-secondary rounded-pill border-0 p-2" type="button"
              data-bs-toggle="dropdown" aria-expanded="false">
              <i class="ti ti-dots-vertical"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
              @can('attendance_edit')
              <li>
                <a href="javascript:void(0);" class="dropdown-item show-modal"
                  data-size="md" data-title="Edit Attendance"
                  data-action="{{ route('attendance.edit', $attendance->id) }}">
                  <i class="fa fa-edit me-1"></i> Edit
                </a>
              </li>
              @endcan
              @can('attendance_delete')
              <li>
                <a href="javascript:void(0);" class="dropdown-item delete-employee-attendance"
                  data-url="{{ route('attendance.destroy', $attendance->id) }}">
                  <i class="fa fa-trash me-1"></i> Delete
                </a>
              </li>
              @endcan
            </ul>
          </div>
        </td>
      </tr>
      @endforeach
    </tbody>
  </table>
</div>

@push('page-scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  $(document).ready(function() {
    $(document).on('click', '.delete-employee-attendance', function(e) {
      e.preventDefault();
      const url = $(this).data('url');
      Swal.fire({
        title: 'Are you sure?',
        text: 'This attendance record will be deleted.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete it'
      }).then((result) => {
        if (!result.isConfirmed) return;
        $.ajax({
          url: url,
          type: 'DELETE',
          data: { _token: '{{ csrf_token() }}' },
          success: function(response) {
            if (response.success) {
              toastr.success(response.message || 'Deleted');
              window.location.reload();
            } else {
              toastr.error(response.message || 'Delete failed');
            }
          },
          error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Delete failed');
          }
        });
      });
    });
  });
</script>
@endpush
