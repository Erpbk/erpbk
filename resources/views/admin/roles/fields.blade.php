@php
    use App\Support\AdminPermissionTreeBuilder;

    $rolePermissions = $rolePermissions ?? [];
    if ($rolePermissions === [] && !empty($selectedPermissionIds ?? [])) {
        $rolePermissions = array_fill_keys($selectedPermissionIds, true);
    }
    $permissionModules = AdminPermissionTreeBuilder::modulesForRoleAssignment();
@endphp

<div class="form-group col-sm-12 mb-3">
    {!! Form::label('name', 'Name:') !!}
    @if(isset($role) && $role->name === 'Super Admin')
        {!! Form::text('name', old('name', $role->name), ['class' => 'form-control', 'required', 'maxlength' => 255, 'readonly']) !!}
    @else
        {!! Form::text('name', old('name', $role->name ?? null), ['class' => 'form-control', 'required', 'maxlength' => 255]) !!}
    @endif
</div>

@include('partials.permission_role_matrix')
