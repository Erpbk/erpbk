<!-- Name Field -->
<div class="form-group mb-3">
  <div class="row g-2 align-items-center">
      <div class="col-md-4">
          {!! Form::label('name', 'Role Name', ['class' => 'form-label fw-semibold text-muted small text-uppercase mb-1 tracking-wide']) !!}
          @isset($roles)
              {!! Form::text('name', null, ['class' => 'form-control form-control-sm border-0 border-bottom border-2 border-secondary rounded-0 px-0 shadow-none', 'required', 'readonly', 'maxlength' => 255, 'placeholder' => 'Enter role name...']) !!}
          @else
              {!! Form::text('name', null, ['class' => 'form-control form-control-sm border-0 border-bottom border-2 border-secondary rounded-0 px-0 shadow-none', 'required', 'maxlength' => 255, 'placeholder' => 'Enter role name...']) !!}
          @endisset
      </div>
  </div>
</div>

@php
    use App\Support\PermissionTreeBuilder;

    $permissionModules = PermissionTreeBuilder::modulesForRoleAssignment();
    $rolePermissions = $rolePermissions ?? [];
@endphp

@include('partials.permission_role_matrix')
