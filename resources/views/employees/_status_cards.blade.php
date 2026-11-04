@php
use Illuminate\Support\Facades\Schema;
$currentStatus = (string) ($employee->status ?? 'active');
$employeeTopViewCategories = $employeeTopViewCategories ?? \App\Services\Permissions\TopBarPermissionSync::filterCategories(
'employees',
\App\Models\EmployeeTopCategory::with(['options' => function ($q) {
$q->where('is_active', 1)->orderBy('display_order')->orderBy('id');
}])->where('show_in_view_cards', 1)->orderBy('display_order')->orderBy('id')->get()
)->map(function ($category) {
$category->setRelation(
'options',
\App\Services\Permissions\TopBarOptionPermissionSync::filterOptions('employees', $category->options)
);
return $category;
})->filter(fn ($cat) => $cat->options->isNotEmpty())->values();
$cardIndex = 0;
$statusIcons = ['ti ti-bell', 'ti ti-user-check', 'ti ti-star', 'ti ti-flag', 'ti ti-shield-check', 'ti ti-briefcase'];
@endphp
<div id="employee-status-cards" data-employee-id="{{ $employee->id }}">
  <div class="employee-status-group">
    <div class="employee-status-group-label">Employment Status</div>
    <div class="employee-status-group-grid">
      <div class="status-card {{ $currentStatus === 'active' ? 'active' : '' }}" id="employee-status-active-card">
        <div class="status-card-main">
          <div class="status-icon"><i class="ti ti-user-check"></i></div>
          <div class="status-content">
            <div class="status-title">Active</div>
            <div class="status-subtitle">{{ $currentStatus === 'active' ? 'Assigned' : 'Tap to set' }}</div>
          </div>
          <div class="status-toggle">
            <input type="radio" name="employee_status_toggle" class="status-radio status-radio-active" id="employee-status-active" value="active" {{ $currentStatus === 'active' ? 'checked' : '' }}>
            <label for="employee-status-active" class="toggle-switch"><span class="toggle-slider"></span></label>
          </div>
        </div>
      </div>
      <div class="status-card {{ $currentStatus === 'on_leave' ? 'active' : '' }}" id="employee-status-leave-card">
        <div class="status-card-main">
          <div class="status-icon"><i class="ti ti-calendar-off"></i></div>
          <div class="status-content">
            <div class="status-title">On Leave</div>
            <div class="status-subtitle">{{ $currentStatus === 'on_leave' ? 'Assigned' : 'Tap to set' }}</div>
          </div>
          <div class="status-toggle">
            <input type="radio" name="employee_status_toggle" class="status-radio status-radio-leave" id="employee-status-leave" value="on_leave" {{ $currentStatus === 'on_leave' ? 'checked' : '' }}>
            <label for="employee-status-leave" class="toggle-switch"><span class="toggle-slider"></span></label>
          </div>
        </div>
      </div>
      <div class="status-card {{ $currentStatus === 'inactive' ? 'active' : '' }}" id="employee-status-inactive-card">
        <div class="status-card-main">
          <div class="status-icon"><i class="ti ti-user-x"></i></div>
          <div class="status-content">
            <div class="status-title">Inactive</div>
            <div class="status-subtitle">{{ $currentStatus === 'inactive' ? 'Assigned' : 'Tap to set' }}</div>
          </div>
          <div class="status-toggle">
            <input type="radio" name="employee_status_toggle" class="status-radio status-radio-inactive" id="employee-status-inactive" value="inactive" {{ $currentStatus === 'inactive' ? 'checked' : '' }}>
            <label for="employee-status-inactive" class="toggle-switch"><span class="toggle-slider"></span></label>
          </div>
        </div>
      </div>
    </div>
  </div>

  @foreach($employeeTopViewCategories as $category)
  @if(($category->employee_column ?? '') === 'status')
  @continue
  @endif
  <div class="employee-status-group">
    <div class="employee-status-group-label">{{ $category->name }}</div>
    <div class="employee-status-group-grid">
      @foreach($category->options as $option)
      @php
      $col = $category->employee_column;
      $isSelected = $col && Schema::hasColumn('employees', $col) && (string) data_get($employee, $col) === (string) $option->name;
      @endphp
      <div class="status-card employee-top-option-card {{ $isSelected ? 'active' : '' }}"
        data-column="{{ $col }}"
        data-value="{{ $option->name }}"
        data-category="{{ $category->name }}">
        <div class="status-card-main">
          <div class="status-icon"><i class="{{ $statusIcons[$cardIndex % count($statusIcons)] }}"></i></div>
          <div class="status-content">
            <div class="status-title">{{ $option->name }}</div>
            <div class="status-subtitle">{{ $isSelected ? 'Assigned' : 'Tap to set' }}</div>
          </div>
          <div class="status-toggle">
            <input type="checkbox" class="employee-top-option-checkbox" id="employee-top-{{ $category->id }}-{{ $option->id }}"
              data-column="{{ $col }}" data-value="{{ $option->name }}" {{ $isSelected ? 'checked' : '' }}>
            <label for="employee-top-{{ $category->id }}-{{ $option->id }}" class="toggle-switch"><span class="toggle-slider"></span></label>
          </div>
        </div>
      </div>
      @php $cardIndex++; @endphp
      @endforeach
    </div>
  </div>
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
