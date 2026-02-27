@extends('employees.view')

@section('page-content')
<style>
    .edit-form {
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        border-radius: 23px;
        padding: 1.5rem;
    }

    .edit-btn {
        font-size: 12px;
        padding: 4px 1px;
        border-radius: 70px;
        width: 32px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .section-form .form-control-sm {
        font-size: 12px;
    }

    .card-header .d-flex {
        align-items: center;
    }

    .card-header i {
        padding: 8px;
        border-radius: 50%;
        margin-right: 8px;
    }

    /* Select2 styling for small forms */
    .edit-form .select2-container .select2-selection--single {
        height: 31px;
        font-size: 12px;
    }

    .edit-form .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 29px;
        padding-left: 8px;
        padding-right: 20px;
    }

    .edit-form .select2-container .select2-selection--single .select2-selection__arrow {
        height: 29px;
        right: 3px;
    }

    .edit-form .select2-dropdown {
        font-size: 12px;
    }

    .edit-form .select2-container--bootstrap4 .select2-selection--single {
        border: 1px solid #ced4da;
        border-radius: 0.25rem;
    }

    /* Expiry date styling */
    .expired {
        color: #dc3545;
        font-weight: bold;
    }

    .expiring-soon {
        color: #ffc107;
        font-weight: bold;
    }

    .valid {
        color: #28a745;
    }

    .badge-expired {
        background-color: #dc3545;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 5px;
    }

    .badge-expiring {
        background-color: #ffc107;
        color: #212529;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 5px;
    }

    .badge-valid {
        background-color: #28a745;
        color: white;
        font-size: 10px;
        padding: 2px 6px;
        border-radius: 4px;
        margin-left: 5px;
    }
</style>

