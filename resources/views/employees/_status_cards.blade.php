@php
use Illuminate\Support\Facades\Schema;
$currentStatus = (string) ($employee->status ?? 'active');
$employeeTopViewCategories = $employeeTopViewCategories ?? \App\Models\EmployeeTopCategory::with(['options' => function ($q) {
    $q->where('is_active', 1)->orderBy('display_order')->orderBy('id');
}])->where('show_in_view_cards', 1)->orderBy('display_order')->orderBy('id')->get();
$cardIndex = 0;
$icons = ['ti ti-bell', 'ti ti-star', 'ti ti-flag', 'ti ti-briefcase'];
@endphp
<div class="d-flex flex-wrap justify-content-start gap-2 gap-md-3 mt-3" id="employee-status-cards" data-employee-id="{{ $employee->id }}">
  <div class="status-card {{ $currentStatus === 'active' ? 'active-success' : '' }}" id="employee-status-active-card">
    <div class="d-flex justify-content-between align-items-start">
      <div class="status-icon"><i class="ti ti-user-check"></i></div>
      <div class="status-content">
        <div class="status-title">Active</div>
        <div class="status-subtitle">{{ $currentStatus === 'active' ? 'Current status' : 'Set as active' }}</div>
      </div>
    </div>
    <div class="status-toggle mt-2">
      <input type="radio" name="employee_status_toggle" class="status-radio status-radio-active" id="employee-status-active" value="active" {{ $currentStatus === 'active' ? 'checked' : '' }}>
      <label for="employee-status-active" class="toggle-switch"></label>
    </div>
  </div>
  <div class="status-card {{ $currentStatus === 'on_leave' ? 'active-info' : '' }}" id="employee-status-leave-card">
    <div class="d-flex justify-content-between align-items-start">
      <div class="status-icon"><i class="ti ti-calendar-off"></i></div>
      <div class="status-content">
        <div class="status-title">On Leave</div>
        <div class="status-subtitle">{{ $currentStatus === 'on_leave' ? 'Current status' : 'Set as on leave' }}</div>
      </div>
    </div>
    <div class="status-toggle mt-2">
      <input type="radio" name="employee_status_toggle" class="status-radio status-radio-leave" id="employee-status-leave" value="on_leave" {{ $currentStatus === 'on_leave' ? 'checked' : '' }}>
      <label for="employee-status-leave" class="toggle-switch"></label>
    </div>
  </div>
  <div class="status-card {{ $currentStatus === 'inactive' ? 'active-danger' : '' }}" id="employee-status-inactive-card">
    <div class="d-flex justify-content-between align-items-start">
      <div class="status-icon"><i class="ti ti-user-x"></i></div>
      <div class="status-content">
        <div class="status-title">Inactive</div>
        <div class="status-subtitle">{{ $currentStatus === 'inactive' ? 'Current status' : 'Set as inactive' }}</div>
      </div>
    </div>
    <div class="status-toggle mt-2">
      <input type="radio" name="employee_status_toggle" class="status-radio status-radio-inactive" id="employee-status-inactive" value="inactive" {{ $currentStatus === 'inactive' ? 'checked' : '' }}>
      <label for="employee-status-inactive" class="toggle-switch"></label>
    </div>
  </div>
  @foreach($employeeTopViewCategories as $category)
    @if(($category->employee_column ?? '') === 'status')
      @continue
    @endif
    @foreach($category->options as $option)
      @php
        $col = $category->employee_column;
        $isSelected = $col && Schema::hasColumn('employees', $col) && (string) data_get($employee, $col) === (string) $option->name;
      @endphp
      <div class="status-card {{ $isSelected ? 'active-success' : '' }} employee-top-option-card" data-column="{{ $col }}" data-value="{{ $option->name }}" data-category="{{ $category->name }}">
        <div class="d-flex justify-content-between align-items-start">
          <div class="status-icon"><i class="{{ $icons[$cardIndex % count($icons)] }}"></i></div>
          <div class="status-content">
            <div class="status-title">{{ $option->name }}</div>
            <div class="status-subtitle">{{ $category->name }}</div>
          </div>
        </div>
        <div class="status-toggle mt-2">
          <input type="checkbox" class="employee-top-option-checkbox" id="employee-top-{{ $category->id }}-{{ $option->id }}"
            data-column="{{ $col }}" data-value="{{ $option->name }}" {{ $isSelected ? 'checked' : '' }}>
          <label for="employee-top-{{ $category->id }}-{{ $option->id }}" class="toggle-switch"></label>
        </div>
      </div>
      @php $cardIndex++; @endphp
    @endforeach
  @endforeach
</div>

<div class="modal fade" id="employeeTopOptionDateModal" tabindex="-1" aria-labelledby="employeeTopOptionDateModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="employeeTopOptionDateModalLabel">Confirm change</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p class="mb-3">Choose the effective date for <strong id="employeeTopOptionModalStatusName">—</strong>. Dates after today are not allowed.</p>
        <label for="employeeTopOptionEffectiveDate" class="form-label">Effective date <span class="text-danger">*</span></label>
        <input type="date" class="form-control" id="employeeTopOptionEffectiveDate" required autocomplete="off">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="button" class="btn btn-primary" id="employeeTopOptionDateSave">Save</button>
      </div>
    </div>
  </div>
</div>
