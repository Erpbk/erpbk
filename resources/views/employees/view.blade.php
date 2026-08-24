@extends('layouts.app', ['hideModuleTopBarSlider' => true])

@section('title', 'Employee Profile')

@section('content')
<style>
  .myform .required:after {
    content: " *";
    color: red;
    font-weight: 200;
  }

  @media print {
    body .content {
      font-size: 18px !important;
    }
  }

  /* Status Cards Styling */
  .status-card {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    border: 2px solid #dee2e6;
    border-radius: 12px;
    padding: 16px;
    min-width: 180px;
    flex: 1;
    max-width: 220px;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
  }

  .status-card.active-success {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: #28a745;
    color: white;
  }

  .status-card.active-info {
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    border-color: #17a2b8;
    color: white;
  }

  .status-card.active-danger {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    border-color: #dc3545;
    color: white;
  }

  .status-card .status-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    font-size: 20px;
  }

  .status-card.active-success .status-icon,
  .status-card.active-info .status-icon,
  .status-card.active-danger .status-icon {
    background: rgba(255, 255, 255, 0.3);
  }

  .status-card .status-content {
    flex: 1;
  }

  .status-card .status-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #495057;
  }

  .status-card.active-success .status-title,
  .status-card.active-info .status-title,
  .status-card.active-danger .status-title {
    color: white;
  }

  .status-card .status-subtitle {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
  }

  .status-card.active-success .status-subtitle,
  .status-card.active-info .status-subtitle,
  .status-card.active-danger .status-subtitle {
    color: rgba(255, 255, 255, 0.9);
  }

  .status-options {
    margin-top: 12px;
    padding-top: 12px;
    border-top: 1px solid rgba(0, 0, 0, 0.1);
  }

  .status-option {
    padding: 4px 0;
  }

  .status-option span {
    font-size: 13px;
    font-weight: 500;
  }

  .status-card.active-success .status-option span,
  .status-card.active-info .status-option span,
  .status-card.active-danger .status-option span {
    color: white;
  }

  .status-toggle {
    display: flex;
    align-items: center;
  }

  .status-radio {
    display: none;
  }

  .toggle-switch {
    position: relative;
    width: 40px;
    height: 20px;
    background: #ccc;
    border-radius: 10px;
    cursor: pointer;
    transition: background 0.3s ease;
    display: inline-block;
  }

  .toggle-switch::after {
    content: '';
    position: absolute;
    top: 2px;
    left: 2px;
    width: 16px;
    height: 16px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }

  .status-radio:checked+.toggle-switch::after {
    transform: translateX(20px);
  }

  .status-radio-active:checked+.toggle-switch {
    background: #28a745;
  }

  .status-radio-leave:checked+.toggle-switch {
    background: #17a2b8;
  }

  .status-radio-inactive:checked+.toggle-switch {
    background: #dc3545;
  }

  .status-card.loading {
    opacity: 0.7;
    pointer-events: none;
  }

  .status-card.loading .toggle-switch {
    animation: pulse 1s infinite;
  }

  @keyframes pulse {
    0% {
      opacity: 1;
    }

    50% {
      opacity: 0.5;
    }

    100% {
      opacity: 1;
    }
  }

  /* Responsive design */
  @media (max-width: 768px) {
    .status-card {
      min-width: 150px;
      max-width: 180px;
      padding: 12px;
      flex: 1;
    }

    .status-title {
      font-size: 14px;
    }

    .status-subtitle {
      font-size: 11px;
    }
  }

  @media (max-width: 576px) {
    .status-card {
      min-width: 140px;
      max-width: 160px;
      padding: 10px;
    }

    .status-title {
      font-size: 13px;
    }

    .status-subtitle {
      font-size: 10px;
    }

    .status-icon {
      width: 35px;
      height: 35px;
      font-size: 18px;
    }
  }

  .info-container .user_list {
    align-items: flex-start;
  }

  .info-container .user_list .icons {
    flex-shrink: 0;
  }

  .info-container .user_list_content {
    flex: 1;
    min-width: 0;
    overflow-wrap: anywhere;
    word-break: break-word;
  }

  .info-container .user_list_content b {
    display: block;
    float: none;
    margin-top: 2px;
  }
