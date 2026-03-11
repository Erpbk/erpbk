@extends('layouts.app')
@section('title','Profile')
@section('content')

@php $usersRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.users' : 'users'; @endphp 
{!! Form::model($user, ['route' => [$usersRoute .'.password', $user->id], 'method' => 'patch', 'id' => 'formajax', 'enctype' => 'multipart/form-data']) !!}
<div class="row">
    <div class="col-xl-4 col-lg-5 col-md-5">
        <!-- Profile Card -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex flex-column align-items-center">
                    <!-- Profile Image with Better Positioning -->
                    <div class="position-relative mb-3">
                        @php
                            if(auth()->user()->image_name){
                                $image_name = auth()->user()->image_name;
                            }else{
                                $image_name = 'default.png';
                            }
                        @endphp
                        <div class="avatar-container" style="position: relative; width: fit-content;">
                            <img src='{{ asset("uploads/$image_name") }}'
                                 id="output" 
                                 class="rounded-circle img-thumbnail" 
                                 style="width: 130px; height: 130px; object-fit: cover; border: 4px solid #f0f2f5;"
                                 alt="Profile Image">
                            
                            <!-- Camera Icon for Upload -->
                            <label for="upload" class="position-absolute p-2 bg-primary rounded-circle cursor-pointer" 
                                   style="bottom: 5px; right: 5px; cursor: pointer; z-index: 10;">
                                <i class="ti ti-camera text-white" style="font-size: 16px;"></i>
                                <input type="file" id="upload" name="image_name" class="d-none" 
                                       accept="image/png, image/jpeg" onchange="loadFile(event)" />
                            </label>
                        </div>
                    </div>

                    <!-- User Name & Email (Read-only Display) -->
                    <h4 class="mb-1">{{ $user->first_name }} {{ $user->last_name }}</h4>
                    <p class="text-muted mb-3">
                        <i class="ti ti-mail me-1"></i>
                        {{ $user->email }}
                    </p>
                    
                    <!-- Role Badge -->
                    <span class="badge bg-label-primary px-3 py-2 rounded-pill mb-3">
                        <i class="ti ti-user me-1"></i>
                        {{ $user->roles->first()->name ?? 'User' }}
                    </span>

                    <!-- Quick Stats -->
                    @if($user->employee)
                    <div class="d-flex align-items-center justify-content-center w-100 mt-2 pt-2 border-top">
                        <div class="text-center px-3">
                            <h6 class="mb-1 text-primary">{{ $user->employee->employee_id ?? 'N/A' }}</h6>
                            <span class="text-muted small">Employee ID</span>
                        </div>
                        <div class="vr mx-2"></div>
                        <div class="text-center px-3">
                            <h6 class="mb-1 text-primary">{{ $user->employee->department->name ?? 'N/A' }}</h6>
                            <span class="text-muted small">Department</span>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Account Info Card -->
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="mb-3 d-flex align-items-center">
                    <i class="ti ti-info-circle me-2 text-primary"></i>
                    Account Details
                </h6>
                
                <div class="d-flex justify-content-between mb-3 pb-1">
                    <span class="text-muted">Member Since</span>
                    <span class="fw-semibold">{{ $user->created_at->format('d M, Y') }}</span>
                </div>
                
                <div class="d-flex justify-content-between mb-3 pb-1">
                    <span class="text-muted">Last Login</span>
                    <span class="fw-semibold">{{ $user->last_login_at ? $user->last_login_at->diffForHumans() : 'N/A' }}</span>
                </div>
                
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Account Status</span>
                    <span class="badge bg-success">Active</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8 col-lg-7 col-md-7">
        <!-- Edit Profile Form -->
        <div class="card">
            
            <div class="card-body">

                <!-- Password Update Section -->
                <div class="mb-4">
                    <h6 class="mb-3 text-primary">
                        <i class="ti ti-lock me-2"></i>
                        Change Password
                    </h6>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Current Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-lock"></i></span>
                                <input type="password" class="form-control" name="current_password" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-key"></i></span>
                                <input type="password" class="form-control" name="new_password" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="ti ti-check"></i></span>
                                <input type="password" class="form-control" name="new_password_confirmation" placeholder="••••••••">
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="alert alert-warning bg-label-warning d-flex align-items-center" role="alert">
                                <i class="ti ti-info-circle me-2"></i>
                                <small>Leave password fields blank if you don't want to change your password</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- File Upload Info -->
                <div class="mb-4">
                    <small class="text-muted d-block">
                        <i class="ti ti-photo me-1"></i>
                        Profile image should be JPG, GIF or PNG. Max size: 800K
                    </small>
                </div>
            </div>
            
            <div class="card-footer border-0 pt-0">
                <div class="d-flex gap-3">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="ti ti-device-floppy me-1"></i>
                        Save Changes
                    </button>
                </div>
            </div>
            
            {!! Form::close() !!}
        </div>
    </div>
</div>

<script>
    var loadFile = function (event) {
        var image = document.getElementById("output");
        image.src = URL.createObjectURL(event.target.files[0]);
    };
</script>

@endsection