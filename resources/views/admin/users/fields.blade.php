<div class="row">
    <div class="col-md-6 mb-3">
        {!! Form::label('name', 'Name' , ['class' => 'required']) !!}
        {!! Form::text('name', old('name', $user->name ?? null), ['class' => 'form-control', 'required']) !!}
    </div>

    <div class="col-md-6 mb-3">
        {!! Form::label('email', 'Email' , ['class' => 'required']) !!}
        {!! Form::email('email', old('email', $user->email ?? null), ['class' => 'form-control', 'required']) !!}
    </div>

    <div class="col-md-6 mb-3">
        {!! Form::label('username', 'Username' , ['class' => 'required']) !!}
        {!! Form::text('username', old('username', $user->username ?? null), ['class' => 'form-control']) !!}
    </div>

    <div class="col-md-6 mb-3">
        {!! Form::label('status', 'Status') !!}
        <div class="form-check mt-2">
            {!! Form::checkbox('status', 1, old('status', isset($user) ? (bool) $user->status : true), ['class' => 'form-check-input', 'id' => 'status']) !!}
            {!! Form::label('status', 'Active', ['class' => 'form-check-label']) !!}
        </div>
    </div>

    <div class="col-md-6 mb-3">
        {!! Form::label('password', 'Password' , ['class' => 'required']) !!}
        {!! Form::password('password', array_merge(['class' => 'form-control'], isset($user) ? [] : ['required' => true])) !!}
        @if(isset($user))
            <small class="text-muted">Leave blank to keep existing password.</small>
        @endif
    </div>

    <div class="col-md-6 mb-3">
        {!! Form::label('password_confirmation', 'Confirm Password' , ['class' => 'required']) !!}
        {!! Form::password('password_confirmation', array_merge(['class' => 'form-control'], isset($user) ? [] : ['required' => true])) !!}
    </div>

    <div class="col-12 mb-3">
        {!! Form::label('roles', 'Roles') !!}
        <select name="roles[]" class="form-control" >
            @foreach($roles as $role)
                <option value="{{ $role->id }}"
                    @if(in_array($role->id, old('roles', isset($user) ? $user->roles->pluck('id')->toArray() : []))) selected @endif>
                    {{ $role->name }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="text-end">
    <button type="submit" class="btn btn-primary">{{ isset($user) ? 'Update' : 'Save' }}</button>
</div>