</style>
@include('partials.entity_profile_styles')

@php
$employee = $employee ?? null;
if ($employee && !isset($result)) {
$result = $employee->toArray();
}
// Ledger-linked account for balance display (create form has no employee yet)
$account = null;
if (!request()->routeIs('employees.create') && isset($employee) && !empty($employee->account_id)) {
$account = App\Models\Accounts::find($employee->account_id);
}
$employeeTopViewCategories = collect();
if (isset($employee)) {
$employeeTopViewCategories = \App\Services\Permissions\TopBarPermissionSync::filterCategories(
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
}
$currentStatus = isset($employee) ? (string) ($employee->status ?? 'active') : 'active';
$employeeStatusLabel = match ($currentStatus) {
  'on_leave' => 'On Leave',
  'inactive' => 'Inactive',
  default => isset($employee) ? 'Active' : 'New',
};
$employeeStatusClass = $currentStatus === 'on_leave' ? 'is-vacation' : ($currentStatus === 'inactive' || !isset($employee) ? 'is-inactive' : '');
$employeeExpiry = isset($employee)
  ? \App\Support\EntityExpiry::countsFor('employee', $employee)
  : ['info_expired' => 0, 'info_expiring' => 0, 'files_expired' => 0, 'files_expiring' => 0];
if (isset($employee)) {
  $profile = company_table('files')
    ->where('type', 'employee')
    ->where('type_id', $employee->id)
    ->where(function ($query) {
      $query->where('name', 'LIKE', '%photo%')
        ->orWhere('name', 'LIKE', '%Photo%')
        ->orWhere('name', 'LIKE', '%picture%')
        ->orWhere('name', 'LIKE', '%Picture%')
        ->orWhere('name', 'LIKE', '%profile%')
        ->orWhere('name', 'LIKE', '%Profile%');
    })
    ->first();
  if ($employee->profile_image) {
    $image_name = $employee->profile_image_url;
  } elseif (isset($profile)) {
    $image_name = storage_url($profile->type . '/' . $profile->type_id . '/' . $profile->file_name);
  } else {
    $image_name = asset('uploads/default.png');
  }
} else {
  $image_name = asset('uploads/default.png');
}
$whatsappHtml = 'N/A';
if ($employee?->company_contact) {
  $phone = preg_replace('/[^0-9]/', '', $employee->company_contact);
  $whatsappNumber = '+971' . ltrim($phone, '0');
  $whatsappHtml = '<a href="https://wa.me/' . $whatsappNumber . '" target="_blank" class="text-success">' . e($employee->company_contact) . '</a>';
}
@endphp

<div class="row">
  <div class="col-xl-3 col-md-5 col-lg-5 order-1 order-md-0">
    <x-entity-profile-card
      icon="ti ti-user"
      :photo="$image_name"
      :is-active="$currentStatus === 'active' && isset($employee)"
      :status-label="$employeeStatusLabel"
      :status-class="$employeeStatusClass"
      status-id="employee-status-value-badge"
      name-id="employee-profile-name"
      :name="$employee?->name ?? 'New Employee'"
      :subtitle="$employee?->employee_id ?? ($empId ?? 'not-set')"
      edit-title="Edit Employee Details"
    >
      <x-slot name="photoAction">
        @if(isset($employee))
        <button type="button" class="entity-view-card-camera" id="employee-photo-edit-btn" title="Change photo" aria-label="Change photo">
          <i class="ti ti-camera"></i>
        </button>
        @endif
      </x-slot>
      <x-slot name="meta">
        <span class="badge bg-label-primary mt-2" id="employee-designation-badge">{{ $employee?->designation ?? 'Not Set' }}</span>
      </x-slot>
      <x-slot name="afterHero">
        @if(isset($employee))
        <div id="employee-photo-upload-form" class="mt-2 text-center" style="display: none;">
          <form action="{{ route('employees.updateSection', $employee->id) }}" method="POST" enctype="multipart/form-data" id="employee-photo-form">
            @csrf
            <div class="button-wrapper">
              <label for="employee-profile-image-upload" class="btn btn-sm btn-outline-primary me-2 mb-2" tabindex="0">
                <span class="d-none d-sm-inline">Change Photo</span>
                <i class="ti ti-upload d-sm-none"></i>
                <input type="file" id="employee-profile-image-upload" name="profile_image" class="account-file-input" hidden accept="image/png, image/jpeg" />
              </label>
              <input type="hidden" name="section" value="photo">
              <button type="submit" class="btn btn-sm btn-primary mb-2">Upload</button>
            </div>
          </form>
        </div>
        @endif
      </x-slot>
      <x-entity-profile-info-row icon="ti ti-mail" label="Company Email" :value="$employee?->company_email" field-key="company_email" />
      <x-entity-profile-info-row icon="ti ti-phone" label="WhatsApp" :value="$whatsappHtml" :html="true" field-key="company_contact_html" />
      <x-entity-profile-info-row icon="ti ti-flag" label="Nationality" :value="$employee?->nationality?->name" field-key="nationality" />
      <x-entity-profile-info-row icon="ti ti-cake" label="Age" :value="$employee?->dob ? \Carbon\Carbon::parse($employee->dob)->age : null" field-key="age" />
      <x-entity-profile-info-row icon="ti ti-calendar-due" label="Date of Joining" :value="$employee?->doj ? \Carbon\Carbon::parse($employee->doj)->format('d M Y') : null" field-key="doj" />
      <x-entity-profile-info-row icon="ti ti-cash-banknote" label="Balance" :value="(isset($account) && $account) ? (App\Helpers\Accounts::getBalance($account->id) . ' ' . \App\Helpers\Currency::code()) : ('0.00 ' . \App\Helpers\Currency::code())" />
      <x-entity-profile-info-row icon="ti ti-cash" label="Salary" :value="number_format($employee?->salary ?? 0, 2) . ' ' . \App\Helpers\Currency::code()" field-key="salary" />
      <x-entity-profile-info-row icon="ti ti-id" label="Emirates ID" :value="$employee?->emirate_id" field-key="emirate_id" />
      <x-entity-profile-info-row icon="ti ti-building" label="Department" :value="$employee?->department?->name ?? $employee?->department_id" field-key="department" />
      <x-entity-profile-info-row icon="ti ti-briefcase" label="Branch" :value="$employee?->branch?->name" field-key="branch" />
      <x-slot name="footer">
        @if(isset($employee))
        @include('employees._status_cards', ['employee' => $employee, 'employeeTopViewCategories' => $employeeTopViewCategories])
        @endif
      </x-slot>
    </x-entity-profile-card>
  </div>

  <div class="col-xl-9 col-md-7 col-lg-7 order-0 order-md-1 position-relative">
    <x-entity-profile-tabs>
      <li class="nav-item nav-priority-1">
        <a class="nav-link @if(request()->routeIs('employees.show') || request()->routeIs('employees.edit') || request()->routeIs('employees.create')) active @endif"
          @if(isset($employee)) href="{{ route('employees.show', $employee->id) }}" @else href="javascript:void(0);" @endif>
          <i class="ti ti-user-check ti-sm me-1_5"></i>Information
          @include('riders._document_status_badges', ['expiredCount' => $employeeExpiry['info_expired'], 'expiringCount' => $employeeExpiry['info_expiring']])
        </a>
      </li>
      @if(isset($employee))
      @can('employees_document_view')
      <li class="nav-item nav-priority-2">
        <a class="nav-link @if(request()->segment(5) == 'files') active @endif"
          href="{{ route('employee.files', $employee->id) }}">
          <i class="ti ti-file-upload ti-sm me-1_5"></i>Files
          @include('riders._document_status_badges', ['expiredCount' => $employeeExpiry['files_expired'], 'expiringCount' => $employeeExpiry['files_expiring']])
        </a>
      </li>
      @endcan
      @can('employees_ledger_view')
      <li class="nav-item nav-priority-3">
        <a class="nav-link @if(request()->routeIs('employee.ledger')) active @endif"
          href="{{ route('employee.ledger', $employee->id) }}">
          <i class="ti ti-file-stack ti-sm me-1_5"></i>Ledger
        </a>
      </li>
      @endcan
      <li class="nav-item nav-priority-4">
        <a class="nav-link @if(request()->segment(5) == 'salary') active @endif"
          href="{{ route('employee.salary', $employee->id) }}">
          <i class="ti ti-cash-banknote ti-sm me-1"></i>Salary
        </a>
      </li>
      @can('employees_attendance_view')
      <li class="nav-item nav-priority-5">
        <a class="nav-link @if(request()->segment(5) == 'attendance') active @endif"
          href="{{ route('employee.attendance', $employee->id) }}">
          <i class="ti ti-activity-heartbeat ti-sm me-1_5"></i>Activities
        </a>
      </li>
      @endcan
      <li class="nav-item nav-priority-7">
        <a class="nav-link @if(request()->routeIs('employee.history')) active @endif"
          href="{{ route('employee.history', $employee->id) }}">
          <i class="ti ti-history ti-sm me-1_5"></i>Employee history
        </a>
      </li>
      @can('employees_voucher_view')
      <li class="nav-item nav-priority-8 nav-action-item">
        <a href="javascript:void(0);"
          data-action="{{ route('employees.voucher', $employee->id) }}"
          data-size="xl" data-title="Voucher"
          class='nav-link show-modal'>
          <i class="ti ti-file-invoice ti-sm me-1_5"></i>Voucher
        </a>
      </li>
      @endcan
      @endif
    </x-entity-profile-tabs>

    <div class="@if(request()->routeIs('employees.create') || request()->routeIs('employees.edit')) card @else card entity-info-section @endif mb-5" id="cardBody" style="margin-top: 12px; position: relative;">
      @yield('page_content')
      @yield('page-content')
    </div>
  </div>
</div>
@include('partials.entity_profile_nav_script')
@include('employees.action-buttons')

@endsection

@section('page-script')
<script>
  $(document).ready(function() {
    window.loadEmployeeProfileImagePreview = function(event) {
      var image = document.getElementById("output");
      if (image && event.target.files && event.target.files[0]) {
        image.src = URL.createObjectURL(event.target.files[0]);
      }
    };
    function refreshEmployeeSidebar(emp) {
      if (!emp) return;
      const nameEl = document.getElementById('employee-profile-name');
      if (nameEl && emp.name) nameEl.textContent = emp.name;
      if (emp.designation !== undefined) {
        const designationBadge = document.getElementById('employee-designation-badge');
        if (designationBadge) designationBadge.textContent = emp.designation || 'Not Set';
      }
      if (emp.status && typeof window.syncEmployeeStatusCards === 'function') {
        window.syncEmployeeStatusCards(emp.status);
      }
      document.querySelectorAll('[data-employee-field]').forEach((el) => {
        const key = el.getAttribute('data-employee-field');
        if (!key || emp[key] === undefined || emp[key] === null) return;
        if (key === 'company_contact_html') {
          el.innerHTML = emp[key] || 'N/A';
          return;
        }
        el.textContent = emp[key] === '' ? 'not-set' : emp[key];
      });
    }
    window.refreshEmployeeSidebar = refreshEmployeeSidebar;

    function employeePhotoNotify(message, type) {
      if (typeof showNotification === 'function') {
        showNotification(message, type);
      } else if (window.toastr) {
        toastr[type === 'success' ? 'success' : 'error'](message);
      } else {
        alert(message);
      }
    }

    const employeePhotoEditBtn = document.getElementById('employee-photo-edit-btn');
    const employeePhotoUploadPanel = document.getElementById('employee-photo-upload-form');
    if (employeePhotoEditBtn && employeePhotoUploadPanel) {
      employeePhotoEditBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const isHidden = employeePhotoUploadPanel.style.display === 'none' ||
          window.getComputedStyle(employeePhotoUploadPanel).display === 'none';
        employeePhotoUploadPanel.style.display = isHidden ? 'block' : 'none';
      });
    }

    const employeeProfileImageInput = document.getElementById('employee-profile-image-upload');
    if (employeeProfileImageInput) {
      employeeProfileImageInput.addEventListener('change', function(e) {
        if (typeof window.loadEmployeeProfileImagePreview === 'function') {
          window.loadEmployeeProfileImagePreview(e);
        }
      });
    }

    const employeePhotoForm = document.getElementById('employee-photo-form');
    if (employeePhotoForm) {
      employeePhotoForm.addEventListener('submit', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const fileInput = this.querySelector('input[name="profile_image"]');
        if (!fileInput || !fileInput.files || !fileInput.files.length) {
          employeePhotoNotify('Please choose a photo before uploading.', 'error');
          return;
        }

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn) submitBtn.disabled = true;

        fetch(this.action, {
            method: 'POST',
            headers: {
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
          })
          .then((r) => r.json().then((data) => ({
            ok: r.ok,
            data
          })))
          .then(({
            ok,
            data
          }) => {
            if (ok && data.success) {
              if (data.image_url) {
                const img = document.getElementById('output');
                if (img) img.src = data.image_url;
              }
              employeePhotoNotify(data.message || 'Photo updated', 'success');
              if (employeePhotoUploadPanel) employeePhotoUploadPanel.style.display = 'none';
              if (fileInput) fileInput.value = '';
            } else {
              let msg = data.message || 'Failed to upload photo';
              if (data.errors) {
                const flat = Object.values(data.errors).flat();
                if (flat.length) msg = flat.join(' ');
              }
              employeePhotoNotify(msg, 'error');
            }
          })
          .catch(() => employeePhotoNotify('Failed to upload photo', 'error'))
          .finally(() => {
            if (submitBtn) submitBtn.disabled = false;
          });
      });
    }

    const employeeStatusCards = document.getElementById('employee-status-cards');
    if (employeeStatusCards) {
      const employeeId = employeeStatusCards.getAttribute('data-employee-id');
      const updateStatusUrl = "{{ route('employee.update-status') }}";
      const updateFieldUrl = "{{ route('employee.update-profile-field') }}";
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      const employeeTopOptionDateModalEl = document.getElementById('employeeTopOptionDateModal');
      const employeeTopOptionModal = employeeTopOptionDateModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal ?
        new bootstrap.Modal(employeeTopOptionDateModalEl) :
        null;
      let pendingEmployeeChange = null;

      function employeeTopOptionTodayYmd() {
        const t = new Date();
        return t.getFullYear() + '-' + String(t.getMonth() + 1).padStart(2, '0') + '-' + String(t.getDate()).padStart(2, '0');
      }

      function openEmployeeDateModal(label) {
        const nameEl = document.getElementById('employeeTopOptionModalStatusName');
        if (nameEl) nameEl.textContent = label || 'Change';
        const dateInput = document.getElementById('employeeTopOptionEffectiveDate');
        const today = employeeTopOptionTodayYmd();
        if (dateInput) {
          dateInput.max = today;
          dateInput.value = today;
        }
        if (employeeTopOptionModal) {
          employeeTopOptionModal.show();
        } else {
          showNotification('Date dialog is unavailable', 'error');
          pendingEmployeeChange = null;
        }
      }

      window.syncEmployeeStatusCards = function syncEmployeeStatusCards(activeStatus) {
        const map = {
          active: 'employee-status-active-card',
          on_leave: 'employee-status-leave-card',
          inactive: 'employee-status-inactive-card',
        };
        Object.keys(map).forEach((key) => {
          const card = document.getElementById(map[key]);
          const radio = card ? card.querySelector('input[name="employee_status_toggle"]') : null;
          if (!card) return;
          const isActive = activeStatus === key;
          card.classList.toggle('active-success', key === 'active' && isActive);
          card.classList.toggle('active-info', key === 'on_leave' && isActive);
          card.classList.toggle('active-danger', key === 'inactive' && isActive);
          if (radio) radio.checked = isActive;
        });
        const badge = document.getElementById('employee-status-value-badge');
        if (badge) {
          const label = (activeStatus || 'active').replace('_', ' ');
          badge.textContent = label.charAt(0).toUpperCase() + label.slice(1);
          badge.className = 'entity-view-card-active' + (
            activeStatus === 'active' ? '' :
            activeStatus === 'inactive' ? ' is-inactive' : ' is-vacation'
          );
        }
      }

      function syncEmployeeTopOptionCards(column) {
        employeeStatusCards.querySelectorAll('.employee-top-option-card[data-column="' + column + '"]').forEach((c) => {
          const cb = c.querySelector('.employee-top-option-checkbox');
          c.classList.toggle('active-success', !!(cb && cb.checked));
        });
      }

      function getActiveEmployeeStatus() {
        const checked = employeeStatusCards.querySelector('input[name="employee_status_toggle"]:checked');
        return checked ? checked.value : 'active';
      }

      function submitEmployeeStatusChange(status, effectiveDate) {
        const card = document.getElementById(
          status === 'active' ? 'employee-status-active-card' :
          status === 'on_leave' ? 'employee-status-leave-card' : 'employee-status-inactive-card'
        );
        if (card) card.classList.add('loading');

        return fetch(updateStatusUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify({
              employee_id: employeeId,
              status: status,
              effective_date: effectiveDate,
            }),
          })
          .then((r) => r.json().then((data) => ({
            ok: r.ok,
            data
          })))
          .then(({
            ok,
            data
          }) => {
            if (ok && data.success) {
              syncEmployeeStatusCards(status);
              refreshEmployeeSidebar(data.employee);
              showNotification(data.message || 'Status updated', 'success');
              return true;
            }
            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to update status');
            showNotification(msg, 'error');
            syncEmployeeStatusCards(getActiveEmployeeStatus());
            return false;
          })
          .catch(() => {
            showNotification('Failed to update status', 'error');
            syncEmployeeStatusCards(getActiveEmployeeStatus());
            return false;
          })
          .finally(() => {
            if (card) card.classList.remove('loading');
          });
      }

      function submitEmployeeFieldChange(checkbox, value, effectiveDate) {
        const column = checkbox.getAttribute('data-column');
        const card = checkbox.closest('.status-card');
        const categoryName = card ? card.getAttribute('data-category') : null;
        if (!column) return Promise.resolve(false);
        if (card) card.classList.add('loading');

        const payload = {
          employee_id: employeeId,
          column: column,
          value: value,
          category_name: categoryName,
        };
        if (value) payload.effective_date = effectiveDate;

        return fetch(updateFieldUrl, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'Accept': 'application/json',
              'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
          })
          .then((r) => r.json().then((data) => ({
            ok: r.ok,
            data
          })))
          .then(({
            ok,
            data
          }) => {
            if (ok && data.success) {
              if (value) {
                employeeStatusCards.querySelectorAll('.employee-top-option-card[data-column="' + column + '"]').forEach((c) => {
                  const cb = c.querySelector('.employee-top-option-checkbox');
                  if (cb && cb !== checkbox) cb.checked = false;
                });
                checkbox.checked = true;
              } else {
                checkbox.checked = false;
              }
              syncEmployeeTopOptionCards(column);
              refreshEmployeeSidebar(data.employee);
              showNotification(data.message || 'Updated', 'success');
              return true;
            }
            const msg = data.message || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Failed to update');
            showNotification(msg, 'error');
            return false;
          })
          .catch(() => {
            showNotification('Failed to update', 'error');
            return false;
          })
          .finally(() => {
            if (card) card.classList.remove('loading');
          });
      }

      employeeStatusCards.addEventListener('click', function(e) {
        const toggleArea = e.target.closest('.status-toggle');
        if (!toggleArea || !employeeStatusCards.contains(toggleArea)) return;

        const radio = toggleArea.querySelector('input[name="employee_status_toggle"]');
        if (radio) {
          if (radio.checked) return;
          e.preventDefault();
          e.stopPropagation();
          const titleEl = radio.closest('.status-card')?.querySelector('.status-title');
          pendingEmployeeChange = {
            type: 'status',
            status: radio.value,
          };
          openEmployeeDateModal(titleEl ? titleEl.textContent.trim() : 'Status');
          return;
        }

        const checkbox = toggleArea.querySelector('.employee-top-option-checkbox');
        if (checkbox) {
          if (checkbox.checked) return;
          e.preventDefault();
          e.stopPropagation();
          const card = checkbox.closest('.status-card');
          const titleEl = card ? card.querySelector('.status-title') : null;
          pendingEmployeeChange = {
            type: 'field',
            checkbox: checkbox,
            value: checkbox.getAttribute('data-value'),
          };
          openEmployeeDateModal(titleEl ? titleEl.textContent.trim() : 'Option');
        }
      }, true);

      if (employeeTopOptionDateModalEl) {
        employeeTopOptionDateModalEl.addEventListener('hidden.bs.modal', function() {
          pendingEmployeeChange = null;
        });
      }

      document.getElementById('employeeTopOptionDateSave')?.addEventListener('click', function() {
        const pending = pendingEmployeeChange;
        if (!pending) return;
        const dateInput = document.getElementById('employeeTopOptionEffectiveDate');
        const effectiveDate = dateInput ? dateInput.value : '';
        const today = employeeTopOptionTodayYmd();
        if (!effectiveDate) {
          showNotification('Please select an effective date', 'error');
          return;
        }
        if (effectiveDate > today) {
          showNotification('Future dates are not allowed', 'error');
          return;
        }

        if (pending.type === 'status') {
          submitEmployeeStatusChange(pending.status, effectiveDate).then((ok) => {
            if (ok && employeeTopOptionModal) employeeTopOptionModal.hide();
          });
          return;
        }

        if (pending.type === 'field' && pending.checkbox) {
          submitEmployeeFieldChange(pending.checkbox, pending.value, effectiveDate).then((ok) => {
            if (ok && employeeTopOptionModal) employeeTopOptionModal.hide();
          });
        }
      });

      employeeStatusCards.addEventListener('change', function(e) {
        const target = e.target;
        if (!employeeId || !csrfToken) return;

        if (target.classList.contains('employee-top-option-checkbox') && !target.checked) {
          submitEmployeeFieldChange(target, null, null).then((ok) => {
            if (!ok) target.checked = true;
          });
        }
      });
    }

    // Function to show notifications
    function showNotification(message, type) {
      const $notification = $('<div>', {
        class: `notification notification-${type}`,
        html: `
                <div class="notification-content">
                    <i class="ti ti-${type === 'success' ? 'check' : 'x'}"></i>
                    <span>${message}</span>
                </div>
            `,
        css: {
          position: 'fixed',
          top: '20px',
          right: '20px',
          background: type === 'success' ? '#28a745' : '#dc3545',
          color: 'white',
          padding: '12px 20px',
          borderRadius: '8px',
          boxShadow: '0 4px 12px rgba(0, 0, 0, 0.15)',
          zIndex: 9999,
          animation: 'slideIn 0.3s ease',
          maxWidth: '300px'
        }
      });

      $('body').append($notification);

      setTimeout(() => {
        $notification.css('animation', 'slideOut 0.3s ease');
        setTimeout(() => {
          $notification.remove();
        }, 300);
      }, 3000);
    }

    // Add CSS for notifications if not already present
    if (!$('#notification-styles').length) {
      $('<style>', {
        id: 'notification-styles',
        text: `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
                @keyframes slideOut {
                    from { transform: translateX(0); opacity: 1; }
                    to { transform: translateX(100%); opacity: 0; }
                }
                .notification-content {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
            `
      }).appendTo('head');
    }
  });
</script>
@endsection