@extends('layouts.app')

@section('title', 'Attendance Records')

@section('content')
<div class="">
    @can('attendance_view')
    <!-- Header Section -->
    <div class="row mb-4">
        <div class="col-md-6">
        </div>
        <div class="col-md-6 text-end">
            <div class="action-buttons d-flex justify-content-end">
                <div class="action-dropdown-container">
                    <button class="action-dropdown-btn" id="addBikeDropdownBtn">
                        <i class="ti ti-plus"></i>
                        <span>Operations</span>
                        <i class="ti ti-chevron-down"></i>
                    </button>
                    <div class="action-dropdown-menu" id="addBikeDropdown">
                        @can('attendance_create')
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="md" data-title="Add New Attendance Record" data-action="{{ route('attendance.create') }}">
                            <i class="ti ti-plus"></i>
                            <span>Add New Record</span>
                        </a>
                        <a class="action-dropdown-item show-modal" href="javascript:void(0);" data-size="xl" data-bs-toggle="modal" data-bs-target="#bulkAttendanceModal">
                            <i class="ti ti-plus"></i>
                            <span>Add Bulk Attendance</span>
                        </a>
                        <a class="action-dropdown-item" href="#">
                            <i class="ti ti-file-upload"></i>
                            <span>Import Attendance</span>
                        </a>
                        @endcan
                        @can('attendance_view')
                        <a class="action-dropdown-item" href="{{ route('attendance.export')}}?from_date={{ \Carbon\Carbon::today()->subMonths(3)->format('Y-m-d') }}&to_date={{ \Carbon\Carbon::today()->format('Y-m-d') }}" data-size="xl" data-title="Export Attendance" data-action="{{ route('attendance.export') }}">
                            <i class="ti ti-file-export"></i>
                            <div>
                                <div class="action-dropdown-item-text">Export Attendance</div>
                                <div class="action-dropdown-item-desc">Last Three Months</div>
                            </div>
                        </a>
                        <a class="action-dropdown-item" href="{{ route('attendance.summary') }}">
                            <i class="ti ti-file"></i>
                            <span>View Summary</span>
                        </a>
                        @endcan
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter Sidebar -->
    <div id="filterSidebar" class="filter-sidebar" style="z-index: 1111;">
        <div class="filter-header">
            <h5>Filter Attendance</h5>
            <button type="button" class="btn-close" id="closeSidebar"></button>
        </div>
        <div class="filter-body" id="searchTopbody">
            <form id="filterForm" action="{{ route('attendance.index') }}" method="GET">
                <div class="row">
                    <div class="form-group col-md-12">
                        <label for="date">Date</label>
                        <input type="date" name="date" class="form-control" value="{{ request('date') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="ref_type" class="form-label">User Type</label>
                        <select class="form-select" id="ref_type" name="ref_type">
                            <option value="">All Types</option>
                            <option value="employee" {{ request('ref_type') == 'employee' ? 'selected' : '' }}>Employee</option>
                            <option value="rider" {{ request('ref_type') == 'rider' ? 'selected' : '' }}>Rider</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="ref_id" class="form-label">User</label>
                        <select class="form-select" id="ref_id" name="ref_id">
                            <option value="">All Users</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Status</option>
                            <option value="present" {{ request('status') == 'present' ? 'selected' : '' }}>Present</option>
                            <option value="absent" {{ request('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                            <option value="late" {{ request('status') == 'late' ? 'selected' : '' }}>Late</option>
                            <option value="half-day" {{ request('status') == 'half-day' ? 'selected' : '' }}>Half Day</option>
                            <option value="holiday" {{ request('status') == 'holiday' ? 'selected' : '' }}>Holiday</option>
                        </select>
                    </div>
                    <div class="form-group col-md-12">
                        <label for="date_from">From Date</label>
                        <input type="date" name="from_date" class="form-control" placeholder="Filter By Date From" value="{{ request('from_date') }}">
                    </div>
                    <div class="form-group col-md-12">
                        <label for="date_to">To Date</label>
                        <input type="date" name="to_date" class="form-control" placeholder="Filter By Date To" value="{{ request('to_date') }}">
                    </div>
                    <div class="col-md-12 form-group text-center">
                        <button type="submit" class="btn btn-primary pull-right mt-3"><i class="fa fa-filter mx-2"></i> Filter Data</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Attendance Table -->
    <div class="card shadow mb-4">
        <div class="card-header d-flex justify-content-between">
            <h4>Attendance Records</h4>
            <div>
                <a href="{{ route('attendance.export', request()->all()) }}" class="btn btn-success btn-sm"><i class="fa fa-file-csv"></i>  Export</a>
                <button class="btn btn-primary btn-sm openFilterSidebar"> <i class="fa fa-search"></i> Filter</button>
            </div>
        </div>
        <div class="totals-cards">
            <div class="total-card total-blue">
                <div class="label"><i class="fa fa-motorcycle"></i>Total Records</div>
                <div class="value" id="total_orders">{{ $attendances->count() ?? 0 }}</div>
            </div>
            <div class="total-card total-green">
                <div class="label"><i class="fa fa-check-circle"></i>Present</div>
                <div class="value" id="avg_ontime">{{ $attendances->where('status', 'present')->count() ?? 0 }}</div>
            </div>
            <div class="total-card total-red">
                <div class="label"><i class="fa fa-times-circle"></i>Absent</div>
                <div class="value" id="total_rejected">{{ $attendances->where('status', 'absent')->count() ?? 0 }}</div>
            </div>
            <div class="total-card total-1">
                <div class="label"><i class="fa fa-building"></i>On leave</div>
                <div class="value" id="total_hours">{{ $attendances->where('status', 'on leave')->count() ?? 0 }}</div>
            </div>
            <div class="total-card total-2">
                <div class="label"><i class="fa fa-building"></i>Half Day</div>
                <div class="value" id="total_hours">{{ $attendances->where('status', 'half day')->count() ?? 0 }}</div>
            </div>
            <div class="total-card total-3">
                <div class="label"><i class="fa fa-user-secret"></i>Holiday</div>
                <div class="value" id="total_hours">{{ $attendances->where('status', 'holiday')->count() ?? 0 }}</div>
            </div>
        </div>
        <div class="card-body table-responsive py-0 px-2" id="table-data">
            @include('attendance.table')
        </div>
    </div>
</div>

<!-- Bulk Attendance Modal - NEW APPROACH -->
<div class="modal fade" id="bulkAttendanceModal" tabindex="-1" aria-labelledby="bulkAttendanceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="bulkAttendanceModalLabel">
                    <i class="fas fa-users me-2"></i>Bulk Mark Attendance
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="{{ route('attendance.bulk-mark') }}" method="POST" id="bulkAttendanceForm">
                @csrf
                <div class="modal-body">
                    <!-- Basic Info Row -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label for="bulk_ref_type" class="form-label fw-bold">
                                <i class="fas fa-user-tag me-1"></i>User Type <span class="text-danger">*</span>
                            </label>
                            <select class="form-select select2-2" id="bulk_ref_type" name="ref_type" required>
                                <option value="">-- Select User Type --</option>
                                <option value="employee"> Employees</option>
                                <option value="rider">Riders</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="userSelect" class="form-label fw-bold">
                                <i class="fas fa-users me-1"></i>Users
                            </label>
                            <select class="form-select select2-3" id="userSelect" style="max-width: 300px;" disabled>
                                <option value="">Select user to add</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="bulk_date" class="form-label fw-bold">
                                <i class="fas fa-calendar-alt me-1"></i>Date <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control" id="bulk_date" name="date" 
                                   value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label for="default_status" class="form-label fw-bold">
                                <i class="fas fa-flag me-1"></i>Default Status
                            </label>
                            <select class="form-select select2-2" id="default_status">
                                <option value="present">Present</option>
                                <option value="absent">Absent</option>
                                <option value="late">Late</option>
                                <option value="half-day">Half Day</option>
                                <option value="holiday">Holiday</option>
                            </select>
                        </div>
                    </div>

                    <!-- Selected Users Table -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label fw-bold mb-0">
                                    <i class="fas fa-table me-1"></i>Selected Users
                                    <span class="badge bg-secondary ms-2" id="selectedUsersCount">0</span>
                                </label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-success" id="setAllPresent">
                                        <i class="fas fa-check-circle"></i> All Present
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="setAllAbsent">
                                        <i class="fas fa-times-circle"></i> All Absent
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning" id="setGeneral">
                                        <i class="fas fa-clock"></i> Set 10am - 6pm
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="removeAllUsers">
                                        <i class="fas fa-trash"></i> Remove All
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 4px;">
                                <table class="table table-bordered mb-0" id="selectedUsersTable">
                                    <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                        <tr>
                                            <th style="width: 50px; background: #f8f9fa;">#</th>
                                            <th style="min-width: 200px; background: #f8f9fa;">User</th>
                                            <th style="min-width: 150px; background: #f8f9fa;">Status</th>
                                            <th style="min-width: 130px; background: #f8f9fa;">Check In</th>
                                            <th style="min-width: 130px; background: #f8f9fa;">Check Out</th>
                                            <th style="min-width: 200px; background: #f8f9fa;">Notes</th>
                                            <th style="width: 80px; background: #f8f9fa;">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="selectedUsersBody">
                                        <tr id="noUsersRow">
                                            <td colspan="7" class="text-center py-5">
                                                <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                                                <h6 class="text-muted">No users selected</h6>
                                                <p class="text-muted small">Select user type and add users from the dropdown above</p>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between align-items-center w-100 pt-3">
                        <div></div>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                <i class="fas fa-times me-1"></i>Cancel
                            </button>
                            <button type="submit" class="btn btn-primary" id="submitBulkBtn" disabled>
                                <i class="fas fa-save me-1"></i>Save Attendance
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
    @endcan
    @cannot('attendance_view')
        <div class="alert alert-danger" role="alert">
            You do not have permission to view attendance records.
        </div>
    @endcannot
</div>

@endsection

@push('third_party_stylesheets')
<style>
    .total-card {
        flex: 1 1 calc(15% - 8px);
    }
</style>
@endpush

@section('page-script')
<script>
$(document).ready(function() {
    // Store users and selected users data
    var allUsers = [];
    var selectedUsers = []; // Array to store selected user objects with their data
    var nextId = 1;
    
    $('.select2-2').select2({
        dropdownParent: $('#bulkAttendanceModal')
    });

    $('.select2-3').select2({
        dropdownParent: $('#bulkAttendanceModal'),
        closeOnSelect: false,
    });
    // Load users when type changes
    $('#bulk_ref_type').change(function() {
        var refType = $(this).val();
        selectedUsers = [];
        renderSelectedUsers();
        if (refType) {
            // Enable search and select
            $('#userSearchInput').prop('disabled', false);
            $('#userSelect').prop('disabled', false);
            $('#addUserBtn').prop('disabled', false);
            $('#userSelectStatus').html('<i class="fas fa-spinner fa-spin"></i> Loading users...');
            
            // Clear previous users
            $('#userSelect').html('<option value="">Loading users...</option>');
            
            $.get('{{ route("attendance.users", "") }}/' + refType, function(users) {
                allUsers = users;
                $('#userSelect').html('<option value="">-- Select user to add --</option>');
                
                if (users.length === 0) {
                    $('#userSelect').append('<option value="" disabled>No users found</option>');
                    $('#userSelectStatus').html('<i class="fas fa-exclamation-triangle text-warning"></i> No users found for this type');
                } else {
                    $.each(users, function(index, user) {
                        $('#userSelect').append('<option value="' + user.id + '">' + user.name + (user.email ? ' - ' + user.email : '') + '</option>');
                    });
                    $('#userSelectStatus').html('<i class="fas fa-check-circle text-success"></i> ' + users.length + ' users available');
                }
                
                // Reset search
                $('#userSearchInput').val('');
            }).fail(function() {
                $('#userSelect').html('<option value="">Error loading users</option>');
                $('#userSelectStatus').html('<i class="fas fa-exclamation-circle text-danger"></i> Failed to load users');
            });
        } else {
            // Disable and reset
            $('#userSearchInput').prop('disabled', true).val('');
            $('#userSelect').prop('disabled', true).html('<option value="">Select user to add</option>');
            $('#addUserBtn').prop('disabled', true);
            $('#userSelectStatus').html('<i class="fas fa-info-circle"></i> First select user type to load users');
            allUsers = [];
        }
    });
    
    // Add user to table
    $('#userSelect').change(function() {
        var userId = $('#userSelect').val();
        var refType = $('#bulk_ref_type').val();
        
        if (!userId || !refType) {
            toastr.warning('Please select a user to add');
            return;
        }
        
        // Check if user already selected
        if (selectedUsers.some(u => u.id == userId)) {
            toastr.warning('This user is already in the list');
            return;
        }
        
        // Find user in allUsers
        var user = allUsers.find(u => u.id == userId);
        if (!user) return;
        
        // Add to selected users
        var selectedUser = {
            id: user.id,
            name: user.name,
            email: user.email || '',
            refType: refType,
            tempId: 'row_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9),
            status: $('#default_status').val(),
            checkIn: '',
            checkOut: '',
            notes: ''
        };
        
        selectedUsers.push(selectedUser);
        renderSelectedUsers();
        
        // Clear selection
        $('#userSelect').val('');
    });
    
    // Render selected users table
    function renderSelectedUsers() {
        var tbody = $('#selectedUsersBody');
        tbody.empty();
        
        if (selectedUsers.length === 0) {
            tbody.append(`
                <tr id="noUsersRow">
                    <td colspan="7" class="text-center py-5">
                        <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">No users selected</h6>
                        <p class="text-muted small">Select user type and add users from the dropdown above</p>
                    </td>
                </tr>
            `);
            $('#selectedUsersCount').text('0');
            $('#totalSelectedCount').text('0');
            $('#presentCount').text('0');
            $('#submitBulkBtn').prop('disabled', true);
            return;
        }
        
        $('#submitBulkBtn').prop('disabled', false);
        
        $.each(selectedUsers, function(index, user) {
            tbody.append(`
                <tr id="${user.tempId}" data-user-id="${user.id}" data-temp-id="${user.tempId}">
                    <td class="text-center align-middle">${index + 1}</td>
                    <td class="align-middle">
                        <div class="d-flex align-items-center">
                            <div>
                                <strong>${user.name}</strong>
                            </div>
                        </div>
                        <input type="hidden" name="attendances[${user.tempId}][ref_id]" value="${user.id}">
                        <input type="hidden" name="attendances[${user.tempId}][ref_type]" value="${user.refType}">
                    </td>
                    <td class="align-middle">
                        <select name="attendances[${user.tempId}][status]" class="form-select form-select-sm status-select" data-user-temp="${user.tempId}">
                            <option value="present" ${user.status === 'present' ? 'selected' : ''}>Present</option>
                            <option value="absent" ${user.status === 'absent' ? 'selected' : ''}>Absent</option>
                            <option value="late" ${user.status === 'late' ? 'selected' : ''}>Late</option>
                            <option value="half-day" ${user.status === 'half-day' ? 'selected' : ''}>Half Day</option>
                            <option value="holiday" ${user.status === 'holiday' ? 'selected' : ''}>Holiday</option>
                        </select>
                    </td>
                    <td class="align-middle">
                        <input type="time" name="attendances[${user.tempId}][check_in]" 
                               class="form-control form-control-sm check-in" 
                               value="${user.checkIn}" step="1">
                    </td>
                    <td class="align-middle">
                        <input type="time" name="attendances[${user.tempId}][check_out]" 
                               class="form-control form-control-sm check-out" 
                               value="${user.checkOut}" step="1">
                    </td>
                    <td class="align-middle">
                        <input type="text" name="attendances[${user.tempId}][notes]" 
                               class="form-control form-control-sm notes" 
                               value="${user.notes}" placeholder="Notes...">
                    </td>
                    <td class="text-center align-middle">
                        <button type="button" class="btn btn-sm btn-outline-danger mt-1 remove-user" 
                                data-user-temp="${user.tempId}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
        
        updateCounts();
    }
    
    // Update counts
    function updateCounts() {
        $('#selectedUsersCount').text(selectedUsers.length);
        $('#totalSelectedCount').text(selectedUsers.length);
        
        var presentCount = selectedUsers.filter(u => u.status === 'present').length;
        $('#presentCount').text(presentCount);
    }
    
    // Update user data when form fields change
    $(document).on('change', '.status-select', function() {
        var tempId = $(this).data('user-temp');
        var user = selectedUsers.find(u => u.tempId === tempId);
        if (user) {
            user.status = $(this).val();
            updateCounts();
        }
    });
    
    $(document).on('change', '.check-in', function() {
        var tempId = $(this).closest('tr').data('temp-id');
        var user = selectedUsers.find(u => u.tempId === tempId);
        if (user) {
            user.checkIn = $(this).val();
        }
    });
    
    $(document).on('change', '.check-out', function() {
        var tempId = $(this).closest('tr').data('temp-id');
        var user = selectedUsers.find(u => u.tempId === tempId);
        if (user) {
            user.checkOut = $(this).val();
        }
    });
    
    $(document).on('change', '.notes', function() {
        var tempId = $(this).closest('tr').data('temp-id');
        var user = selectedUsers.find(u => u.tempId === tempId);
        if (user) {
            user.notes = $(this).val();
        }
    });
    
    // Remove user when remove button clicked
    
    $(document).on('click', '.remove-user', function() {
        var tempId = $(this).data('user-temp');
        removeUser(tempId);
    });
    
    function removeUser(tempId) {
        selectedUsers = selectedUsers.filter(u => u.tempId !== tempId);
        $('#' + tempId).fadeOut(300, function() {
            renderSelectedUsers();
        });
        toastr.info('User removed from list');
    }
    
    // Set all to present
    $('#setAllPresent').click(function() {
        selectedUsers.forEach(user => user.status = 'present');
        $('.status-select').val('present');
        updateCounts();
        toastr.success('All users set to Present');
    });
    
    // Set all to absent
    $('#setAllAbsent').click(function() {
        selectedUsers.forEach(user => user.status = 'absent');
        $('.status-select').val('absent');
        updateCounts();
        toastr.success('All users set to Absent');
    });

    // Set general time (10am - 6pm)
    $('#setGeneral').click(function() {
        selectedUsers.forEach(user => {
            user.checkIn = '10:00';
            user.checkOut = '18:00';
        });
        $('.check-in').val('10:00');
        $('.check-out').val('18:00');
        toastr.success('Check-in set to 10:00 and Check-out set to 18:00 for all users');
    });
    
    // Remove all users
    $('#removeAllUsers').click(function() {
        if (selectedUsers.length > 0 && confirm('Are you sure you want to remove all users?')) {
            selectedUsers = [];
            renderSelectedUsers();
            toastr.info('All users removed');
        }
    });
    
    // Form submission
    $('#bulkAttendanceForm').submit(function(e) {
        e.preventDefault();
        
        if (selectedUsers.length === 0) {
            toastr.warning('Please add at least one user');
            return;
        }
        
        var formData = $(this).serialize();
        var submitBtn = $('#submitBulkBtn');
        
        submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            success: function(response) {
                toastr.success(response.message || 'Attendance marked successfully');
                setTimeout(function() {
                    $('#bulkAttendanceModal').modal('hide');
                    location.reload();
                }, 1500);
            },
            error: function(xhr) {
                submitBtn.prop('disabled', false).html('<i class="fas fa-save"></i> Save Attendance');
                
                if (xhr.status === 422) {
                    var errors = xhr.responseJSON.errors;
                    var errorMessages = [];
                    $.each(errors, function(key, messages) {
                        errorMessages.push(messages[0]);
                    });
                    toastr.error(errorMessages.join('<br>'));
                } else {
                    toastr.error('An error occurred. Please try again.' + (xhr.responseJSON ? ' ' + (xhr.responseJSON.message || '') : ''));
                }
            }
        });
    });
    
    // Reset modal on close
    $('#bulkAttendanceModal').on('hidden.bs.modal', function() {
        selectedUsers = [];
        allUsers = [];
        $('#bulk_ref_type').val('');
        $('#userSearchInput').val('').prop('disabled', true);
        $('#userSelect').html('<option value="">Select user to add</option>').prop('disabled', true);
        $('#addUserBtn').prop('disabled', true);
        $('#userSelectStatus').html('<i class="fas fa-info-circle"></i> First select user type to load users');
        renderSelectedUsers();
    });
});
</script>
@endsection