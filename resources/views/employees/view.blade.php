@extends('layouts.app')

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
      border-top: 1px solid rgba(0,0,0,0.1);
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
  
  .status-radio:checked + .toggle-switch::after {
      transform: translateX(20px);
  }
  
  .status-radio-active:checked + .toggle-switch {
      background: #28a745;
  }
  
  .status-radio-leave:checked + .toggle-switch {
      background: #17a2b8;
  }
  
  .status-radio-inactive:checked + .toggle-switch {
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
      0% { opacity: 1; }
      50% { opacity: 0.5; }
      100% { opacity: 1; }
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
</style>

@php
// Get the account ID for the employee
if(isset($employee)) {
    $account = App\Models\Accounts::where('ref_id', $employee->id)
                ->where('account_type', 'expense')
                ->first();
}
@endphp

<div class="row" style="">
  <div class="col-xl-3 col-md-3 col-lg-5 order-1 order-md-0">
    <!-- User Card -->
    <div class="card mb-6" style="border-radius: 25px 25px 0px 0px;">
      <div class="card-header p-0" style="border-radius: 25px 25px 0px 0px;height: 291px;position: relative;background-image: url({{ asset('assets/img/user_back.jpg') }});background-size: cover;">
        <div class="profile-img">
          @php
          $profile = DB::table('files')
                    ->where('type', 'employee')
                    ->where('type_id', $employee->id)
                    ->where(function($query) {
                        $query->where('name', 'LIKE', '%photo%')
                              ->orWhere('name', 'LIKE', '%Photo%')
                              ->orWhere('name', 'LIKE', '%picture%') 
                              ->orWhere('name', 'LIKE', '%Picture%')
                              ->orWhere('name', 'LIKE', '%profile%')
                              ->orWhere('name', 'LIKE', '%Profile%');
                    })
                    ->first();
          
          if($employee->profile_image)
            $image_name = asset('storage/' . $employee->profile_image);
          elseif (isset($profile))
            $image_name = asset('storage2/'. $profile->type .'/'. $profile->type_id .'/'. $profile->file_name);
          else
            $image_name = asset('uploads/default.png');
          @endphp
          <img src="{{ $image_name }}" id="output" width="270" class="profile-user-img img-fluid" />
        </div>
      </div>
      <div class="card-body pt-12">
        <div class="user-avatar-section">
          <div class=" d-flex align-items-center flex-column">
            <div class="col-md-12 mt-2">
              <div class="d-flex align-items-baseline">
                <div class="user-info" style="width: 100%;">
                  <div class="mt-2" style="width: 100%;display: flex;gap: 10px; margin-bottom: 10px;">
                    <span class="badge bg-label-primary">{{ $employee->designation ?? 'Not Set' }}</span>
                    <span class="badge @if($employee->status == 'active') bg-label-success @elseif($employee->status == 'inactive') bg-label-danger @else bg-label-info @endif">
                      {{ ucfirst($employee->status ?? 'Not Set') }}
                    </span>
                  </div>
                  <span>{{ $employee->employee_id ?? 'not-set' }}</span>
                  <h6>
                    <b>
                      {{ $employee->name ?? 'not-set' }}
                    </b>
                  </h6>
                </div>
                <div class="text-end" style="width: 14%;"  id="photo-icon">
                    <i class="ti ti-edit ti-sm"
                        style="border: 2px solid #9593997a !important; border-radius: 24px; padding: 8px; cursor: pointer;">
                    </i>
                </div>
              </div>
            </div>
            <div id="photo-upload-form" class="mt-4" style="display: none;">
              <form action="{{ route('employees.updateSection', $employee->id) }}" method="POST" enctype="multipart/form-data" id="formAjax2">
                @csrf
                <div class="button-wrapper">
                  <label for="upload" class="btn btn-default me-2 mb-3 mt-3" tabindex="0">
                    <span class="d-none d-sm-block">Change Photo</span>
                    <i class="ti ti-upload d-block d-sm-none"></i>
                    <input type="file" id="upload" name="profile_image" class="account-file-input" hidden accept="image/png, image/jpeg" onchange="loadFile(event)" />
                  </label>
                  <input type="hidden" name="section" value="photo">
                  <button type="submit" class="btn btn-primary">Upload</button>
                </div>
              </form>
            </div>
          </div>
        </div>
        <div class="info-container mt-3">
          <h3>Basic Information</h3>
          <ul class="list-unstyled mb-6">
            <script>
              var loadFile = function(event) {
                var image = document.getElementById("output");
                image.src = URL.createObjectURL(event.target.files[0]);
              };
            </script>

            <ul class="p-0 mb-3">
              <li class="list-group-item pb-1 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-mail ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Personal Email:</span><br> 
                  <b class="float-right">{{ $employee->personal_email ?? 'not-set' }}</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-mail ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Company Email:</span><br> 
                  <b class="float-right">{{ $employee->company_email ?? 'not-set' }}</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-phone ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content mt-2">
                  <span>WhatsApp:</span><br>
                  <b class="float-right">
                    @if($employee->company_contact)
                    @php
                    $phone = preg_replace('/[^0-9]/', '', $employee->company_contact);
                    $whatsappNumber = '+971' . ltrim($phone, '0');
                    @endphp
                    <a href="https://wa.me/{{ $whatsappNumber }}"
                      target="_blank"
                      class="text-success">
                      {{ $employee->company_contact }}
                    </a>
                    @else
                    N/A
                    @endif
                  </b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-flag ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Nationality:</span><br> 
                  <b class="float-right">{{ $employee->nationality->name ?? 'not-set' }}</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-cake ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Age:</span><br>
                  <b class="float-right">
                    @if($employee->dob)
                    {{ \Carbon\Carbon::parse($employee->dob)->age }}
                    @else
                    not-set
                    @endif
                  </b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-calendar-due ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Date Of Joining:</span><br> 
                  <b class="float-right">
                    @if($employee->doj)
                    {{ \Carbon\Carbon::parse($employee->doj)->format('d M Y') }}
                    @else
                    not-set
                    @endif
                  </b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-cash-banknote ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Balance:</span><br> 
                  <b class="float-right">
                    @if($account)
                    {{ App\Helpers\Accounts::getBalance($account->id) }}
                    @else
                    0.00
                    @endif
                  </b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-cash ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Salary:</span><br> 
                  <b class="float-right">{{ number_format($employee->salary ?? 0, 2) }} AED</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-id ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Emirates ID:</span><br> 
                  <b class="float-right">{{ $employee->emirate_id ?? 'not-set' }}</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-building ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Department:</span><br> 
                  <b class="float-right">{{ $employee->department->name ?? $employee->department_id ?? 'not-set' }}</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-briefcase ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Branch:</span><br> 
                  <b class="float-right">{{ $employee->branch->name ?? 'not-set' }}</b>
                </div>
              </li>
            </ul>
          </ul>
          
          @if(isset($employee))
          <!-- Status Card -->
            <div class="status-card" id="employee-status-card" data-employee-id="{{ $employee->id }}">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="status-icon">
                        <i class="ti ti-user-check"></i>
                    </div>
                    <div class="status-content">
                        <div class="status-title">Employee Status</div>
                        <div class="status-subtitle" id="current-status-text">
                            @if($employee->status == 'active')
                                Active Employee
                            @elseif($employee->status == 'on_leave')
                                Currently on Leave
                            @elseif($employee->status == 'inactive')
                                Inactive Employee
                            @else
                                Not Set
                            @endif
                        </div>
                    </div>
                </div>
                
                <div class="status-options">
                    <div class="status-option mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="ti ti-user-check me-2 text-success"></i>Active</span>
                            <div class="status-toggle">
                                <input type="radio"
                                    class="status-radio status-radio-active"
                                    name="employee_status"
                                    id="status-active-{{ $employee->id }}"
                                    data-employee-id="{{ $employee->id }}"
                                    data-status="active"
                                    {{ $employee->status == 'active' ? 'checked' : '' }}>
                                <label for="status-active-{{ $employee->id }}" class="toggle-switch"></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="status-option mb-2">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="ti ti-calendar-off me-2 text-info"></i>On Leave</span>
                            <div class="status-toggle">
                                <input type="radio"
                                    class="status-radio status-radio-leave"
                                    name="employee_status"
                                    id="status-leave-{{ $employee->id }}"
                                    data-employee-id="{{ $employee->id }}"
                                    data-status="on_leave"
                                    {{ $employee->status == 'on_leave' ? 'checked' : '' }}>
                                <label for="status-leave-{{ $employee->id }}" class="toggle-switch"></label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="status-option">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="ti ti-user-x me-2 text-danger"></i>Inactive</span>
                            <div class="status-toggle">
                                <input type="radio"
                                    class="status-radio status-radio-inactive"
                                    name="employee_status"
                                    id="status-inactive-{{ $employee->id }}"
                                    data-employee-id="{{ $employee->id }}"
                                    data-status="inactive"
                                    {{ $employee->status == 'inactive' ? 'checked' : '' }}>
                                <label for="status-inactive-{{ $employee->id }}" class="toggle-switch"></label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
          @endif
        </div>
      </div>
    </div>
  </div>
  
  <div class="col-xl-9 col-md-9 col-lg-7 order-0 order-md-1 position-relative">
    <div class="nav-align-top mb-4" style="position: sticky; top: 0; z-index: 1000; width: 100%;">
      <div class="card" style="z-index: 1;">
        <div class="card-body p-2">
          <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap: 0.5rem;">
            <div class="flex-grow-1" style="min-width: 0;">
              <ul class="nav nav-pills flex-nowrap mb-0 overflow-hidden" id="mainNavigation" style="gap: 0.25rem;">
                <!-- Priority navigation items -->
                <li class="nav-item nav-priority-1">
                  <a class="nav-link @if(request()->routeIs('employees.show')) active @endif"
                    href="{{ route('employees.show', $employee->id) }}">
                    <i class="ti ti-user-check ti-sm me-1_5"></i>Information
                  </a>
                </li>

                @can('employee_document')
                <li class="nav-item nav-priority-2">
                  <a class="nav-link @if(request()->segment(2) == 'files') active @endif"
                    href="{{ route('employee.files', $employee->id) }}">
                    <i class="ti ti-file-upload ti-sm me-1_5"></i>Files
                  </a>
                </li>
                @endcan

                @can('employees_view')
                <li class="nav-item nav-priority-3">
                  <a class="nav-link @if(request()->routeIs('employee.ledger')) active @endif"
                    href="{{ route('employee.ledger', $employee->id) }}">
                    <i class="ti ti-file-stack ti-sm me-1_5"></i>Ledger
                  </a>
                </li>
                @endcan

                @can('employee_salary')
                <li class="nav-item nav-priority-4">
                  <a class="nav-link @if(request()->segment(2) == 'salary') active @endif"
                    href="{{ route('employee.salary', $employee->id) }}">
                    <i class="ti ti-cash-banknote ti-sm me-1"></i>Salary
                  </a>
                </li>
                @endcan

                @can('employee_attendance')
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(request()->segment(2) == 'attendance') active @endif"
                    href="{{ route('employee.attendance', $employee->id) }}">
                    <i class="ti ti-calendar-check ti-sm me-1_5"></i>Attendance
                  </a>
                </li>
                @endcan

                @can('employee_leave')
                <li class="nav-item nav-priority-6">
                  <a class="nav-link @if(request()->segment(2) == 'leaves') active @endif"
                    href="{{ route('employee.leaves', $employee->id) }}">
                    <i class="ti ti-calendar-off ti-sm me-1_5"></i>Leaves
                  </a>
                </li>
                @endcan

                @can('employee_timeline')
                <li class="nav-item nav-priority-7">
                  <a class="nav-link @if(request()->segment(2) == 'timeline') active @endif"
                    href="{{ route('employee.timeline', $employee->id) }}">
                    <i class="ti ti-timeline ti-sm me-1_5"></i>Timeline
                  </a>
                </li>
                @endcan

                <!-- Action items -->
                @canany(['employee_voucher_create'])
                <li class="nav-item nav-priority-8 nav-action-item">
                  <a href="javascript:void(0);"
                    data-action="{{ route('employees.voucher', $employee->id) }}"
                    data-size="xl" data-title="Voucher"
                    class='nav-link show-modal'>
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Voucher
                  </a>
                </li>
                @endcanany
              </ul>
            </div>

            <!-- Dropdown for overflow items -->
            <div class="dropdown">
              <button class="btn btn-outline-secondary rounded-pill p-2 waves-effect"
                type="button" id="actiondropdown" data-bs-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="ti ti-dots icon-md"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" id="dropdownMenu">
                <div id="overflowItems"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
    <div style="margin-top: 20px; position: relative;">
      @yield('page-content')
    </div>
  </div>
</div>

{{-- @include('employees.action-buttons') --}}
@endsection

@section('page-script')
<script>
$(document).ready(function() {
    // Photo upload toggle
    $('#photo-icon').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        const photoForm = $('#photo-upload-form');
        const icon = $(this).find('i');
        
        // Toggle photo upload form visibility with animation
        if (photoForm.is(':visible')) {
            photoForm.slideUp(200);
            icon.attr('class', 'ti ti-edit ti-sm')
                .css({
                    'border': '2px solid #9593997a',
                    'color': '#000'
                });
        } else {
            photoForm.slideDown(200);
            icon.attr('class', 'ti ti-x ti-sm')
                .css({
                    'border': '2px solid #dc3545',
                    'color': '#dc3545'
                });
        }
    });

    // Close photo form when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#photo-iconicon, #photo-upload-form').length) {
            const photoForm = $('#photo-upload-form');
            if (photoForm.is(':visible')) {
                photoForm.slideUp(200);
                $('#photo-icon i').attr('class', 'ti ti-edit ti-sm')
                    .css({
                        'border': '2px solid #9593997a',
                        'color': ''
                    });
            }
        }
    });

    // Prevent closing when clicking inside the form
    $('#photo-upload-form').on('click', function(e) {
        e.stopPropagation();
    });

    // Photo upload form submission
    $('#formAjax2').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formData = new FormData(this);
        const submitBtn = form.find('button[type="submit"]');
        const originalText = submitBtn.html();
        
        // Validate file selection
        if (!$('#upload').val()) {
            toastr.warning('Please select an image first');
            return;
        }
        
        // Add loading state
        submitBtn.html('<i class="fa fa-spinner fa-spin"></i> Uploading...').prop('disabled', true);
        
        $.ajax({
            url: form.attr('action'),
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Update image with cache busting
                    const timestamp = new Date().getTime();
                    $('#output').attr('src', response.image_url + '?' + timestamp);
                    toastr.success('Photo uploaded successfully');
                    
                    // Close form
                    $('#photo-upload-form').slideUp(200);
                    $('#photo-icon i').attr('class', 'ti ti-edit ti-sm')
                        .css({
                            'border': '2px solid #9593997a',
                            'color': ''
                        });
                    
                    // Reset form
                    form[0].reset();
                } else {
                    toastr.error(response.message || 'Error uploading photo');
                }
            },
            error: function(xhr) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    let errorMsg = '';
                    $.each(errors, function(key, value) {
                        errorMsg += value[0] + '\n';
                    });
                    toastr.error(errorMsg);
                } else {
                    toastr.error('Error uploading photo');
                }
            },
            complete: function() {
                submitBtn.html(originalText).prop('disabled', false);
            }
        });
    });

    // Responsive Navigation Handler
    class ResponsiveNavigation {
        constructor() {
            this.mainNav = $('#mainNavigation')[0];
            this.overflowContainer = $('#overflowItems')[0];
            this.dropdownButton = $('#actiondropdown')[0];
            this.allNavItems = [];
            this.init();
        }

        init() {
            this.allNavItems = Array.from(this.mainNav.querySelectorAll('.nav-item')).map(item => {
                const priorityClass = Array.from(item.classList).find(cls => cls.startsWith('nav-priority-'));
                const priority = priorityClass ? parseInt(priorityClass.split('-')[2]) : 999;
                return {
                    element: item,
                    priority: priority,
                    html: item.outerHTML,
                    isActive: $(item).find('.nav-link.active').length > 0
                };
            }).sort((a, b) => a.priority - b.priority);

            this.handleResize();

            let resizeTimeout;
            $(window).on('resize', () => {
                clearTimeout(resizeTimeout);
                resizeTimeout = setTimeout(() => this.handleResize(), 100);
            });
        }

        handleResize() {
            this.resetNavigation();
            requestAnimationFrame(() => {
                requestAnimationFrame(() => {
                    this.redistributeItems();
                });
            });
        }

        resetNavigation() {
            $(this.overflowContainer).empty();
            $(this.mainNav).empty();
            this.allNavItems.forEach(item => {
                $(this.mainNav).append(item.element);
            });
        }

        redistributeItems() {
            const container = this.mainNav.closest('.card-body');
            if (!container) return;

            const containerWidth = container.getBoundingClientRect().width;
            const dropdownWidth = $(this.dropdownButton).outerWidth() + 10;

            let currentWidth = 0;
            const visibleItems = [];
            const overflowItems = [];

            const itemWidths = this.allNavItems.map(item => {
                const width = this.getItemWidth(item.element);
                return { item, width };
            });

            const containerStyles = window.getComputedStyle(container);
            const containerPadding = parseFloat(containerStyles.paddingLeft) + parseFloat(containerStyles.paddingRight);
            const safetyMargin = 20;
            const usableWidth = containerWidth - containerPadding - safetyMargin;
            const availableWidth = usableWidth - dropdownWidth;

            for (let i = 0; i < itemWidths.length; i++) {
                const { item, width } = itemWidths[i];

                if (currentWidth + width <= availableWidth) {
                    currentWidth += width;
                    visibleItems.push(item);
                } else {
                    overflowItems.push(item);
                }
            }

            if (visibleItems.length === 0 && this.allNavItems.length > 0) {
                visibleItems.push(this.allNavItems[0]);
                overflowItems.unshift(...this.allNavItems.slice(1));
            }

            this.updateNavigation(visibleItems, overflowItems);
        }

        getItemWidth(element) {
            const $clone = $(element).clone();
            $clone.css({
                visibility: 'hidden',
                position: 'absolute',
                whiteSpace: 'nowrap',
                top: '-9999px',
                left: '-9999px',
                pointerEvents: 'none',
                zIndex: '-1'
            });
            
            $(this.mainNav.parentNode).append($clone);
            const width = Math.ceil($clone[0].getBoundingClientRect().width) + 6;
            $clone.remove();
            return width;
        }

        updateNavigation(visibleItems, overflowItems) {
            $(this.mainNav).empty();
            visibleItems.forEach(item => {
                $(this.mainNav).append(item.element);
            });

            $(this.overflowContainer).empty();

            if (overflowItems.length > 0) {
                $(this.dropdownButton).css('display', 'flex');
                
                const navigationItems = overflowItems.filter(item => !$(item.element).hasClass('nav-action-item'));
                const actionItems = overflowItems.filter(item => $(item.element).hasClass('nav-action-item'));

                navigationItems.forEach(item => {
                    const dropdownItem = this.createDropdownItem(item);
                    $(this.overflowContainer).append(dropdownItem);
                });

                if (navigationItems.length > 0 && actionItems.length > 0) {
                    $(this.overflowContainer).append('<div class="dropdown-divider"></div>');
                    $(this.overflowContainer).append('<h6 class="dropdown-header">Actions</h6>');
                }

                actionItems.forEach(item => {
                    const dropdownItem = this.createDropdownItem(item);
                    $(this.overflowContainer).append(dropdownItem);
                });
            } else {
                $(this.dropdownButton).css('display', 'none');
            }
        }

        createDropdownItem(navItem) {
            const $link = $(navItem.element).find('.nav-link');
            const href = $link.attr('href');
            const $icon = $link.find('i');
            const text = $link.text().trim();
            const isActive = $link.hasClass('active');
            const isActionItem = $(navItem.element).hasClass('nav-action-item');

            const $dropdownItem = $('<a>', {
                class: `dropdown-item overflow-nav-item ${isActive ? 'active' : ''}`,
                href: href
            });

            if (isActionItem) {
                const dataAction = $link.data('action');
                const dataSize = $link.data('size');
                const dataTitle = $link.data('title');

                if (dataAction) $dropdownItem.attr('data-action', dataAction);
                if (dataSize) $dropdownItem.attr('data-size', dataSize);
                if (dataTitle) $dropdownItem.attr('data-title', dataTitle);

                if ($link.hasClass('show-modal')) {
                    $dropdownItem.addClass('show-modal');
                }
            }

            if ($icon.length) {
                const $iconClone = $icon.clone();
                $iconClone.attr('class', $icon.attr('class').replace('me-1_5', 'me-2'));
                $dropdownItem.append($iconClone);
            }

            $dropdownItem.append(document.createTextNode(text));

            return $dropdownItem[0];
        }
    }

    // Initialize responsive navigation
    const responsiveNav = new ResponsiveNavigation();

    // Status  handlers
    $('.status-radio').on('change', function() {
      const employeeId = $(this).data('employee-id');
      const newStatus = $(this).data('status');
      const card = $('#employee-status-card');
      
      // Add loading state
      card.addClass('loading');
      
      // Make AJAX request
      $.ajax({
          url: '{{ route("employee.update-status") }}',
          method: 'POST',
          data: {
              _token: '{{ csrf_token() }}',
              employee_id: employeeId,
              status: newStatus
          },
          success: function(response) {
              if (response.success) {
                  // Update card color based on status
                  card.removeClass('active-success active-info active-danger');
                  
                  if (newStatus === 'active') {
                      $('#current-status-text').text('Active Employee');
                  } else if (newStatus === 'on leave') {
                      $('#current-status-text').text('Currently on Leave');
                  } else if (newStatus === 'inactive') {
                      $('#current-status-text').text('Inactive Employee');
                  }
                  
                  // Update header badge
                  const $badge = $('.badge.bg-label-success, .badge.bg-label-danger, .badge.bg-label-warning');
                  $badge.removeClass('bg-label-success bg-label-danger bg-label-warning');
                  if (newStatus === 'active') {
                      $badge.addClass('bg-label-success').text('Active');
                  } else if (newStatus === 'inactive') {
                      $badge.addClass('bg-label-danger').text('Inactive');
                  } else if (newStatus === 'on_leave') {
                      $badge.addClass('bg-label-info').text('On Leave');
                  }
                  
                  showNotification(response.message, 'success');
              } else {
                  showNotification('Error: ' + response.message, 'error');
                  // Revert radio button
                  $(this).prop('checked', false);
                  // Set original checked status
                  const originalStatus = '{{ $employee->status }}';
                  $(`.status-radio[data-status="${originalStatus}"]`).prop('checked', true);
              }
          },
          error: function(xhr) {
            // Revert radio button
            const originalStatus = '{{ $employee->status }}';
            $(`.status-radio[data-status="${originalStatus}"]`).prop('checked', true);
            if (xhr.status === 422) {
                const errors = xhr.responseJSON.errors;
                let errorMsg = '';
                $.each(errors, function(key, value) {
                    errorMsg += value[0] + '\n';
                });
                toastr.error(errorMsg);
            } else {
                showNotification('An error occurred while updating status: ' + xhr.responseJSON.message, 'error');
            }
          },
          complete: function() {
              card.removeClass('loading');
          }
      });
    });

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