<!-- Personal Information Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-user ti-sm" style="background: #cadaef; color: #024baa; padding: 8px; border-radius: 50%;"></i>
                <b>Personal Information</b>
            </div>
            <button type="button" class="btn btn-sm btn-primary edit-btn" data-section="personal">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    
    <!-- Display Section -->
    <div class="card-body display-section" id="display-personal">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Full Name</label>
                <p>{{ $employee->name }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Date of Birth</label>
                <p>
                    @if($employee->dob)
                        {{ $employee->dob->format('d M Y') }}
                        <small class="text-muted">({{ $employee->dob->age }} years)</small>
                    @else
                        -
                    @endif
                </p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Nationality</label>
                <p>{{ $employee->nationality->name ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Personal Email</label>
                <p>{{ $employee->personal_email ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Personal Contact</label>
                <p>{{ $employee->personal_contact ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Emergency Contact</label>
                <p>{{ $employee->emergency_contact ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Address</label>
                <p>{{ $employee->address ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Edit Form for Personal Information -->
    <div class="card-body edit-form" id="edit-personal" style="display: none;">
        <form class="section-form" data-section="personal">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Full Name</label>
                    <input type="text" class="form-control form-control-sm" name="name" value="{{ $employee->name }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Date of Birth</label>
                    <input type="date" class="form-control form-control-sm" name="dob" value="{{ $employee->dob ? $employee->dob->format('Y-m-d') : '' }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Nationality</label>
                    <select class="form-control form-control-sm select2" name="nationality_id">
                        <option value="">Select Nationality</option>
                        @foreach($nationalities as $nationality)
                            <option value="{{ $nationality->id }}" {{ $employee->nationality_id == $nationality->id ? 'selected' : '' }}>
                                {{ $nationality->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Personal Email</label>
                    <input type="email" class="form-control form-control-sm" name="personal_email" value="{{ $employee->personal_email }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Personal Contact</label>
                    <input type="text" class="form-control form-control-sm" name="personal_contact" value="{{ $employee->personal_contact }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Emergency Contact</label>
                    <input type="text" class="form-control form-control-sm" name="emergency_contact" value="{{ $employee->emergency_contact }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Address</label>
                    <textarea class="form-control form-control-sm" rows="5" name="address">{{ $employee->address }}</textarea>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit" data-section="personal">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Employment Details Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-briefcase ti-sm" style="background: #a002aa38; color: #a002aa; padding: 8px; border-radius: 50%;"></i>
                <b>Employment Details</b>
            </div>
            <button type="button" class="btn btn-sm btn-primary edit-btn" data-section="employment">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    
    <!-- Display Section -->
    <div class="card-body display-section" id="display-employment">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Employee ID</label>
                <p>{{ $employee->employee_id }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Department</label>
                <p>{{ $employee->department->name ?? $employee->department_id ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Designation</label>
                <p>{{ $employee->designation ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Branch</label>
                <p>{{ $employee->branch ? $employee->branch->name .' ('. $employee->branch->code .')' : '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Date of Joining</label>
                <p>
                    @if($employee->doj)
                        {{ $employee->doj->format('d M Y') }}
                        <small class="text-muted">({{ $employee->doj->diffForHumans() }})</small>
                    @else
                        -
                    @endif
                </p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Salary</label>
                <p>{{ $employee->salary ? number_format($employee->salary, 2) . ' AED' : '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Company Email</label>
                <p>{{ $employee->company_email ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Company Contact</label>
                <p>{{ $employee->company_contact ?? '-' }}</p>
            </div>
        </div>
    </div>

    <!-- Edit Form for Employment Details -->
    <div class="card-body edit-form" id="edit-employment" style="display: none;">
        <form class="section-form" data-section="employment">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Department</label>
                    <select class="form-control form-control-sm select2" name="department_id">
                        <option value="">Select Department</option>
                        @foreach($departments as $department)
                            <option value="{{ $department->id }}" {{ $employee->department_id == $department->id ? 'selected' : '' }}>{{ $department->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Designation</label>
                    <input type="text" class="form-control form-control-sm" name="designation" value="{{ $employee->designation }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Branch</label>
                    <select class="form-control form-control-sm select2" name="branch_id">
                        <option value="">Select Branch</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ $employee->branch_id == $branch->id ? 'selected' : '' }}>{{ $branch->name .' ('. $branch->code .')' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Date of Joining</label>
                    <input type="date" class="form-control form-control-sm" name="doj" value="{{ $employee->doj ? $employee->doj->format('Y-m-d') : '' }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Salary</label>
                    <input type="number" step="0.01" class="form-control form-control-sm" name="salary" value="{{ $employee->salary }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Company Email</label>
                    <input type="email" class="form-control form-control-sm" name="company_email" value="{{ $employee->company_email }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Company Contact</label>
                    <input type="text" class="form-control form-control-sm" name="company_contact" value="{{ $employee->company_contact }}">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit" data-section="employment">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Emirates ID & Passport Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-id ti-sm" style="background: #3a3a3c52; color: #3a3a3c; padding: 8px; border-radius: 50%;"></i>
                <b>Documents ( Emirates ID | Passport | Visa )</b>
            </div>
            <button type="button" class="btn btn-sm btn-primary edit-btn" data-section="documents">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    
    <!-- Display Section -->
    <div class="card-body display-section" id="display-documents">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Emirates ID</label>
                <p>{{ $employee->emirate_id ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Passport Number</label>
                <p>{{ $employee->passport ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Visa Sponsor</label>
                <p>{{ $employee->visa_sponsor ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Visa Occupation</label>
                <p>{{ $employee->visa_occupation ?? '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Emirates ID Expiry</label>
                @php
                    $emirateExpiryClass = '';
                    $emirateBadge = '';
                    if($employee->emirate_expiry) {
                        $today = \Carbon\Carbon::today();
                        $expiry = $employee->emirate_expiry;
                        $daysLeft = $today->diffInDays($expiry, false);
                        
                        if($expiry->isPast()) {
                            $emirateExpiryClass = 'expired';
                            $emirateBadge = '<span class="badge-expired">Expired</span>';
                        } elseif($daysLeft <= 30) {
                            $emirateExpiryClass = 'expiring-soon';
                            $emirateBadge = '<span class="badge-expiring">Expiring Soon ('.$daysLeft.' days)</span>';
                        } else {
                            $emirateExpiryClass = 'valid';
                            $emirateBadge = '<span class="badge-valid">Valid</span>';
                        }
                    }
                @endphp
                <p class="{{ $emirateExpiryClass }}">
                    {{ $employee->emirate_expiry ? $employee->emirate_expiry->format('d M Y') : '-' }}
                    {!! $emirateBadge !!}
                </p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Passport Expiry</label>
                @php
                    $passportExpiryClass = '';
                    $passportBadge = '';
                    if($employee->passport_expiry) {
                        $today = \Carbon\Carbon::today();
                        $expiry = $employee->passport_expiry;
                        $daysLeft = $today->diffInDays($expiry, false);
                        
                        if($expiry->isPast()) {
                            $passportExpiryClass = 'expired';
                            $passportBadge = '<span class="badge-expired">Expired</span>';
                        } elseif($daysLeft <= 90) {
                            $passportExpiryClass = 'expiring-soon';
                            $passportBadge = '<span class="badge-expiring">Expiring Soon ('.$daysLeft.' days)</span>';
                        } else {
                            $passportExpiryClass = 'valid';
                            $passportBadge = '<span class="badge-valid">Valid</span>';
                        }
                    }
                @endphp
                <p class="{{ $passportExpiryClass }}">
                    {{ $employee->passport_expiry ? $employee->passport_expiry->format('d M Y') : '-' }}
                    {!! $passportBadge !!}
                </p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Visa Expiry</label>
                @php
                    $visaExpiryClass = '';
                    $visaBadge = '';
                    if($employee->visa_expiry) {
                        $today = \Carbon\Carbon::today();
                        $expiry = $employee->visa_expiry;
                        $daysLeft = $today->diffInDays($expiry, false);
                        
                        if($expiry->isPast()) {
                            $visaExpiryClass = 'expired';
                            $visaBadge = '<span class="badge-expired">Expired</span>';
                        } elseif($daysLeft <= 30) {
                            $visaExpiryClass = 'expiring-soon';
                            $visaBadge = '<span class="badge-expiring">Expiring Soon ('.$daysLeft.' days)</span>';
                        } else {
                            $visaExpiryClass = 'valid';
                            $visaBadge = '<span class="badge-valid">Valid</span>';
                        }
                    }
                @endphp
                <p class="{{ $visaExpiryClass }}">
                    {{ $employee->visa_expiry ? $employee->visa_expiry->format('d M Y') : '-' }}
                    {!! $visaBadge !!}
                </p>
            </div>
        </div>
    </div>

    <!-- Edit Form for Documents -->
    <div class="card-body edit-form" id="edit-documents" style="display: none;">
        <form class="section-form" data-section="documents">
            @csrf
            <div class="row">
                <div class="col-md-3 form-group col-3">
                    <label>Emirates ID</label>
                    <input type="text" class="form-control form-control-sm" name="emirate_id" value="{{ $employee->emirate_id }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport Number</label>
                    <input type="text" class="form-control form-control-sm" name="passport" value="{{ $employee->passport }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Visa Sponsor</label>
                    <input type="text" class="form-control form-control-sm" name="visa_sponsor" value="{{ $employee->visa_sponsor }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Visa Occupation</label>
                    <input type="text" class="form-control form-control-sm" name="visa_occupation" value="{{ $employee->visa_occupation }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Emirates ID Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="emirate_expiry" value="{{ $employee->emirate_expiry ? $employee->emirate_expiry->format('Y-m-d') : '' }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Passport Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="passport_expiry" value="{{ $employee->passport_expiry ? $employee->passport_expiry->format('Y-m-d') : '' }}">
                </div>
                <div class="col-md-3 form-group col-3">
                    <label>Visa Expiry</label>
                    <input type="date" class="form-control form-control-sm" name="visa_expiry" value="{{ $employee->visa_expiry ? $employee->visa_expiry->format('Y-m-d') : '' }}">
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit" data-section="documents">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Notes Card -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-note ti-sm" style="background: #6c757d38; color: #6c757d; padding: 8px; border-radius: 50%;"></i>
                <b>Notes</b>
            </div>
            <button type="button" class="btn btn-sm btn-primary edit-btn" data-section="notes">
                <i class="ti ti-edit me-1"></i>
            </button>
        </div>
    </div>
    
    <!-- Display Section -->
    <div class="card-body display-section" id="display-notes">
        <div class="row">
            <div class="col-md-12">
                <p>{{ $employee->notes ?? 'No notes available' }}</p>
            </div>
        </div>
    </div>

    <!-- Edit Form for Notes -->
    <div class="card-body edit-form" id="edit-notes" style="display: none;">
        <form class="section-form" data-section="notes">
            @csrf
            <div class="row">
                <div class="col-md-12">
                    <label>Notes</label>
                    <textarea class="form-control form-control-sm" name="notes" rows="3">{{ $employee->notes }}</textarea>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-12">
                    <button type="submit" class="btn btn-success btn-sm">
                        <i class="ti ti-check me-1"></i>Update
                    </button>
                    <button type="button" class="btn btn-secondary btn-sm cancel-edit" data-section="notes">
                        <i class="ti ti-x me-1"></i>Cancel
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- System Information Card (Read-only, no edit) -->
<div class="card border mb-3">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <i class="ti ti-clock ti-sm" style="background: #17a2b838; color: #17a2b8; padding: 8px; border-radius: 50%;"></i>
                <b>System Information</b>
            </div>
        </div>
    </div>
    
    <!-- Display Section -->
    <div class="card-body">
        <div class="row">
            <div class="col-md-3 form-group col-3">
                <label>Created At</label>
                <p>{{ $employee->created_at ? $employee->created_at->format('d M Y H:i') : '-' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Created By</label>
                <p>{{ $employee->creator->name ?? 'System' }}</p>
            </div>
            <div class="col-md-3 form-group col-3">
                <label>Last Updated</label>
                <p>{{ $employee->updated_at ? $employee->updated_at->format('d M Y H:i') : '-' }}</p>
            </div>
            @if($employee->deleted_at)
            <div class="col-md-3 form-group col-3">
                <label>Deleted At</label>
                <p>{{ $employee->deleted_at->format('d M Y H:i') }}</p>
            </div>
            @endif
        </div>
    </div>
</div>

@endsection
@push('third_party_scripts')
<script>
    $(document).ready(function() {
        // Initialize Select2 for all select elements
        function initializeSelect2() {
            $('.select2').select2({
                theme: 'bootstrap4',
                width: '100%',
                placeholder: 'Select an option'
            });
        }

        // Handle edit button clicks
        $('.edit-btn').click(function() {
            const section = $(this).data('section');
            const editForm = $('#edit-' + section);
            const displaySection = $('#display-' + section);
            const card = $(this).closest('.card');
            
            // Toggle between display and edit
            if (displaySection.is(':visible')) {
                // Switch to edit mode
                displaySection.slideUp(200, function() {
                    editForm.slideDown(200);
                });
                $(this).html('<i class="ti ti-x me-1"></i>').removeClass('btn-primary').addClass('btn-secondary');
                
                // Initialize Select2 for this section's dropdowns
                editForm.find('.select2').select2({
                    width: '100%',
                    placeholder: 'Select an option'
                });
            } else {
                // Switch to display mode
                editForm.slideUp(200, function() {
                    displaySection.slideDown(200);
                });
                $(this).html('<i class="ti ti-edit me-1"></i>').removeClass('btn-secondary').addClass('btn-primary');
            }
        });

        // Handle cancel button clicks
        $(document).on('click', '.cancel-edit', function() {
            const section = $(this).data('section');
            const editForm = $('#edit-' + section);
            const displaySection = $('#display-' + section);
            const editBtn = $(this).closest('.card').find('.edit-btn');
            
            // Switch back to display mode
            editForm.slideUp(200, function() {
                displaySection.slideDown(200);
            });
            editBtn.html('<i class="ti ti-edit me-1"></i>').removeClass('btn-secondary').addClass('btn-primary');
        });

        // Handle form submissions
        $('.section-form').submit(function(e) {
            e.preventDefault();

            const form = $(this);
            const section = form.data('section');
            const formData = new FormData(this);
            formData.append('section', section);

            // Add loading state
            const submitBtn = form.find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Updating...').prop('disabled', true);

            $.ajax({
                url: '{{ route("employees.updateSection", $employee->id) }}',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        toastr.success(response.message);

                        // Reload the page to show updated data
                        location.reload();
                    } else {
                        toastr.error('Error updating ' + section + ' information');
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        const errors = xhr.responseJSON.errors;
                        let errorMessage = 'Validation errors:\n';
                        Object.keys(errors).forEach(function(key) {
                            errorMessage += errors[key][0] + '\n';
                        });
                        toastr.error(errorMessage);
                    } else {
                        toastr.error('Error updating ' + section + ' information');
                    }
                },
                complete: function() {
                    // Reset button state
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });
    });
</script>
@endpush