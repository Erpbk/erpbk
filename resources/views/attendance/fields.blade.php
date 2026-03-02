<!-- User Type Selection -->
    <div class="row mb-4">
        <div class="col-md-6">
            <label for="ref_type" class="form-label fw-bold">
                User Type <span class="text-danger">*</span>
            </label>
            <div class="btn-group w-100" role="group">
                <input type="radio" class="btn-check" name="ref_type" id="type_employee" 
                        value="employee" {{ old('ref_type') == 'employee' ? 'checked' : '' }} 
                        autocomplete="off" required>
                <label class="btn btn-outline-primary" for="type_employee">
                    <i class="fas fa-user-tie me-2"></i>Employee
                </label>

                <input type="radio" class="btn-check" name="ref_type" id="type_rider" 
                        value="rider" {{ old('ref_type') == 'rider' ? 'checked' : '' }} 
                        autocomplete="off" required>
                <label class="btn btn-outline-primary" for="type_rider">
                    <i class="fas fa-motorcycle me-2"></i>Rider
                </label>
            </div>
            @error('ref_type')
                <div class="text-danger small mt-1">{{ $message }}</div>
            @enderror
        </div>

        <!-- User Selection -->
        <div class="col-md-6">
            <label for="ref_id" class="form-label fw-bold required">
                Select User
            </label>
            <select class="form-select @error('ref_id') is-invalid @enderror select2" 
                    id="form_ref_id" name="ref_id" required>
                <option value="">-- Select user type first --</option>
            </select>
            @error('ref_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Date Selection -->
    <div class="row mb-3">
        <div class="col-md-6">
            <label for="date" class="form-label fw-bold">
                Date <span class="text-danger">*</span>
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-calendar-alt"></i>
                </span>
                <input type="date" class="form-control" 
                        id="date" name="date" value="{{ old('date', date('Y-m-d')) }}" required>
            </div>
        </div>

        <div class="col-md-6">
            <label for="status" class="form-label fw-bold required">
                Status
            </label>
            <select class="form-select select2" 
                    id="status" name="status" required>
                <option value="">-- Select Status --</option>
                <option value="present" {{ old('status') == 'present' ? 'selected' : '' }}>Present</option>
                <option value="absent" {{ old('status') == 'absent' ? 'selected' : '' }}>Absent</option>
                <option value="late" {{ old('status') == 'late' ? 'selected' : '' }}>Late</option>
                <option value="half day" {{ old('status') == 'half-day' ? 'selected' : '' }}>Half Day</option>
                <option value="holiday" {{ old('status') == 'holiday' ? 'selected' : '' }}>Holiday</option>
            </select>
        </div>
    </div>

    <!-- Time Section -->
    <div class="row">
        <div class="col-md-6">
            <label for="check_in" class="form-label">
                Check In Time
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-sign-in-alt text-success"></i>
                </span>
                <input type="time" class="form-control" 
                        id="check_in" name="check_in" value="{{ old('check_in') }}" step="1">
            </div>
            <small class="text-muted">Format: HH:MM:SS</small>
        </div>

        <div class="col-md-6">
            <label for="check_out" class="form-label">
                Check Out Time
            </label>
            <div class="input-group">
                <span class="input-group-text">
                    <i class="fas fa-sign-out-alt text-danger"></i>
                </span>
                <input type="time" class="form-control" 
                        id="check_out" name="check_out" value="{{ old('check_out') }}" step="1">
            </div>
            <small class="text-muted">Format: HH:MM:SS</small>
        </div>
    </div>

    <!-- Quick Time Buttons -->
    <div class="row mt-3">
        <div class="col-12">
            <label class="form-label small text-muted">Quick Actions:</label>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-sm btn-outline-success" onclick="setCurrentTime('check_in')">
                    <i class="fas fa-clock"></i><span class="px-2"> Now as Check In</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="setCurrentTime('check_out')">
                    <i class="fas fa-clock"></i><span class="px-2"> Now as Check Out</span>
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setWorkingHours()">
                    <i class="fas fa-calculator"></i><span class="px-2"> Set 10am - 6pm</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Notes Section -->
    <div class="mb-4">
        <label for="notes" class="form-label fw-bold">
            <i class="fas fa-sticky-note me-2"></i>Notes / Remarks
        </label>
        <textarea class="form-control" 
            id="notes" name="notes" rows="3" 
            placeholder="Enter any additional notes or remarks...">{{ old('notes') }}
        </textarea>
    </div>