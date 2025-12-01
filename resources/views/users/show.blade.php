@extends('layouts.app')
@section('title', 'Account')

@section('content')
<h4 class="py-3 mb-4">
  <span class="text-muted fw-light">User Account
</h4>
<div class="row">
  <!-- User Sidebar -->
  <div class="col-xl-4 col-lg-5 col-md-5 order-1 order-md-0">
    <!-- User Card -->
    <div class="card mb-4">
      <div class="card-body">
        <div class="user-avatar-section">
          <div class=" d-flex align-items-center flex-column">
            <img class="img-fluid rounded mb-3 pt-1 mt-4" src="{{ asset('assets/img/avatars/1.png') }}" height="100" width="100" alt="User avatar" />
            <div class="user-info text-center">
              <h4 class="mb-2">{{$user->name}}</h4>
              <span class="badge bg-label-secondary mt-1">{{ $user->roles->pluck('name','name')->first()}}</span>
            </div>
          </div>
        </div>
        <div class="d-flex justify-content-around flex-wrap mt-3 pt-3 pb-4 border-bottom">
          <div class="d-flex align-items-start me-4 mt-3 gap-2">
            <span class="badge bg-label-primary p-2 rounded"><i class='ti ti-checkbox ti-sm'></i></span>
            <div>
              <p class="mb-0 fw-medium">1.23k</p>
              <small>Tasks Done</small>
            </div>
          </div>
          <div class="d-flex align-items-start mt-3 gap-2">
            <span class="badge bg-label-primary p-2 rounded"><i class='ti ti-briefcase ti-sm'></i></span>
            <div>
              <p class="mb-0 fw-medium">568</p>
              <small>Projects Done</small>
            </div>
          </div>
        </div>
        <p class="mt-4 small text-uppercase text-muted">Details</p>
        <div class="info-container">
          <ul class="list-unstyled">
            <li class="mb-2">
              <span class="fw-medium me-1">Username:</span>
              <span>{{$user->email}}</span>
            </li>
            <li class="mb-2 pt-1">
              <span class="fw-medium me-1">Email:</span>
              <span>{{$user->email}}</span>
            </li>
            <li class="mb-2 pt-1">
              <span class="fw-medium me-1">Status:</span>
              <span class="badge bg-label-success">Active</span>
            </li>
            <li class="mb-2 pt-1">
              <span class="fw-medium me-1">Role:</span>
              <span>{{ $user->roles->pluck('name','name')->first()}}</span>
            </li>
           
            <li class="mb-2 pt-1">
              <span class="fw-medium me-1">Contact:</span>
              <span>{{$user->phone}}</span>
            </li>
          
            <li class="pt-1">
              <span class="fw-medium me-1">Country:</span>
              <span>{{$user->country}}</span>
            </li>
          </ul>
          <div class="d-flex">
            <a href="javascript:;" class="btn btn-primary me-3 show-modal" style="float: left;" data-action="{{ route('users.edit', $user->id) }}" data-title="Edit User" data-size="lg">Edit</a>
{{--             <a href="javascript:;" class="btn btn-label-danger suspend-user">Suspended</a>
 --}}          </div>
        </div>
      </div>
    </div>
    <!-- /User Card -->
    <!-- Plan Card -->
    {{-- <div class="card mb-4">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-start">
          <span class="badge bg-label-primary">Standard</span>
          <div class="d-flex justify-content-center">
            <sup class="h6 pricing-currency mt-3 mb-0 me-1 text-primary fw-normal">$</sup>
            <h1 class="mb-0 text-primary">99</h1>
            <sub class="h6 pricing-duration mt-auto mb-2 text-muted fw-normal">/month</sub>
          </div>
        </div>
        <ul class="ps-3 g-2 my-3">
          <li class="mb-2">10 Users</li>
          <li class="mb-2">Up to 10 GB storage</li>
          <li>Basic Support</li>
        </ul>
        <div class="d-flex justify-content-between align-items-center mb-1 fw-medium text-heading">
          <span>Days</span>
          <span>65% Completed</span>
        </div>
        <div class="progress mb-1" style="height: 8px;">
          <div class="progress-bar" role="progressbar" style="width: 65%;" aria-valuenow="65" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <span>4 days remaining</span>
        <div class="d-grid w-100 mt-4">
          <button class="btn btn-primary" data-bs-target="#upgradePlanModal" data-bs-toggle="modal">Upgrade Plan</button>
        </div>
      </div>
    </div> --}}
    <!-- /Plan Card -->
  </div>
  <!--/ User Sidebar -->


  <!-- User Content -->
  <div class="col-xl-8 col-lg-7 col-md-7 order-0 order-md-1">
    <!-- User Pills -->
    <ul class="nav nav-pills flex-column flex-md-row mb-4">
      <li class="nav-item"><a class="nav-link active" href="javascript:void(0);"><i class="ti ti-user-check ti-xs me-1"></i>Account</a></li>
      <li class="nav-item"><a class="nav-link" href="#"><i class="ti ti-lock ti-xs me-1"></i>Security</a></li>
      <li class="nav-item"><a class="nav-link" href="#"><i class="ti ti-currency-dollar ti-xs me-1"></i>Billing & Plans</a></li>
      <li class="nav-item"><a class="nav-link" href="#"><i class="ti ti-bell ti-xs me-1"></i>Notifications</a></li>
      <li class="nav-item"><a class="nav-link" href="#"><i class="ti ti-link ti-xs me-1"></i>Connections</a></li>
    </ul>
    <!--/ User Pills -->

    <!-- Project table -->
    <div class="card mb-4">
      <h5 class="card-header">User's Projects List</h5>
      <div class="table-responsive mb-3">
        <table class="table datatable-project border-top">
          <thead>
            <tr>
              <th></th>
              <th>Project</th>
              <th class="text-nowrap">Total Task</th>
              <th>Progress</th>
              <th>Hours</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
    <!-- /Project table -->

    <!-- Activity Timeline -->
    <div class="card mb-4">
      <h5 class="card-header">User Activity Timeline</h5>
      <div class="card-body pb-0">
        <ul class="timeline mb-0" style="height:400px; overflow:auto;padding:20px;">
          @isset($activities)
          @foreach($activities as $activity)
                @php
                    if($activity->activity_type == 1){
                        $class = 'success';
                        $icon = 'phone';
                    }elseif($activity->activity_type == 2){
                        $class = 'danger';
                        $icon = 'heart-handshake';
                    }elseif($activity->activity_type == 3){
                        $class = 'info';
                        $icon = 'message';
                    }elseif($activity->activity_type == 4){
                        $class = 'warning';
                        $icon = 'send';
                    }else{
                        $class = 'info';
                        $icon = 'send';
                    }
                @endphp
                @if($activity->ref_type == IConstants::REFRENCE_TYPE_TICKET )
                    <li class="timeline-item pb-4 timeline-item-{{$class}} border-left-dashed">
                        <span class="timeline-indicator-advanced timeline-indicator-{{$class}}">
                        <i class="ti ti-{{$icon}}"></i>
                        </span>
                        <div class="timeline-event">
                        <div class="timeline-header border-bottom mb-3">
                            <h6 class="mb-0">
                              @can('ticket_edit')
                                <a href="javascript:void(0);" class='show-modal text-{{$class}}' data-action="{{ route('activities.create', ['ticket_id'=>$activity->ref_id,'type'=>1]) }}" 
                                    data-title="TICKET # {{$activity->ref_id}} - {{$activity->ticket->name}}" data-size="xl">
                                    {{ Common::ReferenceType($activity->ref_type, $activity->ref_id)}}
                                </a>
                              <br/>
                              @endcan
                              {{ Common::ActivityType($activity->activity_type)}} 
                               </h6>
                            <span class="text-muted">{{$activity->created_at->diffForHumans()}}</span>
                        </div>
                        
                        <p>{{$activity->comment}}</p>
                        <div class="d-flex justify-content-between">
                            <h6 class="text-warning">@isset($activity->followup_date)Follow Up: {{date('d-m-Y h:ia',strtotime($activity->followup_date))}}@endisset</h6>
                            <div class="d-flex">
                                <i class="ti ti-user text-{{$class}}"></i>&nbsp;<small><em>{{$activity->user->name}}</em></small>

                            </div>
                        </div>
                        <div style="float: right;">
                        </div>
                    
                        </div>
                    </li>
                    @endif
                @endforeach
          @endisset
          {{-- <li class="timeline-item timeline-item-transparent">
            <span class="timeline-point timeline-point-warning"></span>
            <div class="timeline-event">
              <div class="timeline-header mb-1">
                <h6 class="mb-0">Client Meeting</h6>
                <small class="text-muted">45 min ago</small>
              </div>
              <p class="mb-2">Project meeting with john @10:15am</p>
              <div class="d-flex flex-wrap">
                <div class="avatar me-3">
                  <img src="{{ asset('assets/img/avatars/3.png') }}" alt="Avatar" class="rounded-circle" />
                </div>
                <div>
                  <h6 class="mb-0">Lester McCarthy (Client)</h6>
                  <small>CEO of {{ config('variables.creatorName') }}</small>
                </div>
              </div>
            </div>
          </li>
          <li class="timeline-item timeline-item-transparent">
            <span class="timeline-point timeline-point-info"></span>
            <div class="timeline-event">
              <div class="timeline-header mb-1">
                <h6 class="mb-0">Create a new project for client</h6>
                <small class="text-muted">2 Day Ago</small>
              </div>
              <p class="mb-2">5 team members in a project</p>
              <div class="d-flex align-items-center avatar-group">
                <div class="avatar pull-up" data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="Vinnie Mostowy">
                  <img src="{{ asset('assets/img/avatars/5.png') }}" alt="Avatar" class="rounded-circle">
                </div>
                <div class="avatar pull-up" data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="Marrie Patty">
                  <img src="{{ asset('assets/img/avatars/12.png') }}" alt="Avatar" class="rounded-circle">
                </div>
                <div class="avatar pull-up" data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="Jimmy Jackson">
                  <img src="{{ asset('assets/img/avatars/9.png') }}" alt="Avatar" class="rounded-circle">
                </div>
                <div class="avatar pull-up" data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="Kristine Gill">
                  <img src="{{ asset('assets/img/avatars/6.png') }}" alt="Avatar" class="rounded-circle">
                </div>
                <div class="avatar pull-up" data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" title="Nelson Wilson">
                  <img src="{{ asset('assets/img/avatars/4.png') }}" alt="Avatar" class="rounded-circle">
                </div>
              </div>
            </div>
          </li>
          <li class="timeline-item timeline-item-transparent border-transparent">
            <span class="timeline-point timeline-point-success"></span>
            <div class="timeline-event">
              <div class="timeline-header mb-1">
                <h6 class="mb-0">Design Review</h6>
                <small class="text-muted">5 days Ago</small>
              </div>
              <p class="mb-0">Weekly review of freshly prepared design for our new app.</p>
            </div>
          </li> --}}
        </ul>
      </div>
    </div>
    <!-- /Activity Timeline -->

    <!-- Invoice table -->
    <div class="card mb-4">
      <div class="table-responsive mb-3">
        <table class="table datatable-invoice border-top">
          <thead>
            <tr>
              <th></th>
              <th>ID</th>
              <th><i class='ti ti-trending-up text-secondary'></i></th>
              <th>Total</th>
              <th>Issued Date</th>
              <th>Actions</th>
            </tr>
          </thead>
        </table>
      </div>
    </div>
    <!-- /Invoice table -->
  </div>
  <!--/ User Content -->
</div>

<!-- Modal -->

<!-- /Modal -->
@endsection
