<table class="table" id="dataTableBuilder">
    <thead>
        <tr>
            <th>Employee ID</th>
            <th>Name</th>
            <th>Company Contact</th>
            <th>Branch</th>
            <th>Department</th>
            <th>Designation</th>
            <th>Date of Joining</th>
            <th>Documents Expiry</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($employees as $employee)
        <tr>
            <td>{{ $employee->employee_id ?? '-' }}</td>
            <td style="text-align: left;"><a href="{{ route('employees.show', $employee->id) }}">{{ $employee->name }}</a></td>
            <td>
                @if($employee->company_contact)
                    <a class="text-success" href="https://wa.me/{{ $employee->company_contact }}"></a>{{ $employee->company_contact }}</a>
                @else
                    -
                @endif
            </td>
            <td>
                @if($employee->branch)
                    <span class="badge bg-label-primary">{{  $employee->branch->name .' ('. $employee->branch->code .')' }}</span>
                @else
                    <span class="badge bg-label-secondary">No Branch</span>
                @endif
            </td>
            <td>{{ $employee->department->name ?? '-' }}</td>
            <td>{{ $employee->designation ?? '-' }}</td>
            <td>
                @if($employee->doj)
                    {{ $employee->doj->format('d M Y') }}
                    <br>
                    <small class="text-muted">{{ $employee->doj->diffForHumans() }}</small>
                @else
                    -
                @endif
            </td>
            <td>
                @php
                    $today = \Carbon\Carbon::today();
                    $emirateExpiry = $employee->emirate_expiry;
                    $passportExpiry = $employee->passport_expiry;
                    $visaExpiry = $employee->visa_expiry;
                @endphp
                
                @if($emirateExpiry)
                    @if($emirateExpiry->isPast())
                        <span class="badge bg-label-danger mb-1 d-block">Emirates ID Expired</span>
                    @elseif($emirateExpiry->diffInDays($today) <= 30)
                        <span class="badge bg-label-warning mb-1 d-block">Emirates ID: {{ $emirateExpiry->diffInDays($today) .' days remaining' }}</span>
                    @else
                        <span class="badge bg-label-success mb-1 d-block">Emirates ID: {{ $emirateExpiry->diffInDays($today) .' days remaining' }}</span>
                    @endif
                @endif
                
                @if($passportExpiry)
                    @if($passportExpiry->isPast())
                        <span class="badge bg-label-danger mb-1 d-block">Passport Expired</span>
                    @elseif($passportExpiry->diffInDays($today) <= 60)
                        <span class="badge bg-label-warning mb-1 d-block">Passport: {{ $passportExpiry->diffInDays($today) .' days remaining' }}</span>
                    @else
                        <span class="badge bg-label-success mb-1 d-block">Passport: {{ $passportExpiry->diffInDays($today) .' days remaining' }}</span>
                    @endif
                @endif
                
                @if($visaExpiry)
                    @if($visaExpiry->isPast())
                        <span class="badge bg-label-danger">Visa Expired</span>
                    @elseif($visaExpiry->diffInDays($today) <= 30)
                        <span class="badge bg-label-warning">Visa: {{ $visaExpiry->diffInDays($today) .' days remaining' }}</span>
                    @else
                        <span class="badge bg-label-success">Visa: {{ $visaExpiry->diffInDays($today) .' days remaining' }}</span>
                    @endif
                @endif
                
                @if(!$emirateExpiry && !$passportExpiry && !$visaExpiry)
                    <span class="badge bg-label-secondary">No Documents</span>
                @endif
            </td>
            <td>
                @if($employee->status == 'active')
                    <span class="badge bg-label-success">Active</span>
                @elseif($employee->status == 'inactive')
                    <span class="badge bg-label-danger">Inactive</span>
                @else
                    <span class="badge bg-label-warning">On Leave</span>
                @endif
            </td>
            <td style="position: relative;">
                <div class="dropdown">
                    <button class="btn btn-text-secondary rounded-pill text-body-secondary border-0 p-2 me-n1 waves-effect" 
                            type="button" 
                            id="actiondropdown_{{ $employee->id }}" 
                            data-bs-toggle="dropdown" 
                            aria-haspopup="true" 
                            aria-expanded="false" 
                            style="visibility: visible !important; display: inline-block !important;">
                        <i class="icon-base ti ti-dots icon-md text-body-secondary"></i>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown_{{ $employee->id }}" style="z-index: 1050;">
                        @can('employees_view')
                            <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-size="lg" data-title="View {{ $employee->name }}" data-action="{{ route('employees.show', $employee->id) }}">
                                <i class="fa fa-eye my-1"></i> View
                            </a>
                        @endcan
                        
                        @can('employees_edit')
                            <a href="{{ route('employees.edit', $employee) }}" class='dropdown-item waves-effect'>
                                <i class="fa fa-edit my-1"></i> Edit
                            </a>
                        @endcan
                        
                        @can('employees_documents')
                            <a href="javascript:void(0);" class='dropdown-item waves-effect show-modal' data-title="Documents - {{ $employee->name }}" data-action="{{ route('employees.documents', $employee->id) }}">
                                <i class="fa fa-file my-1"></i> Documents
                            </a>
                        @endcan
                        
                        @can('employees_delete')
                            <a href="javascript:void(0);" class='dropdown-item waves-effect delete-employee' 
                               data-url="{{ route('employees.destroy', $employee) }}">
                                <i class="fa fa-trash my-1"></i> Delete
                            </a>
                        @endcan
                    </div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@section('page-script')
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
$(document).ready(function() {
    // Initialize DataTable
    var table = $('#dataTableBuilder').DataTable({
        pageLength: 50,
        dom: "<'row'<'col-md-12'tr>>" +
             "<'row mt-2'<'col-md-6'i><'col-md-6 d-flex justify-content-end'p>>",
        columnDefs: [
            { orderable: false, targets: -1 } // Disable sorting on the Actions column
        ]
    });

    // Quick search functionality
    $('#quickSearch').on('keyup change', function() {
        $('#dataTableBuilder').DataTable().search(this.value).draw();
    });

    // Delete employee
    $(document).on('click', '.delete-employee', function(e) {
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
                            toastr.error('Error deleting employee.');
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
@endsection