{!! Form::open(['route' => ['admin.users.update-roles', $user->id], 'method' => 'post', 'id' => 'formajax']) !!}
<div class="row">
    <div class="col-12 mb-3">
        <label class="form-label fw-semibold">{{ __('User') }}</label>
        <div>{{ $user->name ?: $user->email }}</div>
    </div>
    <div class="col-12">
        <label class="form-label fw-semibold">{{ __('Roles') }}</label>
        <select name="roles[]" class="form-control"  required>
            @foreach($roles as $role)
                <option value="{{ $role->id }}" {{ $user->roles->contains('id', $role->id) ? 'selected' : '' }}>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-3 text-end">
    <button type="submit" class="btn btn-primary">{{ __('Update') }}</button>
</div>
{!! Form::close() !!}

