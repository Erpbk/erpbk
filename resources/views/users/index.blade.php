@extends($layout ?? 'layouts.app')
@section('title', 'Users')
@section('content')

@php
  $usersRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.users' : 'users';
  $rolesRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.roles' : 'roles';
  $permissionsRoute = (View::shared('settings_panel') ?? false) ? 'settings-panel.permissions' : 'permissions';
@endphp

<div class="container-fluid px-4">
    <!-- Header Section with Gradient -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="bg-gradient-primary p-4 rounded-3 shadow-sm">
                <h3 class="text-white mb-0 fw-bold">User Management</h3>
                <p class="text-white-50 mb-0">Manage users, roles and permissions</p>
            </div>
        </div>
    </div>

    <!-- Roles Section -->
    <div class="row mb-4">
        <div class="col-4">
            <div class="d-flex align-items-center mb-4">
                <h4 class="fw-bold mb-0">Roles & Permissions</h4>
                <span class="badge bg-secondary ms-2">{{ $roles->count() }} Total</span>
            </div>
            <p class="text-muted mb-4">A role provides access to predefined menus and features so that depending on assigned role, an administrator can give access to what user needs.</p>
        </div>
        <!-- Add New Role Card -->
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100 bg-light border-dashed">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <img src="{{ asset('assets/img/illustrations/add-new-roles.png') }}" 
                             class="img-fluid mb-3" 
                             alt="add-new-roles" 
                             width="60">
                        <h5 class="fw-bold mb-2">Add New Role</h5>
                        <p class="text-muted small mb-3">Create a new role with custom permissions</p>
                        <button data-action="{{ route($rolesRoute . '.create') }}" 
                                data-title="Create New Role" 
                                data-size="lg" 
                                class="btn btn-primary btn-sm show-modal">
                            <i class="ti ti-plus me-1"></i>New Role
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <!-- Add New Role Card -->
        <div class="col-4">
            <div class="card border-0 shadow-sm h-100 bg-light border-dashed">
                <div class="card-body d-flex align-items-center justify-content-center">
                    <div class="text-center">
                        <img src="{{ asset('assets/img/illustrations/add-new-roles.png') }}" 
                             class="img-fluid mb-3" 
                             alt="add-new-roles" 
                             width="60">
                        <h5 class="fw-bold mb-2">Add New Permission</h5>
                        <p class="text-muted small mb-3">Create a new permission to assign to roles</p>
                        <button data-action="{{ route($permissionsRoute . '.create') }}" 
                                data-title="Create New Role" 
                                data-size="lg" 
                                class="btn btn-primary btn-sm show-modal">
                            <i class="ti ti-plus me-1"></i>New Permission
                        </button>
                        <a class="btn btn-primary btn-sm" href="{{ route($permissionsRoute . '.index') }}" target="_blank">
                            <i class="ti ti-settings me-1"></i>Manage
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Role Cards -->
    <div class="row g-4 mb-5">
        @foreach($roles as $role)
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="card border-0 shadow-sm h-100 role-card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center">
                            <div class="role-icon bg-primary bg-opacity-10 p-2 rounded-3 me-2">
                                <i class="ti ti-shield-lock fs-4 text-white"></i>
                            </div>
                            <h5 class="fw-bold mb-0">{{ $role->name }}</h5>
                        </div>
                        <form action="{{ route($rolesRoute . '.destroy', $role->id) }}" method="POST" class="d-inline" id="formajax">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this role?')">
                                <i class="fa fa-trash"></i>
                            </button>
                        </form>
                    </div>
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="javascript:;" class="role-edit-modal show-modal text-decoration-none" 
                           data-title="Edit Role" 
                           data-size="xl" 
                           data-action="{{ route($rolesRoute . '.edit', $role->id) }}">
                            <i class="ti ti-edit me-1"></i>Edit Permissions
                        </a>
                        <span class="text-muted small">
                            <i class="ti ti-users me-1"></i>{{ $role->users->count() }} assigned
                        </span>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Users Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center justify-content-between mb-4">
                <div class="d-flex align-items-center">
                    <h4 class="fw-bold mb-0">System Users</h4>
                    <span class="badge bg-secondary ms-2">{{ \App\Models\User::count() }} Total</span>
                </div>
                @can('user_create')
                <a class="btn btn-primary show-modal" 
                   href="javascript:void(0)" 
                   data-action="{{ route($usersRoute . '.create') }}"
                   data-title="Add User Account" 
                   data-size="xl">
                    <i class="ti ti-plus me-1"></i>Add New User
                </a>
                @endcan
            </div>
        </div>
    </div>

    <!-- Flash Messages -->
    @include('flash::message')

    <!-- Users Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @include('users.table')
        </div>
    </div>
</div>

<style>
.bg-gradient-primary {
    background: linear-gradient(45deg, #4e73df 0%, #224abe 100%);
}

.role-card {
    transition: transform 0.2s, box-shadow 0.2s;
}

.role-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 1rem 3rem rgba(0,0,0,.175)!important;
}

.border-dashed {
    border: 2px dashed #dee2e6 !important;
}

.role-icon {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.progress {
    border-radius: 10px;
    background-color: #f0f0f0;
}

.card {
    border-radius: 1rem;
}

.btn {
    border-radius: 0.5rem;
}

.badge {
    font-weight: 500;
    padding: 0.5rem 1rem;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .bg-gradient-primary {
        padding: 1.5rem !important;
    }
    
    .container-fluid {
        padding: 1rem !important;
    }
}
</style>

@endsection