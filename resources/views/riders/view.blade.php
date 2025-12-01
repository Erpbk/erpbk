@extends('layouts.app')
@section('title', 'Rider Profile')

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
    cursor: pointer;
    position: relative;
    overflow: hidden;
  }

  .status-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
  }

  .status-card.active {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    border-color: #28a745;
    color: white;
  }

  .absconder-card.active {
    background: linear-gradient(135deg, #dc3545 0%, #fd7e14 100%);
    border-color: #dc3545;
  }

  .flowup-card.active {
    background: linear-gradient(135deg, #007bff 0%, #6f42c1 100%);
    border-color: #007bff;
  }

  .llicense-card.active {
    background: linear-gradient(135deg, #17a2b8 0%, #20c997 100%);
    border-color: #17a2b8;
  }

  .status-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
    transition: left 0.5s;
  }

  .status-card:hover::before {
    left: 100%;
  }

  .status-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    margin-bottom: 12px;
    font-size: 20px;
  }

  .status-card.active .status-icon {
    background: rgba(255, 255, 255, 0.3);
  }

  .status-content {
    margin-bottom: 12px;
  }

  .status-title {
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 4px;
    color: #495057;
  }

  .status-card.active .status-title {
    color: white;
  }

  .status-subtitle {
    font-size: 12px;
    color: #6c757d;
    font-weight: 500;
  }

  .status-card.active .status-subtitle {
    color: rgba(255, 255, 255, 0.9);
  }

  .status-toggle {
    display: flex;
    align-items: center;
  }

  .status-checkbox {
    display: none;
  }

  .toggle-switch {
    position: relative;
    width: 50px;
    height: 24px;
    background: #ccc;
    border-radius: 12px;
    cursor: pointer;
    transition: background 0.3s ease;
  }

  .toggle-slider {
    position: absolute;
    top: 2px;
    left: 2px;
    width: 20px;
    height: 20px;
    background: white;
    border-radius: 50%;
    transition: transform 0.3s ease;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
  }

  .status-checkbox:checked+.toggle-switch {
    background: #28a745;
  }

  .status-checkbox:checked+.toggle-switch .toggle-slider {
    transform: translateX(26px);
  }

  .absconder-card .status-checkbox:checked+.toggle-switch {
    background: #dc3545;
  }

  .flowup-card .status-checkbox:checked+.toggle-switch {
    background: #007bff;
  }

  .llicense-card .status-checkbox:checked+.toggle-switch {
    background: #17a2b8;
  }

  .walker-card .status-checkbox:checked+.toggle-switch {
    background: #ff9800;
  }

  .mol-card.active {
    background: linear-gradient(135deg, #9c27b0 0%, #e91e63 100%);
    border-color: #9c27b0;
  }

  .mol-card .status-checkbox:checked+.toggle-switch {
    background: #9c27b0;
  }

  .pro-card.active {
    background: linear-gradient(135deg, #2196f3 0%, #00bcd4 100%);
    border-color: #2196f3;
  }

  .pro-card .status-checkbox:checked+.toggle-switch {
    background: #2196f3;
  }

  /* Loading state */
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
</style>
@php
if((request()->segment(2)) == 'generatentries' || request()->segment(2) == 'installmentPlan'){
$account_id = DB::table('accounts')->where('id', request()->segment(3))->first();
if(is_numeric(request()->segment(3))){
session()->put('rider_id',$account_id->ref_id);
$riders = App\Models\Riders::where('id', $account_id->ref_id)->first();
}
}else{
if(is_numeric(request()->segment(3))){
session()->put('rider_id',request()->segment(3));
$riders = App\Models\Riders::find(request()->segment(3));
}
}
if(isset($riders)){
$result = $riders->toArray();
}
if(isset($result)){
$account = App\Models\Accounts::where('ref_id', $result['id'])->where('account_type', 'expense')->first();
}

@endphp
<div class="row" style="">
  <div class="col-xl-3 col-md-3 col-lg-5 order-1 order-md-0">
    <!-- User Card -->
    <div class="card mb-6" style="border-radius: 25px 25px 0px 0px;">
      <div class="card-header p-0" style="border-radius: 25px 25px 0px 0px;height: 291px;position: relative;background-image: url(http://127.0.0.1:8000/assets/img/user_back.jpg);background-size: cover;">
        @isset($result)
        <div class="profile-img">
          @php
          if(@$result['image_name']){
          $image_name = url('storage2/profile/'.$result['image_name']);//Storage::url('app/profile/'.$result['image_name']);
          }else{
          $image_name = asset('uploads/default.png');
          }
          @endphp
          <img src="{{ $image_name}}" id="output" width="270" class="profile-user-img img-fluid" />
        </div>
        @endisset
      </div>
      <div class="card-body pt-12">
        <div class="user-avatar-section">
          <div class=" d-flex align-items-center flex-column">
            <div class="col-md-12 mt-2">
              <div class="d-flex align-items-baseline">
                <div class="user-info" style="width: 100%;">
                  <h6>
                    <b>
                      @isset($result)
                      {{ \Illuminate\Support\Str::limit($result['rider_id'] ?? 'not-set', 25) }} - {{ \Illuminate\Support\Str::limit($result['name'] ?? 'not-set', 25) }}
                      @endisset
                    </b>

                  </h6>
                  <div class="mt-2" style="width: 100%;display: flex;gap: 10px;">
                    <span class="badge bg-label-primary">@isset($result){{$result['designation']??'not-set'}}@endisset</span>
                    <span class="badge @isset($result) @if($result['status'] == 1) bg-label-success @else bg-label-danger @endif @endisset">@isset($result){{App\Helpers\General::RiderStatus($result['status'])??'not-set'}}@endisset</span>
                  </div>
                </div>
                <div class="text-end" style="width: 14%;">
                  <i class="ti ti-edit ti-sm"
                    style="border: 2px solid #9593997a !important; border-radius: 24px; padding: 8px; cursor: pointer;"
                    id="edit-icon">
                  </i>
                </div>
              </div>
            </div>
            <div id="photo-upload-form" class="mt-4" style="display: none;">
              @isset($result)
              <form action="{{url('riders/picture_upload/'.$result['id'])}}" method="POST" enctype="multipart/form-data" id="formajax2">
                @endisset
                @csrf
                @isset($result)
                <div class="button-wrapper">
                  <label for="upload" class="btn btn-default me-2 mb-3 mt-3" tabindex="0">
                    <span class="d-none d-sm-block">Change Photo</span>
                    <i class="ti ti-upload d-block d-sm-none"></i>
                    <input type="file" id="upload" name="image_name" class="account-file-input " hidden accept="image/png, image/jpeg" onchange="loadFile(event)" />
                  </label>
                  <button type="submit" class="btn btn-primary">Upload</button>
                </div>
                @endisset
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
            {{-- <div class="text-center">
                         <img class="profile-user-img img-fluid" src="https://placehold.co/400X400" alt="User profile picture">
                      </div> --}}


            <ul class="p-0 mb-3">
              <li class="list-group-item pb-1 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-mail ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Email:</span><br> <b class="float-right">@isset($result){{$result['personal_email']??'not-set'}}@endisset</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-phone ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content mt-2">
                  <span>WhatsApp:</span><br>
                  <b class="float-right">

                    @isset($result['company_contact'])
                    @php
                    $phone = preg_replace('/[^0-9]/', '', $result['company_contact']);
                    $whatsappNumber = '+971' . ltrim($phone, '0');
                    @endphp
                    <a href="https://wa.me/{{ $whatsappNumber }}"
                      target="_blank"
                      class="text-success">
                      {{ $result['company_contact'] }}
                    </a>
                    @else
                    N/A
                    @endisset

                  </b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-flag ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Nationality:</span><br> <b class="float-right">@isset($result){{DB::Table('countries')->where('id' , $result['nationality'])->first()->name ??'not-set'}}@endisset</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-cake ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Age:</span><br>
                  <b class="float-right">
                    @isset($result['dob'])
                    {{ \Carbon\Carbon::parse($result['dob'])->age }}
                    @else
                    not-set
                    @endisset
                  </b>
                </div>
              </li>
              <!-- <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-user-check ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Status:</span><br> <b class="float-right">@isset($result){{App\Helpers\General::RiderStatus($result['status'])??'not-set'}}@endisset</b>
                </div>
              </li> -->
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-calendar-due ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Date Of Joining:</span><br> <b class="float-right">@isset($result){{App\Helpers\General::DateFormat($result['doj'])??'not-set'}}@endisset</b>
                </div>
              </li>
              <!-- <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-user-check ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Shift:</span><br> <b class="float-right">@isset($result){{$result['shift']??'not-set'}}@endisset</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-file-invoice ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Attendance:</span><br> <b class="float-right">@isset($result){{$result['attendance']??'not-set'}}@endisset</b>
                </div>
              </li> -->
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-cash-banknote ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content">
                  <span>Balance:</span><br> <b class="float-right">@isset($result){{App\Helpers\Accounts::getBalance($result['account_id'])}}@endisset</b>
                </div>
              </li>
            </ul>
          </ul>
          @isset($result)
          <div class="d-flex flex-wrap justify-content-start gap-2 gap-md-3">
            <!-- Absconder Status Card -->
            <div class="status-card absconder-card {{ ($result['absconder'] ?? 0) == 1 ? 'active' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-type="absconder">
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="ti ti-user-x"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">Absconder</div>
                  <div class="status-subtitle">{{ ($result['absconder'] ?? 0) == 1 ? 'Marked as Absconder' : 'Not Absconder' }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox absconder-checkbox"
                  id="absconder-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  {{ ($result['absconder'] ?? 0) == 1 ? 'checked' : '' }}>
                <label for="absconder-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            <!-- Follow Up Status Card -->
            <div class="status-card flowup-card {{ ($result['flowup'] ?? 0) == 1 ? 'active' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-type="flowup">
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="ti ti-bell"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">Follow Up</div>
                  <div class="status-subtitle">{{ ($result['flowup'] ?? 0) == 1 ? 'Follow Up Required' : 'No Follow Up' }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox flowup-checkbox"
                  id="flowup-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  {{ ($result['flowup'] ?? 0) == 1 ? 'checked' : '' }}>
                <label for="flowup-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            <!-- Learning License Status Card -->
            <div class="status-card llicense-card {{ ($result['l_license'] ?? 0) == 1 ? 'active' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-type="llicense">
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="ti ti-certificate"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">Learning License</div>
                  <div class="status-subtitle">{{ ($result['l_license'] ?? 0) == 1 ? 'Learning License Required' : 'No Learning License' }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox llicense-checkbox"
                  id="llicense-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  {{ ($result['l_license'] ?? 0) == 1 ? 'checked' : '' }}>
                <label for="llicense-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            <!-- Walker Designation Status Card -->
            <div class="status-card walker-card {{ (isset($result['designation']) && $result['designation'] === 'Walker') ? 'active' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-type="walker">
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="ti ti-walk"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">Walker</div>
                  <div class="status-subtitle">{{ (isset($result['designation']) && $result['designation'] === 'Walker') ? 'Designation is Walker' : 'Not Walker' }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox walker-checkbox"
                  id="walker-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  {{ (isset($result['designation']) && $result['designation'] === 'Walker') ? 'checked' : '' }}>
                <label for="walker-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            <!-- MOL Designation Status Card -->
            <div class="status-card mol-card {{ (isset($result['designation']) && $result['designation'] === 'MOL') ? 'active' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-type="mol">
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="ti ti-building"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">MOL</div>
                  <div class="status-subtitle">{{ (isset($result['designation']) && $result['designation'] === 'MOL') ? 'Designation is MOL' : 'Not MOL' }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox mol-checkbox"
                  id="mol-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  {{ (isset($result['designation']) && $result['designation'] === 'MOL') ? 'checked' : '' }}>
                <label for="mol-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            <!-- PRO Designation Status Card -->
            <div class="status-card pro-card {{ (isset($result['designation']) && $result['designation'] === 'PRO') ? 'active' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-type="pro">
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="ti ti-user-check"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">PRO</div>
                  <div class="status-subtitle">{{ (isset($result['designation']) && $result['designation'] === 'PRO') ? 'Designation is PRO' : 'Not PRO' }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox pro-checkbox"
                  id="pro-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  {{ (isset($result['designation']) && $result['designation'] === 'PRO') ? 'checked' : '' }}>
                <label for="pro-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
          </div>
          @endisset
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
                <!-- Priority navigation items (always visible when possible) -->
                <li class="nav-item nav-priority-1">
                  <a class="nav-link @if(is_numeric(request()->segment(2)) || request()->segment(2) == 'create') active @endif"
                    href="@isset($result['id']){{route('riders.show',$result['id'])}}@else#@endif">
                    <i class="ti ti-user-check ti-sm me-1_5"></i>Information
                  </a>
                </li>

                @isset($result)
                @can('timeline_view')
                <li class="nav-item nav-priority-2">
                  <a class="nav-link @if(request()->segment(2) == 'timeline') active @endif"
                    href="{{route('rider.timeline',$result['id'])}}">
                    <i class="ti ti-timeline ti-sm me-1_5"></i>Timeline
                  </a>
                </li>
                @endcan

                @can('rider_document')
                <li class="nav-item nav-priority-3">
                  <a class="nav-link @if(request()->segment(2) == 'files') active @endif"
                    href="{{route('rider.files',$result['id'])}}">
                    <i class="ti ti-file-upload ti-sm me-1_5"></i>Files
                  </a>
                </li>
                @endcan

                @can('riderinvoice_view')
                <li class="nav-item nav-priority-4">
                  <a class="nav-link @if(request()->segment(2) == 'invoices') active @endif"
                    href="{{route('rider.invoices',$result['id'])}}">
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Invoices
                  </a>
                </li>
                @endcan

                @can('visaexpense_view')
                @if($account)
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(request()->segment(2) == 'generatentries' || request()->segment(2) == 'installmentPlan') active @endif"
                    href="{{ route('VisaExpense.generatentries', $account->id) }}">
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Visa Expense
                  </a>
                </li>
                @endif
                @endcan

                @can('item_view')
                <li class="nav-item nav-priority-6">
                  <a class="nav-link @if(request()->segment(2) == 'items') active @endif"
                    href="{{route('rider.items',$result['id'])}}">
                    <i class="ti ti-cash-banknote ti-sm me-1"></i>Salary
                  </a>
                </li>
                @endcan

                @can('gn_ledger')
                <li class="nav-item nav-priority-7">
                  <a class="nav-link @if(request()->segment(2) == 'ledger') active @endif"
                    href="{{route('rider.ledger',$result['id'])}}">
                    <i class="ti ti-file ti-sm me-1_5"></i>Ledger
                  </a>
                </li>
                @endcan

                @can('activity_view')
                <li class="nav-item nav-priority-8">
                  <a class="nav-link @if(request()->segment(2) == 'activities') active @endif"
                    href="{{route('rider.activities',$result['id'])}}">
                    <i class="ti ti-motorbike ti-sm me-1_5"></i>Activities
                  </a>
                </li>
                @endcan

                @can('email_view')
                <li class="nav-item nav-priority-9">
                  <a class="nav-link @if(request()->segment(2) == 'emails') active @endif"
                    href="{{route('rider.emails',$result['id'])}}">
                    <i class="ti ti-mail ti-sm me-1_5"></i>Emails
                  </a>
                </li>
                @endcan

                <!-- Action items with lower priority -->
                @canany(['advanceloan_create','cod_create','penality_create','payment_create','vendorcharges_create'])
                <li class="nav-item nav-priority-10">
                  <a href="javascript:void(0);"
                    data-action="{{ route('riders.voucher', ['id' => $result['id']]) }}"
                    data-size="xl" data-title="Voucher"
                    class='nav-link show-modal'>
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Voucher
                  </a>
                </li>
                @endcanany

                @can('incentives_create')
                <li class="nav-item nav-priority-11">
                  <a href="javascript:void(0);"
                    data-action="{{ route('riders.incentive' , $result['id']) }}"
                    class='nav-link show-modal'
                    data-size="xl" data-title="Incentive">
                    <i class="ti ti-award ti-sm me-1_5"></i>Incentive
                  </a>
                </li>
                @endcan
                @endisset
              </ul>
            </div>

            <!-- Dropdown for overflow items and actions -->
            <div class="dropdown">
              <button class="btn btn-outline-secondary rounded-pill p-2 waves-effect"
                type="button" id="actiondropdown" data-bs-toggle="dropdown"
                aria-haspopup="true" aria-expanded="false">
                <i class="ti ti-dots icon-md"></i>
              </button>
              <div class="dropdown-menu dropdown-menu-end" aria-labelledby="actiondropdown" id="dropdownMenu">
                <!-- Overflow navigation and action items will be moved here -->
                <div id="overflowItems"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="card mb-5" id="cardBody" style="margin-top: 20px; position: relative;">
      @yield('page_content')
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Responsive Navigation Handler
    class ResponsiveNavigation {
      constructor() {
        this.mainNav = document.getElementById('mainNavigation');
        this.overflowContainer = document.getElementById('overflowItems');
        this.dropdownButton = document.getElementById('actiondropdown');
        this.allNavItems = [];
        this.init();
      }

      init() {
        // Store all navigation items with their priority
        this.allNavItems = Array.from(this.mainNav.querySelectorAll('.nav-item')).map(item => {
          const priorityClass = Array.from(item.classList).find(cls => cls.startsWith('nav-priority-'));
          const priority = priorityClass ? parseInt(priorityClass.split('-')[2]) : 999;
          return {
            element: item,
            priority: priority,
            html: item.outerHTML,
            isActive: item.querySelector('.nav-link.active') !== null
          };
        }).sort((a, b) => a.priority - b.priority);

        this.handleResize();

        // Debounced resize handler for better performance
        let resizeTimeout;
        window.addEventListener('resize', () => {
          clearTimeout(resizeTimeout);
          resizeTimeout = setTimeout(() => this.handleResize(), 100);
        });

        window.addEventListener('load', () => {
          setTimeout(() => this.handleResize(), 200);
        });

        // Handle window focus to recalculate
        window.addEventListener('focus', () => {
          setTimeout(() => this.handleResize(), 100);
        });

        // Handle visibility change
        document.addEventListener('visibilitychange', () => {
          if (!document.hidden) {
            setTimeout(() => this.handleResize(), 100);
          }
        });
      }

      handleResize() {
        // Reset all items to main navigation
        this.resetNavigation();

        // Wait for next frame to ensure layout is updated
        requestAnimationFrame(() => {
          // Wait another frame for styles to apply
          requestAnimationFrame(() => {
            this.redistributeItems();
          });
        });
      }

      resetNavigation() {
        // Clear overflow container
        this.overflowContainer.innerHTML = '';

        // Move all items back to main navigation
        this.mainNav.innerHTML = '';
        this.allNavItems.forEach(item => {
          this.mainNav.appendChild(item.element);
        });
      }

      redistributeItems() {
        const container = this.mainNav.closest('.card-body');
        if (!container) return;

        const containerRect = container.getBoundingClientRect();
        const containerWidth = containerRect.width;
        const dropdownWidth = this.dropdownButton.offsetWidth + 10;

        let currentWidth = 0;
        const visibleItems = [];
        const overflowItems = [];

        // First, try to fit all items without dropdown
        let totalItemsWidth = 0;
        const itemWidths = this.allNavItems.map(item => {
          const width = this.getItemWidth(item.element);
          totalItemsWidth += width;
          return {
            item,
            width
          };
        });

        // Calculate container padding and margins
        const containerStyles = window.getComputedStyle(container);
        const containerPadding = parseFloat(containerStyles.paddingLeft) + parseFloat(containerStyles.paddingRight);
        const safetyMargin = 20;
        const usableWidth = containerWidth - containerPadding - safetyMargin;

        // If all items can fit without dropdown, show them all
        if (totalItemsWidth <= usableWidth) {
          this.allNavItems.forEach(item => visibleItems.push(item));
        } else {
          // Otherwise, calculate what can fit with dropdown visible
          const availableWidth = usableWidth - dropdownWidth;

          for (let i = 0; i < itemWidths.length; i++) {
            const {
              item,
              width
            } = itemWidths[i];

            if (currentWidth + width <= availableWidth) {
              currentWidth += width;
              visibleItems.push(item);
            } else {
              overflowItems.push(item);
            }
          }

          // Ensure at least the first item (Information) is always visible
          if (visibleItems.length === 0 && this.allNavItems.length > 0) {
            visibleItems.push(this.allNavItems[0]);
            overflowItems.unshift(...this.allNavItems.slice(1));
          }
        }

        // Update the navigation
        this.updateNavigation(visibleItems, overflowItems);
      }

      getItemWidth(element) {
        // Create a temporary clone to measure width accurately
        const clone = element.cloneNode(true);
        clone.style.cssText = `
          visibility: hidden; 
          position: absolute; 
          white-space: nowrap; 
          top: -9999px; 
          left: -9999px;
          pointer-events: none;
          z-index: -1;
        `;

        // Append to the same container to inherit styles
        const container = this.mainNav.parentNode;
        container.appendChild(clone);

        const rect = clone.getBoundingClientRect();
        const width = Math.ceil(rect.width) + 6; // Add small margin and round up

        container.removeChild(clone);
        return width;
      }

      updateNavigation(visibleItems, overflowItems) {
        // Update main navigation
        this.mainNav.innerHTML = '';
        visibleItems.forEach(item => {
          this.mainNav.appendChild(item.element);
        });

        // Update overflow container and dropdown button visibility
        this.overflowContainer.innerHTML = '';

        // Show/hide dropdown button based on overflow items
        if (overflowItems.length > 0) {
          this.dropdownButton.style.display = 'flex';
          // Separate navigation and action items for better organization
          const navigationItems = overflowItems.filter(item => !item.element.classList.contains('nav-action-item'));
          const actionItems = overflowItems.filter(item => item.element.classList.contains('nav-action-item'));

          // Add navigation items first
          navigationItems.forEach(item => {
            const dropdownItem = this.createDropdownItem(item);
            this.overflowContainer.appendChild(dropdownItem);
          });

          // Add divider if both types exist
          if (navigationItems.length > 0 && actionItems.length > 0) {
            const divider = document.createElement('div');
            divider.className = 'dropdown-divider';
            this.overflowContainer.appendChild(divider);

            const header = document.createElement('h6');
            header.className = 'dropdown-header';
            header.textContent = 'Actions';
            this.overflowContainer.appendChild(header);
          }

          // Add action items
          actionItems.forEach(item => {
            const dropdownItem = this.createDropdownItem(item);
            this.overflowContainer.appendChild(dropdownItem);
          });
        } else {
          // Hide dropdown button if no overflow items
          this.dropdownButton.style.display = 'none';
        }
      }

      createDropdownItem(navItem) {
        const link = navItem.element.querySelector('.nav-link');
        const href = link.getAttribute('href');
        const icon = link.querySelector('i');
        const text = link.textContent.trim();
        const isActive = link.classList.contains('active');
        const isActionItem = navItem.element.classList.contains('nav-action-item');

        const dropdownItem = document.createElement('a');
        dropdownItem.className = `dropdown-item overflow-nav-item ${isActive ? 'active' : ''}`;
        dropdownItem.href = href;

        // Copy data attributes for action items
        if (isActionItem) {
          const dataAction = link.getAttribute('data-action');
          const dataSize = link.getAttribute('data-size');
          const dataTitle = link.getAttribute('data-title');

          if (dataAction) dropdownItem.setAttribute('data-action', dataAction);
          if (dataSize) dropdownItem.setAttribute('data-size', dataSize);
          if (dataTitle) dropdownItem.setAttribute('data-title', dataTitle);

          // Copy the show-modal class
          if (link.classList.contains('show-modal')) {
            dropdownItem.classList.add('show-modal');
          }
        }

        if (icon) {
          const iconClone = icon.cloneNode(true);
          iconClone.className = icon.className.replace('me-1_5', 'me-2');
          dropdownItem.appendChild(iconClone);
        }

        dropdownItem.appendChild(document.createTextNode(text));

        return dropdownItem;
      }
    }

    // Initialize responsive navigation
    const responsiveNav = new ResponsiveNavigation();

    // Force initial calculation after a short delay to ensure all styles are loaded
    setTimeout(() => {
      responsiveNav.handleResize();
    }, 500);

    // Add change event listener to absconder checkbox
    const absconderCheckbox = document.querySelector('.absconder-checkbox');
    if (absconderCheckbox) {
      absconderCheckbox.addEventListener('change', function() {
        const riderId = this.getAttribute('data-rider-id');
        const isChecked = this.checked;
        const card = this.closest('.status-card');
        const subtitle = card.querySelector('.status-subtitle');

        if (!riderId) {
          showNotification('Rider ID not found', 'error');
          return;
        }

        // Add loading state
        card.classList.add('loading');
        subtitle.textContent = 'Updating...';

        // Make AJAX request
        fetch(`/riders/toggle-absconder/${riderId}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              // Update card appearance
              updateCardStatus(card, 'absconder', isChecked);
              showNotification(data.message, 'success');
            } else {
              showNotification('Error: ' + data.message, 'error');
              // Revert checkbox state on error
              this.checked = !isChecked;
              updateCardStatus(card, 'absconder', !isChecked);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating absconder status', 'error');
            // Revert checkbox state on error
            this.checked = !isChecked;
            updateCardStatus(card, 'absconder', !isChecked);
          })
          .finally(() => {
            // Remove loading state
            card.classList.remove('loading');
          });
      });
    }

    // Add change event listener to flowup checkbox
    document.querySelector('.flowup-checkbox').addEventListener('change', function() {
      const riderId = this.getAttribute('data-rider-id');
      const isChecked = this.checked;
      const card = this.closest('.status-card');
      const subtitle = card.querySelector('.status-subtitle');

      if (!riderId) {
        showNotification('Rider ID not found', 'error');
        return;
      }

      // Add loading state
      card.classList.add('loading');
      subtitle.textContent = 'Updating...';

      // Make AJAX request
      fetch(`/riders/toggle-flowup/${riderId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update card appearance
            updateCardStatus(card, 'flowup', isChecked);
            showNotification(data.message, 'success');
          } else {
            showNotification('Error: ' + data.message, 'error');
            // Revert checkbox state on error
            this.checked = !isChecked;
            updateCardStatus(card, 'flowup', !isChecked);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('An error occurred while updating flowup status', 'error');
          // Revert checkbox state on error
          this.checked = !isChecked;
          updateCardStatus(card, 'flowup', !isChecked);
        })
        .finally(() => {
          // Remove loading state
          card.classList.remove('loading');
        });
    });

    // Add change event listener to llicense checkbox
    document.querySelector('.llicense-checkbox').addEventListener('change', function() {
      const riderId = this.getAttribute('data-rider-id');
      const isChecked = this.checked;
      const card = this.closest('.status-card');
      const subtitle = card.querySelector('.status-subtitle');

      if (!riderId) {
        showNotification('Rider ID not found', 'error');
        return;
      }

      // Add loading state
      card.classList.add('loading');
      subtitle.textContent = 'Updating...';

      // Make AJAX request
      fetch(`/riders/toggle-llicense/${riderId}`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({})
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            // Update card appearance
            updateCardStatus(card, 'llicense', isChecked);
            showNotification(data.message, 'success');
          } else {
            showNotification('Error: ' + data.message, 'error');
            // Revert checkbox state on error
            this.checked = !isChecked;
            updateCardStatus(card, 'llicense', !isChecked);
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('An error occurred while updating learning license status', 'error');
          // Revert checkbox state on error
          this.checked = !isChecked;
          updateCardStatus(card, 'llicense', !isChecked);
        })
        .finally(() => {
          // Remove loading state
          card.classList.remove('loading');
        });
    });

    // Add change event listener to walker checkbox
    const walkerCheckbox = document.querySelector('.walker-checkbox');
    if (walkerCheckbox) {
      walkerCheckbox.addEventListener('change', function() {
        const riderId = this.getAttribute('data-rider-id');
        const isChecked = this.checked;
        const card = this.closest('.status-card');
        const subtitle = card.querySelector('.status-subtitle');

        if (!riderId) {
          showNotification('Rider ID not found', 'error');
          return;
        }

        // Add loading state
        card.classList.add('loading');
        subtitle.textContent = 'Updating...';

        fetch(`/riders/toggle-walker/${riderId}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateCardStatus(card, 'walker', isChecked);
              // Update header badge if present
              const designationBadge = document.querySelector('.badge.bg-label-primary');
              if (designationBadge) {
                designationBadge.textContent = data.designation ?? (isChecked ? 'Walker' : designationBadge.textContent);
              }
              showNotification(data.message, 'success');
            } else {
              showNotification('Error: ' + data.message, 'error');
              this.checked = !isChecked;
              updateCardStatus(card, 'walker', !isChecked);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating designation', 'error');
            this.checked = !isChecked;
            updateCardStatus(card, 'walker', !isChecked);
          })
          .finally(() => {
            card.classList.remove('loading');
          });
      });
    }

    // Add change event listener to MOL checkbox
    const molCheckbox = document.querySelector('.mol-checkbox');
    if (molCheckbox) {
      molCheckbox.addEventListener('change', function() {
        const riderId = this.getAttribute('data-rider-id');
        const isChecked = this.checked;
        const card = this.closest('.status-card');
        const subtitle = card.querySelector('.status-subtitle');

        if (!riderId) {
          showNotification('Rider ID not found', 'error');
          return;
        }

        // Add loading state
        card.classList.add('loading');
        subtitle.textContent = 'Updating...';

        fetch(`/riders/toggle-mol/${riderId}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateCardStatus(card, 'mol', isChecked);
              // Update header badge if present
              const designationBadge = document.querySelector('.badge.bg-label-primary');
              if (designationBadge) {
                designationBadge.textContent = data.designation ?? (isChecked ? 'MOL' : designationBadge.textContent);
              }
              showNotification(data.message, 'success');
            } else {
              showNotification('Error: ' + data.message, 'error');
              this.checked = !isChecked;
              updateCardStatus(card, 'mol', !isChecked);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating designation', 'error');
            this.checked = !isChecked;
            updateCardStatus(card, 'mol', !isChecked);
          })
          .finally(() => {
            card.classList.remove('loading');
          });
      });
    }

    // Add change event listener to PRO checkbox
    const proCheckbox = document.querySelector('.pro-checkbox');
    if (proCheckbox) {
      proCheckbox.addEventListener('change', function() {
        const riderId = this.getAttribute('data-rider-id');
        const isChecked = this.checked;
        const card = this.closest('.status-card');
        const subtitle = card.querySelector('.status-subtitle');

        if (!riderId) {
          showNotification('Rider ID not found', 'error');
          return;
        }

        // Add loading state
        card.classList.add('loading');
        subtitle.textContent = 'Updating...';

        fetch(`/riders/toggle-pro/${riderId}`, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify({})
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              updateCardStatus(card, 'pro', isChecked);
              // Update header badge if present
              const designationBadge = document.querySelector('.badge.bg-label-primary');
              if (designationBadge) {
                designationBadge.textContent = data.designation ?? (isChecked ? 'PRO' : designationBadge.textContent);
              }
              showNotification(data.message, 'success');
            } else {
              showNotification('Error: ' + data.message, 'error');
              this.checked = !isChecked;
              updateCardStatus(card, 'pro', !isChecked);
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred while updating designation', 'error');
            this.checked = !isChecked;
            updateCardStatus(card, 'pro', !isChecked);
          })
          .finally(() => {
            card.classList.remove('loading');
          });
      });
    }

    // Function to update card status
    function updateCardStatus(card, type, isActive) {
      const subtitle = card.querySelector('.status-subtitle');

      if (type === 'absconder') {
        if (isActive) {
          card.classList.add('active');
          subtitle.textContent = 'Marked as Absconder';
        } else {
          card.classList.remove('active');
          subtitle.textContent = 'Not Absconder';
        }
      } else if (type === 'flowup') {
        if (isActive) {
          card.classList.add('active');
          subtitle.textContent = 'Follow Up Required';
        } else {
          card.classList.remove('active');
          subtitle.textContent = 'No Follow Up';
        }
      } else if (type === 'llicense') {
        if (isActive) {
          card.classList.add('active');
          subtitle.textContent = 'Learning License Required';
        } else {
          card.classList.remove('active');
          subtitle.textContent = 'No Learning License';
        }
      } else if (type === 'walker') {
        if (isActive) {
          card.classList.add('active');
          subtitle.textContent = 'Designation is Walker';
        } else {
          card.classList.remove('active');
          subtitle.textContent = 'Not Walker';
        }
      } else if (type === 'mol') {
        if (isActive) {
          card.classList.add('active');
          subtitle.textContent = 'Designation is MOL';
        } else {
          card.classList.remove('active');
          subtitle.textContent = 'Not MOL';
        }
      } else if (type === 'pro') {
        if (isActive) {
          card.classList.add('active');
          subtitle.textContent = 'Designation is PRO';
        } else {
          card.classList.remove('active');
          subtitle.textContent = 'Not PRO';
        }
      }
    }

    // Function to show notifications
    function showNotification(message, type) {
      // Create notification element
      const notification = document.createElement('div');
      notification.className = `notification notification-${type}`;
      notification.innerHTML = `
        <div class="notification-content">
          <i class="ti ti-${type === 'success' ? 'check' : 'x'}"></i>
          <span>${message}</span>
        </div>
      `;

      // Add styles
      notification.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        animation: slideIn 0.3s ease;
        max-width: 300px;
      `;

      // Add to page
      document.body.appendChild(notification);

      // Remove after 3 seconds
      setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
          if (notification.parentNode) {
            notification.parentNode.removeChild(notification);
          }
        }, 300);
      }, 3000);
    }

    // Add CSS for notifications
    const style = document.createElement('style');
    style.textContent = `
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
    `;
    document.head.appendChild(style);

    // Add responsive navigation styles
    const navStyle = document.createElement('style');
    navStyle.textContent = `
      /* Responsive Navigation Styles */
      .nav-align-top {
        width: 100%;
        max-width: 100%;
      }
      
      .nav-align-top .card {
        width: 100%;
        max-width: 100%;
        border-radius: 0.5rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      }
      
      .nav-align-top .card-body {
        padding: 0.75rem 1rem !important;
      }
      
      #mainNavigation {
        display: flex;
        flex-wrap: nowrap;
        overflow: hidden;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 0.25rem;
      }
      
      #mainNavigation .nav-item {
        flex-shrink: 0;
        white-space: nowrap;
        display: flex;
      }
      
      #mainNavigation .nav-link {
        padding: 0.375rem 0.75rem;
        font-size: 0.875rem;
        border-radius: 0.375rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        transition: all 0.2s ease;
      }
      
      .overflow-nav-item {
        display: flex;
        align-items: center;
      }
      
      .overflow-nav-item.active {
        background-color: var(--bs-primary);
        color: white;
      }
      
      .overflow-nav-item i {
        width: 16px;
        height: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
      }
      
      .permanent-action {
        border-top: 1px solid var(--bs-border-color);
        margin-top: 0.25rem;
        padding-top: 0.5rem;
      }
      
      .permanent-action:first-of-type {
        border-top: none;
        margin-top: 0;
        padding-top: 0.25rem;
      }
      
      /* Action items styling */
      .nav-action-item .nav-link {
        background-color: var(--bs-secondary-bg);
        border: 1px solid var(--bs-border-color);
        color: var(--bs-secondary-color);
        transition: all 0.2s ease;
      }
      
      .nav-action-item .nav-link:hover {
        background-color: var(--bs-primary);
        color: white;
        border-color: var(--bs-primary);
        transform: translateY(-1px);
      }
      
      /* Let JavaScript handle responsive behavior dynamically */
      .nav-item {
        display: flex !important; /* Override any CSS hiding */
      }
      
      /* Dropdown styling */
      #actiondropdown {
        flex-shrink: 0 !important;
        border: 1px solid var(--bs-border-color);
        background: white;
        color: var(--bs-body-color);
        // display: none; /* Initially hidden */
        align-items: center;
        justify-content: center;
      }
      
      #actiondropdown:hover {
        background-color: var(--bs-light);
        border-color: var(--bs-primary);
      }
      
      .dropdown-menu {
        max-height: 400px;
        overflow-y: auto;
        border-radius: 0.5rem;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        border: 1px solid var(--bs-border-color);
        margin-top: 0.25rem;
        min-width: 180px;
      }
      
      /* Ensure dropdown stays within viewport */
      .dropdown-menu-end {
        right: 0 !important;
        left: auto !important;
      }
      
      /* Loading state for navigation */
      .nav-loading {
        opacity: 0.7;
        pointer-events: none;
      }
      
      /* Better spacing for icons in dropdown */
      .dropdown-item i {
        width: 20px;
        text-align: center;
      }
      
      /* Highlight active items in dropdown */
      .dropdown-item.active {
        background-color: var(--bs-primary) !important;
        color: white !important;
      }
      
      /* Make navigation more compact on smaller screens */
      @media (max-width: 768px) {
        .nav-align-top .card-body {
          padding: 0.5rem !important;
        }
        
        #mainNavigation .nav-link {
          padding: 0.25rem 0.5rem !important;
          font-size: 0.8rem;
        }
        
        #mainNavigation .nav-link i {
          font-size: 0.8rem !important;
          margin-right: 0.25rem !important;
        }
        
        .nav-action-item .nav-link {
          padding: 0.25rem 0.5rem !important;
          font-size: 0.75rem;
        }
        
        #actiondropdown {
          padding: 0.25rem 0.5rem !important;
        }
      }
      
      /* Extra small screens - only essential items */
      @media (max-width: 480px) {
        .nav-align-top .card-body {
          padding: 0.25rem 0.5rem !important;
        }
        
        #mainNavigation .nav-link {
          padding: 0.25rem 0.4rem !important;
          font-size: 0.75rem;
        }
        
        #mainNavigation .nav-link i {
          margin-right: 0.1rem !important;
        }
        
        .dropdown-menu {
          min-width: 160px;
          font-size: 0.8rem;
        }
      }
      
      /* Very small screens */
      @media (max-width: 380px) {
        #mainNavigation .nav-link {
          padding: 0.2rem 0.3rem !important;
          font-size: 0.7rem;
        }
        
        #mainNavigation .nav-link i {
          display: none; /* Hide icons on very small screens */
        }
        
        #actiondropdown {
          padding: 0.2rem 0.4rem !important;
        }
      }
      
      /* Ensure smooth transitions */
      .nav-item {
        transition: all 0.3s ease;
      }
      
      /* Visual separator between nav and action items */
      .nav-action-item:first-of-type {
        margin-left: 0.5rem;
        position: relative;
      }
      
      .nav-action-item:first-of-type::before {
        content: '';
        position: absolute;
        left: -0.25rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1px;
        height: 20px;
        background-color: var(--bs-border-color);
      }
      
      /* Improve dropdown visibility on mobile */
      @media (max-width: 576px) {
        .dropdown-menu {
          right: 0 !important;
          left: auto !important;
          min-width: 200px;
          font-size: 0.875rem;
        }
        
        .dropdown-item {
          padding: 0.5rem 1rem;
        }
        
        .dropdown-header {
          font-size: 0.75rem;
          padding: 0.25rem 1rem;
        }
      }
    `;
    document.head.appendChild(navStyle);
  });
</script>

@include('riders.action-buttons')

@endsection