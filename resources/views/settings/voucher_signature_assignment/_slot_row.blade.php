@php
  $i = $index;
  $roleLabel = old("slots.$i.role_label", $slot->role_label ?? 'Prepared By');
  $assigneeType = old("slots.$i.assignee_type", $slot->assignee_type ?? 'label_only');
  $departmentId = old("slots.$i.department_id", $slot->department_id ?? '');
  $userId = old("slots.$i.user_id", $slot->user_id ?? '');
  $employeeId = old("slots.$i.employee_id", $slot->employee_id ?? '');
  $designation = old("slots.$i.designation_override", $slot->designation_override ?? '');
  $isActive = old("slots.$i.is_active", isset($slot) ? (bool) $slot->is_active : true);
  $disabled = empty($canEdit) ? 'disabled' : '';
@endphp
<div class="vsa-slot" data-slot-row>
  <div class="d-flex align-items-start gap-2 mb-2">
    @if(!empty($canEdit))
    <span class="drag-handle mt-1" title="Drag to reorder"><i class="ti ti-grip-vertical ti-sm"></i></span>
    @endif
    <span class="badge bg-label-primary vsa-slot-index mt-1">#{{ is_numeric($i) ? ((int) $i + 1) : '—' }}</span>
    <div class="flex-grow-1">
      <div class="row g-2">
        <div class="col-md-4">
          <label class="form-label">Role label</label>
          <input type="text" class="form-control" name="slots[{{ $i }}][role_label]"
                 value="{{ $roleLabel }}" maxlength="100" required {{ $disabled }}>
        </div>
        <div class="col-md-3">
          <label class="form-label">Assignee type</label>
          <select class="form-select vsa-assignee-type" name="slots[{{ $i }}][assignee_type]" {{ $disabled }}>
            @foreach($assigneeTypes as $value => $label)
              <option value="{{ $value }}" @selected($assigneeType === $value)>{{ $label }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 {{ $assigneeType === 'department' ? '' : 'd-none' }}" data-assignee="department">
          <label class="form-label">Department</label>
          <select class="form-select" name="slots[{{ $i }}][department_id]" {{ $disabled }}>
            <option value="">— Select —</option>
            @foreach($departments as $dept)
              <option value="{{ $dept->id }}" @selected((string) $departmentId === (string) $dept->id)>{{ $dept->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 {{ $assigneeType === 'user' ? '' : 'd-none' }}" data-assignee="user">
          <label class="form-label">User</label>
          <select class="form-select" name="slots[{{ $i }}][user_id]" {{ $disabled }}>
            <option value="">— Select —</option>
            @foreach($users as $user)
              @php
                $uname = trim((string) ($user->name ?: trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''))));
              @endphp
              <option value="{{ $user->id }}" @selected((string) $userId === (string) $user->id)>{{ $uname ?: ('User #'.$user->id) }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3 {{ $assigneeType === 'employee' ? '' : 'd-none' }}" data-assignee="employee">
          <label class="form-label">Employee</label>
          <select class="form-select" name="slots[{{ $i }}][employee_id]" {{ $disabled }}>
            <option value="">— Select —</option>
            @foreach($employees as $emp)
              <option value="{{ $emp->id }}" @selected((string) $employeeId === (string) $emp->id)>
                {{ $emp->name }}{{ $emp->designation ? ' — '.$emp->designation : '' }}
              </option>
            @endforeach
          </select>
        </div>
        <div class="col-md-3">
          <label class="form-label">Designation override</label>
          <input type="text" class="form-control" name="slots[{{ $i }}][designation_override]"
                 value="{{ $designation }}" maxlength="255" placeholder="Optional" {{ $disabled }}>
        </div>
        <div class="col-md-2 d-flex align-items-end">
          <div class="form-check mb-2">
            <input type="hidden" name="slots[{{ $i }}][is_active]" value="0">
            <input class="form-check-input" type="checkbox" name="slots[{{ $i }}][is_active]" value="1"
                   id="vsa-active-{{ $i }}" @checked($isActive) {{ $disabled }}>
            <label class="form-check-label" for="vsa-active-{{ $i }}">Active</label>
          </div>
        </div>
      </div>
    </div>
    @if(!empty($canEdit))
    <button type="button" class="btn btn-sm btn-icon btn-outline-danger vsa-remove-slot" title="Remove">
      <i class="ti ti-trash"></i>
    </button>
    @endif
  </div>
</div>
