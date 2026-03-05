@push('third_party_stylesheets')
<style>
   .table-responsive {
      max-height: calc(100vh - 280px);
   }
</style>
@endpush
<table class="table dataTableBuilder" id="dataTableBuilder">
    <thead class="table-light">
        <tr>
            <th>Type</th>
            <th>Name</th>
            <th>Date</th>
            <th>Check In</th>
            <th>Check Out</th>
            <th>Working Hours</th>
            <th>Status</th>
            <th>Notes</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @forelse($attendances as $attendance)
            @php
                $user = $attendance->user;
                $checkInTime = $attendance->check_in ?? null;
                $checkOutTime = $attendance->check_out ?? null;
                
                // Calculate working hours
                $workingHours = null;
                if ($checkInTime && $checkOutTime) {
                    $minutes = $checkOutTime->diffInMinutes($checkInTime);
                    $hours = floor($minutes / 60);
                    $mins = $minutes % 60;
                    $workingHours = sprintf("%02d:%02d", $hours, $mins);
                }
                
                // Status badge color
                $statusColors = [
                    'present' => 'success',
                    'absent' => 'danger',
                    'late' => 'warning',
                    'half day' => 'info',
                    'on leave' => 'primary',
                    'holiday' => 'secondary'
                ];
                $statusColor = $statusColors[$attendance->status] ?? 'secondary';
            @endphp
            <tr>
                <td>
                    <span class="badge bg-{{ $attendance->ref_type == 'employee' ? 'primary' : 'info' }}">
                        {{ ucfirst($attendance->ref_type) }}
                    </span>
                </td>
                <td style="text-align: left;">{{ $user->name ?? 'N/A' }}</td>
                <td style="white-space: nowrap">{{ $attendance->date->format('d M Y') }}</td>
                <td>
                    @if($checkInTime)
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-sign-in-alt text-success"></i>
                            {{ $checkInTime->format('h:i A') }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($checkOutTime)
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-sign-out-alt text-danger"></i>
                            {{ $checkOutTime->format('h:i A') }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    @if($workingHours)
                        <span class="badge bg-light text-dark">
                            <i class="fas fa-clock"></i> {{ $workingHours }} hrs
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td>
                    <span class="badge bg-{{ $statusColor }} px-3 py-2">
                        {{ ucwords($attendance->status) }}
                    </span>
                </td>
                <td>
                    @if($attendance->notes)
                        <span class="text-truncate d-inline-block" style="max-width: 100px;" 
                                data-bs-toggle="tooltip" title="{{ $attendance->notes }}">
                            {{ Str::limit($attendance->notes, 15) }}
                        </span>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </td>
                <td style="position: relative;">
                    <div class="dropdown">
                        <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" 
                                type="button" 
                                id="actiondropdown_{{ $attendance->id }}" 
                                data-bs-toggle="dropdown" 
                                aria-haspopup="true" 
                                aria-expanded="false" 
                                style="visibility: visible !important; display: inline-block !important;">
                            <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $attendance->id }}" style="z-index: 1050;">
                            @can('attendance_view')
                                <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="View {{ $attendance->name }}" data-action="{{ route('attendance.show', $attendance) }}">
                                    <i class="fa fa-eye my-1"></i> View
                                </a>
                            @endcan
                            
                            @can('attendance_edit')
                                <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="md" data-title="Edit Attendance" data-action="{{ route('attendance.edit', $attendance) }}">
                                     <i class="fa fa-edit my-1"></i> Edit
                                </a>
                            @endcan
                            
                            @can('attendance_delete')
                                <a href="javascript:void(0);" class='dropdown-item waves-effect delete-attendance' 
                                data-url="{{ route('attendance.destroy', $attendance) }}">
                                    <i class="fa fa-trash my-1"></i> Delete
                                </a>
                            @endcan
                        </div>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="text-center py-5">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                        <h5>No Attendance Records Found</h5>
                        <p class="text-muted">Try adjusting your filters or create a new record.</p>
                        <a href="javascript:void(0);" class="btn btn-primary show-modal" data-size="md" data-title="Add New Attendance Record" data-action="{{ route('attendance.create') }}">
                            <i class="fas fa-plus-circle"></i> Add New Record
                        </a>
                    </div>
                </td>
            </tr>
        @endforelse
    </tbody>
</table>

@push('third_party_scripts')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    $.fn.dataTable.ext.errMode = 'none';
    $('#dataTableBuilder').DataTable({
        "paging": true, // Enable DataTables pagination
        "pageLength": 50, // Items per page
        "searching": false, // Enable search
        "ordering": false, // Enable column sorting
        "info": true, // Show "Showing X of Y entries"
        "autoWidth": true, // Better column width handling
        "dom":"<'row'<'col-md-12'tr>>" +
            "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
    });
    // Delete attendance
    $(document).on('click', '.delete-attendance', function(e) {
        e.preventDefault();
        var url = $(this).data('url');

        Swal.fire({
            title: 'Are you sure?',
            text: "You won't be able to revert this!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'DELETE',
                    data: {
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            toastr.success(response.message);
                            window.location.reload();
                        } else {
                            toastr.error('Error deleting attendance.');
                        }
                    },
                    error: function(xhr) {
                        toastr.error('Error: ' + (xhr.responseJSON?.message || 'Unknown error'));
                    }
                });
            }
        });
    });
});
</script>
@endpush