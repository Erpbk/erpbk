

@if($maintenances->count() > 0)
        <table class="table dataTable table-hover" id="dataTableBuilder">
            <thead>
                <tr class="table-primary">
                    <th>Date</th>
                    <th>Bike</th>
                    <th>Rider</th>
                    <th>Previous KM</th>
                    <th>Current KM</th>
                    <th>Overdue</th>
                    <th>Total Cost</th>
                    <th>Bill</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($maintenances as $maintenance)
                    @php
                        $overdueKm = $maintenance->overdue_km ?? 0;
                        
                        // Determine severity based on overdue KM
                        if($overdueKm > 0) {
                            $severityClass = 'bg-danger';
                            $rowClass = 'table-danger';
                        } else {
                            $severityClass = 'bg-success';
                            $rowClass = '';
                        }
                    @endphp
                    <tr class="">
                        <td style=" white-space: nowrap;">
                            <strong>{{ $maintenance->maintenance_date->format('d M Y') }}</strong>
                            <br>
                            <small class="text-muted">{{ $maintenance->maintenance_date->diffForHumans() }}</small>
                        </td>
                        <td style=" white-space: nowrap;">
                            <a href="{{ route('bikes.show', $maintenance->bike_id) }}" target="_blank">
                                <strong>{{ $maintenance->bike->emirates ?? '' }}-{{ $maintenance->bike->plate ?? 'N/A' }}</strong>
                            </a>
                        </td>
                        <td>
                            @if($maintenance->rider)
                                {{ $maintenance->rider? $maintenance->rider->rider_id .'-'. $maintenance->rider->name : '-' }}
                            @else
                                <span class="text-muted">No Rider</span>
                            @endif
                        </td>
                        <td>
                            {{ number_format($maintenance->previous_km) }} km
                        </td>
                        <td>
                            {{ number_format($maintenance->current_km) }} km
                        </td>
                        <td>
                            <span class="badge {{ $severityClass }}">
                                <i class="fa fa-exclamation-triangle me-1"></i>
                                {{ number_format($overdueKm, 1) }} km
                            </span><br>
                            @php
                                $overdue_cost = ($maintenance->overdue_cost_per_km*$maintenance->overdue_km??0);
                            @endphp
                            @if($overdue_cost >0)
                                <small class="text-danger">
                                    AED {{ number_format($overdue_cost, 2) }}
                                </small><br>
                                @if($maintenance->overdue_paidby === 'Rider')
                                    <small class="text-muted">Charged</small>
                                @else
                                    <small class="text-muted no-wrap">Not Charged</small>
                                @endif
                            @endif
                        </td>
                        <td>
                            <strong>AED {{ number_format($maintenance->total_cost ?? 0, 2) }}</strong>
                        </td>
                        <td style="white-space: nowrap">
                            <a href="{{ route('bike-maintenance.invoice', $maintenance) }}" 
                            class='' target="_blank">
                                MA-{{ $maintenance->id }}
                            </a>
                        </td>
                        <td style="position: relative;">
                            <div class="dropdown">
                                <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect bg-white" 
                                        type="button" 
                                        id="actiondropdown_{{ $maintenance->id }}" 
                                        data-bs-toggle="dropdown" 
                                        aria-haspopup="true" 
                                        aria-expanded="false" 
                                        style="visibility: visible !important; display: inline-block !important;">
                                    <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                                </button>
                                <div class="dropdown-menu dropdown-menu-end" 
                                    aria-labelledby="actiondropdown_{{ $maintenance->id }}" 
                                    style="z-index: 1050;">
                                    <a class="dropdown-item waves-effect show-modal"
                                        href="javascript:void(0);"
                                        data-size="xl"
                                        data-title="Edit Maintenance Record"
                                        data-action="{{ route('bikeMaintenance.edit', $maintenance) }}">
                                        <i class="fa fa-edit my-1"></i>Edit Record
                                    </a>
                                    @can('bike_delete')
                                    <a href="javascript:void(0);" class='dropdown-item waves-effect delete-record' data-url="{{ route('bikeMaintenance.destroy', $maintenance) }} ">
                                        <i class="fa fa-trash my-1 text-danger"></i>Delete Record
                                    </a>
                                    @endcan
                                </div>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@else
    <div class="text-center py-5">
        <i class="fa fa-tools fa-4x text-muted mb-3"></i>
        <h4>No Maintenance Records Found</h4>
        <p class="text-muted">No maintenance records have been created yet.</p>
    </div>
@endif
@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    $.fn.dataTable.ext.errMode = 'none';
    $('#dataTableBuilder').DataTable({
        "paging": true,           // Enable DataTables pagination
        "pageLength": 50,         // Items per page
        "searching": true,        // Enable search
        "ordering": false,         // Enable column sorting
        "info": true,             // Show "Showing X of Y entries"
        "autoWidth": true,       // Better column width handling
        "dom": "<'row'<'col-md-6'><'col-md-6 d-flex justify-content-end'f>>" +
       "<'row'<'col-md-12'tr>>" +
       "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
    });
    $(document).ready(function() {
        // Handle delete record functionality with AJAX
        $(document).on('click', '.delete-record', function(e) {
            e.preventDefault();
            const url = $(this).data('url');
            
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
                    // Send AJAX DELETE request
                    $.ajax({
                        url: url,
                        type: 'DELETE',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            Swal.fire(
                                'Deleted!',
                                'Maintenance record has been deleted.',
                                'success'
                            ).then(() => {
                                // Reload the page to update the list
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            Swal.fire(
                                'Error!',
                                'Failed to delete file.',
                                'error'
                            );
                        }
                    });
                }
            });
        });
    });
</script>
@endsection