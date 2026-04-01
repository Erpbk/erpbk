@php
    $selectedPermissionIds = $selectedPermissionIds ?? [];
@endphp

<div class="form-group col-sm-12 mb-3">
    {!! Form::label('name', 'Name:') !!}
    @if(isset($role) && $role->name === 'Super Admin')
        {!! Form::text('name', old('name', $role->name), ['class' => 'form-control', 'required', 'maxlength' => 255, 'readonly']) !!}
    @else
        {!! Form::text('name', old('name', $role->name ?? null), ['class' => 'form-control', 'required', 'maxlength' => 255]) !!}
    @endif
</div>

<h5 class="mt-2">Role Permissions</h5>
<div class="table-responsive scrollbar">
    <table class="table table-flush-spacing">
        <tbody>
            @foreach ($modules as $module)
                @php
                    $permissions = \App\Models\AdminPermission::where('parent_id', $module->id)->orderBy('name')->get();
                @endphp
                <tr>
                    <td class="text-nowrap fw-medium">{{ $module->name }}</td>
                    <td>
                        <div class="d-flex flex-wrap">
                            @foreach ($permissions as $item)
                                <div class="form-check me-3 me-lg-5 mb-2">
                                    <input class="form-check-input" name="permission[]" id="perm-{{ $item->id }}" value="{{ $item->id }}" type="checkbox"
                                        {{ in_array($item->id, $selectedPermissionIds, true) ? 'checked' : '' }}>
                                    @php
                                        $parts = explode('_', $item->name, 2);
                                        $label = isset($parts[1]) ? ucwords(str_replace('_', ' ', $parts[1])) : $item->name;
                                    @endphp
                                    <label class="form-check-label" for="perm-{{ $item->id }}">{{ $label }}</label>
                                </div>
                            @endforeach
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
