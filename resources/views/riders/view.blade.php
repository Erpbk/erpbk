@extends('layouts.app', ['hideModuleTopBarSlider' => true])
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

  .vacation-card.active {
    background: linear-gradient(135deg, #00bcd4 0%, #009688 100%);
    border-color: #009688;
  }

  .vacation-card .status-checkbox:checked+.toggle-switch {
    background: #009688;
  }

  .pro-card.active {
    background: linear-gradient(135deg, #2196f3 0%, #00bcd4 100%);
    border-color: #2196f3;
  }

  .pro-card .status-checkbox:checked+.toggle-switch {
    background: #2196f3;
  }

  .cancel-card.active {
    background: linear-gradient(135deg, #607d8b 0%, #455a64 100%);
    border-color: #607d8b;
  }

  .cancel-card .status-checkbox:checked+.toggle-switch {
    background: #607d8b;
  }

  /* Disabled state: only one option selectable at a time */
  .status-card.disabled {
    opacity: 0.6;
    pointer-events: none;
    cursor: not-allowed;
  }

  .status-card.disabled .toggle-switch {
    cursor: not-allowed;
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
$rider = $riders ?? $rider ?? null;
if(isset($riders)){
$result = $riders->toArray();
}
if(isset($result)){
$account = App\Models\ExpenseAccount::where('rider_id', $result['id'])->first();
}
$companySlug = request()->route('company_slug');

@endphp
<div class="row" style="">
  <div class="col-xl-2 col-md-3 col-lg-5 order-1 order-md-0">
    <!-- User Card -->
    <div class="card mb-6" style="border-radius: 25px 25px 0px 0px;">
      <div class="card-header p-0" style="border-radius: 25px 25px 0px 0px; height: 230px; position: relative; background-image: url({{ asset('assets/img/user_back.jpg') }}); background-size: cover;">
        @isset($result)
        <div class="profile-img">
          @php
          $profile = company_table('files')
          ->where('type', 'rider')
          ->where('type_id', $result['id'])
          ->where(function($query) {
          $query->where('name', 'LIKE', '%photo%')
          ->orWhere('name', 'LIKE', '%Photo%')
          ->orWhere('name', 'LIKE', '%picture%')
          ->orWhere('name', 'LIKE', '%Picture%')
          ->orWhere('name', 'LIKE', '%profile%')
          ->orWhere('name', 'LIKE', '%Profile%');
          })
          ->first();
          if(@$result['image_name'])
          $image_name = storage_url('profile/'.$result['image_name']);
          elseif (isset($profile))
          $image_name = storage_url($profile->type .'/'. $profile->type_id .'/'. $profile->file_name);
          else
          $image_name = asset('uploads/default.png');

          @endphp
          <img src="{{ $image_name}}" id="output" width="270" class="profile-user-img img-fluid" />
        </div>
        @endisset
      </div>
      <div class="card-body pt-12">
        @isset($result)
        @php
        $riderTopViewCategories = \App\Models\RiderTopCategory::with(['options' => function($q){
        $q->where('is_active', 1)->orderBy('display_order')->orderBy('id');
        }])->where('show_in_view_cards', 1)->orderBy('display_order')->orderBy('id')->get();
        $riderStatusLabel = trim((string)($result['rider_status'] ?? ''));
        $employmentBadge = \App\Models\Riders::employmentStatusDisplay($result['status'] ?? null);
        $displayStatusLabel = $employmentBadge['label'];
        @endphp
        @endisset
        <div class="user-avatar-section">
          <div class=" d-flex align-items-center flex-column">
            <div class="col-md-12 mt-2">
              <div class="d-flex align-items-baseline">
                <div class="user-info" style="width: 100%;">
                  <div class="mt-2" style="width: 100%;display: flex;gap: 10px; margin-bottom: 10px;">
                    <span class="badge bg-label-primary" id="rider-designation-badge">@isset($result){{ $riderStatusLabel !== '' ? $riderStatusLabel : ($result['designation'] ?? 'not-set') }}@endisset</span>
                    <span class="badge {{ $employmentBadge['badge'] }}" id="rider-status-value-badge">@isset($result){{ $displayStatusLabel ?? 'Inactive' }}@endisset</span>
                  </div>
                  <span>{{ $result['rider_id'] ?? 'not-set' }}</span>
                  <h6>
                    <b>
                      @isset($result)
                      {{ $result['name'] ?? 'not-set' }}
                      @endisset
                    </b>
                  </h6>
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
              <form action="{{ route('rider_picture_upload', ['company_slug' => request()->route('company_slug'), 'id' => $result['id']]) }}" method="POST" enctype="multipart/form-data" id="formajax2">
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
                  <span>Email:</span><br> <b class="float-right">@isset($result){{$result['email']??'not-set'}}@endisset</b>
                </div>
              </li>
              <li class="list-group-item pb-1 mt-3 user_list d-flex align-items-center">
                <div class="icons me-2">
                  <i class="ti ti-phone ti-sm me-1_5"></i>
                </div>
                <div class="user_list_content mt-2">
                  <span>WhatsApp:</span><br>
                  <b class="float-right">

                    @if($rider?->sim?->number)
                    @php
                    $phone = preg_replace('/[^0-9]/', '', $rider?->sim?->number ?? '');
                    if (strpos($phone, '971') === 0) { $whatsappNumber = '+' . $phone; $displayNumber = '0' . substr($phone, 3); }
                    else { $whatsappNumber = '+971' . ltrim($phone, '0'); $displayNumber = '0' . ltrim($phone, '0'); }
                    @endphp
                    <a href="https://wa.me/{{ $whatsappNumber }}"
                      target="_blank"
                      class="text-success">
                      {{ $displayNumber }}
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
                  <span>Nationality:</span><br> <b class="float-right">@isset($result){{company_table('countries')->where('id' , $result['nationality'])->first()->name ??'not-set'}}@endisset</b>
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
          <div class="d-flex flex-wrap justify-content-start gap-2 gap-md-3" id="rider-status-cards">
            @php $cardIndex = 0; @endphp
            @foreach($riderTopViewCategories as $category)
            @php $riderTopColumn = trim((string)($category->rider_column ?? '')); @endphp
            @if($riderTopColumn === 'status')
            @continue
            @endif
            @foreach($category->options as $option)
            @php
            $isSelected = $riderTopColumn !== ''
            && array_key_exists($riderTopColumn, $result)
            && (string)($result[$riderTopColumn] ?? '') === (string)$option->name;
            $cardKey = 'rider_top_option_' . $option->id;
            $icons = ['ti ti-bell', 'ti ti-user-check', 'ti ti-star', 'ti ti-flag'];
            @endphp
            <div class="status-card rider-top-option-card {{ $isSelected ? 'active' : '' }}"
              data-rider-id="{{ $result['id'] ?? '' }}"
              data-option-id="{{ $option->id }}"
              data-column="{{ $riderTopColumn }}"
              data-value="{{ $option->name }}"
              data-category="{{ $category->name }}"
              data-type="{{ $cardKey }}">
              <div class="d-flex justify-content-between">
                <div class="status-icon">
                  <i class="{{ $icons[$cardIndex % count($icons)] }}"></i>
                </div>
                <div class="status-content">
                  <div class="status-title">{{ $option->name }}</div>
                  <div class="status-subtitle">{{ $isSelected ? 'Assigned to rider' : $category->name }}</div>
                </div>
              </div>
              <div class="status-toggle">
                <input type="checkbox"
                  class="status-checkbox rider-top-option-checkbox"
                  id="rider-top-option-{{ $option->id }}-{{ $result['id'] ?? '' }}"
                  data-rider-id="{{ $result['id'] ?? '' }}"
                  data-option-id="{{ $option->id }}"
                  data-column="{{ $riderTopColumn }}"
                  data-value="{{ $option->name }}"
                  {{ $isSelected ? 'checked' : '' }}>
                <label for="rider-top-option-{{ $option->id }}-{{ $result['id'] ?? '' }}" class="toggle-switch">
                  <span class="toggle-slider"></span>
                </label>
              </div>
            </div>
            @php $cardIndex++; @endphp
            @endforeach
            @endforeach
          </div>

          <div class="modal fade" id="riderTopOptionDateModal" tabindex="-1" aria-labelledby="riderTopOptionDateModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
              <div class="modal-content">
                <div class="modal-header">
                  <h5 class="modal-title" id="riderTopOptionDateModalLabel">Confirm status</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                  <p class="mb-3">Choose the effective date for <strong id="riderTopOptionModalStatusName">—</strong>. Dates after today are not allowed.</p>
                  <label for="riderTopOptionEffectiveDate" class="form-label">Effective date <span class="text-danger">*</span></label>
                  <input type="date" class="form-control" id="riderTopOptionEffectiveDate" required autocomplete="off">
                </div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                  <button type="button" class="btn btn-primary" id="riderTopOptionDateSave">Save</button>
                </div>
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
                  <a class="nav-link @if(Route::is('riders.show') || Route::is('riders.create')) active @endif"
                    href="@isset($result['id']){{route('riders.show',$result['id'])}}@else#@endif">
                    <i class="ti ti-user-check ti-sm me-1_5"></i>Information
                  </a>
                </li>

                @isset($result)
                @can('timeline_view')
                <li class="nav-item nav-priority-2">
                  <a class="nav-link @if(Route::is('rider.timeline')) active @endif"
                    href="{{route('rider.timeline',$result['id'])}}">
                    <i class="ti ti-timeline ti-sm me-1_5"></i>Timeline
                  </a>
                </li>
                <li class="nav-item nav-priority-2">
                  <a class="nav-link @if(Route::is('rider.history')) active @endif"
                    href="{{route('rider.history',$result['id'])}}">
                    <i class="ti ti-history ti-sm me-1_5"></i>Rider history
                  </a>
                </li>
                @endcan

                @can('rider_document')
                <li class="nav-item nav-priority-3">
                  <a class="nav-link @if(Route::is('rider.files')) active @endif"
                    href="{{route('rider.files',$result['id'])}}">
                    <i class="ti ti-file-upload ti-sm me-1_5"></i>Files
                  </a>
                </li>
                @endcan

                @can('riderinvoice_view')
                <li class="nav-item nav-priority-4">
                  <a class="nav-link @if(Route::is('rider.invoices')) active @endif"
                    href="{{route('rider.invoices',$result['id'])}}">
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Invoices
                  </a>
                </li>
                @endcan

                @if(\App\Support\VisaExpenseAccess::visibleInRiderTab())
                @if(!empty($riders))
                @php
                $account = company_table('expense_accounts')->where('rider_id', $result['id'])->first();
                @endphp
                @if($account)
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(Route::is('VisaExpense.generatentries')) active @endif"
                    href="{{ \App\Support\VisaRenewalCategoryService::generatentriesUrl($account->id, $account->rider_id) }}">
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Visa Expense
                  </a>
                </li>
                @endif
                @endif
                @endif

                @can('licenseexpense_view')
                @if(\App\Support\CompanyModuleVisibility::enabled('license_expense'))
                @if(!empty($riders))
                @php
                $licenseExpenseAccount = company_table('expense_accounts')->where('rider_id', $result['id'])->first();
                @endphp
                @if($licenseExpenseAccount)
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(Route::is('LicenseExpense.generatentries')) active @endif"
                    href="{{ route('LicenseExpense.generatentries', $licenseExpenseAccount->id) }}">
                    <i class="ti ti-steering-wheel ti-sm me-1_5"></i>License Expense
                  </a>
                </li>
                @endif
                @endif
                @endif
                @endcan

                @can('riderinventory_view')
                @if(\App\Support\CompanyModuleVisibility::enabled('rider_inventory'))
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(Route::is('rider.inventory')) active @endif"
                    href="{{ route('rider.inventory', $result['id']) }}">
                    <i class="ti ti-package ti-sm me-1_5"></i>Rider Inventory
                  </a>
                </li>
                @endif
                @endcan

                @can('legalcase_view')
                @if(\App\Support\CompanyModuleVisibility::enabled('legal_case'))
                @php
                $legalCaseAccount = company_table('legal_case_accounts')->where('rider_id', $result['id'])->first();
                @endphp
                @if($legalCaseAccount)
                <li class="nav-item nav-priority-5">
                  <a class="nav-link @if(Route::is('LegalCase.generatentries')) active @endif"
                    href="{{ route('LegalCase.generatentries', $legalCaseAccount->id) }}">
                    <i class="ti ti-scale ti-sm me-1_5"></i>Legal Case
                  </a>
                </li>
                @endif
                @endif
                @endcan

                @can('item_view')
                <li class="nav-item nav-priority-6">
                  <a class="nav-link @if(Route::is('rider.items')) active @endif"
                    href="{{route('rider.items',$result['id'])}}">
                    <i class="ti ti-cash-banknote ti-sm me-1"></i>Salary
                  </a>
                </li>
                @endcan

                @can('gn_ledger')
                <li class="nav-item nav-priority-7">
                  <a class="nav-link @if(Route::is('rider.ledger')) active @endif"
                    href="{{route('rider.ledger',$result['id'])}}">
                    <i class="ti ti-file ti-sm me-1_5"></i>Ledger
                  </a>
                </li>
                @endcan

                @can('activity_view')
                <li class="nav-item nav-priority-8">
                  <a class="nav-link @if(Route::is('rider.activities')) active @endif"
                    href="{{route('rider.activities',$result['id'])}}">
                    <i class="ti ti-motorbike ti-sm me-1_5"></i>Activities
                  </a>
                </li>
                @endcan

                @can('email_view')
                <li class="nav-item nav-priority-9">
                  <a class="nav-link @if(Route::is('rider.emails')) active @endif"
                    href="{{route('rider.emails',$result['id'])}}">
                    <i class="ti ti-mail ti-sm me-1_5"></i>Emails
                  </a>
                </li>
                @endcan

                <!-- Action items with lower priority -->
                @canany(['advanceloan_create','cod_create','penality_create','payment_create','vendorcharges_create'])
                <li class="nav-item nav-priority-10">
                  <a href="javascript:void(0);"
                    data-action="{{ route('riders.voucher', ['company_slug' => $companySlug, 'id' => $result['id']]) }}"
                    data-size="xl" data-title="Voucher"
                    class='nav-link show-modal'>
                    <i class="ti ti-file-invoice ti-sm me-1_5"></i>Voucher
                  </a>
                </li>
                @endcanany

                @can('incentives_create')
                <li class="nav-item nav-priority-11">
                  <a href="javascript:void(0);"
                    data-action="{{ route('riders.incentive', ['company_slug' => $companySlug, 'id' => $result['id']]) }}"
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

    // Rider Top view cards — per-column updates (matches employee profile cards)
    function syncRiderTopOptionCards(column) {
      const container = document.getElementById('rider-status-cards');
      if (!container) return;
      container.querySelectorAll('.rider-top-option-card[data-column="' + column + '"]').forEach((card) => {
        const checkbox = card.querySelector('.rider-top-option-checkbox');
        const subtitle = card.querySelector('.status-subtitle');
        const isActive = !!(checkbox && checkbox.checked);
        card.classList.toggle('active', isActive);
        if (subtitle) {
          const categoryName = card.getAttribute('data-category') || 'Not assigned';
          subtitle.textContent = isActive ? 'Assigned to rider' : categoryName;
        }
      });
    }

    function refreshRiderSidebarBadges(data) {
      const designationBadge = document.getElementById('rider-designation-badge');
      const statusValueBadge = document.getElementById('rider-status-value-badge');
      if (designationBadge) {
        if (data.column === 'rider_status') {
          designationBadge.textContent = data.rider_status || (data.value || 'not-set');
        } else if (data.value) {
          designationBadge.textContent = data.value;
        } else if (!data.rider_status) {
          designationBadge.textContent = 'not-set';
        }
      }
      if (statusValueBadge) {
        const label = data.employment_label || 'Inactive';
        statusValueBadge.textContent = label;
        statusValueBadge.className = 'badge ' + (data.employment_badge || 'bg-label-danger');
      }
    }

    function submitRiderTopOptionRequest(checkbox, requestOptionId, effectiveDate, isClear) {
      const riderId = checkbox.getAttribute('data-rider-id');
      const column = checkbox.getAttribute('data-column');
      const card = checkbox.closest('.status-card');
      if (!riderId || !card || !column) {
        showNotification('Rider ID not found', 'error');
        return Promise.resolve(false);
      }
      const subtitle = card.querySelector('.status-subtitle');
      card.classList.add('loading');
      if (subtitle) subtitle.textContent = 'Updating...';

      const setOptionUrlTemplate = "{{ route('riders.setRiderTopOption', ['id' => '__RID__']) }}";
      const setOptionUrl = setOptionUrlTemplate.replace('__RID__', riderId);
      const body = isClear ?
        {
          clear_option_id: requestOptionId
        } :
        {
          option_id: requestOptionId,
          effective_date: effectiveDate
        };

      return fetch(setOptionUrl, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify(body)
        })
        .then(async (response) => {
          const data = await response.json().catch(() => ({}));
          if (response.ok && data.success) {
            if (isClear) {
              checkbox.checked = false;
            } else {
              statusCardsContainer.querySelectorAll('.rider-top-option-card[data-column="' + column + '"]').forEach((c) => {
                const cb = c.querySelector('.rider-top-option-checkbox');
                if (cb && cb !== checkbox) cb.checked = false;
              });
              checkbox.checked = true;
            }
            syncRiderTopOptionCards(column);
            refreshRiderSidebarBadges(data);
            showNotification(data.message, 'success');
            return true;
          }
          let msg = data.message || 'Unknown error';
          if (data.errors) {
            const flat = Object.values(data.errors).flat();
            if (flat.length) msg = flat.join(' ');
          }
          showNotification('Error: ' + msg, 'error');
          return false;
        })
        .catch((error) => {
          console.error('Error:', error);
          showNotification('An error occurred while updating status', 'error');
          return false;
        })
        .finally(() => {
          card.classList.remove('loading');
        });
    }

    const statusCardsContainer = document.getElementById('rider-status-cards');
    const riderTopOptionDateModalEl = document.getElementById('riderTopOptionDateModal');
    const riderTopOptionModal = riderTopOptionDateModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal ?
      new bootstrap.Modal(riderTopOptionDateModalEl) :
      null;
    let pendingRiderTopOptionCheckbox = null;
    let riderTopOptionRequestInFlight = false;

    function riderTopOptionTodayYmd() {
      const t = new Date();
      const y = t.getFullYear();
      const m = String(t.getMonth() + 1).padStart(2, '0');
      const d = String(t.getDate()).padStart(2, '0');
      return y + '-' + m + '-' + d;
    }

    if (statusCardsContainer) {
      statusCardsContainer.addEventListener('click', function(e) {
        const toggleArea = e.target.closest('.status-toggle');
        if (!toggleArea || !statusCardsContainer.contains(toggleArea)) return;
        const checkbox = toggleArea.querySelector('.rider-top-option-checkbox');
        if (!checkbox) return;

        const optionId = parseInt(checkbox.getAttribute('data-option-id') || '0', 10);
        if (!optionId) return;

        if (checkbox.checked) {
          e.preventDefault();
          e.stopPropagation();
          if (riderTopOptionRequestInFlight) return;
          riderTopOptionRequestInFlight = true;
          submitRiderTopOptionRequest(checkbox, optionId, null, true).then((ok) => {
            if (!ok) {
              checkbox.checked = true;
              syncRiderTopOptionCards(checkbox.getAttribute('data-column'));
            }
          }).finally(() => {
            riderTopOptionRequestInFlight = false;
          });
          return;
        }

        e.preventDefault();
        e.stopPropagation();
        pendingRiderTopOptionCheckbox = checkbox;
        const card = checkbox.closest('.status-card');
        const titleEl = card ? card.querySelector('.status-title') : null;
        const nameEl = document.getElementById('riderTopOptionModalStatusName');
        if (nameEl) nameEl.textContent = (titleEl && titleEl.textContent) ? titleEl.textContent.trim() : 'Status';
        const dateInput = document.getElementById('riderTopOptionEffectiveDate');
        const today = riderTopOptionTodayYmd();
        if (dateInput) {
          dateInput.max = today;
          dateInput.value = today;
        }
        riderTopOptionModal ? riderTopOptionModal.show() : (function() {
          showNotification('Date dialog is unavailable', 'error');
          pendingRiderTopOptionCheckbox = null;
        })();
      }, true);

      if (riderTopOptionDateModalEl) {
        riderTopOptionDateModalEl.addEventListener('hidden.bs.modal', function() {
          pendingRiderTopOptionCheckbox = null;
        });
      }

      document.getElementById('riderTopOptionDateSave')?.addEventListener('click', function() {
        const checkbox = pendingRiderTopOptionCheckbox;
        if (!checkbox) return;
        const dateInput = document.getElementById('riderTopOptionEffectiveDate');
        const effectiveDate = dateInput ? dateInput.value : '';
        const today = riderTopOptionTodayYmd();
        if (!effectiveDate) {
          showNotification('Please select an effective date', 'error');
          return;
        }
        if (effectiveDate > today) {
          showNotification('Future dates are not allowed', 'error');
          return;
        }
        const optionId = parseInt(checkbox.getAttribute('data-option-id') || '0', 10);
        submitRiderTopOptionRequest(checkbox, optionId, effectiveDate, false).then((ok) => {
          if (ok) {
            riderTopOptionModal ? riderTopOptionModal.hide() : null;
          }
        });
      });

      statusCardsContainer.addEventListener('change', function(e) {
        const checkbox = e.target;
        if (!checkbox.classList.contains('rider-top-option-checkbox')) return;
        if (checkbox.checked || riderTopOptionRequestInFlight) return;

        const optionId = parseInt(checkbox.getAttribute('data-option-id') || '0', 10);
        if (!optionId) {
          showNotification('Option ID not found', 'error');
          checkbox.checked = true;
          return;
        }

        riderTopOptionRequestInFlight = true;
        submitRiderTopOptionRequest(checkbox, optionId, null, true).then((ok) => {
          if (!ok) {
            checkbox.checked = true;
            syncRiderTopOptionCards(checkbox.getAttribute('data-column'));
          }
        }).finally(() => {
          riderTopOptionRequestInFlight = false;
        });
      });
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